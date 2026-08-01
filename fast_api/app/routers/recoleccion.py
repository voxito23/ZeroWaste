from typing import List
import uuid
from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.orm import Session
from datetime import date, datetime, timedelta, timezone
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
from app.services.collection_schedule import ScheduleValidationError, available_slots, lock_slot_capacity, validate_slot
from app.services.collection_qr import CollectionQrError, complete_collection
from app.services.qr_tokens import encrypt_token, new_token, public_content, token_hash

router = APIRouter(prefix="/recolecciones", tags=["Recolección a Domicilio"])

class QrTokenRequest(BaseModel):
    contenido: str


@router.get("/disponibilidad", summary="Consultar horarios disponibles")
def disponibilidad(fecha: date, db: Session = Depends(get_db), _current_user: Usuario = Depends(get_current_user)):
    return {"date": fecha.isoformat(), "timezone": "America/Mexico_City", "slots": available_slots(db, fecha)}

@router.post("", response_model=SolicitudRecoleccionResponse, status_code=status.HTTP_201_CREATED, summary="Solicitar recolección a domicilio")
def solicitar_recoleccion(
    solicitud_in: SolicitudRecoleccionCreate,
    db: Session = Depends(get_db),
    current_user: Usuario = Depends(get_current_user),
):
    """Crea una nueva solicitud de recolección para el usuario actual."""
    try:
        lock_slot_capacity(db, solicitud_in.scheduled_at)
        scheduled_at = validate_slot(db, solicitud_in.scheduled_at)
    except ScheduleValidationError as error:
        raise HTTPException(status_code=422, detail=str(error)) from error
    nueva_solicitud = SolicitudRecoleccion(
        usuario_id=current_user.id,
        latitud=solicitud_in.latitud,
        longitud=solicitud_in.longitud,
        direccion=solicitud_in.direccion,
        materiales=solicitud_in.materiales,
        cantidad_estimada=solicitud_in.cantidad_estimada,
        notas=solicitud_in.notas,
        scheduled_at=scheduled_at,
        folio=f"ZW-{datetime.now(timezone.utc):%Y%m%d}-{uuid.uuid4().hex[:8].upper()}",
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
    if solicitud.estado == "cancelada":
        raise HTTPException(status_code=409, detail="La recolección fue cancelada.")
    raw_token = new_token("collection")
    scheduled = solicitud.scheduled_at or datetime.now(timezone.utc)
    if scheduled.tzinfo is None:
        scheduled = scheduled.replace(tzinfo=timezone.utc)
    expires_at = max(datetime.now(timezone.utc) + timedelta(minutes=10), scheduled + timedelta(hours=6))
    record = db.query(TokenQrRecoleccion).filter_by(solicitud_id=solicitud_id).first()
    if record:
        record.token_hash = token_hash(raw_token)
        record.token_ciphertext = encrypt_token(raw_token)
        record.expires_at = expires_at
        record.used_at = None
        record.used_by = None
        record.invalidated_at = None
        record.status = "active"
        record.version = (record.version or 0) + 1
    else:
        record = TokenQrRecoleccion(solicitud_id=solicitud_id, token_hash=token_hash(raw_token), token_ciphertext=encrypt_token(raw_token), status="active", version=1, expires_at=expires_at)
        db.add(record)
    db.commit()
    return {"content": public_content(raw_token), "expires_at": expires_at}

@router.post("/completar-qr", response_model=MessageResponse, summary="Completar recolección con token QR seguro")
def completar_recoleccion_qr(
    payload: QrTokenRequest,
    db: Session = Depends(get_db),
    current_user: Usuario = Depends(get_current_user),
):
    """
    Endpoint heredado para confirmar un token QR opaco de recolección.
    """
    if current_user.rol not in ['recolector', 'admin'] and not current_user.is_admin:
        raise HTTPException(status_code=status.HTTP_403_FORBIDDEN, detail="Solo los recolectores pueden escanear el QR de recolección.")

    from app.services.qr_tokens import parse_content
    try:
        parsed = parse_content(payload.contenido)
        if parsed.kind != "collection":
            raise CollectionQrError("COLLECTION_MISMATCH", "Este código no corresponde a la recolección seleccionada.")
        complete_collection(db, raw_token=parsed.token, current_user=current_user)
    except CollectionQrError as error:
        raise HTTPException(status_code=error.status_code, detail=error.detail) from error

    return MessageResponse(success=True, message="QR validado. Recolección completada con éxito.")
