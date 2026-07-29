"""
Router del mapa — puntos de reciclaje, calificaciones y recomendaciones.
Replica la lógica de consulta de puntos del microservicio Flask.
"""

from typing import List

from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy import func
from sqlalchemy.orm import Session

from app.data.database import get_db
from app.models.domain_models import Usuario, PuntoMapa, CalificacionPunto
from app.models.schemas import (
    PuntoMapaCreate, PuntoMapaUpdate, PuntoMapaResponse,
    CalificacionCreate, CalificacionResponse, MessageResponse,
)
from app.security.jwt_auth import get_current_user

router = APIRouter(prefix="/mapa", tags=["Mapa"])


# Función auxiliar para consultar puntos con promedios

def _get_puntos_con_promedio(db: Session) -> List[PuntoMapaResponse]:
    """
    Consulta todos los puntos de reciclaje con su promedio de calificación.
    Realiza un LEFT JOIN con calificaciones y calcula promedio y total de reseñas.
    """
    resultados = (
        db.query(
            PuntoMapa,
            func.coalesce(func.avg(CalificacionPunto.estrellas), 0).label("promedio"),
            func.count(CalificacionPunto.id).label("total_reviews"),
        )
        .outerjoin(CalificacionPunto, PuntoMapa.id == CalificacionPunto.location_id)
        .group_by(PuntoMapa.id)
        .order_by(func.avg(CalificacionPunto.estrellas).desc().nullslast())
        .all()
    )

    puntos = []
    for punto, promedio, total in resultados:
        puntos.append(PuntoMapaResponse(
            id=int(getattr(punto, "id", 0)),
            nombre=str(getattr(punto, "nombre", "")),
            direccion=str(getattr(punto, "direccion", "")),
            latitud=float(str(getattr(punto, "latitud", 0.0))),
            longitud=float(str(getattr(punto, "longitud", 0.0))),
            tipo=str(getattr(punto, "tipo", "")),
            materiales=str(getattr(punto, "materiales", "")) if getattr(punto, "materiales", None) else None,
            promedio=round(float(promedio or 0), 1),
            total_reviews=int(total or 0),
        ))
    return puntos


# Endpoints de gestión de puntos de reciclaje

@router.get("/puntos", response_model=List[PuntoMapaResponse], summary="Listar puntos del mapa")
def list_puntos(db: Session = Depends(get_db)):
    """Devuelve todos los puntos de reciclaje con su promedio de calificación."""
    return _get_puntos_con_promedio(db)


@router.get("/puntos/{punto_id}", response_model=PuntoMapaResponse, summary="Obtener punto por ID")
def get_punto(punto_id: int, db: Session = Depends(get_db)):
    """Devuelve un punto de reciclaje específico con su promedio."""
    resultado = (
        db.query(
            PuntoMapa,
            func.coalesce(func.avg(CalificacionPunto.estrellas), 0).label("promedio"),
            func.count(CalificacionPunto.id).label("total_reviews"),
        )
        .outerjoin(CalificacionPunto, PuntoMapa.id == CalificacionPunto.location_id)
        .filter(PuntoMapa.id == punto_id)
        .group_by(PuntoMapa.id)
        .first()
    )

    if not resultado:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Punto no encontrado.")

    punto, promedio, total = resultado
    return PuntoMapaResponse(
        id=punto.id,
        nombre=punto.nombre,
        direccion=punto.direccion,
        latitud=float(punto.latitud),
        longitud=float(punto.longitud),
        tipo=punto.tipo,
        materiales=punto.materiales,
        promedio=round(float(promedio or 0), 1),
        total_reviews=total,
    )


@router.post(
    "/puntos",
    response_model=PuntoMapaResponse,
    status_code=status.HTTP_201_CREATED,
    summary="Crear un nuevo punto de reciclaje",
)
def create_punto(
    punto_in: PuntoMapaCreate,
    db: Session = Depends(get_db),
    _current_user: Usuario = Depends(get_current_user),
):
    """Agrega un nuevo punto de reciclaje al mapa. Requiere JWT."""
    nuevo_punto = PuntoMapa(
        nombre=punto_in.nombre,
        direccion=punto_in.direccion,
        latitud=punto_in.latitud,
        longitud=punto_in.longitud,
        tipo=punto_in.tipo,
        materiales=punto_in.materiales,
    )
    db.add(nuevo_punto)
    db.commit()
    db.refresh(nuevo_punto)

    return PuntoMapaResponse(
        id=int(getattr(nuevo_punto, "id", 0)),
        nombre=str(getattr(nuevo_punto, "nombre", "")),
        direccion=str(getattr(nuevo_punto, "direccion", "")),
        latitud=float(str(getattr(nuevo_punto, "latitud", 0.0))),
        longitud=float(str(getattr(nuevo_punto, "longitud", 0.0))),
        tipo=str(getattr(nuevo_punto, "tipo", "")),
        materiales=str(getattr(nuevo_punto, "materiales", "")) if getattr(nuevo_punto, "materiales", None) else None,
        promedio=0.0,
        total_reviews=0,
    )


@router.put("/puntos/{punto_id}", response_model=PuntoMapaResponse, summary="Actualizar punto")
def update_punto(
    punto_id: int,
    datos: PuntoMapaUpdate,
    db: Session = Depends(get_db),
    _current_user: Usuario = Depends(get_current_user),
):
    """Actualiza un punto de reciclaje existente. Requiere JWT."""
    punto = db.query(PuntoMapa).filter(PuntoMapa.id == punto_id).first()
    if not punto:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Punto no encontrado.")

    update_data = datos.model_dump(exclude_unset=True)
    for field, value in update_data.items():
        setattr(punto, field, value)

    db.commit()
    db.refresh(punto)

    # Recalcular promedio de calificaciones
    promedio = db.query(func.avg(CalificacionPunto.estrellas)).filter_by(location_id=punto_id).scalar()
    total = db.query(func.count(CalificacionPunto.id)).filter_by(location_id=punto_id).scalar()

    return PuntoMapaResponse(
        id=int(getattr(punto, "id", 0)),
        nombre=str(getattr(punto, "nombre", "")),
        direccion=str(getattr(punto, "direccion", "")),
        latitud=float(str(getattr(punto, "latitud", 0.0))),
        longitud=float(str(getattr(punto, "longitud", 0.0))),
        tipo=str(getattr(punto, "tipo", "")),
        materiales=str(getattr(punto, "materiales", "")) if getattr(punto, "materiales", None) else None,
        promedio=round(float(promedio or 0), 1),
        total_reviews=int(total or 0),
    )


@router.delete("/puntos/{punto_id}", response_model=MessageResponse, summary="Eliminar punto")
def delete_punto(
    punto_id: int,
    db: Session = Depends(get_db),
    _current_user: Usuario = Depends(get_current_user),
):
    """Elimina un punto de reciclaje y sus calificaciones. Requiere JWT."""
    punto = db.query(PuntoMapa).filter(PuntoMapa.id == punto_id).first()
    if not punto:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Punto no encontrado.")

    db.query(CalificacionPunto).filter(CalificacionPunto.location_id == punto_id).delete()
    db.delete(punto)
    db.commit()
    return MessageResponse(success=True, message="Punto eliminado correctamente.")


# Calificaciones de puntos de reciclaje

@router.post(
    "/puntos/{punto_id}/calificar",
    response_model=CalificacionResponse,
    summary="Calificar un punto de reciclaje",
)
def calificar_punto(
    punto_id: int,
    calificacion_in: CalificacionCreate,
    db: Session = Depends(get_db),
    current_user: Usuario = Depends(get_current_user),
):
    """
    Califica un punto de 1 a 5 estrellas.
    Si ya calificó, actualiza la calificación existente (upsert).
    """
    if calificacion_in.estrellas < 1 or calificacion_in.estrellas > 5:
        raise HTTPException(
            status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
            detail="Las estrellas deben estar entre 1 y 5.",
        )

    punto = db.query(PuntoMapa).filter(PuntoMapa.id == punto_id).first()
    if not punto:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Punto no encontrado.")

    calificacion = db.query(CalificacionPunto).filter_by(
        location_id=punto_id, usuario_id=current_user.id
    ).first()

    if calificacion:
        setattr(calificacion, "estrellas", calificacion_in.estrellas)
    else:
        nueva = CalificacionPunto(
            location_id=punto_id,
            usuario_id=current_user.id,
            estrellas=calificacion_in.estrellas,
        )
        db.add(nueva)

    db.commit()

    promedio = db.query(func.avg(CalificacionPunto.estrellas)).filter_by(location_id=punto_id).scalar()
    total = db.query(func.count(CalificacionPunto.id)).filter_by(location_id=punto_id).scalar()

    return CalificacionResponse(
        success=True,
        promedio=round(float(promedio or 0), 1),
        total=total or 0,
    )


# Recomendaciones basadas en calificaciones

@router.get(
    "/recomendaciones",
    response_model=List[PuntoMapaResponse],
    summary="Recomendaciones de puntos (ordenados por calificación)",
)
def recomendaciones(db: Session = Depends(get_db)):
    """
    Devuelve los puntos de reciclaje ordenados por mejor calificación.
    Equivalente al endpoint /api/recomendaciones_ia del microservicio Flask.
    """
    return _get_puntos_con_promedio(db)
