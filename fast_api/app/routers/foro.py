"""
Router del foro — CRUD completo: posts, respuestas y likes.
"""

import os
from typing import List

from fastapi import APIRouter, Depends, HTTPException, status
from fastapi.responses import FileResponse
from sqlalchemy.orm import Session

from app.data.database import get_db
from app.models.domain_models import (
    Usuario, Categoria, Foro, RespuestaForo, LikeForo, Actividad, Notificacion
)
from app.models.schemas import (
    PostCreate, PostUpdate, PostResponse, PostDetailResponse,
    RespuestaCreate, RespuestaResponse,
    LikeResponse, CategoriaResponse, MessageResponse,
)
from app.security.jwt_auth import get_current_user

router = APIRouter(prefix="/foro", tags=["Foro"])


# Imágenes

@router.get("/perfiles/{filename}", summary="Obtener imagen de perfil de usuario")
def get_perfil_image(filename: str):
    """Devuelve la imagen de perfil solicitada. Busca en el volumen compartido."""
    # En desarrollo local la ruta es static/img/perfiles. En Docker es el volumen compartido.
    path = f"static/img/perfiles/{filename}"
    if not os.path.exists(path):
        raise HTTPException(status_code=404, detail="Imagen no encontrada")
    return FileResponse(path)


@router.get("/posts/imagenes/{filename}", summary="Obtener imagen de post")
def get_post_image(filename: str):
    """Devuelve la imagen de un post."""
    path = f"static/img/posts/{filename}"
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
    _current_user: Usuario = Depends(get_current_user),
):
    """Devuelve todos los posts del foro con información de autor, categoría, respuestas y likes."""
    posts = db.query(Foro).order_by(Foro.created_at.desc()).all()

    resultado = []
    for post in posts:
        resultado.append(PostDetailResponse(
            id=post.id,
            titulo=post.titulo,
            contenido=post.contenido,
            categoria_id=post.categoria_id,
            autor_id=post.autor_id,
            imagen=post.imagen,
            created_at=post.created_at,
            autor_nombre=post.autor_rel.nombre if post.autor_rel else None,
            autor_foto=post.autor_rel.foto_perfil if post.autor_rel else None,
            categoria_nombre=post.categoria_rel.nombre if post.categoria_rel else None,
            total_respuestas=len(post.respuestas),
            total_likes=len(post.likes),
        ))
    return resultado


@router.get("/posts/{post_id}", response_model=PostDetailResponse, summary="Obtener un post por ID")
def get_post(
    post_id: int,
    db: Session = Depends(get_db),
    _current_user: Usuario = Depends(get_current_user),
):
    """Devuelve un post específico con detalles completos."""
    post = db.query(Foro).filter(Foro.id == post_id).first()
    if not post:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Post no encontrado.")

    return PostDetailResponse(
        id=post.id,
        titulo=post.titulo,
        contenido=post.contenido,
        categoria_id=post.categoria_id,
        autor_id=post.autor_id,
        imagen=post.imagen,
        created_at=post.created_at,
        autor_nombre=post.autor_rel.nombre if post.autor_rel else None,
        autor_foto=post.autor_rel.foto_perfil if post.autor_rel else None,
        categoria_nombre=post.categoria_rel.nombre if post.categoria_rel else None,
        total_respuestas=len(post.respuestas),
        total_likes=len(post.likes),
    )


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
    db: Session = Depends(get_db),
    _current_user: Usuario = Depends(get_current_user),
):
    """Devuelve todas las respuestas de un post, ordenadas cronológicamente."""
    respuestas = (
        db.query(RespuestaForo)
        .filter(RespuestaForo.post_id == post_id)
        .order_by(RespuestaForo.created_at.asc())
        .all()
    )
    resultado = []
    for r in respuestas:
        resultado.append(RespuestaResponse(
            id=r.id,
            post_id=r.post_id,
            autor_id=r.autor_id,
            contenido=r.contenido,
            created_at=r.created_at,
            autor_nombre=r.autor_rel.nombre if r.autor_rel else None,
        ))
    return resultado


@router.post(
    "/posts/{post_id}/respuestas",
    response_model=RespuestaResponse,
    status_code=status.HTTP_201_CREATED,
    summary="Agregar respuesta a un post",
)
def create_respuesta(
    post_id: int,
    respuesta_in: RespuestaCreate,
    db: Session = Depends(get_db),
    current_user: Usuario = Depends(get_current_user),
):
    """Agrega una respuesta a un post. El contenido debe tener más de 10 caracteres."""
    post = db.query(Foro).filter(Foro.id == post_id).first()
    if not post:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Post no encontrado.")

    if len(respuesta_in.contenido.strip()) <= 10:
        raise HTTPException(
            status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
            detail="La respuesta debe tener más de 10 caracteres.",
        )

    nueva_respuesta = RespuestaForo(
        post_id=post_id,
        autor_id=current_user.id,
        contenido=respuesta_in.contenido,
    )
    db.add(nueva_respuesta)

    # Notificar al autor del post (si no es el mismo que responde)
    if post.autor_id != current_user.id:
        noti = Notificacion(
            user_id=post.autor_id,
            titulo=f"{current_user.nombre} respondió a tu post",
            mensaje=respuesta_in.contenido[:100],
            url=f"/foro",
        )
        db.add(noti)

    db.commit()
    db.refresh(nueva_respuesta)

    return RespuestaResponse(
        id=nueva_respuesta.id,
        post_id=nueva_respuesta.post_id,
        autor_id=nueva_respuesta.autor_id,
        contenido=nueva_respuesta.contenido,
        created_at=nueva_respuesta.created_at,
        autor_nombre=current_user.nombre,
    )


# Sistema de likes

@router.post("/posts/{post_id}/like", summary="Dar/quitar like a un post")
def toggle_like(
    post_id: int,
    usuario_id: int,
    db: Session = Depends(get_db)
):
    """
    Toggle de like: si ya existe lo quita, si no existe lo agrega.
    Devuelve la acción realizada y el total actualizado de likes.
    """
    post = db.query(Foro).filter(Foro.id == post_id).first()
    if not post:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Post no encontrado.")

    like = db.query(LikeForo).filter_by(post_id=post_id, usuario_id=usuario_id).first()

    if like:
        db.delete(like)
        action = "unliked"
        liked = False
    else:
        nuevo_like = LikeForo(post_id=post_id, usuario_id=usuario_id)
        db.add(nuevo_like)
        action = "liked"
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
    total_likes = db.query(LikeForo).filter(LikeForo.post_id == post_id).count()

    return {"liked": liked, "total": total_likes}
