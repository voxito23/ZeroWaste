"""
Router de autenticación para documentación API — solo administradores.
Protege /zw-docs, /zw-redoc y /zw-openapi.json con cookie httponly + JWT.
"""

from fastapi import APIRouter, Depends, HTTPException, Request, Response, status
from fastapi.responses import HTMLResponse, RedirectResponse, JSONResponse
from fastapi.security import OAuth2PasswordRequestForm
import os

from app.data.database import get_db
from sqlalchemy.orm import Session
from app.models.domain_models import Usuario
from app.security.jwt_auth import verify_password, create_access_token, SECRET_KEY, ALGORITHM

from jose import JWTError, jwt  # type: ignore

router = APIRouter(tags=["Docs Auth"])

templates_dir = os.path.join(os.path.dirname(__file__), "..", "templates")


def _verify_docs_cookie(request: Request) -> bool:
    """Verifica si la cookie docs_access_token contiene un JWT válido de admin."""
    token_raw = request.cookies.get("docs_access_token")
    if not token_raw:
        return False
    # La cookie se guarda como "Bearer <token>"
    token = token_raw.replace("Bearer ", "", 1) if token_raw.startswith("Bearer ") else token_raw
    try:
        payload = jwt.decode(token, SECRET_KEY, algorithms=[ALGORITHM])
        email = payload.get("sub")
        if not email:
            return False
        return True
    except JWTError:
        return False


@router.get("/zw-docs/login", response_class=HTMLResponse, include_in_schema=False)
async def login_for_docs(request: Request):
    """Página de inicio de sesión para desarrolladores administradores."""
    # Si ya tiene cookie válida, redirigir directamente a docs
    if _verify_docs_cookie(request):
        return RedirectResponse(url="/zw-docs", status_code=status.HTTP_303_SEE_OTHER)
    with open(os.path.join(templates_dir, "docs_login.html"), "r", encoding="utf-8") as f:
        html_content = f.read()
    return HTMLResponse(content=html_content)


@router.post("/zw-docs/auth", include_in_schema=False)
async def authenticate_for_docs(
    response: Response,
    form_data: OAuth2PasswordRequestForm = Depends(),
    db: Session = Depends(get_db)
):
    """Verifica si el usuario es administrador autorizado para ver API."""
    user = db.query(Usuario).filter(Usuario.email == form_data.username).first()
    
    if not user or not verify_password(form_data.password, str(user.password)):
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Credenciales incorrectas."
        )

    if not user.is_admin:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="No tienes permisos de administrador para acceder a la documentación."
        )

    # Crear token válido para acceso a los docs
    access_token = create_access_token(data={"sub": user.email})
    
    # Seteamos cookie httponly para la seguridad de Docs
    response.set_cookie(
        key="docs_access_token",
        value=f"Bearer {access_token}",
        httponly=True,
        max_age=1800,
        expires=1800,
        samesite="lax"
    )
    return {"message": "Login exitoso"}


@router.get("/zw-docs/logout", include_in_schema=False)
async def logout_for_docs():
    """Cierra sesión de docs y redirige al panel Laravel Admin."""
    response = RedirectResponse(url="/zw-interno/dashboard", status_code=status.HTTP_303_SEE_OTHER)
    response.delete_cookie("docs_access_token")
    return response


@router.get("/zw-docs", response_class=HTMLResponse, include_in_schema=False)
async def custom_swagger_ui_html(request: Request):
    """Swagger UI Premium — protegido con cookie de admin."""
    if not _verify_docs_cookie(request):
        return RedirectResponse(url="/zw-docs/login", status_code=status.HTTP_303_SEE_OTHER)
    with open(os.path.join(templates_dir, "custom_swagger.html"), "r", encoding="utf-8") as f:
        html_content = f.read()
    return HTMLResponse(content=html_content)


@router.get("/zw-redoc", response_class=HTMLResponse, include_in_schema=False)
async def custom_redoc_html(request: Request):
    """ReDoc Premium — protegido con cookie de admin."""
    if not _verify_docs_cookie(request):
        return RedirectResponse(url="/zw-docs/login", status_code=status.HTTP_303_SEE_OTHER)
    with open(os.path.join(templates_dir, "custom_redoc.html"), "r", encoding="utf-8") as f:
        html_content = f.read()
    return HTMLResponse(content=html_content)


@router.get("/zw-openapi.json", include_in_schema=False)
async def get_openapi_endpoint(request: Request):
    """OpenAPI JSON schema — protegido con cookie de admin."""
    if not _verify_docs_cookie(request):
        return JSONResponse(
            status_code=status.HTTP_403_FORBIDDEN,
            content={"detail": "Acceso denegado. Inicia sesión en /zw-docs/login"}
        )
    return request.app.openapi()
