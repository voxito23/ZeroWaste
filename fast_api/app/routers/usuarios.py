# pyright: reportGeneralTypeIssues=false, reportAttributeAccessIssue=false, reportArgumentType=false
"""
Router de usuarios — CRUD protegido por JWT.
"""

from typing import List, Optional

from fastapi import APIRouter, Depends, HTTPException, status, Form, File, UploadFile
from sqlalchemy.orm import Session, load_only

import os
import json

from app.data.database import get_db
from app.models.domain_models import Usuario, Notificacion
from app.models.schemas import UsuarioResponse, UsuarioUpdate, MessageResponse
from app.security.jwt_auth import get_current_user, get_current_admin_user
from app.services.media import (
    MAX_PROFILE_IMAGE_BYTES,
    MediaValidationError,
    remove_media_file,
    save_media_image,
)
from app.services.profile_validation import (
    validate_profile_bio,
    validate_profile_location,
    validate_profile_name,
    validate_profile_title,
)

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


@router.get("/me/notificaciones", summary="Listar mis notificaciones")
def read_notifications(db: Session = Depends(get_db), current_user: Usuario = Depends(get_current_user)):
    rows = (
        db.query(Notificacion)
        .options(load_only(
            Notificacion.id,
            Notificacion.titulo,
            Notificacion.mensaje,
            Notificacion.url,
            Notificacion.type,
            Notificacion.entity_id,
            Notificacion.route,
            Notificacion.payload,
            Notificacion.leida,
            Notificacion.created_at,
        ))
        .filter_by(user_id=current_user.id)
        .order_by(Notificacion.created_at.desc())
        .limit(100)
        .all()
    )
    return [{
        "id": row.id,
        "titulo": row.titulo,
        "mensaje": row.mensaje,
        "url": row.url,
        "type": row.type,
        "entity_id": row.entity_id,
        "route": row.route,
        "payload": row.payload or {},
        "leida": row.leida,
        "created_at": row.created_at,
    } for row in rows]


@router.get("/me/notificaciones/no-leidas", summary="Contar notificaciones no leídas")
def unread_notifications(db: Session = Depends(get_db), current_user: Usuario = Depends(get_current_user)):
    return {"total": db.query(Notificacion).filter_by(user_id=current_user.id, leida=False).count()}


@router.put("/me/notificaciones/{notification_id}/leida", summary="Marcar notificación como leída")
def mark_notification_read(notification_id: int, db: Session = Depends(get_db), current_user: Usuario = Depends(get_current_user)):
    row = (
        db.query(Notificacion)
        .options(load_only(Notificacion.id, Notificacion.leida))
        .filter_by(id=notification_id, user_id=current_user.id)
        .first()
    )
    if not row:
        raise HTTPException(status_code=404, detail="Notificación no encontrada.")
    row.leida = True
    db.commit()
    return {"success": True}


@router.put("/me/foto", response_model=UsuarioResponse, summary="Actualizar foto de perfil rápidamente")
def update_foto(
    foto_perfil: UploadFile = File(...),
    db: Session = Depends(get_db),
    current_user: Usuario = Depends(get_current_user)
):
    """Sube y actualiza únicamente la foto de perfil vía Fetch/AJAX."""
    content = foto_perfil.file.read(MAX_PROFILE_IMAGE_BYTES + 1)
    try:
        nombre_archivo_unico = save_media_image(content, "perfiles", maximum_bytes=MAX_PROFILE_IMAGE_BYTES)
    except MediaValidationError as exc:
        raise HTTPException(status_code=exc.status_code, detail=str(exc)) from exc

    current_user.foto_perfil = nombre_archivo_unico  # type: ignore
    try:
        db.commit()
        db.refresh(current_user)
    except Exception:
        db.rollback()
        remove_media_file(nombre_archivo_unico, "perfiles")
        raise
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
    
    current_user.intereses = json.dumps(intereses, ensure_ascii=False)  # type: ignore
    db.commit()
    db.refresh(current_user)
    
    return current_user


# Endpoint exacto para recibir la edición pura
@router.put("/perfil/actualizar", summary="Actualizar datos y foto del perfil")
def actualizar_perfil(
    nombre: Optional[str] = Form(None),
    ubicacion: Optional[str] = Form(None),
    titulo_perfil: Optional[str] = Form(None),
    biografia: Optional[str] = Form(None),
    intereses: Optional[str] = Form(None),
    foto_perfil: Optional[UploadFile] = File(None),
    db: Session = Depends(get_db),
    current_user: Usuario = Depends(get_current_user)
):
    """Actualiza el perfil completo y conserva una foto nueva sólo si la BD confirma."""
    try:
        if nombre is not None:
            current_user.nombre = validate_profile_name(nombre)  # type: ignore
        if ubicacion is not None:
            current_user.ubicacion = validate_profile_location(ubicacion)  # type: ignore
        if titulo_perfil is not None:
            current_user.titulo_perfil = validate_profile_title(titulo_perfil)  # type: ignore
        if biografia is not None:
            current_user.biografia = validate_profile_bio(biografia)  # type: ignore
        if intereses is not None:
            clean_intereses = intereses.strip()
            if len(clean_intereses) > 500:
                raise ValueError("Los intereses pueden tener como máximo 500 caracteres.")
            current_user.intereses = clean_intereses  # type: ignore
    except ValueError as exc:
        raise HTTPException(status_code=422, detail=str(exc)) from exc

    new_image = None
    if foto_perfil is not None and foto_perfil.filename:
        content = foto_perfil.file.read(MAX_PROFILE_IMAGE_BYTES + 1)
        try:
            new_image = save_media_image(content, "perfiles", maximum_bytes=MAX_PROFILE_IMAGE_BYTES)
        except MediaValidationError as exc:
            raise HTTPException(status_code=exc.status_code, detail=str(exc)) from exc
        current_user.foto_perfil = new_image  # type: ignore

    try:
        db.commit()
        db.refresh(current_user)
    except Exception:
        db.rollback()
        remove_media_file(new_image, "perfiles")
        raise

    return {
        "message": "Perfil actualizado exitosamente",
        "perfil": UsuarioResponse.model_validate(current_user),
    }


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
    
    if not verify_password(password_actual, current_user.password):  # type: ignore
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="La contraseña actual es incorrecta."
        )

    if len(password_nueva) < 6:
        raise HTTPException(
            status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
            detail="La nueva contraseña debe tener al menos 6 caracteres."
        )

    current_user.password = hash_password(password_nueva)  # type: ignore
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
    validators = {
        "nombre": validate_profile_name,
        "ubicacion": validate_profile_location,
        "titulo_perfil": validate_profile_title,
        "biografia": validate_profile_bio,
    }
    try:
        for field, validator in validators.items():
            if field in update_data:
                if update_data[field] is None:
                    raise ValueError(f"{field} no puede quedar vacío.")
                update_data[field] = validator(update_data[field])
    except ValueError as exc:
        raise HTTPException(status_code=422, detail=str(exc)) from exc
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

    if usuario_a_eliminar.email == os.getenv("ADMIN_EMAIL", "admin@ejemplo.com"):
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="Error: Sin autorización de eliminar admin principal."
        )

    db.delete(usuario_a_eliminar)
    db.commit()
    return MessageResponse(success=True, message="Usuario eliminado correctamente.")
