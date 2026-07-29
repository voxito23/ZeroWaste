"""
Punto de entrada de la API FastAPI — ZeroWaste API Completa.
Incluye TODOS los routers del proyecto + hardening de seguridad.
Monitoreo con Prometheus + Firewall WAF integrado.
"""

import os
from fastapi import FastAPI, Request
from fastapi.middleware.cors import CORSMiddleware

from fastapi.responses import FileResponse, JSONResponse

from slowapi import Limiter, _rate_limit_exceeded_handler
from slowapi.util import get_remote_address
from slowapi.errors import RateLimitExceeded

from app.routers import auth, usuarios, foro, mapa, eventos, analisis, formularios, docs_auth, campanas, recoleccion, firewall_monitor
from app.security.api_key_auth import ApiKeyMiddleware
from app.security.firewall import FirewallMiddleware

# ==========================================================================
#  Rate Limiter global
# ==========================================================================
limiter = Limiter(key_func=get_remote_address, default_limits=["60/minute"])

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
        {"url": "/api", "description": "Servidor de Producción ZeroWaste (/api)"},
        {"url": "/", "description": "Servidor Local Directo (/)"}
    ],
    root_path="/api",
)

# Registrar rate limiter
app.state.limiter = limiter
app.add_exception_handler(RateLimitExceeded, _rate_limit_exceeded_handler)  # type: ignore

# ==========================================================================
#  Middlewares de seguridad (orden importa: último registrado = primero ejecutado)
# ==========================================================================

# 1. Middleware de API-Key (identificación de sistemas permitidos)
app.add_middleware(ApiKeyMiddleware)

# 2. Middleware de Firewall WAF (rate limiting, detección de ataques, bloqueo de IPs)
app.add_middleware(FirewallMiddleware)

# 3. CORS restringido a orígenes conocidos (NO usar "*" con credenciales)
ALLOWED_ORIGINS = [
    "http://localhost:5001",
    "http://localhost:8001",
    "http://localhost:8081",
    "http://localhost:19006",
    "http://localhost:3000",
    "http://10.0.2.2",
    "http://167.99.239.121:5001",
    "http://167.99.239.121:8001",
    "https://zerowaste-qro.com",
    "https://www.zerowaste-qro.com",
    "http://zerowaste-qro.com",
    "http://www.zerowaste-qro.com",
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


@app.get("/favicon.ico", include_in_schema=False)
async def favicon():
    return FileResponse("static/favicon/faviconZeroWaste.svg", media_type="image/svg+xml")
