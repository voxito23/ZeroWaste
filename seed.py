import os
import sys

# Agregamos los directorios para que Python encuentre los modulos
sys.path.append(os.path.join(os.path.dirname(__file__), 'fast_api'))

# Usamos localhost para conectarnos desde fuera de Docker
os.environ["DATABASE_URL"] = "postgresql://postgres:postgrespassword@127.0.0.1:5432/zerowaste_db"

from app.data.database import engine, Base, SessionLocal
from app.models.domain_models import Evento, Categoria, Foro, seed_database

def reset_and_seed():
    print("Conectando a la base de datos...")
    
    # 1. Dropear y recrear solo eventos
    print("Eliminando tabla eventos...")
    Evento.__table__.drop(engine, checkfirst=True)
    
    print("Creando tabla eventos con el nuevo esquema...")
    Evento.__table__.create(engine, checkfirst=True)

    # 2. Recrear tablas categorias y posts (por si no existen)
    Categoria.__table__.create(engine, checkfirst=True)
    Foro.__table__.create(engine, checkfirst=True)

    # 3. Seed de la base de datos
    db = SessionLocal()
    try:
        print("Ejecutando Seeder de categorías, eventos y foro...")
        seed_database(db)
        print("Seed completado exitosamente.")
    except Exception as e:
        print(f"Error durante el seeder: {e}")
        db.rollback()
    finally:
        db.close()

if __name__ == "__main__":
    reset_and_seed()
