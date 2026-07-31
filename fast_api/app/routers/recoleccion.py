from typing import List
import hashlib
import secrets
from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.orm import Session
from datetime import datetime, timedelta, timezone
from pydantic import BaseModel

from app.data.database import get_db
from app.models.domain_models import Usuario, SolicitudRecoleccion, TokenQrRecoleccion
from app.models.schemas import (
    SolicitudRecoleccionCreate, 
    SolicitudRecoleccionResponse, 
    CalificarRecolectorRequest,
    MessageResponse
)
from app.security.jwt_auth import get_current_user
from app.services.points import award_points

router = APIRouter(prefix="/recolecciones", tags=["Recolección a Domicilio"])

class QrTokenRequest(BaseModel):
    token: str

@router.post("", response_model=SolicitudRecoleccionResponse, status_code=status.HTTP_201_CREATED, summary="Solicitar recolección a domicilio")
def solicitar_recoleccion(
    solicitud_in: SolicitudRecoleccionCreate,
    db: Session = Depends(get_db),
    current_user: Usuario = Depends(get_current_user),
):
    """Crea una nueva solicitud de recolección para el usuario actual."""
    nueva_solicitud = SolicitudRecoleccion(
        usuario_id=current_user.id,
        latitud=solicitud_in.latitud,
        longitud=solicitud_in.longitud,
        direccion=solicitud_in.direccion,
        materiales=solicitud_in.materiales,
        estado="pendiente"
    )
    db.add(nueva_solicitud)
    db.commit()
    db.refresh(nueva_solicitud)
    return nueva_solicitud

@router.get("", response_model=List[SolicitudRecoleccionResponse], summary="Ver mis solicitudes de recolección")
def mis_solicitudes(
    db: Session = Depends(get_db),
    current_user: Usuario = Depends(get_current_user),
):
    """Devuelve el historial de solicitudes de recolección o todas si el usuario es recolector/admin."""
    query = db.query(SolicitudRecoleccion)
    if current_user.rol not in ['recolector', 'admin'] and not current_user.is_admin:
        query = query.filter(SolicitudRecoleccion.usuario_id == current_user.id)
    solicitudes = query.order_by(SolicitudRecoleccion.created_at.desc()).all()
    return solicitudes

@router.post("/{solicitud_id}/calificar", response_model=MessageResponse, summary="Calificar al recolector")
def calificar_recolector(
    solicitud_id: int,
    calificacion_in: CalificarRecolectorRequest,
    db: Session = Depends(get_db),
    current_user: Usuario = Depends(get_current_user),
):
    """Permite al usuario calificar al recolector una vez completada la recolección."""
    solicitud = db.query(SolicitudRecoleccion).filter(
        SolicitudRecoleccion.id == solicitud_id,
        SolicitudRecoleccion.usuario_id == current_user.id
    ).first()

    if not solicitud:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Solicitud no encontrada.")

    if solicitud.estado != "completada":
        raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail="Solo puedes calificar recolecciones completadas.")
    
    if calificacion_in.calificacion < 1 or calificacion_in.calificacion > 5:
        raise HTTPException(status_code=status.HTTP_422_UNPROCESSABLE_ENTITY, detail="La calificación debe estar entre 1 y 5 estrellas.")

    solicitud.calificacion_recolector = calificacion_in.calificacion
    solicitud.comentario_recolector = calificacion_in.comentario
    db.commit()

    return MessageResponse(success=True, message="Recolector calificado exitosamente.")

@router.post("/{solicitud_id}/qr", summary="Generar QR temporal de una recolección")
def generar_qr_recoleccion(solicitud_id: int, db: Session = Depends(get_db), current_user: Usuario = Depends(get_current_user)):
    solicitud = db.query(SolicitudRecoleccion).filter_by(id=solicitud_id).first()
    if not solicitud or (solicitud.usuario_id != current_user.id and not current_user.is_admin):
        raise HTTPException(status_code=404, detail="Solicitud no encontrada.")
    if solicitud.estado == "completada":
        raise HTTPException(status_code=409, detail="La recolección ya fue completada.")
    raw_token = secrets.token_urlsafe(32)
    token_hash = hashlib.sha256(raw_token.encode()).hexdigest()
    record = db.query(TokenQrRecoleccion).filter_by(solicitud_id=solicitud_id).first()
    if record:
        record.token_hash = token_hash; record.expires_at = datetime.now(timezone.utc) + timedelta(minutes=10); record.used_at = None; record.used_by = None
    else:
        db.add(TokenQrRecoleccion(solicitud_id=solicitud_id, token_hash=token_hash, expires_at=datetime.now(timezone.utc) + timedelta(minutes=10)))
    db.commit()
    return {"token": raw_token, "expires_in": 600}

@router.post("/completar-qr", response_model=MessageResponse, summary="Completar recolección con token QR seguro")
def completar_recoleccion_qr(
    payload: QrTokenRequest,
    db: Session = Depends(get_db),
    current_user: Usuario = Depends(get_current_user),
):
    """
    Endpoint para que el recolector escanee el QR (que contiene el ID de la solicitud)
    y la marque como completada al instante.
    """
    if current_user.rol not in ['recolector', 'admin'] and not current_user.is_admin:
        raise HTTPException(status_code=status.HTTP_403_FORBIDDEN, detail="Solo los recolectores pueden escanear el QR de recolección.")

    token_hash = hashlib.sha256(payload.token.strip().encode()).hexdigest()
    qr = db.query(TokenQrRecoleccion).filter_by(token_hash=token_hash).with_for_update().first()
    now = datetime.now(timezone.utc)
    if not qr or qr.used_at is not None or qr.expires_at.replace(tzinfo=timezone.utc) <= now:
        raise HTTPException(status_code=status.HTTP_409_CONFLICT, detail="El código QR es inválido, expiró o ya fue utilizado.")
    solicitud = db.query(SolicitudRecoleccion).filter(SolicitudRecoleccion.id == qr.solicitud_id).with_for_update().first()
    
    if solicitud.estado == 'completada':
        raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail="Esta recolección ya fue completada anteriormente.")
    
    solicitud.estado = "completada"
    solicitud.recolector_id = current_user.id
    qr.used_at = now
    qr.used_by = current_user.id
    award_points(db, user_id=solicitud.usuario_id, rule_code="RECOLECCION_QR", reference_type="RECOLECCION", reference_id=str(solicitud.id), description="Recolección verificada mediante QR")
    db.commit()

    return MessageResponse(success=True, message="QR validado. Recolección completada con éxito.")
