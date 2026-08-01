"""Google mobile OAuth and one-time email verification owned by FastAPI."""

import base64
import hashlib
import json
import os
import secrets
from datetime import datetime, timedelta, timezone
from urllib.parse import urlencode
from urllib.request import Request as UrlRequest, urlopen

from fastapi import APIRouter, Depends, HTTPException, Query
from fastapi.responses import HTMLResponse, RedirectResponse
from pydantic import BaseModel, EmailStr, Field
from sqlalchemy.orm import Session

from app.data.database import get_db
from app.models.domain_models import EmailVerificationToken, OauthAccount, OauthLoginState, Usuario
from app.security.jwt_auth import create_access_token, hash_password, verify_password
from app.services.auth_crypto import decrypt, digest, encrypt
from app.services.media import build_public_avatar_url
from app.services.transactional_email import EmailDeliveryError, send_verification

router = APIRouter(prefix="/auth", tags=["Autenticación externa"])
GOOGLE_AUTH_URL = "https://accounts.google.com/o/oauth2/v2/auth"
GOOGLE_TOKEN_URL = "https://oauth2.googleapis.com/token"
MOBILE_CALLBACK = "zerowaste://auth/google"


class HandoffRequest(BaseModel):
    code: str = Field(min_length=32, max_length=200)


class LinkGoogleRequest(HandoffRequest):
    password: str = Field(min_length=6, max_length=200)


class ResendVerificationRequest(BaseModel):
    email: EmailStr


def _google_config() -> tuple[str, str, str]:
    client_id = os.getenv("GOOGLE_CLIENT_ID", "").strip()
    client_secret = os.getenv("GOOGLE_CLIENT_SECRET", "").strip()
    redirect_uri = os.getenv("GOOGLE_REDIRECT_URI", "https://www.zerowaste-qro.com/api/auth/google/callback").strip()
    if not client_id or not client_secret:
        raise HTTPException(status_code=503, detail="El acceso con Google todavía no está configurado.")
    if not redirect_uri.startswith("https://"):
        raise HTTPException(status_code=503, detail="La redirección de Google debe usar HTTPS.")
    return client_id, client_secret, redirect_uri


def _utc(value: datetime) -> datetime:
    return value.replace(tzinfo=value.tzinfo or timezone.utc)


def _user_payload(user: Usuario) -> dict:
    return {"id": user.id, "nombre": user.nombre, "email": user.email, "rol": user.rol or ("admin" if user.is_admin else "usuario"), "is_admin": bool(user.is_admin), "foto_perfil": user.foto_perfil, "avatar_url": build_public_avatar_url(user.foto_perfil), "profile_completed": bool(user.profile_completed)}


@router.post("/google/start")
def google_start(db: Session = Depends(get_db)):
    client_id, _client_secret, redirect_uri = _google_config()
    state = secrets.token_urlsafe(32)
    nonce = secrets.token_urlsafe(32)
    verifier = secrets.token_urlsafe(64)
    challenge = base64.urlsafe_b64encode(hashlib.sha256(verifier.encode()).digest()).rstrip(b"=").decode()
    db.add(OauthLoginState(
        state_hash=digest(state), verifier_ciphertext=encrypt(verifier), nonce_hash=digest(nonce),
        status="pending", expires_at=datetime.now(timezone.utc) + timedelta(minutes=10),
    ))
    db.commit()
    params = {"client_id": client_id, "redirect_uri": redirect_uri, "response_type": "code", "scope": "openid email profile", "state": state, "nonce": nonce, "code_challenge": challenge, "code_challenge_method": "S256", "prompt": "select_account"}
    return {"authorization_url": GOOGLE_AUTH_URL + "?" + urlencode(params), "redirect_uri": redirect_uri}


def _exchange_code(code: str, verifier: str) -> dict:
    client_id, client_secret, redirect_uri = _google_config()
    body = urlencode({"code": code, "client_id": client_id, "client_secret": client_secret, "redirect_uri": redirect_uri, "grant_type": "authorization_code", "code_verifier": verifier}).encode()
    request = UrlRequest(GOOGLE_TOKEN_URL, data=body, method="POST", headers={"Content-Type": "application/x-www-form-urlencoded"})
    try:
        with urlopen(request, timeout=12) as response:
            return json.loads(response.read().decode())
    except Exception as error:
        raise HTTPException(status_code=401, detail="Google no pudo validar la autorización.") from error


def _verified_claims(raw_id_token: str, expected_nonce_hash: str) -> dict:
    from google.auth.transport.requests import Request as GoogleRequest
    from google.oauth2 import id_token

    client_id, _secret, _redirect = _google_config()
    try:
        claims = id_token.verify_oauth2_token(raw_id_token, GoogleRequest(), client_id)
    except Exception as error:
        raise HTTPException(status_code=401, detail="El token de Google no es válido.") from error
    if claims.get("iss") not in {"accounts.google.com", "https://accounts.google.com"}:
        raise HTTPException(status_code=401, detail="El emisor del token de Google no es válido.")
    if claims.get("aud") != client_id or not claims.get("email_verified"):
        raise HTTPException(status_code=401, detail="Google no confirmó este correo.")
    if not claims.get("nonce") or not secrets.compare_digest(digest(str(claims["nonce"])), expected_nonce_hash):
        raise HTTPException(status_code=401, detail="La sesión de Google no es válida.")
    if not claims.get("sub") or not claims.get("email"):
        raise HTTPException(status_code=401, detail="Google no devolvió una identidad válida.")
    return claims


@router.get("/google/callback", include_in_schema=False)
def google_callback(code: str = Query(default=""), state: str = Query(default=""), error: str = Query(default=""), db: Session = Depends(get_db)):
    if error or not code or not state:
        return RedirectResponse(MOBILE_CALLBACK + "?error=cancelled", status_code=302)
    login_state = db.query(OauthLoginState).filter_by(state_hash=digest(state)).with_for_update().first()
    now = datetime.now(timezone.utc)
    if not login_state or login_state.status != "pending" or _utc(login_state.expires_at) <= now:
        return RedirectResponse(MOBILE_CALLBACK + "?error=invalid_state", status_code=302)
    token_data = _exchange_code(code, decrypt(login_state.verifier_ciphertext))
    claims = _verified_claims(str(token_data.get("id_token") or ""), login_state.nonce_hash)
    provider_sub = str(claims["sub"])
    email = str(claims["email"]).strip().lower()
    account = db.query(OauthAccount).filter_by(provider="google", provider_subject=provider_sub).first()
    user = db.query(Usuario).filter_by(id=account.usuario_id).first() if account else None
    if account:
        account.last_login_at = now
    elif (existing := db.query(Usuario).filter(Usuario.email == email).first()):
        login_state.claims_ciphertext = encrypt(json.dumps({"sub": provider_sub, "email": email, "name": claims.get("name"), "picture": claims.get("picture")}))
        login_state.usuario_id = existing.id
        login_state.status = "link_required"
    else:
        user = Usuario(nombre=str(claims.get("name") or email.split("@", 1)[0])[:100], email=email, password=hash_password(secrets.token_urlsafe(48)), foto_perfil="perfil_default.png", auth_provider="google", profile_completed=False, rol="usuario", is_admin=False, email_verified_at=now)
        db.add(user)
        db.flush()
        db.add(OauthAccount(usuario_id=user.id, provider="google", provider_subject=provider_sub, provider_email=email, linked_at=now, last_login_at=now))
        login_state.usuario_id = user.id
        login_state.status = "callback_complete"
    handoff = secrets.token_urlsafe(40)
    login_state.handoff_hash = digest(handoff)
    login_state.expires_at = now + timedelta(minutes=3)
    if login_state.status == "pending":
        login_state.status = "callback_complete"
    db.commit()
    return RedirectResponse(MOBILE_CALLBACK + "?" + urlencode({"code": handoff}), status_code=302)


def _handoff(db: Session, code: str) -> OauthLoginState:
    row = db.query(OauthLoginState).filter_by(handoff_hash=digest(code)).with_for_update().first()
    if not row or row.used_at is not None or _utc(row.expires_at) <= datetime.now(timezone.utc):
        raise HTTPException(status_code=401, detail="La autorización de Google venció o ya fue utilizada.")
    return row


@router.post("/google/complete")
def google_complete(payload: HandoffRequest, db: Session = Depends(get_db)):
    row = _handoff(db, payload.code)
    if row.status == "link_required":
        return {"success": False, "link_required": True, "detail": "Confirma la contraseña de tu cuenta ZeroWaste para enlazar Google."}
    if row.status != "callback_complete" or not row.usuario_id:
        raise HTTPException(status_code=401, detail="La autorización de Google no está completa.")
    user = db.query(Usuario).filter_by(id=row.usuario_id).first()
    if not user or user.bloqueado:
        raise HTTPException(status_code=403, detail="La cuenta no está disponible.")
    row.used_at = datetime.now(timezone.utc)
    row.status = "used"
    db.commit()
    return {"success": True, "access_token": create_access_token({"sub": user.email}), "user": _user_payload(user)}


@router.post("/google/link")
def google_link(payload: LinkGoogleRequest, db: Session = Depends(get_db)):
    row = _handoff(db, payload.code)
    if row.status != "link_required" or not row.usuario_id or not row.claims_ciphertext:
        raise HTTPException(status_code=409, detail="Esta cuenta no requiere enlace.")
    user = db.query(Usuario).filter_by(id=row.usuario_id).with_for_update().first()
    if not user or not verify_password(payload.password, str(user.password)):
        raise HTTPException(status_code=401, detail="La contraseña de ZeroWaste no es correcta.")
    claims = json.loads(decrypt(row.claims_ciphertext))
    if str(user.email).lower() != str(claims["email"]).lower():
        raise HTTPException(status_code=409, detail="El correo de Google no corresponde a esta cuenta.")
    now = datetime.now(timezone.utc)
    db.add(OauthAccount(usuario_id=user.id, provider="google", provider_subject=claims["sub"], provider_email=claims["email"], linked_at=now, last_login_at=now))
    user.email_verified_at = user.email_verified_at or now
    user.auth_provider = "google+local"
    row.used_at = now
    row.status = "used"
    db.commit()
    return {"success": True, "access_token": create_access_token({"sub": user.email}), "user": _user_payload(user)}


def _verification_page(title: str, message: str, success: bool) -> HTMLResponse:
    color = "#047857" if success else "#B45309"
    body = f"<!doctype html><html lang='es'><meta name='viewport' content='width=device-width'><body style='font-family:Arial;background:#f1f5f4;padding:32px'><main style='max-width:560px;margin:auto;background:white;padding:36px;border-radius:20px'><h1 style='color:{color}'>{title}</h1><p>{message}</p><a href='zerowaste://auth/verified' style='display:inline-block;background:#047857;color:white;padding:14px 20px;border-radius:12px;text-decoration:none;font-weight:bold'>Abrir ZeroWaste</a></main></body></html>"
    return HTMLResponse(body)


@router.get("/email/verificar", response_class=HTMLResponse, include_in_schema=False)
def verify_email(token: str = Query(min_length=32, max_length=200), db: Session = Depends(get_db)):
    record = db.query(EmailVerificationToken).filter_by(token_hash=digest(token)).with_for_update().first()
    now = datetime.now(timezone.utc)
    if not record or record.used_at is not None or record.revoked_at is not None:
        return _verification_page("Enlace no válido", "Este enlace ya fue utilizado o fue revocado.", False)
    if _utc(record.expires_at) <= now:
        return _verification_page("Este enlace de verificación venció.", "Abre la aplicación y selecciona Enviar un nuevo correo.", False)
    user = db.query(Usuario).filter_by(id=record.usuario_id).with_for_update().first()
    user.email_verified_at = now
    record.used_at = now
    db.commit()
    return _verification_page("Correo verificado", "Tu correo quedó verificado correctamente.", True)


@router.post("/email/reenviar")
def resend_email(payload: ResendVerificationRequest, db: Session = Depends(get_db)):
    user = db.query(Usuario).filter(Usuario.email == str(payload.email).lower()).first()
    if not user or user.email_verified_at:
        return {"success": True, "message": "Si la cuenta requiere verificación, enviaremos un correo."}
    try:
        result = send_verification(db, user)
    except EmailDeliveryError as error:
        raise HTTPException(status_code=503, detail=str(error)) from error
    if result.get("code") == "RATE_LIMITED":
        raise HTTPException(status_code=429, detail=result["detail"], headers={"Retry-After": "60"})
    if not result.get("sent"):
        raise HTTPException(status_code=503, detail=result.get("detail"))
    return {"success": True, "message": "Si la cuenta requiere verificación, enviaremos un correo."}
