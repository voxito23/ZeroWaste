from datetime import datetime, timezone

from sqlalchemy import func
from sqlalchemy.exc import IntegrityError
from sqlalchemy.orm import Session

from app.models.domain_models import MovimientoPuntos, Notificacion, ReglaPuntos, SaldoPuntos
from app.services.push_notifications import in_app_allowed


def award_points(db: Session, *, user_id: int, rule_code: str, reference_type: str, reference_id: str, description: str) -> bool:
    rule = db.query(ReglaPuntos).filter_by(codigo=rule_code, activa=True).first()
    if not rule or rule.puntos <= 0:
        return False
    if rule.limite_diario:
        from zoneinfo import ZoneInfo
        today = datetime.now(ZoneInfo("America/Mexico_City")).date()
        rewarded_today = db.query(func.count(MovimientoPuntos.id)).filter(
            MovimientoPuntos.usuario_id == user_id,
            MovimientoPuntos.regla_id == rule.id,
            func.date(func.timezone("America/Mexico_City", MovimientoPuntos.created_at)) == today,
        ).scalar()
        if rewarded_today >= rule.limite_diario:
            return False

    balance = db.query(SaldoPuntos).filter_by(usuario_id=user_id).with_for_update().first()
    if not balance:
        balance = SaldoPuntos(usuario_id=user_id, puntos_disponibles=0, impacto_historico=0)
        db.add(balance)
        db.flush()
    previous_balance = balance.puntos_disponibles
    previous_impact = balance.impacto_historico
    balance.puntos_disponibles += rule.puntos
    balance.impacto_historico += rule.puntos
    try:
        with db.begin_nested():
            movement = MovimientoPuntos(
                usuario_id=user_id, tipo="GANADO", cantidad=rule.puntos,
                saldo_anterior=previous_balance, saldo_nuevo=balance.puntos_disponibles,
                impacto_anterior=previous_impact, impacto_nuevo=balance.impacto_historico,
                referencia_tipo=reference_type, referencia_id=str(reference_id), regla_id=rule.id,
                descripcion=description,
            )
            db.add(movement)
            db.flush()
            if in_app_allowed(db, user_id, "points_earned"):
                payload = {
                    "type": "points_earned",
                    "entityId": str(movement.id),
                    "points": int(rule.puntos),
                    "balance": int(balance.puntos_disponibles),
                    "route": "/impacto/puntos",
                }
                notification = Notificacion(
                    user_id=user_id,
                    titulo=f"Ganaste {rule.puntos} puntos",
                    mensaje=f"{description}. Tu saldo disponible es de {balance.puntos_disponibles} puntos.",
                    url="zerowaste://points",
                    type="points_earned",
                    entity_id=str(movement.id),
                    route="/impacto/puntos",
                    payload=payload,
                )
                db.add(notification)
                db.flush()
                notification.payload = {**payload, "notificationId": str(notification.id)}
        return True
    except IntegrityError:
        balance.puntos_disponibles = previous_balance
        balance.impacto_historico = previous_impact
        return False
