"""
Router de usuarios — CRUD protegido por JWT.
"""

from typing import List

from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.orm import Session

from app.data.database import get_db
from app.models.domain_models import Usuario
from app.models.schemas import UsuarioResponse, UsuarioUpdate, MessageResponse
from app.security.jwt_auth import get_current_user

router = APIRouter(prefix="/usuarios", tags=["Usuarios"])


@router.get("/me", response_model=UsuarioResponse, summary="Datos del usuario autenticado")
def read_current_user(current_user: Usuario = Depends(get_current_user)):
    """Devuelve los datos del usuario que posee el JWT actual."""
    return current_user


@router.get("/", response_model=List[UsuarioResponse], summary="Listar todos los usuarios")
def list_users(
    db: Session = Depends(get_db),
    _current_user: Usuario = Depends(get_current_user),
):
    """Devuelve la lista completa de usuarios registrados. Requiere JWT."""
    return db.query(Usuario).order_by(Usuario.id).all()


@router.get("/{usuario_id}", response_model=UsuarioResponse, summary="Obtener usuario por ID")
def get_user(
    usuario_id: int,
    db: Session = Depends(get_db),
    _current_user: Usuario = Depends(get_current_user),
):
    """Devuelve los datos de un usuario específico por su ID."""
    usuario = db.query(Usuario).filter(Usuario.id == usuario_id).first()
    if not usuario:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Usuario no encontrado.")
    return usuario


@router.put("/{usuario_id}", response_model=UsuarioResponse, summary="Actualizar perfil de usuario")
def update_user(
    usuario_id: int,
    datos: UsuarioUpdate,
    db: Session = Depends(get_db),
    current_user: Usuario = Depends(get_current_user),
):
    """
    Actualiza los campos del perfil del usuario.
    Solo el propio usuario puede editar su perfil.
    """
    if current_user.id != usuario_id:
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="Solo puedes editar tu propio perfil.",
        )

    update_data = datos.model_dump(exclude_unset=True)
    for field, value in update_data.items():
        setattr(current_user, field, value)

    db.commit()
    db.refresh(current_user)
    return current_user


@router.delete("/{usuario_id}", response_model=MessageResponse, summary="Eliminar usuario")
def delete_user(
    usuario_id: int,
    db: Session = Depends(get_db),
    current_user: Usuario = Depends(get_current_user),
):
    """Elimina un usuario. Solo el propio usuario puede eliminarse."""
    if current_user.id != usuario_id:
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="Solo puedes eliminar tu propia cuenta.",
        )

    db.delete(current_user)
    db.commit()
    return MessageResponse(success=True, message="Usuario eliminado correctamente.")
