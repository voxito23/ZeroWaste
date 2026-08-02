"""Canonical QR generation and validation for ZeroWaste."""

from datetime import datetime, timezone
from io import BytesIO

from fastapi import APIRouter, Depends, Query
from fastapi.responses import JSONResponse, Response
from pydantic import BaseModel, Field
from sqlalchemy import text
from sqlalchemy.orm import Session

from app.data.database import get_db
from app.models.domain_models import PointQrCode, PuntoMapa, TokenQrRecoleccion, Usuario
from app.security.api_key_auth import require_api_key
from app.security.jwt_auth import get_optional_current_user
from app.services.qr_tokens import (
    QrContentError,
    decrypt_token,
    encrypt_token,
    new_token,
    parse_content,
    public_content,
    token_hash,
)
from app.services.collection_qr import CollectionQrError, complete_collection

router = APIRouter(prefix="/qr", tags=["Códigos QR"])


class QrValidationRequest(BaseModel):
    contenido: str = Field(min_length=1, max_length=2048)
    collection_id: int | None = Field(default=None, gt=0)


@router.post("/render")
def render_qr(
    request: QrValidationRequest,
    format: str = Query(default="svg", pattern="^(svg|png)$"),
    _system_key: str = Depends(require_api_key),
):
    """Render only a structurally valid ZeroWaste URL; never renders arbitrary content."""
    try:
        parse_content(request.contenido)
    except QrContentError as error:
        return _problem(422, error.code, error.detail)
    import qrcode

    qr_image = qrcode.QRCode(
        error_correction=qrcode.constants.ERROR_CORRECT_Q,
        box_size=12,
        border=4,
    )
    qr_image.add_data(request.contenido)
    qr_image.make(fit=True)
    output = BytesIO()
    if format == "svg":
        import qrcode.image.svg

        image = qr_image.make_image(image_factory=qrcode.image.svg.SvgPathFillImage)
        image.save(output)
        media_type = "image/svg+xml"
    else:
        image = qr_image.make_image(fill_color="black", back_color="white")
        image.save(output, format="PNG")
        media_type = "image/png"
    return Response(content=output.getvalue(), media_type=media_type)


def _problem(status_code: int, code: str, detail: str) -> JSONResponse:
    return JSONResponse(status_code=status_code, content={"valid": False, "code": code, "detail": detail})


def _point_payload(point: PuntoMapa) -> dict:
    materials = [item.strip() for item in str(point.materiales or "").split(",") if item.strip()]
    return {
        "id": str(point.id),
        "nombre": point.nombre,
        "direccion": point.direccion,
        "latitud": float(point.latitud),
        "longitud": float(point.longitud),
        "materiales": materials,
        "imagen_url": getattr(point, "image_url", None),
        "horario": point.horario,
        "activo": point.activo,
    }


@router.post("/validar")
def validate_qr(
    request: QrValidationRequest,
    db: Session = Depends(get_db),
    current_user: Usuario | None = Depends(get_optional_current_user),
):
    try:
        parsed = parse_content(request.contenido)
    except QrContentError as error:
        return _problem(400, error.code, error.detail)

    if parsed.kind == "collection":
        if current_user is None:
            return _problem(401, "AUTH_REQUIRED", "Inicia sesión para validar esta recolección.")
        try:
            collection = complete_collection(
                db,
                raw_token=parsed.token,
                current_user=current_user,
                expected_collection_id=request.collection_id,
            )
            qr = db.query(TokenQrRecoleccion).filter_by(solicitud_id=collection.id).first()
            return {
                "valid": True,
                "type": "collection",
                "collection_id": str(collection.id),
                "status": collection.estado,
                "expires_at": qr.expires_at.isoformat() if qr and qr.expires_at else None,
            }
        except CollectionQrError as error:
            return _problem(error.status_code, error.code, error.detail)

    qr = db.query(PointQrCode).filter(PointQrCode.token_hash == token_hash(parsed.token)).first()
    if not qr:
        return _problem(422, "QR_TAMPERED", "Este código QR no es válido o fue modificado.")
    if not qr.active or qr.revoked_at is not None:
        return _problem(422, "QR_REVOKED", "Este código QR ya no está activo.")
    point = db.query(PuntoMapa).filter(PuntoMapa.id == qr.location_id).first()
    if not point or not point.activo or point.deleted_at is not None:
        return _problem(422, "QR_REVOKED", "Este código QR ya no está activo.")
    return {"valid": True, "type": "recycling_point", "point": _point_payload(point)}


def _active_point_qr(db: Session, point_id: int) -> PointQrCode | None:
    return (
        db.query(PointQrCode)
        .filter(PointQrCode.location_id == point_id, PointQrCode.active.is_(True))
        .order_by(PointQrCode.generated_at.desc())
        .first()
    )


def _lock_point_qr(db: Session, point_id: int) -> None:
    """Serialize QR lifecycle changes, including the first QR for a point."""
    db.execute(
        text("SELECT pg_advisory_xact_lock(:namespace, :point_id)"),
        {"namespace": 20517, "point_id": point_id},
    )


@router.post("/puntos/{point_id}/generar", status_code=201)
def generate_point_qr(
    point_id: int,
    db: Session = Depends(get_db),
    _system_key: str = Depends(require_api_key),
):
    _lock_point_qr(db, point_id)
    point = db.query(PuntoMapa).filter(PuntoMapa.id == point_id).first()
    if not point:
        return _problem(404, "POINT_NOT_FOUND", "Punto no encontrado.")
    existing = _active_point_qr(db, point_id)
    if existing:
        token = decrypt_token(existing.token_ciphertext)
        return {"valid": True, "status": "active", "content": public_content(token), "generated_at": existing.generated_at}
    token = new_token("recycling_point")
    qr = PointQrCode(
        location_id=point_id,
        token_hash=token_hash(token),
        token_ciphertext=encrypt_token(token),
        version=1,
        active=True,
        created_by=None,
    )
    db.add(qr)
    db.commit()
    db.refresh(qr)
    return {"valid": True, "status": "active", "content": public_content(token), "generated_at": qr.generated_at}


@router.get("/puntos/{point_id}")
def show_point_qr(
    point_id: int,
    db: Session = Depends(get_db),
    _system_key: str = Depends(require_api_key),
):
    qr = _active_point_qr(db, point_id)
    if not qr:
        return _problem(404, "QR_NOT_FOUND", "El punto todavía no tiene un código QR activo.")
    return {
        "valid": True,
        "status": "active",
        "public_id": qr.token_hash[:10].upper(),
        "content": public_content(decrypt_token(qr.token_ciphertext)),
        "generated_at": qr.generated_at,
    }


@router.post("/puntos/{point_id}/revocar")
def revoke_point_qr(
    point_id: int,
    db: Session = Depends(get_db),
    _system_key: str = Depends(require_api_key),
):
    _lock_point_qr(db, point_id)
    qr = _active_point_qr(db, point_id)
    if not qr:
        return _problem(404, "QR_NOT_FOUND", "El punto todavía no tiene un código QR activo.")
    qr.active = False
    qr.revoked_at = datetime.now(timezone.utc)
    db.commit()
    return {"valid": True, "message": "El código QR fue revocado correctamente."}


@router.post("/puntos/{point_id}/regenerar", status_code=201)
def regenerate_point_qr(
    point_id: int,
    db: Session = Depends(get_db),
    _system_key: str = Depends(require_api_key),
):
    _lock_point_qr(db, point_id)
    previous = _active_point_qr(db, point_id)
    now = datetime.now(timezone.utc)
    if previous:
        previous.active = False
        previous.revoked_at = now
        previous.regenerated_at = now
    token = new_token("recycling_point")
    qr = PointQrCode(
        location_id=point_id,
        token_hash=token_hash(token),
        token_ciphertext=encrypt_token(token),
        version=(previous.version + 1 if previous else 1),
        active=True,
        generated_at=now,
        created_by=None,
    )
    db.add(qr)
    db.commit()
    return {"valid": True, "status": "active", "content": public_content(token), "generated_at": now}


@router.get("/puntos/{point_id}/historial")
def point_qr_history(
    point_id: int,
    db: Session = Depends(get_db),
    _system_key: str = Depends(require_api_key),
):
    rows = db.query(PointQrCode).filter(PointQrCode.location_id == point_id).order_by(PointQrCode.generated_at.desc()).all()
    return [{
        "public_id": row.token_hash[:10].upper(),
        "version": row.version,
        "active": row.active,
        "generated_at": row.generated_at,
        "regenerated_at": row.regenerated_at,
        "revoked_at": row.revoked_at,
    } for row in rows]
