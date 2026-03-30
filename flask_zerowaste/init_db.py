import os
import uuid
from datetime import datetime, timedelta
from werkzeug.security import generate_password_hash
from app import app
from models import db, Usuario, Categoria, PuntoMapa, Evento, Foro, RespuestaForo, LikeForo, Actividad, CalificacionPunto

def init_database():
    with app.app_context():
        # Limpiar y recrear las tablas de SQLAlchemy
        db.drop_all()
        db.create_all()

        print("Poblando usuarios y perfiles...")
        pass_default = generate_password_hash('password123', method='pbkdf2:sha256', salt_length=8)
        
        # Usuarios base
        us1 = Usuario(nombre='Victor Osvaldo Rodriguez Hernandez', email='victor@zerowaste.com', password=pass_default, biografia='Desarrollador y apasionado del medio ambiente.')
        us2 = Usuario(nombre='Maria Belen Frias Sanchez', email='belen@zerowaste.com', password=pass_default, biografia='Co-fundadora de la iniciativa ZeroWaste.')
        us3 = Usuario(nombre='Guadalupe Hernandez Medina', email='guadalupe@zerowaste.com', password=pass_default, biografia='Activista por el reciclaje local.')
        
        # Usuario de pruebas requerido con avatar
        us_pruebas = Usuario(nombre='Emiliano Jimenez Cantu', email='vpruebas16@gmail.com', password=pass_default, biografia='Usuario activo de la comunidad ZeroWaste.', foto_perfil='perfil1.jpg')

        db.session.add_all([us1, us2, us3, us_pruebas])
        db.session.commit()

        print("Configurando categorías...")
        cat1 = Categoria(nombre='Reciclaje')
        cat2 = Categoria(nombre='Compostaje')
        cat3 = Categoria(nombre='Reducción de residuos')
        cat4 = Categoria(nombre='Eventos')
        cat5 = Categoria(nombre='Dudas')
        
        db.session.add_all([cat1, cat2, cat3, cat4, cat5])
        db.session.commit()

        print("Geolocalizando Puntos en Mapa...")
        pm1 = PuntoMapa(nombre='Punto Verde Alameda', latitud=20.5881, longitud=-100.3899, direccion='Alameda Hidalgo, Centro Histórico, Querétaro', materiales='PET, Cartón, Vidrio, Aluminio', tipo='Centro Principal')
        pm2 = PuntoMapa(nombre='Centro de Acopio UAQ', latitud=20.5932, longitud=-100.4127, direccion='Universidad Autónoma de Querétaro, Campus Centro', materiales='PET, Papel, Cartón, Electrónicos', tipo='Centro Principal')
        pm3 = PuntoMapa(nombre='Tierra Com', latitud=20.6185, longitud=-100.4052, direccion='Col. Álamos, Querétaro', materiales='Orgánicos, Composta, Residuos de jardín', tipo='Contenedor Público')
        pm4 = PuntoMapa(nombre='Recicla Qro', latitud=20.6340, longitud=-100.4480, direccion='Blvd. B. Quintana, Col. Arboledas', materiales='Plástico, Vidrio, Metal, Textiles', tipo='Centro Principal')

        db.session.add_all([pm1, pm2, pm3, pm4])
        db.session.commit()

        print("Inyectando Eventos comunitarios...")
        ev1 = Evento(titulo='Mega Jornada de Acopio', fecha_inicio=datetime.utcnow() + timedelta(days=2), fecha_fin=datetime.utcnow() + timedelta(days=2, hours=5), lugar='Parque Bicentenario, Qro.', descripcion='Trae tus electrónicos y plásticos PET para reciclaje masivo.', tipo_etiqueta='Acopio', imagen_url='acopio.png')
        ev2 = Evento(titulo='Programa Ecomunidad', fecha_inicio=datetime.utcnow() + timedelta(days=5), fecha_fin=datetime.utcnow() + timedelta(days=5, hours=3), lugar='Centro Cívico, Querétaro', descripcion='Talleres de sostenibilidad y composta comunitaria para vecinos.', tipo_etiqueta='Educación', imagen_url='ecomunidad.png')
        ev3 = Evento(titulo='Reforestación Autóctona', fecha_inicio=datetime.utcnow() + timedelta(days=10), fecha_fin=datetime.utcnow() + timedelta(days=10, hours=4), lugar='Cerro del Tambor, Qro', descripcion='Unidos para rescatar áreas verdes con árboles endémicos.', tipo_etiqueta='Voluntariado', imagen_url='reforestacion.png')
        
        db.session.add_all([ev1, ev2, ev3])
        
        print("Añadiendo foros y publicaciones base...")
        post1 = Foro(titulo='¿Cómo empezar con el compostaje en departamento?', contenido='Quiero iniciar pero tengo poco espacio.', categoria_id=cat2.id, autor_id=us1.id)
        post2 = Foro(titulo='Guía de reciclaje en el centro', contenido='Chicos, les comparto los centros de acopio activos.', categoria_id=cat1.id, autor_id=us_pruebas.id)
        post3 = Foro(titulo='Alternativas al vidrio en el súper', contenido='Me ha costado mucho dejar de comprar empaques plásticos.', categoria_id=cat3.id, autor_id=us1.id)

        db.session.add_all([post1, post2, post3])
        db.session.commit()
        
        print("Registrando actividad referencial...")
        act1 = Actividad(usuario_id=us_pruebas.id, tipo='post', descripcion=f'Inició una nueva discusión #{post2.id}')
        db.session.add(act1)
        db.session.commit()
        
        # Ratings
        db.session.add_all([
            CalificacionPunto(location_id=pm1.id, usuario_id=us1.id, estrellas=5),
            CalificacionPunto(location_id=pm2.id, usuario_id=us2.id, estrellas=4),
            CalificacionPunto(location_id=pm3.id, usuario_id=us3.id, estrellas=5),
            CalificacionPunto(location_id=pm4.id, usuario_id=us_pruebas.id, estrellas=5)
        ])
        db.session.commit()

        print("==============================")
        print("DB Seed Completado por Flask.")
        print("==============================")

if __name__ == '__main__':
    init_database()
