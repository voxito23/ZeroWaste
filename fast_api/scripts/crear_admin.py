"""
Script para crear/actualizar los administradores del sistema en PostgreSQL.
Las contraseñas se hashean con bcrypt ($2y$) para compatibilidad con FastAPI y Laravel.
Ejecutar: docker exec fastapi_app python crear_admin.py
"""

import os
from app.data.database import SessionLocal
from app.models.domain_models import Usuario
from app.security.jwt_auth import hash_password

db = SessionLocal()

admins = [
    {"nombre": "Victor Admin",      "email": os.getenv("ADMIN_EMAIL", "admin@ejemplo.com"),           "password": "123456"}
]

try:
    for admin in admins:
        hashed = hash_password(admin["password"])
        existe = db.query(Usuario).filter(Usuario.email == admin["email"]).first()

        if existe:
            existe.is_admin = True  # type: ignore
            existe.password = hashed  # type: ignore
            print(f"  [ACTUALIZADO] {admin['email']}")
        else:
            nuevo = Usuario(
                nombre=admin["nombre"],
                email=admin["email"],
                password=hashed,
                is_admin=True,
            )
            db.add(nuevo)
            print(f"  [CREADO]      {admin['email']}")

    db.commit()
    print("\nAdministradores procesados exitosamente.")
finally:
    db.close()
