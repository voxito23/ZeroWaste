import os

from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.orm import Session

from app.data.database import get_db
from app.models.domain_models import ContactMessage, PasswordResetRequest, Usuario, Actividad
from app.models.schemas import ContactMessageCreate, PasswordResetRequestCreate, MessageResponse

router = APIRouter(prefix="/formularios", tags=["Formularios Frontend"])

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
    """
    Recuperación de contraseña — delega al servicio Flask que envía el email real.
    Este endpoint actúa como proxy para mantener la arquitectura API-first.
    """
    import requests
    try:
        # Reenviar la solicitud al servicio Flask que maneja el SMTP
        flask_base_url = os.getenv("FLASK_INTERNAL_URL", "http://cliente:5000").rstrip("/")
        flask_url = f"{flask_base_url}/forgot-password"
        response = requests.post(flask_url, json={"email": data.email}, timeout=30)
        result = response.json()
        
        if result.get("success"):
            return MessageResponse(success=True, message=result.get("message", "Contraseña temporal enviada a tu correo."))
        else:
            from fastapi import HTTPException
            raise HTTPException(
                status_code=response.status_code,
                detail=result.get("error", "Error al procesar la solicitud.")
            )
    except requests.exceptions.ConnectionError:
        from fastapi import HTTPException
        raise HTTPException(
            status_code=503,
            detail="El servicio de correo no está disponible en este momento."
        )

