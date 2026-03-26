"""
Punto de entrada de la API FastAPI — ZeroWaste API Completa.
Incluye TODOS los routers del proyecto.
"""

from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware

from app.routers import auth, usuarios, foro, mapa, eventos

app = FastAPI(
    title="ZeroWaste API Completa",
    description=(
        "Microservicio FastAPI que expone TODOS los endpoints del proyecto ZeroWaste. "
        "Comparte la base de datos PostgreSQL con Flask y Laravel."
    ),
    version="2.0.0",
)

# ── CORS — permitir todos los orígenes en desarrollo ─────────────────────────
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# ── Registrar TODOS los routers ─────────────────────────────────────────────
app.include_router(auth.router)
app.include_router(usuarios.router)
app.include_router(foro.router)
app.include_router(mapa.router)
app.include_router(eventos.router)


@app.get("/", tags=["Health"])
def health_check():
    return {"status": "ok", "service": "ZeroWaste FastAPI", "version": "2.0.0"}
