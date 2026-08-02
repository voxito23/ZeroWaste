"""Authentication email owner. Sends only through Resend's HTTPS API."""

import html
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

VERIFY_TTL_MINUTES = 10


class EmailDeliveryError(RuntimeError):
    pass


def provider_configured() -> bool:
    return bool(
        os.getenv("RESEND_API_KEY", "").strip()
        and os.getenv("MAIL_FROM_ADDRESS", "").strip()
        and os.getenv("EMAIL_OTP_SECRET", "").strip()
    )


def verification_otp(token_hash: str) -> str:
    secret = os.getenv("EMAIL_OTP_SECRET", "").strip()
    if not secret:
        raise EmailDeliveryError("El proveedor de correo no está configurado.")
    digest_bytes = hmac.new(secret.encode("utf-8"), f"email-verification:{token_hash}".encode("utf-8"), hashlib.sha256).digest()
    return f"{int.from_bytes(digest_bytes[:8], 'big') % 1_000_000:06d}"


def _template(user: Usuario, verification_url: str, otp: str) -> str:
    name = html.escape(str(user.nombre or "Usuario"))
    url = html.escape(verification_url, quote=True)
    support = html.escape(os.getenv("SUPPORT_EMAIL", "soporte@zerowaste-qro.com"))
    return f"""<!doctype html><html lang="es"><body style="margin:0;background:#f3f4f6;font-family:'Segoe UI',Roboto,Arial,sans-serif;color:#1f2937"><table role="presentation" cellpadding="0" cellspacing="0" width="100%"><tr><td align="center" style="padding:24px 12px"><table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="max-width:560px;background:#fff;border-radius:18px;overflow:hidden"><tr><td align="center" style="background:#064e3b;padding:34px 24px 28px"><img src="https://www.zerowaste-qro.com/static/img/logo_texture.png" width="64" height="64" alt="ZeroWaste" style="display:block;border-radius:14px;margin:0 auto 16px"><h1 style="color:#fff;margin:0 0 10px;font-size:26px;letter-spacing:2px">ZEROWASTE</h1><div style="width:52px;height:3px;background:#00e096;border-radius:2px;margin:0 auto 14px"></div><p style="color:#a7f3d0;margin:0;font-size:12px;letter-spacing:1.5px;text-transform:uppercase">Verificación de correo</p></td></tr><tr><td style="padding:32px 24px 26px"><p style="font-size:16px;margin:0 0 8px">Hola <strong style="color:#064e3b">{name}</strong>,</p><p style="color:#6b7280;font-size:14px;line-height:1.7;margin:0 0 24px">Usa este código en la aplicación ZeroWaste para confirmar tu correo y activar tu cuenta.</p><table role="presentation" width="100%" style="margin:0 0 24px"><tr><td align="center" style="background:#f0fdf4;border:1px solid #d1fae5;border-radius:12px;padding:24px 16px"><p style="color:#6b7280;font-size:11px;margin:0 0 12px;text-transform:uppercase;letter-spacing:2px;font-weight:700">Tu código de verificación</p><span style="display:inline-block;background:#fff;border:2px solid #10b981;border-radius:10px;padding:14px 22px;color:#064e3b;font-family:'Courier New',monospace;font-size:32px;font-weight:900;letter-spacing:8px">{otp}</span></td></tr></table><table role="presentation" width="100%" style="margin:0 0 24px"><tr><td style="background:#fef3c7;border:1px solid #fde68a;border-radius:10px;padding:14px 16px"><p style="color:#92400e;font-size:13px;font-weight:700;margin:0 0 3px">Este código vence en {VERIFY_TTL_MINUTES} minutos.</p><p style="color:#a16207;font-size:12px;margin:0">No lo compartas. ZeroWaste nunca te lo solicitará por llamada o mensaje.</p></td></tr></table><p style="font-size:14px;font-weight:700;margin:0 0 12px">También puedes verificar desde este dispositivo:</p><p style="margin:0 0 24px"><a href="{url}" style="display:inline-block;background:#047857;color:#fff;text-decoration:none;padding:13px 20px;border-radius:10px;font-weight:700">Verificar correo</a></p><p style="color:#9ca3af;font-size:11px;line-height:1.6;margin:0">Si no creaste esta cuenta, ignora este correo. Enlace alternativo:<br><a href="{url}" style="color:#047857;word-break:break-all">{url}</a><br><br>Soporte: {support}</p></td></tr><tr><td align="center" style="background:#022c22;padding:20px 24px"><p style="color:#6ee7b7;font-size:12px;font-weight:700;margin:0">ZeroWaste</p><p style="color:#6ee7b7;font-size:11px;margin:8px 0 0">Clasificar y reciclar para un futuro más verde · © 2026</p></td></tr></table></td></tr></table></body></html>"""


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
    record = EmailVerificationToken(usuario_id=user.id, token_hash=digest(token), expires_at=now + timedelta(minutes=VERIFY_TTL_MINUTES), created_at=now)
    db.add(record)
    db.flush()
    otp = verification_otp(record.token_hash)
    verification_url = f"https://www.zerowaste-qro.com/api/auth/email/verificar?token={token}"
    try:
        message_id = _send_resend(user.email, "Verifica tu correo en ZeroWaste", _template(user, verification_url, otp))
    except EmailDeliveryError:
        db.rollback()
        raise
    record.provider_message_id = message_id
    record.sent_at = now
    db.commit()
    return {"sent": True, "expires_at": record.expires_at}
