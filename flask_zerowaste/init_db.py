import os
from datetime import datetime, timedelta
from werkzeug.security import generate_password_hash
from app import app
from models import db, Usuario, Categoria, PuntoMapa, Evento, Foro, RespuestaForo, LikeForo, Actividad, CalificacionPunto

def init_database():
    with app.app_context():
        # Limpiar y recrear las tablas de SQLAlchemy
        db.drop_all()
        db.create_all()

        pass_default = generate_password_hash('password123', method='pbkdf2:sha256', salt_length=8)
        

        cat1, cat2, cat3, cat4, cat5 = [Categoria(nombre=n) for n in ['Reciclaje', 'Compostaje', 'Reducción de residuos', 'Eventos', 'Dudas']]
        
        db.session.add_all([cat1, cat2, cat3, cat4, cat5])
        db.session.commit()

        pm1 = PuntoMapa(nombre='Punto Verde Alameda', latitud=20.5881, longitud=-100.3899, direccion='Alameda Hidalgo, Centro Histórico, Querétaro', materiales='PET, Cartón, Vidrio, Aluminio', tipo='Centro Principal')
        pm2 = PuntoMapa(nombre='Centro de Acopio UAQ', latitud=20.5932, longitud=-100.4127, direccion='Universidad Autónoma de Querétaro, Campus Centro', materiales='PET, Papel, Cartón, Electrónicos', tipo='Centro Principal')
        pm3 = PuntoMapa(nombre='Tierra Com', latitud=20.6185, longitud=-100.4052, direccion='Col. Álamos, Querétaro', materiales='Orgánicos, Composta, Residuos de jardín', tipo='Contenedor Público')
        pm4 = PuntoMapa(nombre='Recicla Qro', latitud=20.6340, longitud=-100.4480, direccion='Blvd. B. Quintana, Col. Arboledas', materiales='Plástico, Vidrio, Metal, Textiles', tipo='Centro Principal')

        db.session.add_all([pm1, pm2, pm3, pm4])
        db.session.commit()

        ev1 = Evento(titulo='Mega Jornada de Acopio', fecha_inicio=datetime.utcnow() + timedelta(days=2), fecha_fin=datetime.utcnow() + timedelta(days=2, hours=5), lugar='Parque Bicentenario, Qro.', descripcion='Trae tus electrónicos y plásticos PET para reciclaje masivo.', tipo_etiqueta='Acopio', imagen_url='acopio.png', link_evento='https://www.facebook.com/events/zerowaste-acopio')
        ev2 = Evento(titulo='Programa Ecomunidad', fecha_inicio=datetime.utcnow() + timedelta(days=5), fecha_fin=datetime.utcnow() + timedelta(days=5, hours=3), lugar='Centro Cívico, Querétaro', descripcion='Talleres de sostenibilidad y composta comunitaria para vecinos.', tipo_etiqueta='Educación', imagen_url='ecomunidad.png', link_evento='https://www.facebook.com/events/zerowaste-taller')
        ev3 = Evento(titulo='Reforestación Autóctona', fecha_inicio=datetime.utcnow() + timedelta(days=10), fecha_fin=datetime.utcnow() + timedelta(days=10, hours=4), lugar='Cerro del Tambor, Qro', descripcion='Unidos para rescatar áreas verdes con árboles endémicos.', tipo_etiqueta='Voluntariado', imagen_url='reforestacion.png', link_evento='https://www.facebook.com/events/zerowaste-reforestacion')
        
        db.session.add_all([ev1, ev2, ev3])
        db.session.commit()

if __name__ == '__main__':
    init_database()
