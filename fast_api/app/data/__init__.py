"""
Paquete de datos — exporta los componentes de conexión a la base de datos.
"""

from .database import engine, SessionLocal, Base, get_db

__all__ = ["engine", "SessionLocal", "Base", "get_db"]
