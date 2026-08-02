"""Authentication email owner. Sends only through Resend's HTTPS API."""

import hashlib
import hmac
import json
import os
import secrets
from datetime import datetime, timedelta, timezone
from urllib.error import HTTPError, URLError
from urllib.request import Request, urlopen

from sqlalchemy.orm import Session

from app.models.domain_models import EmailVerificationToken, Usuario
from app.services.auth_crypto import digest
from app.services.email_templates import EmailContent, render

VERIFY_TTL_MINUTES = int(os.getenv("EMAIL_VERIFICATION_TTL_MINUTES", "60"))


class EmailDeliveryError(RuntimeError):
    pass


def provider_configured() -> bool:
    return bool(
        os.getenv("RESEND_API_KEY", "").strip()
        and (os.getenv("RESEND_FROM_EMAIL", "").strip() or os.getenv("MAIL_FROM_ADDRESS", "").strip())
        and os.getenv("EMAIL_OTP_SECRET", "").strip()
    )


def verification_otp(token_hash: str) -> str:
    secret = os.getenv("EMAIL_OTP_SECRET", "").strip()
    if not secret:
        raise EmailDeliveryError("El proveedor de correo no está configurado.")
    digest_bytes = hmac.new(secret.encode("utf-8"), f"email-verification:{token_hash}".encode("utf-8"), hashlib.sha256).digest()
    return f"{int.from_bytes(digest_bytes[:8], 'big') % 1_000_000:06d}"


def send_resend(to_email: str, content: EmailContent, *, idempotency_key: str) -> str:
    api_key = os.getenv("RESEND_API_KEY", "").strip()
    from_address = (os.getenv("RESEND_FROM_EMAIL", "").strip() or os.getenv("MAIL_FROM_ADDRESS", "").strip())
    from_name = (os.getenv("RESEND_FROM_NAME", "").strip() or os.getenv("MAIL_FROM_NAME", "ZeroWaste").strip() or "ZeroWaste")
    reply_to = os.getenv("RESEND_REPLY_TO", "").strip()
    if not api_key or not from_address:
        raise EmailDeliveryError("El proveedor de correo no está configurado.")
    message = {"from": f"{from_name} <{from_address}>", "to": [to_email], "subject": content.subject, "html": content.html, "text": content.text}
    if reply_to:
        message["reply_to"] = reply_to
    payload = json.dumps(message).encode("utf-8")
    request = Request("https://api.resend.com/emails", data=payload, method="POST", headers={"Authorization": f"Bearer {api_key}", "Content-Type": "application/json", "User-Agent": "ZeroWaste/1.0", "Idempotency-Key": idempotency_key[:256]})
    try:
        with urlopen(request, timeout=12) as response:
            body = json.loads(response.read().decode("utf-8"))
            return str(body.get("id") or "")
    except (HTTPError, URLError, TimeoutError, ValueError) as error:
        raise EmailDeliveryError("No fue posible entregar el correo de verificación.") from error


def send_verification(db: Session, user: Usuario) -> dict:
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
    content = render("verification", name=str(user.nombre or "Usuario"), action_url=verification_url, expires_minutes=VERIFY_TTL_MINUTES, detail=f"Código para la aplicación: {otp}")
    try:
        message_id = send_resend(user.email, content, idempotency_key=f"email-verification/{record.token_hash}")
    except EmailDeliveryError:
        db.rollback()
        raise
    record.provider_message_id = message_id
    record.sent_at = now
    db.commit()
    return {"sent": True, "expires_at": record.expires_at}
