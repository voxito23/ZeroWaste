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
    safe_token = token.replace("&", "&amp;").replace('"', "&quot;").replace("<", "&lt;").replace(">", "&gt;")
    return HTMLResponse(f'''<!doctype html><html lang="es"><meta name="viewport" content="width=device-width"><body style="font-family:Arial;background:#f1f5f4;padding:24px"><main style="max-width:520px;margin:auto;background:white;padding:30px;border-radius:18px"><h1 style="color:#064e3b">Restablecer contraseña</h1><form id="reset"><input id="password" type="password" minlength="8" maxlength="128" required placeholder="Nueva contraseña" style="width:100%;box-sizing:border-box;padding:13px;margin:12px 0"><button style="background:#047857;color:white;border:0;border-radius:10px;padding:13px 18px">Guardar contraseña</button></form><p id="message"></p></main><script>document.getElementById('reset').addEventListener('submit',async(e)=>{{e.preventDefault();const r=await fetch('/api/formularios/reset-password',{{method:'POST',headers:{{'Content-Type':'application/json'}},body:JSON.stringify({{token:"{safe_token}",password:document.getElementById('password').value}})}});const b=await r.json();document.getElementById('message').textContent=r.ok?b.message:(b.detail||'No fue posible restablecer la contraseña.');}});</script></body></html>''')


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
        content = render("password_changed", name=str(user.nombre or "Usuario"), action_url=os.getenv("PUBLIC_BASE_URL", "https://www.zerowaste-qro.com"))
        send_resend(record.email, content, idempotency_key=f"password-changed/{record.id}")
    except EmailDeliveryError as exc:
        logger.warning("Password-change confirmation delivery failed: %s", type(exc).__name__)
    return MessageResponse(success=True, message="Tu contraseña fue actualizada correctamente.")

