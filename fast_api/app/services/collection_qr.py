from datetime import datetime, timezone

from sqlalchemy.orm import Session

from app.models.domain_models import SolicitudRecoleccion, TokenQrRecoleccion, Usuario
from app.services.points import award_points
from app.services.qr_tokens import token_hash


class CollectionQrError(ValueError):
    def __init__(self, code: str, detail: str, status_code: int = 422):
        super().__init__(detail)
        self.code = code
        self.detail = detail
        self.status_code = status_code


def complete_collection(db: Session, *, raw_token: str, current_user: Usuario) -> SolicitudRecoleccion:
    if current_user.rol not in {"recolector", "admin"} and not current_user.is_admin:
        raise CollectionQrError("FORBIDDEN", "No tienes permiso para confirmar esta recolección.", 403)
    qr = db.query(TokenQrRecoleccion).filter_by(token_hash=token_hash(raw_token)).with_for_update().first()
    if not qr:
        raise CollectionQrError("QR_TAMPERED", "Este código QR no es válido o fue modificado.")
    if qr.used_at is not None or qr.status == "used":
        raise CollectionQrError("QR_ALREADY_USED", "Esta recolección ya fue confirmada anteriormente.", 409)
    if qr.status != "active" or qr.invalidated_at is not None:
        raise CollectionQrError("QR_REVOKED", "Este código QR ya no está activo.")
    now = datetime.now(timezone.utc)
    expires_at = qr.expires_at.replace(tzinfo=qr.expires_at.tzinfo or timezone.utc)
    if expires_at <= now:
        raise CollectionQrError("QR_EXPIRED", "Este código QR ha vencido.", 409)
    collection = db.query(SolicitudRecoleccion).filter_by(id=qr.solicitud_id).with_for_update().first()
    if not collection or collection.estado == "cancelada":
        raise CollectionQrError("QR_REVOKED", "Este código QR ya no está activo.")
    if collection.estado == "completada":
        raise CollectionQrError("QR_ALREADY_USED", "Esta recolección ya fue confirmada anteriormente.", 409)
    collection.estado = "completada"
    collection.recolector_id = current_user.id
    qr.used_at = now
    qr.used_by = current_user.id
    qr.status = "used"
    qr.invalidated_at = now
    award_points(
        db,
        user_id=collection.usuario_id,
        rule_code="RECOLECCION_QR",
        reference_type="RECOLECCION",
        reference_id=str(collection.id),
        description="Recolección verificada mediante QR",
    )
    db.commit()
    db.refresh(collection)
    return collection
