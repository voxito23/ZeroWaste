from typing import List
from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.orm import Session
from datetime import datetime, timezone

from app.data.database import get_db
from app.models.domain_models import Usuario, SolicitudRecoleccion
from app.models.schemas import (
    SolicitudRecoleccionCreate, 
    SolicitudRecoleccionResponse, 
    CalificarRecolectorRequest,
    MessageResponse
)
from app.security.jwt_auth import get_current_user

router = APIRouter(prefix="/recolecciones", tags=["Recolección a Domicilio"])

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

@router.post("/{solicitud_id}/completar-qr", response_model=MessageResponse, summary="Completar recolección por QR (Solo Recolector)")
def completar_recoleccion_qr(
    solicitud_id: int,
    db: Session = Depends(get_db),
    current_user: Usuario = Depends(get_current_user),
):
    """
    Endpoint para que el recolector escanee el QR (que contiene el ID de la solicitud)
    y la marque como completada al instante.
    """
    if current_user.rol not in ['recolector', 'admin'] and not current_user.is_admin:
        raise HTTPException(status_code=status.HTTP_403_FORBIDDEN, detail="Solo los recolectores pueden escanear el QR de recolección.")

    solicitud = db.query(SolicitudRecoleccion).filter(SolicitudRecoleccion.id == solicitud_id).first()
    if not solicitud:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Código QR inválido o solicitud no encontrada.")
    
    if solicitud.estado == 'completada':
        raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail="Esta recolección ya fue completada anteriormente.")
    
    solicitud.estado = "completada"
    solicitud.recolector_id = current_user.id
    db.commit()

    return MessageResponse(success=True, message="QR validado. Recolección completada con éxito.")
