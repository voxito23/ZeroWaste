import os
from datetime import datetime, timedelta
from app import app
from models import db, Categoria, PuntoMapa, Campaign

def init_database():
    """Inicializa la base de datos creando tablas faltantes y sembrando datos base idempotentes."""
    with app.app_context():
        db.create_all()

        # Categorías del foro (idempotente)
        categorias_base = ['Reciclaje', 'Compostaje', 'Reducción de residuos', 'Eventos', 'Dudas']
        for nombre in categorias_base:
            if not Categoria.query.filter_by(nombre=nombre).first():
                db.session.add(Categoria(nombre=nombre))
        db.session.commit()

        # Puntos de reciclaje reales de Querétaro (idempotente)
        puntos_base = [
            dict(nombre='Acopio Querétaro PetStar', latitud=20.566195172754586, longitud=-100.29811885089731, direccion='Calle Cascada 4 B, Parque Industrial La Noria, Qro.', materiales='PET, Plásticos, Tapitas', tipo='Centro Principal', imagen='petstar.jpg'),
            dict(nombre='Alcamare Qro', latitud=20.609742163190546, longitud=-100.41774955960878, direccion='Av. 5 de Febrero 1410, Santiago de Querétaro, Qro.', materiales='Cartón, Papel, Archivo muerto, Plástico, Metales', tipo='Centro Principal', imagen='alcamare.jpg'),
            dict(nombre='Centro De Acopio Raices Y Semillas Jurica', latitud=20.64698626631188, longitud=-100.43392338157149, direccion='Paseo Jurica No. 1, Jurica, Qro.', materiales='Semillas, Raíces, Reforestación, Vidrio, PET', tipo='Organización Ambiental', imagen='jurica.jpg'),
            dict(nombre='El Faro Centro de Acopio', latitud=20.586852722572445, longitud=-100.4048242074262, direccion='Calle Pino Suárez esq. Churubusco, Centro, Qro.', materiales='PET, Cartón, Papel, Electrónicos', tipo='Centro Principal', imagen='elfaro.jpg'),
        ]
        for p in puntos_base:
            if not PuntoMapa.query.filter_by(nombre=p['nombre']).first():
                db.session.add(PuntoMapa(**p))
        db.session.commit()

        # 3 Campañas obligatorias (idempotente — reemplazan las anteriores)
        campanas_obligatorias = [
            dict(
                nombre='Talleres Ambientales en La Queretana',
                descripcion='El municipio ha anunciado una serie de eventos en el Parque Intraurbano "La Queretana", destacando talleres de polinizadores programados específicamente para el mes de abril.',
                lugar='Parque Intraurbano La Queretana, Querétaro',
                fecha_inicio=datetime(2026, 4, 1),
                fecha_fin=datetime(2026, 4, 30),
                tipo_etiqueta='Taller',
                link_evento='https://rotativo.com.mx/calendario-eventos-ambientales-la-queretana-queretaro-2026?share_id=9284828&socialux=facebook&utm_campaign=RebelMouse&utm_content=Rotativo%20de%20Queretaro&utm_medium=social&utm_source=facebook',
                recompensa_puntos=50,
                activa=True,
                imagen_url='event1.png',
            ),
            dict(
                nombre='Lidera y Recicla en tu Escuela 2026',
                descripcion='Esta iniciativa de la Secretaría de Educación del Estado (SEDEQ) estará activa durante todo el mes. Se enfoca en la recolección de botellas PET y tapas de plástico en instituciones de todos los niveles educativos.',
                lugar='Instituciones educativas de Querétaro',
                fecha_inicio=datetime(2026, 4, 1),
                fecha_fin=datetime(2026, 4, 30),
                tipo_etiqueta='Educación',
                link_evento='https://linktr.ee/EDUCACIONQRO',
                recompensa_puntos=100,
                activa=True,
                imagen_url='event2.jpg',
            ),
            dict(
                nombre='Campaña de Tapitas',
                descripcion='Campaña enfocada en la recolección solidaria de tapitas.',
                lugar='Querétaro, Qro.',
                fecha_inicio=datetime(2026, 4, 1),
                fecha_fin=datetime(2026, 6, 30),
                tipo_etiqueta='Acopio',
                link_evento='https://amancqueretaro.org/reciclando/',
                recompensa_puntos=30,
                activa=True,
                imagen_url='event3.jpg',
            ),
        ]
        for c in campanas_obligatorias:
            if not Campaign.query.filter_by(nombre=c['nombre']).first():
                db.session.add(Campaign(**c))
        db.session.commit()

if __name__ == '__main__':
    init_database()
