import os
from datetime import datetime
from werkzeug.security import generate_password_hash
from app import app
from models import db, Usuario, Categoria, PuntoMapa, Evento, Foro, RespuestaForo, Campaign, Material, CalificacionPunto

def init_database():
    with app.app_context():
        db.drop_all()
        db.create_all()

        pass_victor = generate_password_hash('password123', method='pbkdf2:sha256', salt_length=8)
        pass_belen = generate_password_hash('password123', method='pbkdf2:sha256', salt_length=8)
        pass_lupita = generate_password_hash('password123', method='pbkdf2:sha256', salt_length=8)
        pass_chago = generate_password_hash('password123', method='pbkdf2:sha256', salt_length=8)
        pass_brian = generate_password_hash('password123', method='pbkdf2:sha256', salt_length=8)

        us1 = Usuario(nombre='Victor Osvaldo Rodriguez Hernandez', email='victor@zerowaste.com', password=pass_victor, biografia='Desarrollador y apasionado del medio ambiente.')
        us2 = Usuario(nombre='Maria Belen Frias Sanchez', email='belen@zerowaste.com', password=pass_belen, biografia='Co-fundadora de la iniciativa ZeroWaste.')
        us3 = Usuario(nombre='Guadalupe Hernandez Medina', email='guadalupe@zerowaste.com', password=pass_lupita, biografia='Activista por el reciclaje local.')
        us4 = Usuario(nombre='Santiago', email='santiago@zerowaste.com', password=pass_chago, biografia='Impulsor de proyectos sustentables.')
        us5 = Usuario(nombre='Brian', email='brian@zerowaste.com', password=pass_brian, biografia='Especialista en economía circular.')
        
        db.session.add_all([us1, us2, us3, us4, us5])
        db.session.commit()

        cat1 = Categoria(nombre='Reciclaje')
        cat2 = Categoria(nombre='Compostaje')
        cat3 = Categoria(nombre='Reducción de residuos')
        cat4 = Categoria(nombre='Energías limpias')
        cat5 = Categoria(nombre='Reutilización')
        
        db.session.add_all([cat1, cat2, cat3, cat4, cat5])
        db.session.commit()

        pm1 = PuntoMapa(nombre='Centro de Acopio UPQ', direccion='Universidad Politécnica de Querétaro', latitud=20.5694, longitud=-100.2369, tipo='Centro Principal', materiales='PET, Cartón, Aluminio, Electrónicos')
        pm2 = PuntoMapa(nombre='Acopio Alameda Sur', direccion='Frente a Centro Gómez Morín', latitud=20.5881, longitud=-100.3899, tipo='Contenedor Público', materiales='Vidrio, Pilas, Papel')
        pm3 = PuntoMapa(nombre='EcoPunto Juriquilla', direccion='Plaza Juriquilla', latitud=20.6865, longitud=-100.4497, tipo='Estación de Reciclaje', materiales='PET, Electrónicos, Tapitas')
        pm4 = PuntoMapa(nombre='Reciclaje Centro Histórico', direccion='Jardín Zenea', latitud=20.5931, longitud=-100.3920, tipo='Contenedor Público', materiales='Cartón, Papel, Vidrio')
        pm5 = PuntoMapa(nombre='Centro Verde El Refugio', direccion='Anillo Vial Fray Junípero Serra', latitud=20.6558, longitud=-100.3705, tipo='Centro Especializado', materiales='Pilas, Baterías de auto, Electrónicos')

        db.session.add_all([pm1, pm2, pm3, pm4, pm5])
        db.session.commit()

        cal1 = CalificacionPunto(location_id=1, usuario_id=2, estrellas=5)
        cal2 = CalificacionPunto(location_id=1, usuario_id=3, estrellas=4)
        cal3 = CalificacionPunto(location_id=2, usuario_id=1, estrellas=3)
        cal4 = CalificacionPunto(location_id=3, usuario_id=5, estrellas=5)
        cal5 = CalificacionPunto(location_id=4, usuario_id=4, estrellas=4)

        db.session.add_all([cal1, cal2, cal3, cal4, cal5])

        ev1 = Evento(titulo='Mega Jornada de Acopio 2026', fecha_inicio=datetime(2026, 1, 15, 9, 0), ubicacion='Alameda Hidalgo, Qro.', descripcion='Nuestra primera jornada masiva del año para recibir residuos electrónicos, pilas y pet. ¡Te esperamos para lograr la meta de 5 toneladas!', categoria='Recolección Masiva', link_unirse='https://www.google.com/maps/search/?api=1&query=20.5881,-100.3899')
        ev2 = Evento(titulo='Programa Ecomunidad - Talleres', fecha_inicio=datetime(2026, 2, 8, 10, 0), ubicacion='Universidad Politécnica de Querétaro', descripcion='Talleres prácticos de compostaje casero y creación de huertos urbanos impartidos por especialistas de ZeroWaste.', categoria='Taller Educativo', link_unirse='https://www.google.com/maps/search/?api=1&query=20.5694,-100.2369')
        ev3 = Evento(titulo='Inauguración Nuevo Centro de Acopio', fecha_inicio=datetime(2026, 3, 20, 11, 30), ubicacion='Plaza Juriquilla, Qro.', descripcion='Celebramos la apertura de un nuevo macro-contenedor inteligente con la presencia de autoridades locales y miembros del equipo.', categoria='Inauguración', link_unirse='https://www.google.com/maps/search/?api=1&query=20.6865,-100.4497')

        db.session.add_all([ev1, ev2, ev3])
        
        post1 = Foro(titulo='¿Cómo empezar a compostar en un departamento pequeño?', contenido='Llevo meses queriendo compostar pero vivo en un departamento. ¿Tienen algún consejo?', categoria_id=2, autor_id=3)
        post2 = Foro(titulo='Excelente iniciativa en la jornada de hoy', contenido='Fui a la UPQ a dejar mis residuos electrónicos y estuvo muy bien organizado.', categoria_id=1, autor_id=2)

        db.session.add_all([post1, post2])
        db.session.commit()
        print("Base de datos inicializada correctamente.")

if __name__ == '__main__':
    init_database()
