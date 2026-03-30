import os
from datetime import datetime, timedelta
from werkzeug.security import generate_password_hash
from app import app
from models import db, Usuario, Categoria, PuntoMapa, Evento, Foro, RespuestaForo, LikeForo, Actividad, CalificacionPunto, Campaign

def init_database():
    with app.app_context():
        # Limpiar y recrear las tablas de SQLAlchemy
        db.drop_all()
        db.create_all()

        pass_default = generate_password_hash('password123', method='pbkdf2:sha256', salt_length=8)
        

        cat1, cat2, cat3, cat4, cat5 = [Categoria(nombre=n) for n in ['Reciclaje', 'Compostaje', 'Reducción de residuos', 'Eventos', 'Dudas']]
        
        db.session.add_all([cat1, cat2, cat3, cat4, cat5])
        db.session.commit()

        pm1 = PuntoMapa(nombre='Acopio Querétaro PetStar', latitud=20.566195172754586, longitud=-100.29811885089731, direccion='Calle Cascada 4 B, Parque Industrial La Noria, Qro.', materiales='PET, Plásticos, Tapitas', tipo='Centro Principal', imagen='petstar.jpg')
        pm2 = PuntoMapa(nombre='Alcamare Qro', latitud=20.609742163190546, longitud=-100.41774955960878, direccion='Av. 5 de Febrero 1410, Santiago de Querétaro, Qro.', materiales='Cartón, Papel, Archivo muerto, Plástico, Metales', tipo='Centro Principal', imagen='alcamare.jpg')
        pm3 = PuntoMapa(nombre='Centro De Acopio Raices Y Semillas Jurica', latitud=20.64698626631188, longitud=-100.43392338157149, direccion='Paseo Jurica No. 1, Jurica, Qro.', materiales='Semillas, Raíces, Reforestación, Vidrio, PET', tipo='Organización Ambiental', imagen='jurica.jpg')
        pm4 = PuntoMapa(nombre='El Faro Centro de Acopio', latitud=20.586852722572445, longitud=-100.4048242074262, direccion='Calle Pino Suárez esq. Churubusco, Centro, Qro.', materiales='PET, Cartón, Papel, Electrónicos', tipo='Centro Principal', imagen='elfaro.jpg')

        db.session.add_all([pm1, pm2, pm3, pm4])
        db.session.commit()

        # Campañas (los 3 originales de index.html)
        cp1 = Campaign(nombre='Mega Jornada de Acopio', fecha_inicio=datetime.utcnow() + timedelta(days=2), fecha_fin=datetime.utcnow() + timedelta(days=2, hours=5), lugar='Parque Bicentenario, Qro.', descripcion='Trae tus electrónicos y plásticos PET para reciclaje masivo. Gana puntos doble por kilo.', tipo_etiqueta='Acopio', imagen_url='acopio.png', link_evento='https://www.facebook.com/events/zerowaste-acopio', recompensa_puntos=50)
        cp2 = Campaign(nombre='Programa Ecomunidad', fecha_inicio=datetime.utcnow() + timedelta(days=5), fecha_fin=datetime.utcnow() + timedelta(days=5, hours=3), lugar='Centro Cívico, Querétaro', descripcion='Talleres de sostenibilidad y composta comunitaria para vecinos.', tipo_etiqueta='Educación', imagen_url='ecomunidad.png', link_evento='https://www.facebook.com/events/zerowaste-taller', recompensa_puntos=100)
        cp3 = Campaign(nombre='Reforestación Autóctona', fecha_inicio=datetime.utcnow() + timedelta(days=10), fecha_fin=datetime.utcnow() + timedelta(days=10, hours=4), lugar='Cerro del Tambor, Qro', descripcion='Unidos para rescatar áreas verdes con árboles endémicos.', tipo_etiqueta='Voluntariado', imagen_url='reforestacion.png', link_evento='https://www.facebook.com/events/zerowaste-reforestacion', recompensa_puntos=80)
        
        # Nuevos Eventos simples
        ev1 = Evento(titulo='Feria de Consumo Responsable', fecha_inicio=datetime.utcnow() + timedelta(days=15), fecha_fin=datetime.utcnow() + timedelta(days=15, hours=6), lugar='Plaza de Armas, Centro', descripcion='Conoce alternativas ecológicas y productos locales amigables con el medio ambiente.', tipo_etiqueta='Feria Libre', imagen_url='feria_res.png', link_evento='https://www.facebook.com/events/zerowaste-feria')
        ev2 = Evento(titulo='Taller de Huertos Comunitarios', fecha_inicio=datetime.utcnow() + timedelta(days=20), fecha_fin=datetime.utcnow() + timedelta(days=20, hours=3), lugar='Alameda Central, Qro', descripcion='Aprende a sembrar hortalizas usando envases reciclados y composta.', tipo_etiqueta='Taller', imagen_url='taller_huertos.png', link_evento='https://www.facebook.com/events/zerowaste-huerto')
        
        db.session.add_all([cp1, cp2, cp3, ev1, ev2])
        db.session.commit()

if __name__ == '__main__':
    init_database()
