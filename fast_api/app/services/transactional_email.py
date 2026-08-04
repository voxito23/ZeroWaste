"""Authentication email owner. Sends only through Resend's HTTPS API."""
from __future__ import annotations

import hashlib
import hmac
import json
import os
import secrets
from datetime import datetime, timedelta, timezone
from email.utils import parseaddr
from typing import TYPE_CHECKING
from urllib.error import HTTPError, URLError
from urllib.request import Request, urlopen

from sqlalchemy.orm import Session

from app.services.auth_crypto import digest
from app.services.email_templates import EmailContent, render

if TYPE_CHECKING:
    from app.models.domain_models import Usuario

VERIFY_TTL_MINUTES = int(os.getenv("EMAIL_VERIFICATION_TTL_MINUTES", "60"))


class EmailDeliveryError(RuntimeError):
    def __init__(self, message: str, *, code: str = "EMAIL_DELIVERY_FAILED"):
        super().__init__(message)
        self.code = code


def provider_configured() -> bool:
    return bool(
        os.getenv("RESEND_API_KEY", "").strip()
        and (os.getenv("RESEND_FROM_EMAIL", "").strip() or os.getenv("MAIL_FROM_ADDRESS", "").strip())
        and os.getenv("EMAIL_OTP_SECRET", "").strip()
    )


def verification_otp(token_hash: str) -> str:
    secret = os.getenv("EMAIL_OTP_SECRET", "").strip()
    if not secret:
        raise EmailDeliveryError("El proveedor de correo no está configurado.", code="EMAIL_PROVIDER_NOT_CONFIGURED")
    digest_bytes = hmac.new(secret.encode("utf-8"), f"email-verification:{token_hash}".encode("utf-8"), hashlib.sha256).digest()
    return f"{int.from_bytes(digest_bytes[:8], 'big') % 1_000_000:06d}"


def _resend_http_error(error: HTTPError) -> EmailDeliveryError:
    status = int(getattr(error, "code", 0) or 0)
    marker = ""
    try:
        body = json.loads(error.read(8192).decode("utf-8"))
        marker = f"{body.get('name', '')} {body.get('type', '')} {body.get('message', '')}".lower()
    except (AttributeError, UnicodeDecodeError, ValueError):
        pass
    if status == 401 or "api_key" in marker or "api key" in marker:
        return EmailDeliveryError("La credencial del proveedor de correo no es válida.", code="EMAIL_PROVIDER_AUTH_INVALID")
    if status == 403 and any(word in marker for word in ("domain", "sender", "testing", "recipient")):
        return EmailDeliveryError("El remitente o dominio de correo todavía no está verificado.", code="EMAIL_SENDER_NOT_VERIFIED")
    if status == 403:
        return EmailDeliveryError("El proveedor de correo rechazó la solicitud.", code="EMAIL_PROVIDER_FORBIDDEN")
    if status == 422 and any(word in marker for word in ("from", "sender", "domain")):
        return EmailDeliveryError("La dirección remitente de correo no es válida.", code="EMAIL_SENDER_INVALID")
    if status == 429:
        return EmailDeliveryError("El proveedor de correo alcanzó temporalmente su límite. Inténtalo de nuevo en un minuto.", code="EMAIL_PROVIDER_RATE_LIMITED")
    if status >= 500:
        return EmailDeliveryError("El proveedor de correo no está disponible temporalmente.", code="EMAIL_PROVIDER_UNAVAILABLE")
    return EmailDeliveryError("No fue posible entregar el correo de verificación.")


def send_resend(to_email: str, content: EmailContent, *, idempotency_key: str) -> str:
    api_key = os.getenv("RESEND_API_KEY", "").strip()
    from_address = (os.getenv("RESEND_FROM_EMAIL", "").strip() or os.getenv("MAIL_FROM_ADDRESS", "").strip())
    from_name = (os.getenv("RESEND_FROM_NAME", "").strip() or os.getenv("MAIL_FROM_NAME", "ZeroWaste").strip() or "ZeroWaste")
    reply_to = os.getenv("RESEND_REPLY_TO", "").strip()
    if not api_key or not from_address:
        raise EmailDeliveryError("El proveedor de correo no está configurado.", code="EMAIL_PROVIDER_NOT_CONFIGURED")
    _, parsed_from = parseaddr(from_address)
    _, parsed_to = parseaddr(to_email)
    if not parsed_from or "@" not in parsed_from or any(character in parsed_from for character in "\r\n"):
        raise EmailDeliveryError("La dirección remitente de correo no es válida.", code="EMAIL_SENDER_INVALID")
    if not parsed_to or "@" not in parsed_to or any(character in parsed_to for character in "\r\n"):
        raise EmailDeliveryError("La dirección de correo del destinatario no es válida.", code="EMAIL_RECIPIENT_INVALID")
    message = {"from": f"{from_name} <{parsed_from}>", "to": [parsed_to], "subject": content.subject, "html": content.html, "text": content.text}
    if reply_to:
        message["reply_to"] = reply_to
    payload = json.dumps(message).encode("utf-8")
    request = Request("https://api.resend.com/emails", data=payload, method="POST", headers={"Authorization": f"Bearer {api_key}", "Content-Type": "application/json", "User-Agent": "ZeroWaste/1.0", "Idempotency-Key": idempotency_key[:256]})
    try:
        with urlopen(request, timeout=12) as response:
            body = json.loads(response.read().decode("utf-8"))
            message_id = str(body.get("id") or "").strip()
            if not message_id:
                raise EmailDeliveryError("El proveedor de correo devolvió una respuesta inválida.", code="EMAIL_PROVIDER_INVALID_RESPONSE")
            return message_id
    except HTTPError as error:
        raise _resend_http_error(error) from error
    except EmailDeliveryError:
        raise
    except (URLError, TimeoutError) as error:
        raise EmailDeliveryError("El proveedor de correo no está disponible temporalmente.", code="EMAIL_PROVIDER_UNAVAILABLE") from error
    except (UnicodeDecodeError, ValueError) as error:
        raise EmailDeliveryError("El proveedor de correo devolvió una respuesta inválida.", code="EMAIL_PROVIDER_INVALID_RESPONSE") from error


def send_verification(db: Session, user: Usuario) -> dict:
    from app.models.domain_models import EmailVerificationToken

    now = datetime.now(timezone.utc)
    recent = db.query(EmailVerificationToken).filter(
        EmailVerificationToken.usuario_id == user.id,
        EmailVerificationToken.created_at >= now - timedelta(minutes=1),
    ).first()
    if recent:
        return {"sent": False, "code": "RATE_LIMITED", "detail": "Espera un minuto antes de solicitar otro correo."}
    if not provider_configured():
        return {"sent": False, "code": "EMAIL_PROVIDER_NOT_CONFIGURED", "detail": "El proveedor de correo no está configurado."}
    db.query(EmailVerificationToken).filter(
        EmailVerificationToken.usuario_id == user.id,
        EmailVerificationToken.used_at.is_(None),
        EmailVerificationToken.revoked_at.is_(None),
    ).update({"revoked_at": now}, synchronize_session=False)
    token = "ev1_" + secrets.token_urlsafe(32)
    record = EmailVerificationToken(usuario_id=user.id, token_hash=digest(token), expires_at=now + timedelta(minutes=VERIFY_TTL_MINUTES), created_at=now)
    db.add(record)
    db.flush()
    otp = verification_otp(record.token_hash)
    base_url = os.getenv("PUBLIC_BASE_URL", "https://www.zerowaste-qro.com").rstrip("/")
    verification_url = f"{base_url}/api/auth/email/verificar?token={token}"
    content = render(
        "verification",
        name=str(user.nombre or "Usuario"),
        action_url=verification_url,
        expires_minutes=VERIFY_TTL_MINUTES,
        detail="También puedes escribir este código de seis dígitos en la aplicación:",
        otp_code=otp,
    )
    try:
        message_id = send_resend(user.email, content, idempotency_key=f"email-verification/{record.token_hash}")
    except EmailDeliveryError:
        db.rollback()
        raise
    record.provider_message_id = message_id
    record.sent_at = now
    db.commit()
    return {"sent": True, "expires_at": record.expires_at}
