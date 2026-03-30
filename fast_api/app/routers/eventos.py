"""
Router de eventos — operaciones CRUD.
Expone como API REST la gestión de eventos del sistema.
"""

from typing import List

from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.orm import Session

from app.data.database import get_db
from app.models.domain_models import Usuario, Evento
from app.models.schemas import EventoCreate, EventoUpdate, EventoResponse, MessageResponse
from app.security.jwt_auth import get_current_user, get_current_admin_user

router = APIRouter(prefix="/eventos", tags=["Eventos"])


@router.get("/", response_model=List[EventoResponse], summary="Listar todos los eventos")
def list_eventos(db: Session = Depends(get_db)):
    """Devuelve todos los eventos ordenados por fecha más reciente."""
    return db.query(Evento).order_by(Evento.fecha_inicio.desc()).all()


@router.get("/{evento_id}", response_model=EventoResponse, summary="Obtener evento por ID")
def get_evento(evento_id: int, db: Session = Depends(get_db)):
    """Devuelve un evento específico por su ID."""
    evento = db.query(Evento).filter(Evento.id == evento_id).first()
    if not evento:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Evento no encontrado.")
    return evento


@router.post(
    "/",
    response_model=EventoResponse,
    status_code=status.HTTP_201_CREATED,
    summary="Crear un nuevo evento",
)
def create_evento(
    evento_in: EventoCreate,
    db: Session = Depends(get_db),
    _current_admin: Usuario = Depends(get_current_admin_user),
):
    """Crea un nuevo evento. Exclusivo para administradores."""
    nuevo_evento = Evento(
        titulo=evento_in.titulo,
        fecha_inicio=evento_in.fecha_inicio,
        ubicacion=evento_in.ubicacion,
        descripcion=evento_in.descripcion,
        categoria=evento_in.categoria,
        imagen=evento_in.imagen,
        link_unirse=evento_in.link_unirse,
    )
    db.add(nuevo_evento)
    db.commit()
    db.refresh(nuevo_evento)
    return nuevo_evento


@router.put("/{evento_id}", response_model=EventoResponse, summary="Actualizar un evento")
def update_evento(
    evento_id: int,
    datos: EventoUpdate,
    db: Session = Depends(get_db),
    _current_user: Usuario = Depends(get_current_user),
):
    """Actualiza los campos de un evento existente. Requiere JWT."""
    evento = db.query(Evento).filter(Evento.id == evento_id).first()
    if not evento:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Evento no encontrado.")

    update_data = datos.model_dump(exclude_unset=True)
    for field, value in update_data.items():
        setattr(evento, field, value)

    db.commit()
    db.refresh(evento)
    return evento


@router.delete("/{evento_id}", response_model=MessageResponse, summary="Eliminar un evento")
def delete_evento(
    evento_id: int,
    db: Session = Depends(get_db),
    _current_user: Usuario = Depends(get_current_user),
):
    """Elimina un evento por su ID. Requiere JWT."""
    evento = db.query(Evento).filter(Evento.id == evento_id).first()
    if not evento:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Evento no encontrado.")

    db.delete(evento)
    db.commit()
    return MessageResponse(success=True, message="Evento eliminado correctamente.")
