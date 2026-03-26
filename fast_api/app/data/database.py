"""
Configuración de la base de datos compartida con Flask y Laravel.
Se conecta a la MISMA instancia de PostgreSQL — solo lectura de tablas existentes.
"""

import os
from sqlalchemy import create_engine
from sqlalchemy.orm import sessionmaker, declarative_base

DATABASE_URL = os.getenv(
    "DATABASE_URL",
    "postgresql://postgres:postgrespassword@db:5432/zerowaste_db",
)

engine = create_engine(DATABASE_URL, pool_pre_ping=True)

SessionLocal = sessionmaker(autocommit=False, autoflush=False, bind=engine)

Base = declarative_base()


def get_db():
    """Inyecta una sesión de BD en cada request y la cierra al terminar."""
    db = SessionLocal()
    try:
        yield db
    finally:
        db.close()
