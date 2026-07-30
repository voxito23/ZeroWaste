"""
Configuración de la base de datos compartida con Flask y Laravel.
Se conecta a la misma instancia de PostgreSQL mediante SQLAlchemy.
"""

import os
from urllib.parse import urlparse

from sqlalchemy import create_engine
from sqlalchemy.orm import sessionmaker, declarative_base


def require_env(name: str) -> str:
    """Return a required setting without ever logging its value."""
    value = os.getenv(name, "").strip()
    if not value:
        raise RuntimeError(f"Required environment variable is not configured: {name}")
    return value


DATABASE_URL = require_env("DATABASE_URL")
parsed_database_url = urlparse(DATABASE_URL)
if parsed_database_url.scheme not in {"postgresql", "postgresql+psycopg2"}:
    raise RuntimeError("DATABASE_URL must use the PostgreSQL psycopg2 driver")

DB_SSLMODE = os.getenv("DB_SSLMODE", "require")
DB_CONNECT_TIMEOUT = int(os.getenv("DB_CONNECT_TIMEOUT", "10"))
DB_POOL_SIZE = int(os.getenv("DB_POOL_SIZE", "5"))
DB_MAX_OVERFLOW = int(os.getenv("DB_MAX_OVERFLOW", "5"))
DB_POOL_TIMEOUT = int(os.getenv("DB_POOL_TIMEOUT", "10"))
DB_POOL_RECYCLE = int(os.getenv("DB_POOL_RECYCLE", "300"))
DB_STATEMENT_TIMEOUT_MS = os.getenv("DB_STATEMENT_TIMEOUT_MS", "").strip()

connect_args = {
    "sslmode": DB_SSLMODE,
    "connect_timeout": DB_CONNECT_TIMEOUT,
}
if DB_STATEMENT_TIMEOUT_MS:
    connect_args["options"] = f"-c statement_timeout={int(DB_STATEMENT_TIMEOUT_MS)}"

engine = create_engine(
    DATABASE_URL,
    pool_pre_ping=True,
    pool_size=DB_POOL_SIZE,
    max_overflow=DB_MAX_OVERFLOW,
    pool_timeout=DB_POOL_TIMEOUT,
    pool_recycle=DB_POOL_RECYCLE,
    connect_args=connect_args,
)

SessionLocal = sessionmaker(autocommit=False, autoflush=False, bind=engine)

Base = declarative_base()


def get_db():
    """Inyecta una sesión de BD en cada request y la cierra al terminar."""
    db = SessionLocal()
    try:
        yield db
    finally:
        db.close()
