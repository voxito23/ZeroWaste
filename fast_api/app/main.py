"""
Punto de entrada de la API FastAPI — ZeroWaste API Completa.
Incluye TODOS los routers del proyecto.
"""

from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from fastapi.openapi.docs import get_redoc_html
from fastapi.responses import FileResponse

from app.routers import auth, usuarios, foro, mapa, eventos

app = FastAPI(
    title="ZeroWaste API Completa",
    description=(
        "Microservicio FastAPI que expone TODOS los endpoints del proyecto ZeroWaste. "
        "Comparte la base de datos PostgreSQL con Flask y Laravel."
    ),
    version="2.0.0",
    redoc_url=None,  # Se deshabilita la URL automática de ReDoc por incompatibilidad con el CDN
)

# Configuración de CORS para permitir todos los orígenes en desarrollo
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Registro de todos los routers de la aplicación
app.include_router(auth.router)
app.include_router(usuarios.router)
app.include_router(foro.router)
app.include_router(mapa.router)
app.include_router(eventos.router)


@app.get("/", tags=["Salud"])
def health_check():
    return {"status": "ok", "service": "ZeroWaste FastAPI", "version": "2.0.0"}


@app.get("/favicon.ico", include_in_schema=False)
async def favicon():
    return FileResponse("app/data/faviconZeroWaste.svg", media_type="image/svg+xml")


@app.get("/redoc", include_in_schema=False)
async def redoc_html():
    """Documentación ReDoc con URL corregida del CDN."""
    return get_redoc_html(
        openapi_url=app.openapi_url,
        title=app.title + " - ReDoc",
        redoc_js_url="https://cdn.jsdelivr.net/npm/redoc/bundles/redoc.standalone.js",
    )

