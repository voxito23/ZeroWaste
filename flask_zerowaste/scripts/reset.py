import os
from app import app
from models import db
from init_db import init_database

def reset():
    with app.app_context():
        print("Borrando todas las tablas...")
        db.drop_all()
        print("Tablas borradas.")
        print("Ejecutando init_database...")
        init_database()
        print("Reseteo completo.")

if __name__ == '__main__':
    reset()
