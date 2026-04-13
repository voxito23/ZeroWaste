import sys
import os

# Agregamos la ruta
sys.path.append('c:\\ZeroWaste\\flask_zerowaste')

from app import app, db, Notificacion, Usuario

with app.app_context():
    try:
        usuario = Usuario.query.first()
        if usuario:
            print(f"Usuario {usuario.id}")
            unread = Notificacion.query.filter(
                Notificacion.user_id == usuario.id,
                db.or_(Notificacion.leida == False, Notificacion.leida == None)
            ).order_by(Notificacion.created_at.desc()).all()
            print(f"Unread count: {len(unread)}")
        else:
            print("No hay usuarios.")
    except Exception as e:
        print(f"ERROR: {e}")
