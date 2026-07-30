"""
Router de campañas — operaciones CRUD.
Expone como API REST la gestión de campañas del sistema.
"""

from typing import List

from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.orm import Session

from app.data.database import get_db
from app.models.domain_models import Usuario, Campaign
from app.models.schemas import CampaignCreate, CampaignUpdate, CampaignResponse, MessageResponse
from app.security.jwt_auth import get_current_user, get_current_admin_user

router = APIRouter(prefix="/campanas", tags=["Campañas"])


@router.get("", response_model=List[CampaignResponse], summary="Listar todas las campañas")
def list_campanas(db: Session = Depends(get_db)):
    """Devuelve todas las campañas activas ordenadas por fecha de creación."""
    return db.query(Campaign).filter(Campaign.activa == True).order_by(Campaign.created_at.desc()).all()


@router.get("/{campana_id}", response_model=CampaignResponse, summary="Obtener campaña por ID")
def get_campana(campana_id: int, db: Session = Depends(get_db)):
    """Devuelve una campaña específica por su ID."""
    campana = db.query(Campaign).filter(Campaign.id == campana_id).first()
    if not campana:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Campaña no encontrada.")
    return campana


@router.post(
    "",
    response_model=CampaignResponse,
    status_code=status.HTTP_201_CREATED,
    summary="Crear una nueva campaña",
)
def create_campana(
    campana_in: CampaignCreate,
    db: Session = Depends(get_db),
    _current_admin: Usuario = Depends(get_current_admin_user),
):
    """Crea una nueva campaña. Exclusivo para administradores."""
    nueva_campana = Campaign(
        nombre=campana_in.nombre,
        lugar=campana_in.lugar,
        fecha_inicio=campana_in.fecha_inicio,
        fecha_fin=campana_in.fecha_fin,
        descripcion=campana_in.descripcion,
        tipo_etiqueta=campana_in.tipo_etiqueta,
        imagen_url=campana_in.imagen_url,
        link_evento=campana_in.link_evento,
        recompensa_puntos=campana_in.recompensa_puntos,
        activa=campana_in.activa,
    )
    db.add(nueva_campana)
    db.commit()
    db.refresh(nueva_campana)
    return nueva_campana


@router.put("/{campana_id}", response_model=CampaignResponse, summary="Actualizar una campaña")
def update_campana(
    campana_id: int,
    datos: CampaignUpdate,
    db: Session = Depends(get_db),
    _current_admin: Usuario = Depends(get_current_admin_user),
):
    """Actualiza los campos de una campaña existente. Requiere ser administrador."""
    campana = db.query(Campaign).filter(Campaign.id == campana_id).first()
    if not campana:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Campaña no encontrada.")

    update_data = datos.model_dump(exclude_unset=True)
    for field, value in update_data.items():
        setattr(campana, field, value)

    db.commit()
    db.refresh(campana)
    return campana


@router.delete("/{campana_id}", response_model=MessageResponse, summary="Eliminar una campaña")
def delete_campana(
    campana_id: int,
    db: Session = Depends(get_db),
    _current_admin: Usuario = Depends(get_current_admin_user),
):
    """Elimina una campaña por su ID. Requiere ser administrador."""
    campana = db.query(Campaign).filter(Campaign.id == campana_id).first()
    if not campana:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Campaña no encontrada.")

    db.delete(campana)
    db.commit()
    return MessageResponse(success=True, message="Campaña eliminada correctamente.")
