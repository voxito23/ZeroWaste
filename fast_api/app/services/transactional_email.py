"""Authentication email owner. Sends only through Resend's HTTPS API."""

import html
import json
import os
import secrets
from datetime import datetime, timedelta, timezone
from urllib.error import HTTPError, URLError
from urllib.request import Request, urlopen

from sqlalchemy.orm import Session

from app.models.domain_models import EmailVerificationToken, Usuario
from app.services.auth_crypto import digest

VERIFY_TTL_HOURS = 2


class EmailDeliveryError(RuntimeError):
    pass


def provider_configured() -> bool:
    return bool(os.getenv("RESEND_API_KEY", "").strip() and os.getenv("MAIL_FROM_ADDRESS", "").strip())


def _template(user: Usuario, verification_url: str) -> str:
    name = html.escape(str(user.nombre or "Usuario"))
    url = html.escape(verification_url, quote=True)
    support = html.escape(os.getenv("SUPPORT_EMAIL", "soporte@zerowaste-qro.com"))
    return f"""<!doctype html><html lang="es"><body style="margin:0;background:#f1f5f4;font-family:Arial,sans-serif;color:#16352b"><table role="presentation" width="100%"><tr><td align="center" style="padding:32px 16px"><table role="presentation" width="100%" style="max-width:560px;background:#fff;border-radius:20px"><tr><td style="padding:36px"><p style="font-weight:800;color:#047857">ZeroWaste</p><h1 style="font-size:26px">Verifica tu correo</h1><p>Hola, {name}.</p><p>Confirma tu dirección para proteger tu cuenta y continuar en ZeroWaste.</p><p style="margin:30px 0"><a href="{url}" style="background:#047857;color:#fff;text-decoration:none;padding:14px 22px;border-radius:12px;font-weight:700">Verificar correo</a></p><p>Este enlace vence en {VERIFY_TTL_HOURS} horas y solo funciona una vez.</p><p style="font-size:13px;color:#64748b">Si no creaste esta cuenta, ignora el mensaje. Enlace alternativo:<br><a href="{url}">{url}</a></p><p style="font-size:13px;color:#64748b">Soporte: {support}</p></td></tr></table></td></tr></table></body></html>"""


def _send_resend(to_email: str, subject: str, html_body: str) -> str:
    api_key = os.getenv("RESEND_API_KEY", "").strip()
    from_address = os.getenv("MAIL_FROM_ADDRESS", "").strip()
    from_name = os.getenv("MAIL_FROM_NAME", "ZeroWaste").strip() or "ZeroWaste"
    if not api_key or not from_address:
        raise EmailDeliveryError("El proveedor de correo no está configurado.")
    payload = json.dumps({"from": f"{from_name} <{from_address}>", "to": [to_email], "subject": subject, "html": html_body}).encode("utf-8")
    request = Request("https://api.resend.com/emails", data=payload, method="POST", headers={"Authorization": f"Bearer {api_key}", "Content-Type": "application/json"})
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
    record = EmailVerificationToken(usuario_id=user.id, token_hash=digest(token), expires_at=now + timedelta(hours=VERIFY_TTL_HOURS), created_at=now)
    db.add(record)
    db.flush()
    verification_url = f"https://www.zerowaste-qro.com/api/auth/email/verificar?token={token}"
    try:
        message_id = _send_resend(user.email, "Verifica tu correo en ZeroWaste", _template(user, verification_url))
    except EmailDeliveryError:
        db.rollback()
        raise
    record.provider_message_id = message_id
    record.sent_at = now
    db.commit()
    return {"sent": True, "expires_at": record.expires_at}
