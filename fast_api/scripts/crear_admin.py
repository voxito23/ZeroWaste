"""
Script para crear/actualizar los administradores del sistema en PostgreSQL.
Las contraseñas se hashean con bcrypt ($2y$) para compatibilidad con FastAPI y Laravel.
Ejecutar manualmente dentro del servicio fast_api sólo con variables explícitas.
"""

import os
from app.data.database import SessionLocal
from app.models.domain_models import Usuario
from app.security.jwt_auth import hash_password

def require_env(name: str) -> str:
    value = os.getenv(name, "").strip()
    if not value:
        raise RuntimeError(f"Required environment variable is not configured: {name}")
    return value


def main() -> None:
    admin = {
        "nombre": require_env("ADMIN_NAME"),
        "email": require_env("ADMIN_EMAIL"),
        "password": require_env("ADMIN_PASSWORD"),
    }
    if len(admin["password"]) < 12:
        raise RuntimeError("ADMIN_PASSWORD must contain at least 12 characters")

    db = SessionLocal()
    try:
        hashed = hash_password(admin["password"])
        existing = db.query(Usuario).filter(Usuario.email == admin["email"]).first()
        if existing:
            existing.is_admin = True  # type: ignore
            existing.password = hashed  # type: ignore
            action = "updated"
        else:
            db.add(Usuario(
                nombre=admin["nombre"],
                email=admin["email"],
                password=hashed,
                is_admin=True,
            ))
            action = "created"
        db.commit()
        print(f"Administrator {action} successfully; credentials were not printed.")
    except Exception:
        db.rollback()
        raise
    finally:
        db.close()


if __name__ == "__main__":
    main()
