"""
Paquete de seguridad — exporta las utilidades de autenticación JWT.
"""

from .jwt_auth import (
    hash_password,
    verify_password,
    create_access_token,
    get_current_user,
    oauth2_scheme,
    SECRET_KEY,
    ALGORITHM,
    ACCESS_TOKEN_EXPIRE_MINUTES,
)
from .api_key_auth import (
    verify_api_key,
    require_api_key,
    ApiKeyMiddleware,
    get_allowed_api_keys,
)

__all__ = [
    "hash_password",
    "verify_password",
    "create_access_token",
    "get_current_user",
    "oauth2_scheme",
    "SECRET_KEY",
    "ALGORITHM",
    "ACCESS_TOKEN_EXPIRE_MINUTES",
    "verify_api_key",
    "require_api_key",
    "ApiKeyMiddleware",
    "get_allowed_api_keys",
]

