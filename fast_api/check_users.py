"""
Script para verificar los usuarios registrados en la base de datos.
Ejecutar: docker exec fastapi_app python check_users.py
"""

from app.data.database import SessionLocal
from app.models.domain_models import Usuario

db = SessionLocal()
users = db.query(Usuario).all()
for u in users:
    print(f"ID={u.id} | {u.email} | is_admin={u.is_admin} | hash_type={u.password[:15]}")
print(f"\nTotal: {len(users)} usuarios")
db.close()
