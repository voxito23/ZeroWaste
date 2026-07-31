"""
Script para verificar los usuarios registrados en la base de datos.
No imprime correos, hashes ni otros datos personales.
"""

from app.data.database import SessionLocal
from sqlalchemy import func

from app.models.domain_models import Usuario

db = SessionLocal()
try:
    total = db.query(func.count(Usuario.id)).scalar() or 0
    admins = db.query(func.count(Usuario.id)).filter(Usuario.is_admin.is_(True)).scalar() or 0
    blocked = db.query(func.count(Usuario.id)).filter(Usuario.bloqueado.is_(True)).scalar() or 0
    print(f"Total users: {total}")
    print(f"Administrators: {admins}")
    print(f"Blocked users: {blocked}")
finally:
    db.close()
