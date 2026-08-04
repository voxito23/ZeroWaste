"""Expo push delivery without logging device tokens or notification payloads."""

from __future__ import annotations

import json
from datetime import datetime, timezone
from urllib import request

from app.data.database import SessionLocal
from app.models.domain_models import DevicePushToken, NotificationPreference


EXPO_PUSH_URL = "https://exp.host/--/api/v2/push/send"
EXPO_RECEIPTS_URL = "https://exp.host/--/api/v2/push/getReceipts"
TYPE_PREFERENCE = {
    "post_comment": "comments",
    "comment_reply": "replies",
    "post_like": "likes",
    "article_published": "articles",
    "news_published": "news",
    "collection_created": "collections",
    "collection_assigned": "collections",
    "collection_reminder": "collections",
    "collection_status": "collections",
    "reward_status": "rewards",
    "points_earned": "points",
    "system_notice": "system",
}


def push_allowed(db, user_id: int, notification_type: str) -> bool:
    preference = db.query(NotificationPreference).filter_by(user_id=user_id).first()
    if not preference:
        return True
    field = TYPE_PREFERENCE.get(notification_type, "system")
    return bool(preference.push_enabled and getattr(preference, field, False))


def in_app_allowed(db, user_id: int, notification_type: str) -> bool:
    preference = db.query(NotificationPreference).filter_by(user_id=user_id).first()
    if not preference:
        return True
    field = TYPE_PREFERENCE.get(notification_type, "system")
    return bool(preference.in_app_enabled and getattr(preference, field, False))


def active_tokens(db, user_id: int) -> list[str]:
    return [
        str(row.expo_push_token)
        for row in db.query(DevicePushToken).filter_by(user_id=user_id, active=True).all()
    ]


def _safe_delivery_error(value: object) -> str:
    normalized = "".join(character for character in str(value or "ExpoPushError") if character.isalnum() or character in {"_", "-", ":"})
    return normalized[:100] or "ExpoPushError"


def _persist_delivery_results(successful: set[str], errors: dict[str, str]) -> None:
    tokens = list(successful | set(errors))
    if not tokens:
        return
    db = SessionLocal()
    try:
        now = datetime.now(timezone.utc)
        rows = db.query(DevicePushToken).filter(DevicePushToken.expo_push_token.in_(tokens)).all()
        for row in rows:
            token = str(row.expo_push_token)
            if token in errors:
                row.last_error = _safe_delivery_error(errors[token])
                if row.last_error == "DeviceNotRegistered":
                    row.active = False
                    row.disabled_at = now
            elif token in successful:
                row.last_error = None
        db.commit()
    except Exception:
        db.rollback()
    finally:
        db.close()


def send_expo_push(tokens: list[str], *, title: str, body: str, data: dict) -> dict[str, int]:
    messages = [{"to": token, "title": title[:100], "body": body[:240], "data": data, "sound": "default", "channelId": "zerowaste-general"} for token in tokens]
    successful: set[str] = set()
    errors: dict[str, str] = {}
    receipt_tokens: dict[str, str] = {}
    for offset in range(0, len(messages), 100):
        batch = messages[offset:offset + 100]
        payload = json.dumps(batch).encode("utf-8")
        push_request = request.Request(EXPO_PUSH_URL, data=payload, method="POST", headers={"Accept": "application/json", "Content-Type": "application/json"})
        try:
            with request.urlopen(push_request, timeout=10) as response:
                result = json.loads(response.read().decode("utf-8"))
            tickets = result.get("data", []) if isinstance(result, dict) else []
            for message, ticket in zip(batch, tickets):
                token = message["to"]
                if ticket.get("status") == "error":
                    errors[token] = _safe_delivery_error(ticket.get("details", {}).get("error") or "ExpoTicketError")
                elif ticket.get("status") == "ok":
                    successful.add(token)
                    if ticket.get("id"):
                        receipt_tokens[str(ticket["id"])] = token
            if len(tickets) < len(batch):
                for message in batch[len(tickets):]:
                    errors[message["to"]] = "ExpoTicketMissing"
        except Exception as exc:
            # Delivery is best-effort. The persisted in-app notification remains authoritative.
            code = f"TransportError:{type(exc).__name__}"
            for message in batch:
                errors[message["to"]] = code
            continue
    receipt_ids = list(receipt_tokens)
    for offset in range(0, len(receipt_ids), 300):
        ids = receipt_ids[offset:offset + 300]
        receipt_request = request.Request(
            EXPO_RECEIPTS_URL,
            data=json.dumps({"ids": ids}).encode("utf-8"),
            method="POST",
            headers={"Accept": "application/json", "Content-Type": "application/json"},
        )
        try:
            with request.urlopen(receipt_request, timeout=10) as response:
                result = json.loads(response.read().decode("utf-8"))
            receipts = result.get("data", {}) if isinstance(result, dict) else {}
            for receipt_id, receipt in receipts.items():
                token = receipt_tokens.get(str(receipt_id))
                if token and receipt.get("status") == "error":
                    errors[token] = _safe_delivery_error(receipt.get("details", {}).get("error") or "ExpoReceiptError")
                    successful.discard(token)
        except Exception:
            continue
    _persist_delivery_results(successful, errors)
    return {"accepted": len(successful), "failed": len(errors)}
