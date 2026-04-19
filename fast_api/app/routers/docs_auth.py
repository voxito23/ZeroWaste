from fastapi import APIRouter, Depends, HTTPException, Request, Response, status
from fastapi.responses import HTMLResponse, RedirectResponse
from fastapi.templating import Jinja2Templates
from fastapi.security import OAuth2PasswordRequestForm
from fastapi.openapi.docs import get_swagger_ui_html, get_redoc_html
import os

from app.data.database import get_db
from sqlalchemy.orm import Session
from app.models.domain_models import Usuario
from app.security.jwt_auth import verify_password
from app.security.jwt_auth import create_access_token

router = APIRouter(tags=["Docs Auth"])

# Se asume que el template existe en app/templates/
# Si Jinja2Templates no está instalado, fallará, pero en FastAPI suele estar junto con python-multipart.
# Se usará lectura de archivo directa si no hay templates.
templates_dir = os.path.join(os.path.dirname(__file__), "..", "templates")
try:
    templates = Jinja2Templates(directory=templates_dir)
    USE_TEMPLATES = True
except ImportError:
    USE_TEMPLATES = False


@router.get("/docs/login", response_class=HTMLResponse, include_in_schema=False)
async def login_for_docs(request: Request):
    """Página de inicio de sesión hermosamente diseñada para desarrolladores."""
    if USE_TEMPLATES:
        return templates.TemplateResponse("docs_login.html", {"request": request})
    
    # Fallback si no hay jinja2 instalado
    with open(os.path.join(templates_dir, "docs_login.html"), "r", encoding="utf-8") as f:
        html_content = f.read()
    return HTMLResponse(content=html_content)


@router.post("/docs/auth", include_in_schema=False)
async def authenticate_for_docs(
    response: Response,
    form_data: OAuth2PasswordRequestForm = Depends(),
    db: Session = Depends(get_db)
):
    """Verifica si el usuario es el administrador principal autorizado para ver API."""
    if form_data.username != "vichdz@gmail.com":
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="No tienes permisos de administrador principal."
        )

    user = db.query(Usuario).filter(Usuario.email == form_data.username).first()
    if not user or not verify_password(form_data.password, user.password):
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Credenciales incorrectas."
        )

    # Crear token válido para acceso a los docs
    access_token = create_access_token(data={"sub": user.email})
    
    # Crear respuesta y setear cookie
    cookie_response = dict(message="Login exitoso")
    
    # Seteamos cookie httponly para la seguridad de Docs
    response.set_cookie(
        key="docs_access_token",
        value=f"Bearer {access_token}",
        httponly=True,
        max_age=1800,
        expires=1800,
        samesite="lax"
    )
    return cookie_response


async def get_current_user_from_cookie(request: Request):
    token = request.cookies.get("docs_access_token")
    if not token:
        raise HTTPException(status_code=status.HTTP_303_SEE_OTHER, headers={"Location": "/docs/login"})
    return token


@router.get("/docs", include_in_schema=False)
async def custom_swagger_ui_html(request: Request, _=Depends(get_current_user_from_cookie)):
    """Swagger UI Protegido."""
    return get_swagger_ui_html(
        openapi_url="/openapi.json",
        title="ZeroWaste API - Swagger UI",
        oauth2_redirect_url="/docs/oauth2-redirect",
        swagger_js_url="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui-bundle.js",
        swagger_css_url="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui.css",
        swagger_ui_parameters={"defaultModelsExpandDepth": -1, "syntaxHighlight.theme": "monokai"}
    )


@router.get("/redoc_auth", include_in_schema=False)
async def custom_redoc_html(request: Request, _=Depends(get_current_user_from_cookie)):
    """ReDoc Protegido."""
    return get_redoc_html(
        openapi_url="/openapi.json",
        title="ZeroWaste API - ReDoc",
        redoc_js_url="https://cdn.jsdelivr.net/npm/redoc@next/bundles/redoc.standalone.js",
    )
