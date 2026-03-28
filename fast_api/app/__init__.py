"""
Microservicio FastAPI de ZeroWaste

Expone la API REST completa del proyecto ZeroWaste.
Comparte la base de datos PostgreSQL con Flask y Laravel.

Subpaquetes:
    app.data      — Conexión y sesión de base de datos (SQLAlchemy).
    app.models    — Modelos ORM y esquemas Pydantic de validación.
    app.routers   — Endpoints agrupados por dominio.
    app.security  — Autenticación JWT y utilidades de hashing.
"""

__version__ = "2.0.0"
