"""
Router del foro — CRUD completo: posts, respuestas y likes.
"""

import os
from typing import List

from fastapi import APIRouter, BackgroundTasks, Depends, File, Form, HTTPException, Query, UploadFile, status
from fastapi.responses import FileResponse
from sqlalchemy import or_
from sqlalchemy.exc import IntegrityError
from sqlalchemy.orm import Session
from app.data.database import get_db
from app.models.domain_models import (
    Usuario, Categoria, Foro, RespuestaForo, LikeForo, Actividad, Notificacion
)
from app.models.schemas import (
    PostCreate, PostUpdate, PostResponse, PostDetailResponse,
    ForumAuthor, RespuestaCreate, RespuestaResponse,
    LikeResponse, CategoriaResponse, MessageResponse,
)
from app.security.jwt_auth import get_current_user, get_optional_current_user
from app.services.media import (
    MAX_IMAGE_BYTES,
    MediaValidationError,
    build_public_avatar_url,
    media_directory,
    remove_media_file,
    save_media_image,
)
from app.services.points import award_points
from app.services.forum_content import forum_text_for_output, validate_forum_text
from app.services.push_notifications import active_tokens, in_app_allowed, push_allowed, send_expo_push

router = APIRouter(prefix="/foro", tags=["Foro"])


def _serialize_post(post: Foro, current_user: Usuario | None = None) -> PostDetailResponse:
    response = PostDetailResponse(
        id=post.id,
        titulo=post.titulo,
        contenido=forum_text_for_output(post.contenido),
        categoria_id=post.categoria_id,
        autor_id=post.autor_id,
        imagen=post.imagen,
        created_at=post.created_at,
        aprobado=bool(post.aprobado),
        autor_nombre=post.autor_rel.nombre if post.autor_rel else None,
        autor_foto=post.autor_rel.foto_perfil if post.autor_rel else None,
        categoria_nombre=post.categoria_rel.nombre if post.categoria_rel else None,
        total_respuestas=len(post.respuestas),
        total_likes=len(post.likes),
        comments_count=len(post.respuestas),
        likes_count=len(post.likes),
        liked_by_me=bool(
            current_user and any(like.usuario_id == current_user.id for like in post.likes)
        ),
    )
    if post.autor_rel:
        response.author = ForumAuthor(
            id=post.autor_rel.id,
            nombre=post.autor_rel.nombre,
            avatar_url=response.avatar_url,
        )
    return response


def _serialize_reply(reply: RespuestaForo) -> RespuestaResponse:
    response = RespuestaResponse(
        id=reply.id,
        post_id=reply.post_id,
        autor_id=reply.autor_id,
        parent_comment_id=reply.parent_comment_id,
        contenido=reply.contenido,
        created_at=reply.created_at,
        autor_nombre=reply.autor_rel.nombre if reply.autor_rel else None,
        autor_foto=reply.autor_rel.foto_perfil if reply.autor_rel else None,
    )
    if reply.autor_rel:
        response.author = ForumAuthor(
            id=reply.autor_rel.id,
            nombre=reply.autor_rel.nombre,
            avatar_url=response.avatar_url,
        )
    return response


def _get_post_or_404(db: Session, post_id: int, current_user: Usuario | None = None) -> Foro:
    visibility = Foro.aprobado.is_(True)
    if current_user:
        visibility = or_(visibility, Foro.autor_id == current_user.id)
    post = db.query(Foro).filter(Foro.id == post_id, visibility).first()
    if not post:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Post no encontrado.")
    return post


def _like_response(db: Session, post_id: int, liked: bool) -> LikeResponse:
    total = max(0, db.query(LikeForo).filter(LikeForo.post_id == post_id).count())
    return LikeResponse(liked=liked, likes_count=total, total=total)

async def _store_post_image(upload: UploadFile) -> str:
    content = await upload.read(MAX_IMAGE_BYTES + 1)
    try:
        return save_media_image(content, "foro")
    except MediaValidationError as exc:
        raise HTTPException(status_code=exc.status_code, detail=str(exc)) from exc


# Imágenes

@router.get("/perfiles/{filename}", summary="Obtener imagen de perfil de usuario")
def get_perfil_image(filename: str):
    """Devuelve la imagen de perfil solicitada. Busca en el volumen compartido."""
    safe_name = os.path.basename(filename)
    if safe_name != filename:
        raise HTTPException(status_code=400, detail="Nombre de archivo inválido")
    path = media_directory("perfiles") / safe_name
    if not os.path.exists(path):
        raise HTTPException(status_code=404, detail="Imagen no encontrada")
    return FileResponse(path)


@router.get("/posts/imagenes/{filename}", summary="Obtener imagen de post")
def get_post_image(filename: str):
    """Devuelve la imagen de un post."""
    safe_name = os.path.basename(filename)
    if safe_name != filename:
        raise HTTPException(status_code=400, detail="Nombre de archivo inválido")
    path = media_directory("foro") / safe_name
    if not os.path.exists(path):
        raise HTTPException(status_code=404, detail="Imagen no encontrada")
    return FileResponse(path)


# Categorías del foro

@router.get("/categorias", response_model=List[CategoriaResponse], summary="Listar categorías")
def list_categorias(db: Session = Depends(get_db)):
    """Devuelve todas las categorías del foro."""
    return db.query(Categoria).order_by(Categoria.id).all()


# Operaciones CRUD de publicaciones

@router.get("/posts", response_model=List[PostDetailResponse], summary="Listar todos los posts")
def list_posts(
    db: Session = Depends(get_db),
    current_user: Usuario | None = Depends(get_optional_current_user),
):
    """Devuelve todos los posts del foro con información de autor, categoría, respuestas y likes."""
    visibility = Foro.aprobado.is_(True)
    if current_user:
        visibility = or_(visibility, Foro.autor_id == current_user.id)
    posts = db.query(Foro).filter(visibility).order_by(Foro.created_at.desc()).all()

    return [_serialize_post(post, current_user) for post in posts]


@router.get("/posts/{post_id}", response_model=PostDetailResponse, summary="Obtener un post por ID")
def get_post(
    post_id: int,
    db: Session = Depends(get_db),
    current_user: Usuario | None = Depends(get_optional_current_user),
):
    """Devuelve un post específico con detalles completos."""
    return _serialize_post(_get_post_or_404(db, post_id, current_user), current_user)


@router.post(
    "/posts",
    response_model=PostResponse,
    status_code=status.HTTP_201_CREATED,
    summary="Crear un nuevo post",
)
def create_post(
    post_in: PostCreate,
    db: Session = Depends(get_db),
    current_user: Usuario = Depends(get_current_user),
):
    """Crea un nuevo post en el foro. Requiere JWT."""
    # Validar que la categoría exista
    cat = db.query(Categoria).filter(Categoria.id == post_in.categoria_id).first()
    if not cat:
        raise HTTPException(
            status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
            detail="La categoría seleccionada no existe.",
        )

    nuevo_post = Foro(
        titulo=post_in.titulo,
        contenido=post_in.contenido,
        categoria_id=post_in.categoria_id,
        autor_id=current_user.id,
        imagen=post_in.imagen,
        aprobado=False,
    )
    db.add(nuevo_post)
    
    # Inserción de Actividad
    actividad = Actividad(
        usuario_id=current_user.id,
        tipo="post",
        descripcion=f"Publicó un nuevo post en el foro"
    )
    db.add(actividad)
    db.commit()
    db.refresh(nuevo_post)
    return nuevo_post


@router.post(
    "/posts/con-imagen",
    response_model=PostResponse,
    status_code=status.HTTP_201_CREATED,
    summary="Crear un post con imagen opcional",
)
async def create_post_with_image(
    titulo: str = Form(..., min_length=3, max_length=200),
    contenido: str = Form(..., min_length=3, max_length=5000),
    categoria_id: int = Form(...),
    imagen: UploadFile | None = File(None),
    db: Session = Depends(get_db),
    current_user: Usuario = Depends(get_current_user),
):
    titulo = validate_forum_text(titulo, field_name="El título", minimum=3, maximum=200)
    contenido = validate_forum_text(contenido, field_name="El contenido", minimum=3, maximum=5000)
    categoria = db.query(Categoria).filter(Categoria.id == categoria_id).first()
    if not categoria:
        raise HTTPException(status_code=422, detail="La categoría seleccionada no existe.")

    filename = await _store_post_image(imagen) if imagen else None
    nuevo_post = Foro(
        titulo=titulo.strip(), contenido=contenido.strip(), categoria_id=categoria_id,
        autor_id=current_user.id, imagen=filename,
        aprobado=False,
    )
    db.add(nuevo_post)
    db.add(Actividad(
        usuario_id=current_user.id,
        tipo="post",
        descripcion="Publicó un nuevo post en el foro",
    ))
    try:
        db.commit()
        db.refresh(nuevo_post)
    except Exception:
        db.rollback()
        if filename:
            try:
                remove_media_file(filename, "foro")
            except OSError:
                pass
        raise
    return nuevo_post


@router.put("/posts/{post_id}", response_model=PostResponse, summary="Editar un post")
def update_post(
    post_id: int,
    datos: PostUpdate,
    db: Session = Depends(get_db),
    current_user: Usuario = Depends(get_current_user),
):
    """Edita un post existente. Solo el autor puede editarlo."""
    post = db.query(Foro).filter(Foro.id == post_id).first()
    if not post:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Post no encontrado.")
    if post.autor_id != current_user.id:
        raise HTTPException(status_code=status.HTTP_403_FORBIDDEN, detail="Solo el autor puede editar este post.")

    update_data = datos.model_dump(exclude_unset=True)
    for field, value in update_data.items():
        setattr(post, field, value)

    db.commit()
    db.refresh(post)
    return post


@router.delete("/posts/{post_id}", response_model=MessageResponse, summary="Eliminar un post")
def delete_post(
    post_id: int,
    db: Session = Depends(get_db),
    current_user: Usuario = Depends(get_current_user),
):
    """Elimina un post y sus respuestas/likes asociados. Solo el autor puede eliminarlo."""
    post = db.query(Foro).filter(Foro.id == post_id).first()
    if not post:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Post no encontrado.")
    if post.autor_id != current_user.id:
        raise HTTPException(status_code=status.HTTP_403_FORBIDDEN, detail="Solo el autor puede eliminar este post.")

    # Eliminar registros dependientes (respuestas y likes)
    db.query(RespuestaForo).filter(RespuestaForo.post_id == post_id).delete()
    db.query(LikeForo).filter(LikeForo.post_id == post_id).delete()
    db.delete(post)
    db.commit()
    return MessageResponse(success=True, message="Post eliminado correctamente.")


# Respuestas a publicaciones

@router.get(
    "/posts/{post_id}/respuestas",
    response_model=List[RespuestaResponse],
    summary="Listar respuestas de un post",
)
def list_respuestas(
    post_id: int,
    limit: int = Query(50, ge=1, le=100),
    offset: int = Query(0, ge=0),
    db: Session = Depends(get_db),
):
    """Devuelve todas las respuestas de un post, ordenadas cronológicamente."""
    respuestas = (
        db.query(RespuestaForo)
        .filter(RespuestaForo.post_id == post_id)
        .order_by(RespuestaForo.created_at.asc())
        .offset(offset)
        .limit(limit)
        .all()
    )
    return [_serialize_reply(reply) for reply in respuestas]


@router.post(
    "/posts/{post_id}/respuestas",
    response_model=RespuestaResponse,
    status_code=status.HTTP_201_CREATED,
    summary="Agregar respuesta a un post",
)
def create_respuesta(
    post_id: int,
    respuesta_in: RespuestaCreate,
    background_tasks: BackgroundTasks,
    db: Session = Depends(get_db),
    current_user: Usuario = Depends(get_current_user),
):
    """Agrega una respuesta a un post. El contenido debe tener más de 10 caracteres."""
    post = db.query(Foro).filter(Foro.id == post_id).first()
    if not post:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Post no encontrado.")

    parent = None
    if respuesta_in.parent_comment_id:
        parent = db.query(RespuestaForo).filter_by(id=respuesta_in.parent_comment_id, post_id=post_id).first()
        if not parent:
            raise HTTPException(status_code=status.HTTP_422_UNPROCESSABLE_ENTITY, detail="El comentario al que intentas responder no pertenece a esta publicación.")

    nueva_respuesta = RespuestaForo(
        post_id=post_id,
        autor_id=current_user.id,
        parent_comment_id=parent.id if parent else None,
        contenido=respuesta_in.contenido,
    )
    db.add(nueva_respuesta)
    db.flush()
    award_points(
        db, user_id=current_user.id, rule_code="RESPUESTA_VALIDA",
        reference_type="RESPUESTA_FORO", reference_id=str(nueva_respuesta.id),
        description="Respuesta válida en el foro",
    )

    recipient_id = parent.autor_id if parent else post.autor_id
    notification_type = "comment_reply" if parent else "post_comment"
    notification = None
    push_message = None
    if recipient_id != current_user.id:
        route = f"/posts/{post_id}"
        payload = {
            "type": notification_type,
            "entityId": str(nueva_respuesta.id),
            "postId": str(post_id),
            "route": route,
            "openComments": True,
            "highlightCommentId": str(nueva_respuesta.id),
            "actorName": current_user.nombre,
            "actorAvatarUrl": build_public_avatar_url(current_user.foto_perfil),
        }
        title = f"{current_user.nombre} respondió tu comentario" if parent else f"{current_user.nombre} comentó tu publicación"
        body = respuesta_in.contenido[:100]
        push_message = {"title": title, "body": body, "data": payload}
        if in_app_allowed(db, recipient_id, notification_type):
            noti = Notificacion(
                user_id=recipient_id,
                titulo=title,
                mensaje=body,
                url=f"zerowaste://posts/{post_id}",
                type=notification_type,
                entity_id=str(nueva_respuesta.id),
                post_id=post_id,
                comment_id=nueva_respuesta.id,
                route=route,
                payload=payload,
            )
            db.add(noti)
            db.flush()
            noti.payload = {**payload, "notificationId": str(noti.id)}
            push_message["data"] = noti.payload
            notification = noti

    db.commit()
    db.refresh(nueva_respuesta)

    if push_message and push_allowed(db, recipient_id, notification_type):
        tokens = active_tokens(db, recipient_id)
        if tokens:
            background_tasks.add_task(send_expo_push, tokens, **push_message)

    return _serialize_reply(nueva_respuesta)


# Sistema de likes

@router.put("/posts/{post_id}/like", response_model=LikeResponse, summary="Dar like a un post")
def activate_like(
    post_id: int,
    background_tasks: BackgroundTasks,
    db: Session = Depends(get_db),
    current_user: Usuario = Depends(get_current_user),
):
    """Idempotently activate the authenticated user's like."""
    post = _get_post_or_404(db, post_id)
    existing = db.query(LikeForo).filter_by(post_id=post_id, usuario_id=current_user.id).first()
    if existing:
        return _like_response(db, post_id, True)

    db.add(LikeForo(post_id=post_id, usuario_id=current_user.id))
    notification = None
    push_message = None
    if post.autor_id != current_user.id:
        payload = {
            "type": "post_like", "entityId": str(post_id), "postId": str(post_id),
            "route": f"/posts/{post_id}", "actorName": current_user.nombre,
            "actorAvatarUrl": build_public_avatar_url(current_user.foto_perfil),
        }
        title = f"A {current_user.nombre} le gustó tu post"
        body = f"Tu publicación \"{post.titulo[:50]}\" recibió un like"
        push_message = {"title": title, "body": body, "data": payload}
        if in_app_allowed(db, post.autor_id, "post_like"):
            notification = Notificacion(
                user_id=post.autor_id,
                titulo=title,
                mensaje=body,
                url=f"zerowaste://posts/{post_id}",
                type="post_like",
                entity_id=str(post_id),
                post_id=post_id,
                route=f"/posts/{post_id}",
                payload=payload,
            )
            db.add(notification)
            db.flush()
            notification.payload = {**payload, "notificationId": str(notification.id)}
            push_message["data"] = notification.payload
    committed = False
    try:
        db.commit()
        committed = True
    except IntegrityError:
        db.rollback()
    if committed and push_message and push_allowed(db, post.autor_id, "post_like"):
        tokens = active_tokens(db, post.autor_id)
        if tokens:
            background_tasks.add_task(send_expo_push, tokens, **push_message)
    return _like_response(db, post_id, True)


@router.delete("/posts/{post_id}/like", response_model=LikeResponse, summary="Quitar like de un post")
def deactivate_like(
    post_id: int,
    db: Session = Depends(get_db),
    current_user: Usuario = Depends(get_current_user),
):
    """Idempotently remove the authenticated user's like."""
    _get_post_or_404(db, post_id)
    db.query(LikeForo).filter_by(post_id=post_id, usuario_id=current_user.id).delete(
        synchronize_session=False
    )
    db.commit()
    return _like_response(db, post_id, False)


@router.post("/posts/{post_id}/like", response_model=LikeResponse, deprecated=True, summary="Alternar like (compatibilidad)")
def toggle_like(
    post_id: int,
    db: Session = Depends(get_db),
    current_user: Usuario = Depends(get_current_user),
):
    """
    Toggle de like: si ya existe lo quita, si no existe lo agrega.
    Devuelve la acción realizada y el total actualizado de likes.
    """
    post = _get_post_or_404(db, post_id)

    usuario_id = current_user.id
    like = db.query(LikeForo).filter_by(post_id=post_id, usuario_id=usuario_id).first()

    if like:
        db.delete(like)
        liked = False
    else:
        nuevo_like = LikeForo(post_id=post_id, usuario_id=usuario_id)
        db.add(nuevo_like)
        liked = True

        # Notificar al autor del post (si no es el mismo que da like)
        if post.autor_id != usuario_id:
            quien = db.query(Usuario).filter(Usuario.id == usuario_id).first()
            nombre = quien.nombre if quien else "Alguien"
            noti = Notificacion(
                user_id=post.autor_id,
                titulo=f"A {nombre} le gustó tu post",
                mensaje=f"Tu publicación \"{post.titulo[:50]}\" recibió un like",
                url=f"/foro",
            )
            db.add(noti)

    db.commit()
    return _like_response(db, post_id, liked)
