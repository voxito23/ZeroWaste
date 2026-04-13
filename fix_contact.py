import os
import sys

# Ensure path is correct for imports
sys.path.insert(0, os.path.abspath('.'))

from flask_zerowaste.app import app, db
from flask_zerowaste.models import ContactMessage, Usuario

with app.app_context():
    # Find active user by name or email
    user = Usuario.query.filter(Usuario.email == 'vrodriguez75@gmail.com').first()
    
    if not user:
         user = Usuario.query.first() # fallback if email is different

    if user:
        print(f"Found user: {user.nombre} ({user.email})")
        # Check if they already have messages
        existing = ContactMessage.query.filter_by(usuario_id=user.id).all()
        if not existing:
            print("No messages found. Creating a test one...")
            nuevo_msg = ContactMessage(
                nombre=user.nombre,
                email=user.email,
                ubicacion="Querétaro (Problema Técnico)",
                mensaje="Hola, este es el mensaje que enviaste pero que se perdió por el error del proxy. ¡Ya está arreglado el sistema de contactos y notificaciones!",
                estado="respondido",
                usuario_id=user.id
            )
            db.session.add(nuevo_msg)
            db.session.commit()
            
            # Add a reply too
            from flask_zerowaste.models import ContactReply
            reply = ContactReply(
                contact_message_id=nuevo_msg.id,
                sender='admin',
                mensaje="¡Hola! Hemos revisado el problema con las rutas de FastAPI. Ya no deberías tener problemas para enviar mensajes, subir archivos grandes, ni ver tus notificaciones. Cualquier otra duda, estamos aquí."
            )
            db.session.add(reply)
            db.session.commit()
            print("Message created successfully!")
        else:
            print(f"User already has {len(existing)} messages. Updating text...")
            existing[0].mensaje = "Hola equipo, intento subir imagen y no puedo."
            db.session.commit()
    else:
        print("No users found in database!")
