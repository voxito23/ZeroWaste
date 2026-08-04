import json
import os
import secrets
import logging
from datetime import datetime, timedelta, timezone

from fastapi import APIRouter, Depends, HTTPException, Query, status
from fastapi.responses import HTMLResponse
from pydantic import BaseModel, Field
from sqlalchemy.orm import Session

from app.data.database import get_db
from app.models.domain_models import ContactMessage, PasswordResetRequest, Usuario, Actividad
from app.models.schemas import ContactMessageCreate, PasswordResetRequestCreate, MessageResponse
from app.services.auth_crypto import digest
from app.security.jwt_auth import hash_password
from app.services.email_templates import render
from app.services.transactional_email import EmailDeliveryError, send_resend

router = APIRouter(prefix="/formularios", tags=["Formularios Frontend"])
logger = logging.getLogger("zerowaste.email")

@router.post("/contacto", response_model=MessageResponse)
def api_contacto(data: ContactMessageCreate, db: Session = Depends(get_db)):
    """Recibe mensaje de contacto y lo persiste en PostgreSQL."""
    
    if len(data.nombre) <= 10:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="Nombre inválido (mínimo 11 caracteres)."
        )
        
    if len(data.mensaje) <= 10:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="Mensaje muy corto."
        )

    nuevo = ContactMessage(
        nombre=data.nombre, 
        email=data.email, 
        ubicacion=data.ubicacion, 
        mensaje=data.mensaje
    )
    
    usuario = db.query(Usuario).filter(Usuario.email == data.email).first()
    if usuario:
        nuevo.usuario_id = usuario.id
    
    db.add(nuevo)
    
    if usuario:
        nueva_actividad = Actividad(
            usuario_id=usuario.id,
            tipo='contacto',
            descripcion='Envió un mensaje al equipo de soporte'
        )
        db.add(nueva_actividad)
        
    db.commit()
    return MessageResponse(success=True, message="Mensaje recibido correctamente.")


@router.post("/forgot-password", response_model=MessageResponse)
def api_forgot_password(data: PasswordResetRequestCreate, db: Session = Depends(get_db)):
    """Create a single-use reset token without disclosing account existence."""
    email = str(data.email).strip().lower()
    user = db.query(Usuario).filter(Usuario.email == email).first()
    generic = MessageResponse(success=True, message="Si existe una cuenta, enviaremos instrucciones para restablecer la contraseña.")
    if not user:
        return generic
    now = datetime.now(timezone.utc)
    recent = db.query(PasswordResetRequest).filter(PasswordResetRequest.email == email, PasswordResetRequest.created_at >= now - timedelta(minutes=1)).first()
    if recent:
        raise HTTPException(status_code=429, detail="Espera un minuto antes de solicitar otro correo.", headers={"Retry-After": "60"})
    db.query(PasswordResetRequest).filter(PasswordResetRequest.email == email, PasswordResetRequest.usado.is_(False)).update({"usado": True, "estado": "reemplazado"}, synchronize_session=False)
    token = "pr1_" + secrets.token_urlsafe(32)
    token_hash = digest(token)
    ttl = int(os.getenv("PASSWORD_RESET_TTL_MINUTES", "30"))
    record = PasswordResetRequest(email=email, temp_password_hash=token_hash, expires_at=now + timedelta(minutes=ttl), usado=False, estado="pendiente", created_at=now)
    db.add(record)
    db.flush()
    base_url = os.getenv("PUBLIC_BASE_URL", "https://www.zerowaste-qro.com").rstrip("/")
    url = f"{base_url}/api/formularios/restablecer?token={token}"
    content = render("password_reset", name=str(user.nombre or "Usuario"), action_url=url, expires_minutes=ttl)
    try:
        send_resend(email, content, idempotency_key=f"password-reset/{token_hash}")
    except EmailDeliveryError as error:
        db.rollback()
        raise HTTPException(status_code=503, detail="No fue posible entregar el correo de recuperación.") from error
    db.commit()
    return generic


class PasswordResetConfirm(BaseModel):
    token: str = Field(min_length=32, max_length=200)
    password: str = Field(min_length=8, max_length=128)


@router.get("/restablecer", response_class=HTMLResponse, include_in_schema=False)
def password_reset_page(token: str = Query(min_length=32, max_length=200)):
    token_literal = json.dumps(token).replace("<", "\\u003c").replace(">", "\\u003e").replace("&", "\\u0026")
    return HTMLResponse(f'''<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><title>Restablecer contraseña · ZeroWaste</title><style>*{{box-sizing:border-box}}body{{margin:0;min-height:100vh;background:#ecfdf5;color:#0f172a;font-family:Arial,sans-serif;padding:max(24px,env(safe-area-inset-top)) 18px max(24px,env(safe-area-inset-bottom))}}main{{width:min(100%,520px);margin:5vh auto 0;background:#fff;border:1px solid #d1fae5;border-radius:26px;padding:clamp(24px,6vw,38px);box-shadow:0 22px 60px rgba(6,78,59,.14)}}.mark{{width:64px;height:64px;display:grid;place-items:center;margin:0 auto 20px;border-radius:20px;background:#d1fae5;color:#047857;font-size:32px}}h1{{margin:0;color:#064e3b;font-size:clamp(28px,7vw,36px);text-align:center}}p{{color:#64748b;line-height:1.65}}label{{display:block;margin-top:24px;margin-bottom:8px;color:#334155;font-weight:700}}.field{{display:flex;align-items:center;border:1px solid #cbd5e1;border-radius:14px;background:#f8fafc;overflow:hidden}}input{{min-width:0;flex:1;border:0;outline:0;background:transparent;padding:15px;color:#0f172a;font-size:16px}}.toggle{{border:0;background:transparent;color:#047857;font-weight:700;padding:15px;cursor:pointer}}.submit,.app-link{{width:100%;min-height:54px;display:flex;align-items:center;justify-content:center;margin-top:18px;border:0;border-radius:14px;background:#047857;color:#fff;font-size:16px;font-weight:800;text-decoration:none;cursor:pointer}}.submit:disabled{{opacity:.55;cursor:wait}}#message{{min-height:24px;margin:14px 0 0;color:#b91c1c;font-weight:700;text-align:center}}#success[hidden]{{display:none}}#success{{text-align:center}}#success h1{{font-size:30px}}.hint{{font-size:13px}}@media(max-height:650px){{main{{margin-top:0}}}}</style></head><body><main><section id="form-state"><div class="mark" aria-hidden="true">♻</div><h1>Crea tu contraseña nueva</h1><p>Escribe una contraseña segura para recuperar tu acceso a ZeroWaste.</p><form id="reset"><label for="password">Nueva contraseña</label><div class="field"><input id="password" type="password" minlength="8" maxlength="128" autocomplete="new-password" required placeholder="Mínimo 8 caracteres"><button class="toggle" id="toggle" type="button" aria-label="Mostrar contraseña">Mostrar</button></div><button class="submit" id="submit" type="submit">Guardar contraseña</button></form><p id="message" role="alert" aria-live="polite"></p></section><section id="success" hidden><div class="mark" aria-hidden="true">✓</div><h1>Contraseña actualizada</h1><p>Regresa a la aplicación de ZeroWaste e inicia sesión con tu contraseña nueva.</p><a class="app-link" href="zerowaste://auth/login">Volver a la aplicación ZeroWaste</a><p class="hint">Si el botón no abre la aplicación, ciérralo y abre ZeroWaste manualmente. Después escribe tu correo y tu contraseña nueva.</p></section></main><script>const form=document.getElementById('reset');const password=document.getElementById('password');const submit=document.getElementById('submit');const message=document.getElementById('message');document.getElementById('toggle').addEventListener('click',()=>{{const visible=password.type==='text';password.type=visible?'password':'text';document.getElementById('toggle').textContent=visible?'Mostrar':'Ocultar';}});form.addEventListener('submit',async(event)=>{{event.preventDefault();if(!form.reportValidity())return;submit.disabled=true;submit.textContent='Guardando…';message.textContent='';try{{const response=await fetch('/api/formularios/reset-password',{{method:'POST',headers:{{'Content-Type':'application/json'}},body:JSON.stringify({{token:{token_literal},password:password.value}})}});let body={{}};try{{body=await response.json();}}catch{{}}if(!response.ok){{message.textContent=body.detail||'No fue posible restablecer la contraseña.';return;}}document.getElementById('form-state').hidden=true;document.getElementById('success').hidden=false;}}catch{{message.textContent='No fue posible conectar con ZeroWaste. Inténtalo nuevamente.';}}finally{{submit.disabled=false;submit.textContent='Guardar contraseña';}}}});</script></body></html>''')


@router.post("/reset-password", response_model=MessageResponse)
def reset_password(payload: PasswordResetConfirm, db: Session = Depends(get_db)):
    record = db.query(PasswordResetRequest).filter(PasswordResetRequest.temp_password_hash == digest(payload.token)).with_for_update().first()
    now = datetime.now(timezone.utc)
    if not record or record.usado or record.estado != "pendiente":
        raise HTTPException(status_code=409, detail="Este enlace ya fue utilizado o reemplazado.")
    expires_at = record.expires_at if record.expires_at.tzinfo else record.expires_at.replace(tzinfo=timezone.utc)
    if expires_at <= now:
        record.estado = "vencido"
        db.commit()
        raise HTTPException(status_code=410, detail="Este enlace de recuperación venció.")
    user = db.query(Usuario).filter(Usuario.email == record.email).with_for_update().first()
    if not user:
        raise HTTPException(status_code=404, detail="No fue posible restablecer la contraseña.")
    user.password = hash_password(payload.password)
    record.usado = True
    record.estado = "completado"
    db.commit()
    try:
        content = render("password_changed", name=str(user.nombre or "Usuario"), detail="Regresa a la aplicación de ZeroWaste e inicia sesión con tu contraseña nueva.")
        send_resend(record.email, content, idempotency_key=f"password-changed/{record.id}")
    except EmailDeliveryError as exc:
        logger.warning("Password-change confirmation delivery failed: %s", type(exc).__name__)
    return MessageResponse(success=True, message="Tu contraseña fue actualizada correctamente.")

