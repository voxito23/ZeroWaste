from fastapi import APIRouter, Depends, HTTPException, Request, Response, status
from fastapi.responses import HTMLResponse, RedirectResponse
from fastapi.security import OAuth2PasswordRequestForm
import os

from app.data.database import get_db
from sqlalchemy.orm import Session
from app.models.domain_models import Usuario
from app.security.jwt_auth import verify_password
from app.security.jwt_auth import create_access_token

router = APIRouter(tags=["Docs Auth"])

templates_dir = os.path.join(os.path.dirname(__file__), "..", "templates")

@router.get("/zw-docs/login", response_class=HTMLResponse, include_in_schema=False)
async def login_for_docs(request: Request):
    """Página de inicio de sesión hermosamente diseñada para desarrolladores."""
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

    # El usuario debe ser el administrador principal, definido de forma segura en las variables de entorno
    admin_email = os.getenv("ADMIN_EMAIL")
    
    if not user.is_admin or not admin_email or user.email != admin_email:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="No tienes permisos de administrador principal."
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
        raise HTTPException(status_code=status.HTTP_303_SEE_OTHER, headers={"Location": "/zw-docs/login"})
    return token


@router.get("/zw-docs/logout", include_in_schema=False)
async def logout_for_docs():
    """Cierra sesión eliminando la cookie JWT y redirige a Laravel Admin."""
    response = RedirectResponse(url="/zw-interno/login", status_code=status.HTTP_303_SEE_OTHER)
    response.delete_cookie("docs_access_token")
    return response


@router.get("/zw-docs", response_class=HTMLResponse, include_in_schema=False)
async def custom_swagger_ui_html(request: Request, _=Depends(get_current_user_from_cookie)):
    """Swagger UI Privado y Premium."""
    with open(os.path.join(templates_dir, "custom_swagger.html"), "r", encoding="utf-8") as f:
        html_content = f.read()
    return HTMLResponse(content=html_content)


@router.get("/zw-redoc", response_class=HTMLResponse, include_in_schema=False)
async def custom_redoc_html(request: Request, _=Depends(get_current_user_from_cookie)):
    """ReDoc Privado y Premium."""
    with open(os.path.join(templates_dir, "custom_redoc.html"), "r", encoding="utf-8") as f:
        html_content = f.read()
    return HTMLResponse(content=html_content)


@router.get("/zw-openapi.json", include_in_schema=False)
async def get_openapi_endpoint(request: Request, _=Depends(get_current_user_from_cookie)):
    """OpenAPI JSON schema protegido por autenticación JWT."""
    return request.app.openapi()
