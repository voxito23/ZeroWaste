"""Responsive HTML/text transactional templates without secrets or session tokens."""
from __future__ import annotations

import html
from dataclasses import dataclass


@dataclass(frozen=True)
class EmailContent:
    subject: str
    html: str
    text: str


def render(kind: str, *, name: str, action_url: str = "", expires_minutes: int | None = None, detail: str = "", otp_code: str = "") -> EmailContent:
    definitions = {
        "verification": ("Verifica tu correo en ZeroWaste", "Verificar correo", "Confirma tu correo para activar tu cuenta."),
        "password_reset": ("Restablece tu contraseña de ZeroWaste", "Restablecer contraseña", "Recibimos una solicitud para cambiar tu contraseña."),
        "password_changed": ("Tu contraseña de ZeroWaste fue actualizada", "Abrir ZeroWaste", "Tu contraseña fue cambiada correctamente."),
        "collection_confirmed": ("Recolección confirmada en ZeroWaste", "Ver recolección", "Tu solicitud de recolección fue confirmada."),
        "collection_status": ("Actualización de tu recolección", "Ver recolección", "El estado de tu recolección cambió."),
        "redemption_status": ("Actualización de tu canje ZeroWaste", "Ver canje", "El estado de tu canje cambió."),
        "google_account": ("Tu cuenta ZeroWaste está lista", "Abrir ZeroWaste", "Tu cuenta fue creada de forma segura con Google."),
        "admin_alert": ("Alerta administrativa de ZeroWaste", "Abrir panel", "Existe una alerta administrativa autorizada."),
    }
    if kind not in definitions:
        raise ValueError("Unsupported transactional email template")
    subject, button, intro = definitions[kind]
    safe_name, safe_url, safe_detail = html.escape(name or "Usuario"), html.escape(action_url, quote=True), html.escape(detail)
    normalized_otp = otp_code.strip() if otp_code.strip().isdigit() and len(otp_code.strip()) == 6 else ""
    safe_otp = html.escape(normalized_otp)
    expiry = f"Este enlace vence en {expires_minutes} minutos." if expires_minutes else ""
    action = f'<a href="{safe_url}" style="display:inline-block;background:#047857;color:#fff;text-decoration:none;padding:14px 22px;border-radius:10px;font-weight:700">{button}</a>' if safe_url else ""
    alternate = f'<p style="color:#64748b;font-size:12px;word-break:break-all">Enlace alternativo: {safe_url}</p>' if safe_url else ""
    otp = f'<div role="text" aria-label="Código de verificación {safe_otp}" style="margin:20px 0;background:#ecfdf5;border:1px solid #a7f3d0;border-radius:14px;padding:18px;text-align:center"><div style="color:#047857;font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase">Código de verificación</div><div style="margin-top:8px;color:#064e3b;font-family:monospace;font-size:32px;font-weight:800;letter-spacing:.22em">{safe_otp}</div></div>' if safe_otp else ""
    text_otp = f"\n\nCódigo de verificación: {normalized_otp}" if normalized_otp else ""
    body = f"{intro} {safe_detail}".strip()
    html_body = f'''<!doctype html><html lang="es"><body style="margin:0;background:#f1f5f4;font-family:Arial,sans-serif;color:#1f2937"><table role="presentation" width="100%"><tr><td align="center" style="padding:24px"><table role="presentation" width="100%" style="max-width:560px;background:#fff;border-radius:18px;overflow:hidden"><tr><td align="center" style="background:#064e3b;padding:30px"><img src="https://www.zerowaste-qro.com/static/img/logo_texture.png" width="56" height="56" alt="ZeroWaste"><h1 style="color:#fff">ZeroWaste</h1></td></tr><tr><td style="padding:30px"><p>Hola <strong>{safe_name}</strong>,</p><p style="line-height:1.7;color:#475569">{body}</p>{otp}<p>{action}</p><p style="color:#92400e">{expiry}</p>{alternate}<p style="font-size:12px;color:#64748b">Si no solicitaste esta operación, ignora el mensaje y contacta a soporte. ZeroWaste nunca solicitará tu contraseña.</p></td></tr><tr><td align="center" style="background:#022c22;color:#a7f3d0;padding:18px;font-size:12px">ZeroWaste · https://www.zerowaste-qro.com</td></tr></table></td></tr></table></body></html>'''
    text_body = f"ZeroWaste\n\nHola {name or 'Usuario'},\n\n{body}{text_otp}\n\n{button}: {action_url}\n{expiry}\n\nSi no solicitaste esta operación, ignora este mensaje.\nhttps://www.zerowaste-qro.com"
    return EmailContent(subject=subject, html=html_body, text=text_body)
