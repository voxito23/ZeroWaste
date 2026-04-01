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
    """Registra solicitud de recuperación de contraseña en PostgreSQL."""
    nueva = PasswordResetRequest(email=data.email)
    db.add(nueva)
    db.commit()
    return MessageResponse(success=True, message="Solicitud registrada. Nuestro equipo se pondrá en contacto contigo.")
