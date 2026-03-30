"""
Router de autenticación — login y registro con JWT.
"""

import os
import uuid
from fastapi import APIRouter, Depends, HTTPException, status, File, UploadFile, Form
from fastapi.security import OAuth2PasswordRequestForm
from sqlalchemy.orm import Session

from app.data.database import get_db
from app.models.domain_models import Usuario
from app.models.schemas import Token, UsuarioResponse, MessageResponse
from app.security.jwt_auth import verify_password, hash_password, create_access_token

UPLOAD_DIR = "/app/static/img/perfiles"
os.makedirs(UPLOAD_DIR, exist_ok=True)

router = APIRouter(prefix="/auth", tags=["Autenticación"])


@router.post("/login", response_model=Token, summary="Iniciar sesión y obtener JWT")
def login(
    form_data: OAuth2PasswordRequestForm = Depends(),
    db: Session = Depends(get_db),
):
    """
    Recibe **username** (email) y **password** desde el formulario OAuth2 de Swagger
    y devuelve un `access_token` JWT si las credenciales son válidas.
    """
    usuario = db.query(Usuario).filter(Usuario.email == form_data.username).first()

    if not usuario or not verify_password(form_data.password, usuario.password):
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Correo o contraseña incorrectos.",
            headers={"WWW-Authenticate": "Bearer"},
        )

    # --- Validación de rol inyectada ---
    if not usuario.is_admin:
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="Acceso denegado: Solo los administradores pueden generar tokens e iniciar sesión en esta API."
        )

    access_token = create_access_token(data={"sub": usuario.email})
    return Token(access_token=access_token)


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
    if len(nombre.strip()) <= 10:
        raise HTTPException(
            status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
            detail="El nombre completo debe tener más de 10 caracteres.",
        )

    if len(password) < 6:
        raise HTTPException(
            status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
            detail="La contraseña debe tener al menos 6 caracteres.",
        )

    # B) Guardado de Imagen en la Nueva Ruta
    extension = foto_perfil.filename.split(".")[-1] if "." in foto_perfil.filename else "png"
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
    
    # db.refresh devuelve la instancia sincronizada y mapeada desde la DB nativa
    db.refresh(nuevo_usuario)

    return nuevo_usuario
