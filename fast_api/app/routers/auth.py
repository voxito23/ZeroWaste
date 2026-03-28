"""
Router de autenticación — login y registro con JWT.
"""

from fastapi import APIRouter, Depends, HTTPException, status
from fastapi.security import OAuth2PasswordRequestForm
from sqlalchemy.orm import Session

from app.data.database import get_db
from app.models.domain_models import Usuario
from app.models.schemas import Token, UsuarioCreate, UsuarioResponse, MessageResponse
from app.security.jwt_auth import verify_password, hash_password, create_access_token

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

    access_token = create_access_token(data={"sub": usuario.email})
    return Token(access_token=access_token)


@router.post(
    "/registro",
    response_model=UsuarioResponse,
    status_code=status.HTTP_201_CREATED,
    summary="Registrar un nuevo usuario",
)
def registro(usuario_in: UsuarioCreate, db: Session = Depends(get_db)):
    """
    Crea un usuario nuevo con contraseña hasheada.
    Devuelve los datos del usuario creado. Requiere inicio de sesión posterior.
    """
    # Validación de datos de entrada
    if len(usuario_in.nombre.strip()) <= 10:
        raise HTTPException(
            status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
            detail="El nombre completo debe tener más de 10 caracteres.",
        )

    if len(usuario_in.password) < 6:
        raise HTTPException(
            status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
            detail="La contraseña debe tener al menos 6 caracteres.",
        )

    existe = db.query(Usuario).filter(Usuario.email == usuario_in.email).first()
    if existe:
        raise HTTPException(
            status_code=status.HTTP_409_CONFLICT,
            detail="El correo electrónico ya está registrado.",
        )

    nuevo_usuario = Usuario(
        nombre=usuario_in.nombre,
        email=usuario_in.email,
        password=hash_password(usuario_in.password),
        foto_perfil=usuario_in.foto_perfil or "perfil_default.png",
    )
    db.add(nuevo_usuario)
    db.commit()
    db.refresh(nuevo_usuario)

    return nuevo_usuario
