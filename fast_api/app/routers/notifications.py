"""Typed mobile notification, device and preference endpoints."""

from __future__ import annotations

import re
from datetime import datetime, timezone
from typing import Literal

from fastapi import APIRouter, Depends, HTTPException, Query, status
from pydantic import BaseModel, ConfigDict, Field, field_validator
from sqlalchemy import and_, or_
from sqlalchemy.exc import IntegrityError
from sqlalchemy.orm import Session

from app.data.database import get_db
from app.models.domain_models import DevicePushToken, Notificacion, NotificationPreference, Usuario
from app.security.jwt_auth import get_current_user


router = APIRouter(tags=["Notificaciones móviles"])
EXPO_TOKEN = re.compile(r"^(?:ExponentPushToken|ExpoPushToken)\[[A-Za-z0-9_-]+\]$")


class DeviceTokenRequest(BaseModel):
    model_config = ConfigDict(populate_by_name=True)
    expo_push_token: str = Field(alias="expoPushToken", min_length=20, max_length=255)
    device_id: str = Field(alias="deviceId", min_length=3, max_length=255)
    platform: Literal["android", "ios"]

    @field_validator("expo_push_token")
    @classmethod
    def validate_token(cls, value: str) -> str:
        if not EXPO_TOKEN.fullmatch(value.strip()):
            raise ValueError("Token Expo Push inválido.")
        return value.strip()


class NotificationPreferencesUpdate(BaseModel):
    push_enabled: bool | None = None
    in_app_enabled: bool | None = None
    comments: bool | None = None
    replies: bool | None = None
    likes: bool | None = None
    news: bool | None = None
    articles: bool | None = None
    campaigns: bool | None = None
    collections: bool | None = None
    points: bool | None = None
    rewards: bool | None = None
    system: bool | None = None


def preference_payload(row: NotificationPreference | None) -> dict:
    fields = NotificationPreferencesUpdate.model_fields
    return {name: bool(getattr(row, name, True)) if row else True for name in fields}


def notification_payload(row: Notificacion) -> dict:
    data = dict(row.payload or {})
    data.setdefault("type", row.type)
    if row.entity_id:
        data.setdefault("entityId", row.entity_id)
    if row.post_id:
        data.setdefault("postId", str(row.post_id))
    if row.comment_id:
        data.setdefault("highlightCommentId", str(row.comment_id))
    if row.route:
        data.setdefault("route", row.route)
    created_at = row.created_at
    if created_at and created_at.tzinfo is None:
        created_at = created_at.replace(tzinfo=timezone.utc)
    return {
        "id": row.id,
        "type": row.type,
        "title": row.titulo,
        "body": row.mensaje,
        "data": data,
        "read": bool(row.leida),
        "created_at": created_at,
    }


@router.post("/devices/push-token", status_code=status.HTTP_201_CREATED)
def register_push_token(payload: DeviceTokenRequest, db: Session = Depends(get_db), current_user: Usuario = Depends(get_current_user)):
    now = datetime.now(timezone.utc)
    rows = db.query(DevicePushToken).filter(or_(
        DevicePushToken.expo_push_token == payload.expo_push_token,
        and_(DevicePushToken.user_id == current_user.id, DevicePushToken.device_id == payload.device_id),
    )).with_for_update().all()
    token_row = next((item for item in rows if item.expo_push_token == payload.expo_push_token), None)
    device_row = next((item for item in rows if item.user_id == current_user.id and item.device_id == payload.device_id), None)
    row = token_row or device_row
    if token_row and device_row and token_row.id != device_row.id:
        db.delete(device_row)
    if row and row.user_id != current_user.id:
        row.user_id = current_user.id
    if not row:
        row = DevicePushToken(user_id=current_user.id, expo_push_token=payload.expo_push_token, device_id=payload.device_id, platform=payload.platform, last_seen_at=now)
        db.add(row)
    else:
        row.expo_push_token = payload.expo_push_token
        row.device_id = payload.device_id
        row.platform = payload.platform
        row.active = True
        row.disabled_at = None
        row.last_error = None
        row.last_seen_at = now
    try:
        db.commit()
    except IntegrityError:
        db.rollback()
        raise HTTPException(status_code=409, detail="El token cambió durante el registro. Inténtalo nuevamente.")
    return {"registered": True}


@router.delete("/devices/push-token")
def disable_push_token(payload: DeviceTokenRequest, db: Session = Depends(get_db), current_user: Usuario = Depends(get_current_user)):
    row = db.query(DevicePushToken).filter_by(user_id=current_user.id, expo_push_token=payload.expo_push_token).first()
    if row:
        row.active = False
        row.disabled_at = datetime.now(timezone.utc)
        db.commit()
    return {"disabled": True}


@router.get("/notifications")
def list_notifications(limit: int = Query(30, ge=1, le=100), offset: int = Query(0, ge=0), db: Session = Depends(get_db), current_user: Usuario = Depends(get_current_user)):
    query = db.query(Notificacion).filter_by(user_id=current_user.id).order_by(Notificacion.created_at.desc(), Notificacion.id.desc())
    rows = query.offset(offset).limit(limit + 1).all()
    return {"items": [notification_payload(row) for row in rows[:limit]], "has_more": len(rows) > limit, "next_offset": offset + min(limit, len(rows))}


@router.patch("/notifications/{notification_id}/read")
def mark_read(notification_id: int, db: Session = Depends(get_db), current_user: Usuario = Depends(get_current_user)):
    row = db.query(Notificacion).filter_by(id=notification_id, user_id=current_user.id).first()
    if not row:
        raise HTTPException(status_code=404, detail="Notificación no encontrada.")
    row.leida = True
    db.commit()
    return {"read": True}


@router.post("/notifications/read-all")
def mark_all_read(db: Session = Depends(get_db), current_user: Usuario = Depends(get_current_user)):
    updated = db.query(Notificacion).filter_by(user_id=current_user.id, leida=False).update({"leida": True}, synchronize_session=False)
    db.commit()
    return {"updated": updated}


@router.get("/preferences/notifications")
def get_preferences(db: Session = Depends(get_db), current_user: Usuario = Depends(get_current_user)):
    return preference_payload(db.query(NotificationPreference).filter_by(user_id=current_user.id).first())


@router.patch("/preferences/notifications")
def update_preferences(payload: NotificationPreferencesUpdate, db: Session = Depends(get_db), current_user: Usuario = Depends(get_current_user)):
    row = db.query(NotificationPreference).filter_by(user_id=current_user.id).first()
    if not row:
        row = NotificationPreference(user_id=current_user.id)
        db.add(row)
    for field, value in payload.model_dump(exclude_none=True).items():
        setattr(row, field, value)
    row.updated_at = datetime.now(timezone.utc)
    db.commit()
    return preference_payload(row)
