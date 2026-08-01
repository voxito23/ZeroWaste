"""Collection availability in the canonical America/Mexico_City timezone."""

from datetime import date, datetime, time, timedelta, timezone
from zoneinfo import ZoneInfo

from sqlalchemy import func
from sqlalchemy.orm import Session

from app.models.domain_models import CollectionSchedule, ScheduleException, SolicitudRecoleccion

TIMEZONE_NAME = "America/Mexico_City"
LOCAL_TZ = ZoneInfo(TIMEZONE_NAME)
SCHEDULE_MESSAGE = "Las recolecciones se realizan lunes, miércoles y viernes de 10:00 a. m. a 2:00 p. m."


class ScheduleValidationError(ValueError):
    pass


def _local(value: datetime) -> datetime:
    if value.tzinfo is None:
        return value.replace(tzinfo=LOCAL_TZ)
    return value.astimezone(LOCAL_TZ)


def _exception(db: Session, target: date):
    return db.query(ScheduleException).filter_by(exception_date=target, active=True).order_by(ScheduleException.id.desc()).first()


def schedule_for_date(db: Session, target: date):
    exception = _exception(db, target)
    if exception and exception.kind in {"closed", "holiday", "blocked"}:
        return None, exception
    schedule = db.query(CollectionSchedule).filter_by(weekday=target.isoweekday(), active=True).first()
    return schedule, exception


def validate_slot(db: Session, scheduled_at: datetime) -> datetime:
    local = _local(scheduled_at)
    schedule, exception = schedule_for_date(db, local.date())
    if not schedule:
        raise ScheduleValidationError(SCHEDULE_MESSAGE)
    start = exception.starts_at if exception and exception.starts_at else schedule.starts_at
    end = exception.ends_at if exception and exception.ends_at else schedule.ends_at
    if not start <= local.time().replace(tzinfo=None) <= end:
        raise ScheduleValidationError(SCHEDULE_MESSAGE)
    minute_delta = local.hour * 60 + local.minute - (start.hour * 60 + start.minute)
    if minute_delta < 0 or minute_delta % schedule.interval_minutes != 0:
        raise ScheduleValidationError("Selecciona uno de los horarios disponibles.")
    capacity = exception.capacity_per_interval if exception and exception.capacity_per_interval is not None else schedule.capacity_per_interval
    utc_slot = local.astimezone(timezone.utc)
    occupied = db.query(func.count(SolicitudRecoleccion.id)).filter(
        SolicitudRecoleccion.scheduled_at == utc_slot,
        SolicitudRecoleccion.estado != "cancelada",
    ).scalar() or 0
    if occupied >= capacity:
        raise ScheduleValidationError("No hay horarios disponibles para esta fecha.")
    return utc_slot


def available_slots(db: Session, target: date) -> list[dict]:
    schedule, exception = schedule_for_date(db, target)
    if not schedule:
        return []
    start = exception.starts_at if exception and exception.starts_at else schedule.starts_at
    end = exception.ends_at if exception and exception.ends_at else schedule.ends_at
    cursor = datetime.combine(target, start, LOCAL_TZ)
    finish = datetime.combine(target, end, LOCAL_TZ)
    result = []
    while cursor <= finish:
        try:
            utc_slot = validate_slot(db, cursor)
            result.append({"value": utc_slot.isoformat(), "label": cursor.strftime("%H:%M"), "timezone": TIMEZONE_NAME})
        except ScheduleValidationError:
            pass
        cursor += timedelta(minutes=schedule.interval_minutes)
    return result
