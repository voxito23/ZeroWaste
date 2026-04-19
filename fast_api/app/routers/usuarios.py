"""
Router de usuarios — CRUD protegido por JWT.
"""

from typing import List, Optional

from fastapi import APIRouter, Depends, HTTPException, status, Form, File, UploadFile
from sqlalchemy.orm import Session
import os
import uuid
import json

from app.data.database import get_db
from app.models.domain_models import Usuario
from app.models.schemas import UsuarioResponse, UsuarioUpdate, MessageResponse
from app.security.jwt_auth import get_current_user, get_current_admin_user

router = APIRouter(prefix="/usuarios", tags=["Usuarios"])


@router.get(
    "/me",
    response_model=UsuarioResponse,
    summary="Datos del usuario autenticado",
    responses={
        401: {"description": "No autorizado"},
        404: {"description": "No encontrado"},
    },
)
def read_current_user(current_user: Usuario = Depends(get_current_user)):
    """Devuelve los datos del usuario que posee el JWT actual."""
    return current_user


UPLOAD_DIR = "/app/static/img/perfiles"

@router.put("/me/foto", response_model=UsuarioResponse, summary="Actualizar foto de perfil rápidamente")
def update_foto(
    foto_perfil: UploadFile = File(...),
    db: Session = Depends(get_db),
    current_user: Usuario = Depends(get_current_user)
):
    """Sube y actualiza únicamente la foto de perfil vía Fetch/AJAX."""
    extension = foto_perfil.filename.split(".")[-1] if "." in foto_perfil.filename else "png"
    nombre_archivo_unico = f"{uuid.uuid4().hex}.{extension}"
    ruta_destino = f"{UPLOAD_DIR}/{nombre_archivo_unico}"

    os.makedirs(os.path.dirname(ruta_destino), exist_ok=True)
    with open(ruta_destino, "wb") as buffer:
        buffer.write(foto_perfil.file.read())

    current_user.foto_perfil = nombre_archivo_unico
    db.commit()
    db.refresh(current_user)
    return current_user


@router.put("/me/intereses", response_model=UsuarioResponse, summary="Actualizar lista de intereses")
def update_intereses(
    intereses: list[str] = Form(...), 
    db: Session = Depends(get_db),
    current_user: Usuario = Depends(get_current_user)
):
    """Reemplaza la lista actual de intereses ecológicos."""
    if len(intereses) > 5:
        raise HTTPException(status_code=400, detail="Máximo 5 intereses permitidos.")
    
    current_user.intereses = json.dumps(intereses, ensure_ascii=False)
    db.commit()
    db.refresh(current_user)
    
    return current_user


# Endpoint exacto para recibir la edición pura
@router.put("/perfil/actualizar", summary="Actualizar datos biográficos del perfil")
def actualizar_perfil(
    nombre: Optional[str] = Form(None),
    ubicacion: Optional[str] = Form(None),
    titulo_perfil: Optional[str] = Form(None),
    biografia: Optional[str] = Form(None),
    intereses: Optional[str] = Form(None),
    db: Session = Depends(get_db),
    current_user: Usuario = Depends(get_current_user)
):
    """
    Recibe la payload desde el frontend mediante Form Data,
    asigna los nuevos valores verificando que no vengan vacíos
    """
    if nombre:
        current_user.nombre = nombre
    if ubicacion:
        current_user.ubicacion = ubicacion
    if titulo_perfil:
        current_user.titulo_perfil = titulo_perfil
    if biografia:
        current_user.biografia = biografia
    if intereses:
        current_user.intereses = intereses

    # 1. Empujar actualización nativa de la fila en BD
    # 2. Refrescar el objeto local para actualizar su estado
    db.commit()
    db.refresh(current_user)
    
    return {"message": "Perfíl actualizado exitosamente", "perfil": current_user}


@router.put("/perfil/password", summary="Actualizar contraseña del perfil")
def actualizar_password(
    password_actual: str = Form(...),
    password_nueva: str = Form(...),
    db: Session = Depends(get_db),
    current_user: Usuario = Depends(get_current_user)
):
    """
    Permite al usuario cambiar su contraseña, verificando de forma segura
    su contraseña actual antes de aplicar el cambio.
    """
    from app.security.jwt_auth import verify_password, hash_password
    
    if not verify_password(password_actual, current_user.password):
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="La contraseña actual es incorrecta."
        )

    if len(password_nueva) < 6:
        raise HTTPException(
            status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
            detail="La nueva contraseña debe tener al menos 6 caracteres."
        )

    current_user.password = hash_password(password_nueva)
    db.commit()
    
    return {"message": "Contraseña actualizada exitosamente"}



@router.get("/", response_model=List[UsuarioResponse], summary="Listar todos los usuarios")
def list_users(
    db: Session = Depends(get_db),
    _current_admin: Usuario = Depends(get_current_admin_user),
):
    """Devuelve la lista completa de usuarios registrados. Exclusivo para administradores."""
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
    _current_admin: Usuario = Depends(get_current_admin_user),
):
    """Elimina un usuario de la plataforma por ID. Exclusivo para administradores."""
    usuario_a_eliminar = db.query(Usuario).filter(Usuario.id == usuario_id).first()
    
    if not usuario_a_eliminar:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Usuario no encontrado.",
        )

    if usuario_a_eliminar.email == "vichdz@gmail.com":
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="Error: Sin autorización de eliminar admin principal."
        )

    db.delete(usuario_a_eliminar)
    db.commit()
    return MessageResponse(success=True, message="Usuario eliminado correctamente.")
