"""
Punto de entrada de la API FastAPI — ZeroWaste API Completa.
Incluye TODOS los routers del proyecto + hardening de seguridad.
"""

import os
from fastapi import FastAPI, Request
from fastapi.middleware.cors import CORSMiddleware

from fastapi.responses import FileResponse, JSONResponse

from slowapi import Limiter, _rate_limit_exceeded_handler
from slowapi.util import get_remote_address
from slowapi.errors import RateLimitExceeded

from app.routers import auth, usuarios, foro, mapa, eventos, analisis, formularios, docs_auth, campanas

# ==========================================================================
#  Rate Limiter global
# ==========================================================================
limiter = Limiter(key_func=get_remote_address, default_limits=["60/minute"])

# ==========================================================================
#  Inicialización de la app
# ==========================================================================
# (IS_PRODUCTION ya no se usa porque bloqueamos docs_url siempre)

app = FastAPI(
    title="ZeroWaste API Completa",
    description=(
        "Microservicio FastAPI que expone TODOS los endpoints del proyecto ZeroWaste. "
        "Comparte la base de datos PostgreSQL con Flask y Laravel."
    ),
    version="2.0.0",
    redoc_url=None,
    docs_url=None,
    root_path="/api",
)

# Registrar rate limiter
app.state.limiter = limiter
app.add_exception_handler(RateLimitExceeded, _rate_limit_exceeded_handler)  # type: ignore

# CORS restringido a orígenes conocidos (NO usar "*" con credenciales)
ALLOWED_ORIGINS = [
    "http://localhost:5001",
    "http://localhost:8001",
    "http://167.99.239.121:5001",
    "http://167.99.239.121:8001",
]

app.add_middleware(
    CORSMiddleware,
    allow_origins=ALLOWED_ORIGINS,
    allow_credentials=True,
    allow_methods=["GET", "POST", "PUT", "DELETE"],
    allow_headers=["Authorization", "Content-Type"],
)

# Registro de todos los routers de la aplicación
app.include_router(auth.router)
app.include_router(usuarios.router)
app.include_router(foro.router)
app.include_router(mapa.router)
app.include_router(eventos.router)
app.include_router(campanas.router)
app.include_router(analisis.router)
app.include_router(formularios.router)
app.include_router(docs_auth.router)


@app.get("/", tags=["Salud"])
def health_check():
    return {"status": "ok", "service": "ZeroWaste FastAPI", "version": "2.0.0"}


@app.get("/favicon.ico", include_in_schema=False)
async def favicon():
    return FileResponse("static/favicon/faviconZeroWaste.svg", media_type="image/svg+xml")

