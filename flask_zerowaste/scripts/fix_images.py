from app import app
from models import db, Campaign

with app.app_context():
    c1 = Campaign.query.filter_by(nombre='Talleres Ambientales en La Queretana').first()
    if c1: c1.imagen_url = 'event1.png'
    
    c2 = Campaign.query.filter_by(nombre='Lidera y Recicla en tu Escuela 2026').first()
    if c2: c2.imagen_url = 'event2.jpg'
    
    c3 = Campaign.query.filter_by(nombre='Campaña de Tapitas').first()
    if c3: c3.imagen_url = 'event3.jpg'
    
    db.session.commit()
    print('Imagenes de eventos actualizadas exitosamente!')
