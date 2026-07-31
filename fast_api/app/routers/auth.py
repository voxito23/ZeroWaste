"""
Router de autenticación — login y registro con JWT.
Incluye rate limiting y validación estricta de inputs.
"""

import os
import uuid
from fastapi import APIRouter, Depends, HTTPException, status, File, UploadFile, Form, Request
from fastapi.security import OAuth2PasswordRequestForm
from sqlalchemy.orm import Session

from slowapi import Limiter
from slowapi.util import get_remote_address

from app.data.database import get_db
from app.models.domain_models import Usuario
from app.models.schemas import Token, UsuarioResponse, MessageResponse
from app.security.jwt_auth import verify_password, hash_password, create_access_token
from app.security.login_throttle import INVALID_MESSAGE, get_client_ip, get_login_throttle
from pydantic import BaseModel

class MobileLogin(BaseModel):
    email: str
    password: str

class MobileRegister(BaseModel):
    nombre: str
    email: str
    password: str

# Constantes de seguridad
ALLOWED_IMAGE_EXTENSIONS = {'png', 'jpg', 'jpeg', 'gif', 'webp'}
MAX_UPLOAD_SIZE = 250 * 1024 * 1024  # 250MB

UPLOAD_DIR = "static/img/perfiles"
os.makedirs(UPLOAD_DIR, exist_ok=True)

router = APIRouter(prefix="/auth", tags=["Autenticación"])


@router.post("/login", response_model=Token, summary="Iniciar sesión y obtener JWT")
def login(
    request: Request,
    form_data: OAuth2PasswordRequestForm = Depends(),
    db: Session = Depends(get_db),
):
    """
    Recibe **username** (email) y **password** desde el formulario OAuth2 de Swagger
    y devuelve un `access_token` JWT si las credenciales son válidas.
    """
    throttle = get_login_throttle()
    client_ip = get_client_ip(request)
    throttle.assert_allowed(form_data.username, client_ip)
    usuario = db.query(Usuario).filter(Usuario.email == form_data.username).first()

    if not usuario or not verify_password(form_data.password, str(usuario.password)):
        throttle.record_failure(form_data.username, client_ip)
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail=INVALID_MESSAGE,
            headers={"WWW-Authenticate": "Bearer"},
        )

    throttle.clear(form_data.username, client_ip)
    if usuario.bloqueado:
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="Usuario bloqueado por subir contenido indebido."
        )

    # --- Validación de rol inyectada ---
    if not usuario.is_admin:
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="Acceso denegado: Solo los administradores pueden generar tokens e iniciar sesión en esta API."
        )

    access_token = create_access_token(data={"sub": usuario.email})
    return Token(access_token=access_token)


@router.post("/mobile/login", summary="Login exclusivo para App Móvil (React Native)")
def mobile_login(
    request: Request,
    credentials: MobileLogin,
    db: Session = Depends(get_db),
):
    """
    Recibe JSON con email y password, permite acceso a TODOS los usuarios (no solo admins),
    y devuelve el access_token y los datos del usuario con rol e is_admin.
    """
    throttle = get_login_throttle()
    client_ip = get_client_ip(request)
    throttle.assert_allowed(credentials.email, client_ip)
    usuario = db.query(Usuario).filter(Usuario.email == credentials.email).first()

    if not usuario or not verify_password(credentials.password, str(usuario.password)):
        throttle.record_failure(credentials.email, client_ip)
        raise HTTPException(status_code=401, detail=INVALID_MESSAGE)

    throttle.clear(credentials.email, client_ip)
    if usuario.bloqueado:
        raise HTTPException(status_code=403, detail="Usuario bloqueado.")

    access_token = create_access_token(data={"sub": usuario.email})
    
    return {
        "success": True,
        "access_token": access_token,
        "user": {
            "id": usuario.id,
            "nombre": usuario.nombre,
            "email": usuario.email,
            "rol": usuario.rol or ("admin" if usuario.is_admin else "usuario"),
            "is_admin": usuario.is_admin,
            "foto_perfil": usuario.foto_perfil,
            "profile_completed": usuario.profile_completed
        }
    }


@router.post(
    "/registro",
    response_model=UsuarioResponse,
    status_code=status.HTTP_201_CREATED,
    summary="Registrar un nuevo usuario mediante Form Data y carga de imagen obligatoria",
)
def registro(
    nombre: str = Form(..., example="Juan Pérez"),
    email: str = Form(..., example="correo@ejemplo.com"),
    password: str = Form(..., example="Password123!"),
    foto_perfil: UploadFile = File(...),
    db: Session = Depends(get_db)
):
    """
    Crea un usuario nuevo con contraseña hasheada, exigiendo una carga de archivo físico (multipart/form-data).
    """
    # A) Validación de Correo Único (ANTES de guardar archivos)
    existe = db.query(Usuario).filter(Usuario.email == email).first()
    if existe:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="Error: El correo ingresado ya está registrado en ZeroWaste."
        )

    # Validaciones de longitud
    if len(nombre.strip()) < 2:
        raise HTTPException(
            status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
            detail="El nombre completo debe tener al menos 2 caracteres.",
        )

    if len(password) < 6:
        raise HTTPException(
            status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
            detail="La contraseña debe tener al menos 6 caracteres.",
        )

    # B) Guardado de Imagen en la Nueva Ruta
    # Validar extensión de archivo
    extension = foto_perfil.filename.rsplit(".", 1)[-1].lower() if (foto_perfil.filename and "." in foto_perfil.filename) else ""
    if extension not in ALLOWED_IMAGE_EXTENSIONS:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail=f"Formato de imagen no permitido. Usa: {', '.join(ALLOWED_IMAGE_EXTENSIONS)}",
        )

    # Validar tamaño de archivo
    contents = foto_perfil.file.read()
    if len(contents) > MAX_UPLOAD_SIZE:
        raise HTTPException(
            status_code=status.HTTP_413_REQUEST_ENTITY_TOO_LARGE,
            detail="La imagen no debe superar 250MB.",
        )
    foto_perfil.file.seek(0)

    nombre_archivo_unico = f"{uuid.uuid4().hex}.{extension}"
    ruta_destino = f"{UPLOAD_DIR}/{nombre_archivo_unico}"

    # Asegurar que el directorio padre exista antes de escribir el archivo
    os.makedirs(os.path.dirname(ruta_destino), exist_ok=True)

    try:
        with open(ruta_destino, "wb") as buffer:
            buffer.write(foto_perfil.file.read())
    except Exception:
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail="Ocurrió un error al guardar la imagen de perfil en el servidor."
        )

    # Mapeo explícito de la variable "nombre" junto con el resto de los campos
    nuevo_usuario = Usuario(
        nombre=nombre,
        email=email,
        password=hash_password(password),
        foto_perfil=nombre_archivo_unico,
    )
    
    db.add(nuevo_usuario)
    db.commit()
    
    db.refresh(nuevo_usuario)

    return nuevo_usuario


@router.post("/mobile/registro", summary="Registro exclusivo para App Móvil (React Native)")
def mobile_registro(
    data: MobileRegister,
    db: Session = Depends(get_db)
):
    """
    Registro por JSON que no requiere subir foto obligatoria (usa default).
    """
    existe = db.query(Usuario).filter(Usuario.email == data.email).first()
    if existe:
        raise HTTPException(status_code=400, detail="El correo ya está registrado.")

    if len(data.nombre.strip()) < 2:
        raise HTTPException(status_code=422, detail="El nombre completo debe tener al menos 2 caracteres.")

    if len(data.password) < 6:
        raise HTTPException(status_code=422, detail="La contraseña debe tener al menos 6 caracteres.")

    nuevo_usuario = Usuario(
        nombre=data.nombre,
        email=data.email,
        password=hash_password(data.password),
        foto_perfil="perfil_default.png",
    )
    
    db.add(nuevo_usuario)
    db.commit()
    db.refresh(nuevo_usuario)

    return {"success": True, "message": "Cuenta creada con éxito."}
