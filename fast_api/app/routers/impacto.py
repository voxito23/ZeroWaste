from fastapi import APIRouter, Depends, HTTPException, Query, status
from pydantic import BaseModel, Field
from sqlalchemy import func
from sqlalchemy.orm import Session

from app.data.database import get_db
from app.models.domain_models import Canje, MovimientoPuntos, Recompensa, SaldoPuntos, Usuario
from app.security.jwt_auth import get_current_user

router = APIRouter(prefix="/impacto", tags=["Impacto y recompensas"])


class CanjeCreate(BaseModel):
    recompensa_id: int
    cantidad: int = Field(default=1, ge=1, le=10)


def _reward_dict(recompensa: Recompensa) -> dict:
    return {
        "id": recompensa.id,
        "nombre": recompensa.nombre,
        "descripcion": recompensa.descripcion,
        "costo_puntos": recompensa.costo_puntos,
        "stock": recompensa.stock,
        "imagen_url": f"https://www.zerowaste-qro.com/images/recompensas/{recompensa.imagen}" if recompensa.imagen else None,
        "activa": recompensa.activa,
        "limite_por_usuario": recompensa.limite_por_usuario,
    }


@router.get("/ranking")
def ranking(limit: int = Query(default=50, ge=1, le=100), db: Session = Depends(get_db)):
    rows = (
        db.query(Usuario, SaldoPuntos)
        .join(SaldoPuntos, SaldoPuntos.usuario_id == Usuario.id)
        .order_by(SaldoPuntos.impacto_historico.desc(), Usuario.id.asc())
        .limit(limit).all()
    )
    return [{
        "posicion": index,
        "usuario_id": user.id,
        "nombre": user.nombre,
        "avatar": user.foto_perfil,
        "impacto_historico": balance.impacto_historico,
    } for index, (user, balance) in enumerate(rows, start=1)]


@router.get("/me")
def my_impact(db: Session = Depends(get_db), current_user: Usuario = Depends(get_current_user)):
    balance = db.query(SaldoPuntos).filter_by(usuario_id=current_user.id).first()
    available = balance.puntos_disponibles if balance else 0
    impact = balance.impacto_historico if balance else 0
    position = db.query(func.count(SaldoPuntos.usuario_id)).filter(SaldoPuntos.impacto_historico > impact).scalar() + 1
    return {
        "usuario_id": current_user.id,
        "nombre": current_user.nombre,
        "avatar": current_user.foto_perfil,
        "posicion": position,
        "impacto_historico": impact,
        "puntos_disponibles": available,
        "nivel": 1 + impact // 500,
        "progreso_nivel": impact % 500,
        "siguiente_nivel": 500,
    }


@router.get("/movimientos")
def my_movements(db: Session = Depends(get_db), current_user: Usuario = Depends(get_current_user)):
    rows = db.query(MovimientoPuntos).filter_by(usuario_id=current_user.id).order_by(MovimientoPuntos.created_at.desc()).limit(100).all()
    return [{
        "id": row.id, "tipo": row.tipo, "cantidad": row.cantidad,
        "saldo_nuevo": row.saldo_nuevo, "impacto_nuevo": row.impacto_nuevo,
        "descripcion": row.descripcion, "created_at": row.created_at,
    } for row in rows]


@router.get("/recompensas")
def rewards(db: Session = Depends(get_db)):
    rows = db.query(Recompensa).filter(Recompensa.activa.is_(True)).order_by(Recompensa.orden, Recompensa.id).all()
    return [_reward_dict(row) for row in rows]


@router.get("/recompensas/{recompensa_id}")
def reward_detail(recompensa_id: int, db: Session = Depends(get_db)):
    row = db.query(Recompensa).filter(Recompensa.id == recompensa_id, Recompensa.activa.is_(True)).first()
    if not row:
        raise HTTPException(status_code=404, detail="Recompensa no encontrada.")
    return _reward_dict(row)


@router.post("/canjes", status_code=status.HTTP_201_CREATED)
def redeem(payload: CanjeCreate, db: Session = Depends(get_db), current_user: Usuario = Depends(get_current_user)):
    reward = db.query(Recompensa).filter(Recompensa.id == payload.recompensa_id).with_for_update().first()
    if not reward or not reward.activa:
        raise HTTPException(status_code=404, detail="Recompensa no disponible.")
    if reward.stock < payload.cantidad:
        raise HTTPException(status_code=409, detail="No hay stock suficiente.")
    balance = db.query(SaldoPuntos).filter_by(usuario_id=current_user.id).with_for_update().first()
    total = reward.costo_puntos * payload.cantidad
    if not balance or balance.puntos_disponibles < total:
        raise HTTPException(status_code=409, detail="No tienes puntos suficientes.")
    previous = balance.puntos_disponibles
    balance.puntos_disponibles -= total
    reward.stock -= payload.cantidad
    redemption = Canje(usuario_id=current_user.id, recompensa_id=reward.id, cantidad=payload.cantidad, puntos_utilizados=total)
    db.add(redemption)
    db.flush()
    db.add(MovimientoPuntos(
        usuario_id=current_user.id, tipo="CANJE", cantidad=-total,
        saldo_anterior=previous, saldo_nuevo=balance.puntos_disponibles,
        impacto_anterior=balance.impacto_historico, impacto_nuevo=balance.impacto_historico,
        referencia_tipo="CANJE", referencia_id=str(redemption.id),
        descripcion=f"Canje de {reward.nombre}",
    ))
    db.commit()
    return {"id": redemption.id, "estado": redemption.estado, "puntos_utilizados": total}


@router.get("/canjes")
def my_redemptions(db: Session = Depends(get_db), current_user: Usuario = Depends(get_current_user)):
    rows = db.query(Canje).filter_by(usuario_id=current_user.id).order_by(Canje.created_at.desc()).all()
    return [{
        "id": row.id, "recompensa_id": row.recompensa_id,
        "recompensa": row.recompensa.nombre, "imagen_url": _reward_dict(row.recompensa)["imagen_url"],
        "cantidad": row.cantidad, "puntos_utilizados": row.puntos_utilizados,
        "estado": row.estado, "created_at": row.created_at,
    } for row in rows]
