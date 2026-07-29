"""
Seguridad API-Key — Identificar sistemas permitidos (V2 / V5 de seguridad).
Permite restringir el acceso a la API exclusivamente a clientes autorizados
(por ejemplo, la App Móvil o el Frontend oficial), requiriendo el header 'X-API-Key'.
"""

import os
from typing import List, Optional
from fastapi import Request, HTTPException, status, Security
from fastapi.security import APIKeyHeader, APIKeyQuery
from fastapi.responses import JSONResponse
from starlette.middleware.base import BaseHTTPMiddleware, RequestResponseEndpoint
from starlette.responses import Response

# Nombre del Header esperado
API_KEY_NAME = "X-API-Key"

# Esquemas para OpenAPI/Swagger en caso de usarse como dependencia
api_key_header_scheme = APIKeyHeader(name=API_KEY_NAME, auto_error=False)
api_key_query_scheme = APIKeyQuery(name="api_key", auto_error=False)

def get_allowed_api_keys() -> List[str]:
    """
    Obtiene la lista de API-Keys autorizadas desde la configuración de entorno (.env).
    Soporta múltiples claves separadas por coma.
    """
    raw_keys = os.getenv("SYSTEM_API_KEY", "zw_mobile_secret_key_2026")
    return [k.strip() for k in raw_keys.split(",") if k.strip()]


def verify_api_key(api_key: Optional[str]) -> bool:
    """Verifica si la API-Key proporcionada es válida."""
    if not api_key:
        return False
    return api_key in get_allowed_api_keys()


async def require_api_key(
    api_key_header: Optional[str] = Security(api_key_header_scheme),
    api_key_query: Optional[str] = Security(api_key_query_scheme),
) -> str:
    """
    Dependencia de FastAPI para validar la API Key por Header o Query Param.
    Lanza HTTPException 403 si es inválida o ausente.
    """
    api_key = api_key_header or api_key_query
    if not verify_api_key(api_key):
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="Acceso denegado: API-Key de sistema inválida o ausente. Debe incluir el header 'X-API-Key'.",
        )
    return api_key


class ApiKeyMiddleware(BaseHTTPMiddleware):
    """
    Middleware global de FastAPI para exigir API-Key en todos los endpoints,
    salvo rutas públicas esenciales (salud, CORS preflight options, favicon).
    """
    EXEMPT_PATHS = {
        "/",
        "/api",
        "/api/",
        "/favicon.ico",
        "/docs",
        "/redoc",
        "/openapi.json",
    }

    async def dispatch(self, request: Request, call_next: RequestResponseEndpoint) -> Response:
        # 1. Nunca bloquear peticiones preflight CORS (OPTIONS)
        if request.method == "OPTIONS":
            return await call_next(request)

        # 2. Verificar rutas exentas
        path = request.url.path.rstrip("/")
        if path in self.EXEMPT_PATHS or request.url.path in self.EXEMPT_PATHS:
            return await call_next(request)

        # 3. Leer header X-API-Key
        api_key = request.headers.get(API_KEY_NAME) or request.headers.get(API_KEY_NAME.lower())
        if not verify_api_key(api_key):
            return JSONResponse(
                status_code=status.HTTP_403_FORBIDDEN,
                content={
                    "detail": (
                        "Acceso denegado: API-Key de sistema inválida o ausente. "
                        f"Debe incluir el header '{API_KEY_NAME}' configurado en su cliente."
                    )
                },
            )

        # 4. Continuar con la solicitud si la API-Key es válida
        return await call_next(request)
