"""
Seguridad JWT — generación, verificación de tokens y dependencia get_current_user.
Compatible con hashes werkzeug (pbkdf2:sha256) de Flask y bcrypt ($2b$/$2y$) de Laravel.
"""

import os
from datetime import datetime, timedelta
from typing import Optional

import bcrypt
from fastapi import Depends, HTTPException, status
from fastapi.security import OAuth2PasswordBearer
from jose import JWTError, jwt
from werkzeug.security import check_password_hash, generate_password_hash
from sqlalchemy.orm import Session

from app.data.database import get_db
from app.models.domain_models import Usuario
from app.models.schemas import TokenData

# Configuración de JWT
SECRET_KEY = os.getenv("JWT_SECRET_KEY", "zerowaste_super_secret_jwt_2026")
ALGORITHM = "HS256"
ACCESS_TOKEN_EXPIRE_MINUTES = int(os.getenv("JWT_EXPIRE_MINUTES", "60"))

# Esquema OAuth2 para autenticación en Swagger UI
oauth2_scheme = OAuth2PasswordBearer(tokenUrl="/auth/login")


# Funciones auxiliares de autenticación

def hash_password(password: str) -> str:
    """Genera hash de contraseña con pbkdf2:sha256, compatible con Flask."""
    return generate_password_hash(password, method='pbkdf2:sha256', salt_length=8)


def _verify_bcrypt(plain_password: str, hashed_password: str) -> bool:
    """Verifica contraseña contra hash bcrypt ($2b$ / $2y$) generado por Laravel."""
    # Laravel usa $2y$, bcrypt de Python espera $2b$; son compatibles.
    normalized = hashed_password.replace("$2y$", "$2b$", 1)
    return bcrypt.checkpw(
        plain_password.encode("utf-8"),
        normalized.encode("utf-8"),
    )


def verify_password(plain_password: str, hashed_password: str) -> bool:
    """
    Verifica la contraseña contra formatos de hash seguros:
      1. bcrypt ($2b$ / $2y$) — hashes de Laravel.
      2. pbkdf2:sha256 — hashes de werkzeug / Flask.
    NOTA: NO se acepta comparación en texto plano (eliminado por seguridad).
    """
    # 1. Hash bcrypt (Laravel)
    if hashed_password.startswith(("$2b$", "$2y$", "$2a$")):
        try:
            return _verify_bcrypt(plain_password, hashed_password)
        except Exception:
            return False

    # 2. Hash werkzeug / pbkdf2 (Flask)
    if hashed_password.startswith("pbkdf2:"):
        try:
            return check_password_hash(hashed_password, plain_password)
        except Exception:
            return False

    # Cualquier otro formato se rechaza por seguridad
    return False


def create_access_token(data: dict, expires_delta: Optional[timedelta] = None) -> str:
    to_encode = data.copy()
    expire = datetime.utcnow() + (expires_delta or timedelta(minutes=ACCESS_TOKEN_EXPIRE_MINUTES))
    to_encode.update({"exp": expire})
    return jwt.encode(to_encode, SECRET_KEY, algorithm=ALGORITHM)


def get_current_user(
    token: str = Depends(oauth2_scheme),
    db: Session = Depends(get_db),
) -> Usuario:
    """Dependencia de FastAPI: extrae y valida el JWT, devuelve el usuario."""
    credentials_exception = HTTPException(
        status_code=status.HTTP_401_UNAUTHORIZED,
        detail="Token inválido o expirado.",
        headers={"WWW-Authenticate": "Bearer"},
    )

    try:
        payload = jwt.decode(token, SECRET_KEY, algorithms=[ALGORITHM])
        email: str = payload.get("sub")
        if email is None:
            raise credentials_exception
        token_data = TokenData(email=email)
    except JWTError:
        raise credentials_exception

    user = db.query(Usuario).filter(Usuario.email == token_data.email).first()
    if user is None:
        raise credentials_exception

    return user


def get_current_admin_user(
    current_user: Usuario = Depends(get_current_user),
) -> Usuario:
    """
    Dependencia de FastAPI: verifica que el usuario autenticado
    sea administrador (is_admin == True). Lanza HTTP 403 en caso contrario.
    """
    if not current_user.is_admin:
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="Acceso denegado: Esta acción es exclusiva para administradores de ZeroWaste",
        )
    return current_user
