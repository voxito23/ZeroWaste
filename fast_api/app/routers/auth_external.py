"""Google mobile OAuth and one-time email verification owned by FastAPI."""

import base64
import hashlib
import html
import json
import os
import secrets
from datetime import datetime, timedelta, timezone
from urllib.parse import urlencode
from urllib.request import Request as UrlRequest, urlopen

from fastapi import APIRouter, Depends, HTTPException, Query, Request
from fastapi.responses import HTMLResponse
from pydantic import BaseModel, EmailStr, Field
from sqlalchemy.orm import Session

from app.data.database import get_db
from app.models.domain_models import EmailVerificationToken, OauthAccount, OauthLoginState, Usuario
from app.security.jwt_auth import create_access_token, hash_password, verify_password
from app.security.login_throttle import get_client_ip, get_login_throttle
from app.services.auth_crypto import decrypt, digest, encrypt
from app.services.media import build_public_avatar_url
from app.services.transactional_email import EmailDeliveryError, send_verification, verification_otp

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


class VerifyEmailOtpRequest(BaseModel):
    email: EmailStr
    code: str = Field(pattern=r"^\d{6}$")


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


def _mobile_callback_page(params: dict[str, str]) -> HTMLResponse:
    deep_link = MOBILE_CALLBACK + "?" + urlencode(params)
    safe_link = html.escape(deep_link, quote=True)
    script_link = json.dumps(deep_link)
    return HTMLResponse(
        f"""<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title>Volver a ZeroWaste</title><style>body{{margin:0;background:#ecfdf5;color:#0f172a;font-family:system-ui,sans-serif;display:grid;min-height:100vh;place-items:center}}main{{max-width:420px;margin:24px;padding:32px;border:1px solid #a7f3d0;border-radius:28px;background:#fff;box-shadow:0 20px 50px #064e3b20;text-align:center}}h1{{color:#064e3b}}a{{display:block;margin-top:24px;padding:15px;border-radius:16px;background:#047857;color:#fff;font-weight:800;text-decoration:none}}p{{line-height:1.55;color:#475569}}</style></head><body><main><h1>Autorización completada</h1><p>Regresa a ZeroWaste para terminar tu inicio de sesión de forma segura.</p><a href="{safe_link}">Volver a ZeroWaste</a><p>Si la aplicación no se abre automáticamente, toca el botón.</p></main><script>window.setTimeout(function(){{window.location.href={script_link};}},150);</script></body></html>""",
        headers={
            "Cache-Control": "no-store",
            "Referrer-Policy": "no-referrer",
            "X-Content-Type-Options": "nosniff",
            "Content-Security-Policy": "default-src 'none'; style-src 'unsafe-inline'; script-src 'unsafe-inline'; base-uri 'none'; frame-ancestors 'none'",
        },
    )


@router.get("/google/callback", include_in_schema=False)
def google_callback(code: str = Query(default=""), state: str = Query(default=""), error: str = Query(default=""), db: Session = Depends(get_db)):
    if error or not code or not state:
        return _mobile_callback_page({"error": "cancelled"})
    login_state = db.query(OauthLoginState).filter_by(state_hash=digest(state)).with_for_update().first()
    now = datetime.now(timezone.utc)
    if not login_state or login_state.status != "pending" or _utc(login_state.expires_at) <= now:
        return _mobile_callback_page({"error": "invalid_state"})
    token_data = _exchange_code(code, decrypt(login_state.verifier_ciphertext))
    claims = _verified_claims(str(token_data.get("id_token") or ""), login_state.nonce_hash)
    provider_sub = str(claims["sub"])
    email = str(claims["email"]).strip().lower()
    account = db.query(OauthAccount).filter_by(provider="google", provider_subject=provider_sub).first()
    user = db.query(Usuario).filter_by(id=account.usuario_id).first() if account else None
    if account:
        account.last_login_at = now
        account.provider_email = email
        login_state.usuario_id = account.usuario_id
        login_state.status = "callback_complete"
    elif (existing := db.query(Usuario).filter(Usuario.email == email).first()):
        if existing.firebase_uid and secrets.compare_digest(str(existing.firebase_uid), provider_sub):
            db.add(OauthAccount(usuario_id=existing.id, provider="google", provider_subject=provider_sub, provider_email=email, linked_at=now, last_login_at=now))
            existing.email_verified_at = existing.email_verified_at or now
            existing.auth_provider = "google" if existing.auth_provider == "local" else existing.auth_provider
            login_state.usuario_id = existing.id
            login_state.status = "callback_complete"
        else:
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
    return _mobile_callback_page({"code": handoff})


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
def google_link(payload: LinkGoogleRequest, request: Request, db: Session = Depends(get_db)):
    row = _handoff(db, payload.code)
    if row.status != "link_required" or not row.usuario_id or not row.claims_ciphertext:
        raise HTTPException(status_code=409, detail="Esta cuenta no requiere enlace.")
    user = db.query(Usuario).filter_by(id=row.usuario_id).with_for_update().first()
    if not user:
        raise HTTPException(status_code=401, detail="La contraseña de ZeroWaste no es correcta.")
    throttle = get_login_throttle()
    client_ip = get_client_ip(request)
    throttle.assert_allowed(user.email, client_ip)
    if not verify_password(payload.password, str(user.password)):
        throttle.record_failure(user.email, client_ip)
        raise HTTPException(status_code=401, detail="La contraseña de ZeroWaste no es correcta.")
    throttle.clear(user.email, client_ip)
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


@router.post("/email/verificar-otp")
def verify_email_otp(payload: VerifyEmailOtpRequest, request: Request, db: Session = Depends(get_db)):
    email = str(payload.email).strip().lower()
    throttle = get_login_throttle()
    client_ip = get_client_ip(request)
    throttle_id = f"email-otp:{email}"
    throttle_ip = f"email-otp:{client_ip}"
    throttle.assert_allowed(throttle_id, throttle_ip)
    user = db.query(Usuario).filter(Usuario.email == email).first()
    if not user:
        throttle.record_failure(throttle_id, throttle_ip)
        raise HTTPException(status_code=422, detail="El código de verificación no es válido.")
    records = (
        db.query(EmailVerificationToken)
        .filter(EmailVerificationToken.usuario_id == user.id)
        .order_by(EmailVerificationToken.created_at.desc())
        .with_for_update()
        .all()
    )
    try:
        matched = next((record for record in records if secrets.compare_digest(verification_otp(record.token_hash), payload.code)), None)
    except EmailDeliveryError as error:
        raise HTTPException(status_code=503, detail="La verificación por código todavía no está configurada.") from error
    if not matched:
        throttle.record_failure(throttle_id, throttle_ip)
        raise HTTPException(status_code=422, detail="El código de verificación no es válido.")
    now = datetime.now(timezone.utc)
    if matched.used_at is not None or matched.revoked_at is not None:
        raise HTTPException(status_code=409, detail="Este código ya fue utilizado o reemplazado.")
    if _utc(matched.expires_at) <= now:
        raise HTTPException(status_code=410, detail="Este código de verificación venció.")
    user.email_verified_at = now
    matched.used_at = now
    for record in records:
        if record.id != matched.id and record.used_at is None and record.revoked_at is None:
            record.revoked_at = now
    throttle.clear(throttle_id, throttle_ip)
    db.commit()
    return {"success": True, "message": "Tu correo fue verificado correctamente."}


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
