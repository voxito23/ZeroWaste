"""
Punto de entrada de la API FastAPI — ZeroWaste API Completa.
Incluye TODOS los routers del proyecto + hardening de seguridad.
Monitoreo con Prometheus + Firewall WAF integrado.
"""

import logging
import os
from fastapi import FastAPI, Request, HTTPException
from fastapi.middleware.cors import CORSMiddleware

from fastapi.responses import FileResponse, JSONResponse

from slowapi import Limiter, _rate_limit_exceeded_handler
from slowapi.util import get_remote_address
from slowapi.errors import RateLimitExceeded
from sqlalchemy import text
from sqlalchemy.exc import SQLAlchemyError

from app.routers import auth, usuarios, foro, mapa, eventos, analisis, formularios, docs_auth, campanas, recoleccion, firewall_monitor
from app.security.firewall import FirewallMiddleware
from app.data.database import engine
from app.observability import READINESS

logger = logging.getLogger("zerowaste.api")

# ==========================================================================
#  Rate Limiter global
# ==========================================================================
REDIS_URL = os.getenv("REDIS_URL")
if not REDIS_URL:
    raise RuntimeError("Required environment variable is not configured: REDIS_URL")
limiter = Limiter(
    key_func=get_remote_address,
    default_limits=["60/minute"],
    storage_uri=REDIS_URL,
)

# ==========================================================================
#  Inicialización de la app
# ==========================================================================

app = FastAPI(
    title="ZeroWaste API Completa",
    description=(
        "Microservicio FastAPI que expone TODOS los endpoints del proyecto ZeroWaste. "
        "Comparte la base de datos PostgreSQL con Flask y Laravel. "
        "Protegido con JWT, API-Key, Firewall WAF, y monitoreo Prometheus."
    ),
    version="2.1.0",
    redoc_url=None,
    docs_url=None,
    openapi_url=None,
    servers=[
        {"url": "/api", "description": "Servidor de Producción ZeroWaste (/api)"}
    ],
    root_path="/api",
)

# Registrar rate limiter
app.state.limiter = limiter
app.add_exception_handler(RateLimitExceeded, _rate_limit_exceeded_handler)  # type: ignore


@app.exception_handler(HTTPException)
async def structured_http_exception(_request: Request, exc: HTTPException):
    headers = exc.headers or {}
    if exc.status_code == 429 and "Retry-After" in headers:
        retry_after = max(int(headers["Retry-After"]), 1)
        return JSONResponse(
            status_code=429,
            headers={"Retry-After": str(retry_after)},
            content={"detail": exc.detail, "retry_after": retry_after},
        )
    return JSONResponse(status_code=exc.status_code, headers=headers, content={"detail": exc.detail})

# ==========================================================================
#  Middlewares de seguridad (orden importa: último registrado = primero ejecutado)
# ==========================================================================

# 1. Middleware de API-Key (identificación de sistemas permitidos)

# 2. Middleware de Firewall WAF (rate limiting, detección de ataques, bloqueo de IPs)
app.add_middleware(FirewallMiddleware)

# 3. CORS restringido a orígenes conocidos (NO usar "*" con credenciales)
DEFAULT_ALLOWED_ORIGINS = [
    "https://zerowaste-qro.com",
    "https://www.zerowaste-qro.com",
]
ALLOWED_ORIGINS = [
    origin.strip()
    for origin in os.getenv("ALLOWED_ORIGINS", ",".join(DEFAULT_ALLOWED_ORIGINS)).split(",")
    if origin.strip()
]

app.add_middleware(
    CORSMiddleware,
    allow_origins=ALLOWED_ORIGINS,
    allow_credentials=True,
    allow_methods=["GET", "POST", "PUT", "DELETE", "OPTIONS"],
    allow_headers=["Authorization", "Content-Type", "X-API-Key"],
)

# ==========================================================================
#  Prometheus — Monitoreo de métricas
# ==========================================================================
try:
    from prometheus_fastapi_instrumentator import Instrumentator
    instrumentator = Instrumentator(
        should_group_status_codes=True,
        should_ignore_untemplated=True,
        should_respect_env_var=False,
        excluded_handlers=["/metrics", "/favicon.ico"],
        env_var_name="ENABLE_METRICS",
    )
    instrumentator.instrument(app).expose(app, include_in_schema=False, should_gzip=True)
except ImportError:
    pass  # Si prometheus no está instalado, continúa sin métricas

# ==========================================================================
#  Personalización de OpenAPI para inyectar X-API-Key en Swagger UI
# ==========================================================================
from fastapi.openapi.utils import get_openapi

def custom_openapi():
    if app.openapi_schema:
        return app.openapi_schema
    openapi_schema = get_openapi(
        title=app.title,
        version=app.version,
        description=app.description,
        routes=app.routes,
        servers=[
            {"url": "/api", "description": "Servidor de Producción ZeroWaste (/api)"}
        ],
    )
    # Asegurar que el servidor principal en Swagger UI siempre sea /api
    openapi_schema["servers"] = [
        {"url": "/api", "description": "Servidor de Producción ZeroWaste (/api)"}
    ]
    
    app.openapi_schema = openapi_schema
    return app.openapi_schema

app.openapi = custom_openapi

# ==========================================================================
#  Registro de todos los routers de la aplicación
# ==========================================================================
app.include_router(auth.router)
app.include_router(usuarios.router)
app.include_router(foro.router)
app.include_router(mapa.router)
app.include_router(eventos.router)
app.include_router(campanas.router)
app.include_router(analisis.router)
app.include_router(formularios.router)
app.include_router(recoleccion.router)
app.include_router(firewall_monitor.router)
app.include_router(docs_auth.router)


@app.get("/", tags=["Salud"])
def health_check():
    return {"status": "ok", "service": "ZeroWaste FastAPI", "version": "2.1.0"}


@app.get("/health", tags=["Salud"], include_in_schema=False)
def health():
    """Liveness only: never opens a database connection."""
    return {"status": "ok", "service": "fastapi"}


@app.get("/ready", tags=["Salud"], include_in_schema=False)
def readiness():
    """Read-only dependency check; it never creates or migrates schema."""
    try:
        with engine.connect() as connection:
            connection.execute(text("SELECT 1"))
        READINESS.set(1)
        return {"status": "ready", "service": "fastapi", "database": "ok"}
    except SQLAlchemyError as exc:
        READINESS.set(0)
        logger.error("Database readiness check failed: %s", type(exc).__name__)
        return JSONResponse(
            status_code=503,
            content={"status": "not_ready", "service": "fastapi", "database": "unavailable"},
        )


@app.get("/favicon.ico", include_in_schema=False)
async def favicon():
    return FileResponse("static/favicon/faviconZeroWaste.svg", media_type="image/svg+xml")
