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


def _disable_invalid_tokens(tokens: list[str]) -> None:
    if not tokens:
        return
    db = SessionLocal()
    try:
        db.query(DevicePushToken).filter(DevicePushToken.expo_push_token.in_(tokens)).update(
            {"active": False, "last_error": "DeviceNotRegistered", "disabled_at": datetime.now(timezone.utc)},
            synchronize_session=False,
        )
        db.commit()
    except Exception:
        db.rollback()
    finally:
        db.close()


def send_expo_push(tokens: list[str], *, title: str, body: str, data: dict) -> None:
    messages = [{"to": token, "title": title[:100], "body": body[:240], "data": data, "sound": "default", "channelId": "zerowaste-general"} for token in tokens]
    invalid: list[str] = []
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
                if ticket.get("status") == "error" and ticket.get("details", {}).get("error") == "DeviceNotRegistered":
                    invalid.append(message["to"])
                elif ticket.get("status") == "ok" and ticket.get("id"):
                    receipt_tokens[str(ticket["id"])] = message["to"]
        except Exception:
            # Delivery is best-effort. The persisted in-app notification remains authoritative.
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
                if receipt.get("status") == "error" and receipt.get("details", {}).get("error") == "DeviceNotRegistered":
                    token = receipt_tokens.get(str(receipt_id))
                    if token:
                        invalid.append(token)
        except Exception:
            continue
    _disable_invalid_tokens(invalid)
