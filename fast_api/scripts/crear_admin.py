"""
Script para crear/actualizar los administradores del sistema en PostgreSQL.
Las contraseñas se hashean con werkzeug (pbkdf2:sha256) para compatibilidad con FastAPI.
Ejecutar: docker exec fastapi_app python crear_admin.py
"""

from app.data.database import SessionLocal
from app.models.domain_models import Usuario
from passlib.hash import bcrypt

db = SessionLocal()

admins = [
    {"nombre": "Victor Admin",      "email": "admin@zerowaste.com",           "password": "123456"}
]

try:
    for admin in admins:
        hashed = bcrypt.using(ident="2y").hash(admin["password"])
        existe = db.query(Usuario).filter(Usuario.email == admin["email"]).first()

        if existe:
            existe.is_admin = True
            existe.password = hashed
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
