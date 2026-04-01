from flask import Flask, render_template, request, redirect, url_for, jsonify, session
from flask_cors import CORS
from werkzeug.security import check_password_hash, generate_password_hash
import os
import uuid
from sqlalchemy.sql import func
from datetime import datetime, timedelta
import requests as http_requests

os.environ['OAUTHLIB_INSECURE_TRANSPORT'] = '1'

from models import (db, Usuario, Categoria, PuntoMapa, CalificacionPunto,
                     Evento, Foro, RespuestaForo, LikeForo, Actividad,
                     Campaign, ContactMessage, ContactReply, PasswordResetRequest, Notificacion)
# IA delegada a FastAPI

app = Flask(__name__)
CORS(app, supports_credentials=True)
app.secret_key = 'super_secreta_zerowaste_2026'

app.config['SQLALCHEMY_DATABASE_URI'] = os.environ.get('DATABASE_URL', 'postgresql://postgres:postgrespassword@127.0.0.1:5432/zerowaste_db')
app.config['SQLALCHEMY_TRACK_MODIFICATIONS'] = False

# Variable dinámica para Producción vs Desarrollo
PUBLIC_API_URL = os.environ.get('PUBLIC_API_URL', 'http://localhost:6001')

@app.context_processor
def inject_global_vars():
    return dict(API_URL=PUBLIC_API_URL)

db.init_app(app)

with app.app_context():
    db.create_all()

# ==========================================================================
#  Template Filters
# ==========================================================================
@app.template_filter('datetime_mx')
def datetime_mx_filter(dt):
    if not dt:
        return ''
    mx_dt = dt - timedelta(hours=6)
    meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic']
    mes_str = meses[mx_dt.month - 1]
    return f"{mx_dt.day:02d} {mes_str}, {mx_dt.strftime('%H:%M')}"

# ==========================================================================
#  Rutas de navegación básica
# ==========================================================================

@app.route('/')
def root():
    return render_template('login.html')

@app.route('/login', methods=['GET', 'POST'])
def login():
    if request.method == 'POST':
        email = request.form.get('email', '').strip()
        password = request.form.get('password', '')
        
        usuario = Usuario.query.filter_by(email=email).first()
        if not usuario:
            return jsonify({'success': False, 'error': 'Credenciales inválidas.'}), 401
            
        import bcrypt
        if isinstance(usuario.password, str) and bcrypt.checkpw(password.encode('utf-8'), usuario.password.encode('utf-8')):
            session['usuario_id'] = usuario.id
            session['nombre'] = usuario.nombre
            session['email'] = usuario.email
            session['foto_perfil'] = usuario.foto_perfil
            session['profile_completed'] = usuario.profile_completed
            return jsonify({'success': True, 'redirect': url_for('index')})
        else:
            return jsonify({'success': False, 'error': 'Credenciales inválidas.'}), 401
            
    return render_template('login.html')

@app.route('/registro', methods=['GET', 'POST'])
def registro():
    if request.method == 'POST':
        data = request.get_json() if request.is_json else request.form
        nombre = data.get('nombre', '').strip()
        email = data.get('email', '').strip()
        password = data.get('password', '')
        
        if not nombre or not email or len(password) < 6:
            return jsonify({'success': False, 'error': 'Datos incompletos o contraseña muy corta.'}), 400
            
        if Usuario.query.filter_by(email=email).first():
            return jsonify({'success': False, 'error': 'El correo ya está registrado.'}), 400
            
        import bcrypt
        hashed = bcrypt.hashpw(password.encode('utf-8'), bcrypt.gensalt()).decode('utf-8')
        
        nuevo = Usuario(
            nombre=nombre,
            email=email,
            password=hashed,
            auth_provider='local',
            profile_completed=True
        )
        db.session.add(nuevo)
        db.session.commit()
        
        session['usuario_id'] = nuevo.id
        session['nombre'] = nuevo.nombre
        session['email'] = nuevo.email
        session['profile_completed'] = True
        
        return jsonify({'success': True, 'redirect': url_for('index')})
        
    return render_template('registro.html')

@app.route('/tema/reciclar-plastico')
def articulo_plastico():
    return render_template('reciclar_plastico.html')

@app.route('/tema/ahorro-agua')
def articulo_agua():
    return render_template('ahorro_agua.html')

@app.route('/tema/energia-solar')
def articulo_solar():
    return render_template('energia_solar.html')

@app.route('/tema/compostaje-urbano')
def articulo_compostaje():
    return render_template('compostaje_urbano.html')


# ==========================================================================
#  Autenticación Firebase (Google Sign-In)
# ==========================================================================

@app.route('/auth/firebase', methods=['POST'])
def auth_firebase():
    """Recibe idToken de Firebase, verifica con Google y crea/enlaza usuario."""
    data = request.get_json()
    id_token = data.get('idToken')
    if not id_token:
        return jsonify({'success': False, 'error': 'Token no proporcionado.'}), 400

    try:
        # Verificar token con Google
        verify_url = f'https://www.googleapis.com/oauth2/v3/tokeninfo?id_token={id_token}'
        resp = http_requests.get(verify_url, timeout=10)
        if resp.status_code != 200:
            return jsonify({'success': False, 'error': 'Token inválido.'}), 401

        token_info = resp.json()
        email = token_info.get('email')
        name = token_info.get('name', email.split('@')[0])
        picture = token_info.get('picture', 'perfil_default.png')
        firebase_uid = token_info.get('sub')

        if not email:
            return jsonify({'success': False, 'error': 'No se pudo obtener el correo.'}), 400

        # Buscar usuario existente por email
        usuario = Usuario.query.filter_by(email=email).first()

        if usuario:
            # Enlazar firebase_uid si no lo tiene
            if not usuario.firebase_uid:
                usuario.firebase_uid = firebase_uid
                usuario.auth_provider = 'google' if usuario.auth_provider == 'local' else usuario.auth_provider
                db.session.commit()

            session['usuario_id'] = usuario.id
            session['nombre'] = usuario.nombre
            session['email'] = usuario.email
            session['foto_perfil'] = usuario.foto_perfil
            session['titulo_perfil'] = usuario.titulo_perfil
            session['biografia'] = usuario.biografia
            session['ubicacion'] = usuario.ubicacion
            session['intereses'] = usuario.intereses
            # Si es admin, redirigir al panel de Laravel
            if usuario.is_admin:
                redirect_url = 'http://localhost:8001/admin/dashboard'
            else:
                redirect_url = url_for('perfil')

            return jsonify({'success': True, 'redirect': redirect_url, 'new_user': False})
        else:
            # Crear usuario nuevo con perfil incompleto
            import bcrypt
            random_pass = bcrypt.hashpw(uuid.uuid4().hex.encode('utf-8'), bcrypt.gensalt()).decode('utf-8')
            nuevo = Usuario(
                nombre=name,
                email=email,
                password=random_pass,
                foto_perfil=picture,
                firebase_uid=firebase_uid,
                auth_provider='google',
                profile_completed=False
            )
            db.session.add(nuevo)
            db.session.commit()

            session['usuario_id'] = nuevo.id
            session['nombre'] = nuevo.nombre
            session['email'] = nuevo.email
            session['foto_perfil'] = nuevo.foto_perfil
            session['titulo_perfil'] = nuevo.titulo_perfil
            session['biografia'] = nuevo.biografia
            session['ubicacion'] = nuevo.ubicacion
            session['intereses'] = nuevo.intereses
            return jsonify({'success': True, 'redirect': url_for('completar_perfil_view'), 'new_user': True})

    except Exception as e:
        return jsonify({'success': False, 'error': f'Error de verificación: {str(e)}'}), 500


# ==========================================================================
#  Completar Perfil (tras Google Sign-In)
# ==========================================================================

@app.route('/completar-perfil')
def completar_perfil_view():
    if 'usuario_id' not in session:
        return redirect(url_for('login'))
    usuario = Usuario.query.get(session['usuario_id'])
    if not usuario:
        return redirect(url_for('login'))
    return render_template('completar_perfil.html', usuario=usuario)

@app.route('/completar-perfil', methods=['POST'])
def completar_perfil_save():
    if 'usuario_id' not in session:
        return redirect(url_for('login'))

    usuario = Usuario.query.get(session['usuario_id'])
    if not usuario:
        return redirect(url_for('login'))

    nombre = request.form.get('nombre', '').strip()
    ubicacion = request.form.get('ubicacion', '').strip()
    titulo_perfil = request.form.get('titulo_perfil', '').strip()
    biografia = request.form.get('biografia', '').strip()
    intereses_lista = request.form.getlist('intereses')
    intereses = ", ".join(intereses_lista) if intereses_lista else ""
    password_local = request.form.get('password', '').strip()
    foto_perfil_file = request.files.get('foto_perfil')

    if nombre and len(nombre) > 2:
        usuario.nombre = nombre
    if ubicacion:
        usuario.ubicacion = ubicacion
    if titulo_perfil:
        usuario.titulo_perfil = titulo_perfil
    if biografia:
        usuario.biografia = biografia
    if intereses:
        usuario.intereses = intereses

    # Contraseña local opcional (bcrypt para compatibilidad con Laravel)
    if password_local and len(password_local) >= 6:
        import bcrypt
        hashed = bcrypt.hashpw(password_local.encode('utf-8'), bcrypt.gensalt()).decode('utf-8')
        usuario.password = hashed
        if usuario.auth_provider == 'google':
            usuario.auth_provider = 'google+local'

    # Foto de perfil
    if foto_perfil_file and foto_perfil_file.filename:
        extension = foto_perfil_file.filename.split('.')[-1].lower()
        nombre_foto = f"{uuid.uuid4().hex}.{extension}"
        carpeta_destino = os.path.join(app.root_path, 'static', 'img', 'perfiles')
        os.makedirs(carpeta_destino, exist_ok=True)
        foto_perfil_file.save(os.path.join(carpeta_destino, nombre_foto))
        usuario.foto_perfil = nombre_foto

    usuario.profile_completed = True
    db.session.commit()

    session['nombre'] = usuario.nombre
    session['foto_perfil'] = usuario.foto_perfil
    session['ubicacion'] = usuario.ubicacion
    session['titulo_perfil'] = usuario.titulo_perfil
    session['biografia'] = usuario.biografia
    session['intereses'] = usuario.intereses

    return redirect(url_for('index'))


@app.route('/inicio')
def index():
    if 'usuario_id' not in session:
        return redirect(url_for('login'))

    # Verificar si necesita completar perfil
    usuario = Usuario.query.get(session['usuario_id'])
    if usuario and not usuario.profile_completed:
        return redirect(url_for('completar_perfil_view'))
    
    eventos = Evento.query.order_by(Evento.fecha_inicio.asc()).limit(3).all()
    campaigns = Campaign.query.filter_by(activa=True).order_by(Campaign.fecha_inicio.asc()).all()
    return render_template('index.html', eventos=eventos, campaigns=campaigns)

@app.route('/noticia-queretaro')
def noticia_queretaro():
    return render_template('noticia-queretaro.html')

@app.route('/Acercade')
def Acercade():
    return render_template('acercade.html')

@app.route('/contacto')
def contacto():
    return render_template('contacto.html')


# ==========================================================================
#  API: Contacto y Recuperación de Contraseña
# ==========================================================================

# Endpoints migrados a FastAPI

@app.route('/api/mis_mensajes_contacto')
def mis_mensajes_contacto():
    """Devuelve los mensajes de contacto del usuario logueado con hilo de respuestas."""
    if 'usuario_id' not in session:
        return jsonify({'success': False, 'error': 'No autenticado.'}), 401
    
    usuario = Usuario.query.get(session['usuario_id'])
    if not usuario:
        return jsonify({'success': False, 'error': 'Usuario no encontrado.'}), 404
    
    mensajes = ContactMessage.query.filter_by(email=usuario.email).order_by(ContactMessage.created_at.desc()).all()
    
    result = []
    for m in mensajes:
        replies = ContactReply.query.filter_by(contact_message_id=m.id).order_by(ContactReply.created_at.asc()).all()
        replies_list = []
        for r in replies:
            replies_list.append({
                'sender': r.sender,
                'mensaje': r.mensaje,
                'created_at': (r.created_at - timedelta(hours=6)).strftime('%d %b, %Y %H:%M') if r.created_at else ''
            })
        
        result.append({
            'id': m.id,
            'mensaje': m.mensaje,
            'estado': m.estado,
            'respuesta_admin': m.respuesta_admin,
            'replies': replies_list,
            'created_at': (m.created_at - timedelta(hours=6)).strftime('%d %b, %Y %H:%M') if m.created_at else ''
        })
    
    return jsonify({'success': True, 'mensajes': result})


@app.route('/api/responder_contacto/<int:msg_id>', methods=['POST'])
def responder_contacto(msg_id):
    """Permite al usuario responder a un hilo de contacto."""
    if 'usuario_id' not in session:
        return jsonify({'success': False, 'error': 'No autenticado.'}), 401
    
    msg = ContactMessage.query.get(msg_id)
    if not msg:
        return jsonify({'success': False, 'error': 'Mensaje no encontrado.'}), 404
    
    usuario = Usuario.query.get(session['usuario_id'])
    if not usuario or usuario.email != msg.email:
        return jsonify({'success': False, 'error': 'No autorizado.'}), 403
    
    data = request.get_json()
    texto = data.get('mensaje', '').strip() if data else ''
    if len(texto) < 2:
        return jsonify({'success': False, 'error': 'Mínimo 2 caracteres.'}), 400
    
    reply = ContactReply(
        contact_message_id=msg_id,
        sender='user',
        mensaje=texto
    )
    db.session.add(reply)
    
    # Change status back so admin sees there's a new message
    msg.estado = 'pendiente'
    db.session.commit()
    
    return jsonify({'success': True, 'message': 'Respuesta enviada.'})


# ==========================================================================
#  Mapa y recomendaciones
# ==========================================================================

def get_puntos_con_promedio():
    resultados = db.session.query(
        PuntoMapa,
        func.coalesce(func.avg(CalificacionPunto.estrellas), 0).label('promedio'),
        func.count(CalificacionPunto.id).label('total_reviews')
    ).outerjoin(CalificacionPunto, PuntoMapa.id == CalificacionPunto.location_id)\
    .group_by(PuntoMapa.id).order_by(db.desc('promedio')).all()
    
    puntos = []
    for punto, promedio, total in resultados:
        resenas_raw = db.session.query(CalificacionPunto, Usuario)\
            .join(Usuario, CalificacionPunto.usuario_id == Usuario.id)\
            .filter(CalificacionPunto.location_id == punto.id)\
            .order_by(CalificacionPunto.created_at.desc()).all()
            
        resenas_list = []
        for c, u in resenas_raw:
            resenas_list.append({
                'usuario': u.nombre,
                'estrellas': c.estrellas,
                'comentario': c.comentario,
                'fecha': c.created_at.strftime("%Y-%m-%d %H:%M")
            })
            
        puntos.append({
            'id': punto.id,
            'nombre': punto.nombre,
            'direccion': punto.direccion,
            'latitud': float(punto.latitud),
            'longitud': float(punto.longitud),
            'tipo': punto.tipo,
            'materiales': punto.materiales,
            'imagen': punto.imagen,
            'promedio': float(f"{float(promedio or 0):.1f}"),
            'total_reviews': total,
            'resenas': resenas_list
        })
    return puntos

@app.route('/mapa')
def mapa():
    puntos = get_puntos_con_promedio()
    return render_template('mapa.html', puntos=puntos)

@app.route('/recomendaciones')
def recomendaciones():
    puntos = get_puntos_con_promedio()
    try:
        # aqui se manda llamar la api de fast api y analiza todo
        sentimiento = http_requests.get('http://fastapi_app:6000/analisis/sentimiento', timeout=10).json().get('data', {"POS": 0, "NEG": 0, "NEU": 0, "total": 0})
    except Exception:
        sentimiento = {"POS": 0, "NEG": 0, "NEU": 0, "total": 0}
    
    recomendaciones_ia = []
    if sentimiento['total'] > 0:
        if sentimiento['POS'] >= 60:
            recomendaciones_ia.append("La comunidad se siente muy positiva. ¡Sigue compartiendo tus logros de reciclaje!")
        elif sentimiento['NEG'] >= 40:
            recomendaciones_ia.append("Notamos preocupación en la comunidad. Participa con soluciones prácticas en el foro.")
        else:
            recomendaciones_ia.append("El sentimiento es equilibrado. Comparte consejos útiles para inspirar a otros.")
        total_foros = Foro.query.count()
        total_estrellas = CalificacionPunto.query.count()
        total_resenas = CalificacionPunto.query.filter(CalificacionPunto.comentario != None, CalificacionPunto.comentario != '').count()
        
        recomendaciones_ia.append(f"Se analizaron {total_foros} publicaciones del foro, {total_estrellas} calificaciones de estrellas y {total_resenas} reseñas con IA de procesamiento de lenguaje natural.")
    
    return render_template('recomendaciones.html', puntos=puntos, sentimiento=sentimiento, recomendaciones_ia=recomendaciones_ia)

@app.route('/calificar_punto', methods=['POST'])
def calificar_punto():
    if 'usuario_id' not in session:
        return jsonify({'success': False, 'error': 'Inicia sesión para calificar.'})
    
    data = request.get_json()
    location_id = data.get('location_id')
    estrellas = data.get('estrellas')
    comentario = data.get('comentario', '')

    if not location_id or not estrellas or int(estrellas) < 1 or int(estrellas) > 5:
        return jsonify({'success': False, 'error': 'Datos inválidos.'})
    
    try:
        calificacion = CalificacionPunto.query.filter_by(location_id=location_id, usuario_id=session['usuario_id']).first()
        if calificacion:
            calificacion.estrellas = int(estrellas)
            calificacion.comentario = comentario
        else:
            nueva_calificacion = CalificacionPunto(location_id=location_id, usuario_id=session['usuario_id'], estrellas=int(estrellas), comentario=comentario)
            db.session.add(nueva_calificacion)
        db.session.commit()
        
        promedio = db.session.query(func.avg(CalificacionPunto.estrellas)).filter_by(location_id=location_id).scalar()
        total = db.session.query(func.count(CalificacionPunto.id)).filter_by(location_id=location_id).scalar()
        
        return jsonify({
            'success': True,
            'promedio': float(f"{float(promedio or 0):.1f}"),
            'total': total or 0,
            'usuario': session.get('usuario_nombre', 'Voz')
        })
    except Exception as e:
        db.session.rollback()
        return jsonify({'success': False, 'error': 'Error de servidor.'})

@app.route('/api/recomendaciones_ia')
def recomendaciones_ia():
    try:
        puntos = get_puntos_con_promedio()
        return jsonify({'success': True, 'recomendaciones': puntos})
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)})


# ==========================================================================
#  Gestión del perfil de usuario
# ==========================================================================

@app.route('/perfil')
def perfil():
    if 'usuario_id' not in session:
        return redirect(url_for('login'))
        
    usuario = Usuario.query.get(session['usuario_id'])
    if not usuario:
        return redirect(url_for('logout'))
        
    # Sincronización en tiempo real con la Base de Datos (en caso de ediciones desde el panel Laravel)
    session['nombre'] = usuario.nombre
    session['foto_perfil'] = usuario.foto_perfil
    session['ubicacion'] = usuario.ubicacion
    session['titulo_perfil'] = usuario.titulo_perfil
    session['biografia'] = usuario.biografia
    session['intereses'] = usuario.intereses
        
    mis_posts = Foro.query.filter_by(autor_id=usuario.id).order_by(Foro.created_at.desc()).limit(10).all()
    mis_respuestas_raw = db.session.query(RespuestaForo, Foro).join(Foro).filter(RespuestaForo.autor_id==usuario.id).order_by(RespuestaForo.created_at.desc()).limit(10).all()
    
    mis_respuestas = []
    for resp, post in mis_respuestas_raw:
        mis_respuestas.append({
            'contenido': resp.contenido,
            'created_at': resp.created_at,
            'post_id': post.id,
            'post_titulo': post.titulo
        })

    actividades = Actividad.query.filter_by(usuario_id=usuario.id).order_by(Actividad.fecha_creacion.desc()).limit(10).all()

    return render_template('perfil.html', mis_posts=mis_posts, mis_respuestas=mis_respuestas, actividades=actividades, timedelta=timedelta)

@app.route('/editar_perfil', methods=['POST'])
def editar_perfil():
    if 'usuario_id' not in session:
        return redirect(url_for('login'))

    nombre = request.form.get('nombre')
    ubicacion = request.form.get('ubicacion')
    titulo_perfil = request.form.get('titulo_perfil')
    biografia = request.form.get('biografia')
    intereses_lista = request.form.getlist('intereses')
    intereses = ", ".join(intereses_lista) if intereses_lista else ""
    foto_perfil_file = request.files.get('foto_perfil')
    password = request.form.get('password')
    
    if not nombre or not nombre.strip():
        return redirect(url_for('perfil'))
    
    usuario = Usuario.query.get(session['usuario_id'])
    if usuario:
        usuario.nombre = nombre
        usuario.ubicacion = ubicacion
        usuario.titulo_perfil = titulo_perfil
        usuario.biografia = biografia
        usuario.intereses = intereses
        
        if foto_perfil_file and foto_perfil_file.filename:
            extension = foto_perfil_file.filename.split('.')[-1].lower()
            nombre_foto = f"{uuid.uuid4().hex}.{extension}"
            carpeta_destino = os.path.join(app.root_path, 'static', 'img', 'perfiles')
            os.makedirs(carpeta_destino, exist_ok=True)
            ruta_completa = os.path.join(carpeta_destino, nombre_foto)
            foto_perfil_file.save(ruta_completa)
            usuario.foto_perfil = nombre_foto
            session['foto_perfil'] = nombre_foto

        if password and len(password) >= 6:
            import bcrypt
            hashed = bcrypt.hashpw(password.encode('utf-8'), bcrypt.gensalt()).decode('utf-8')
            usuario.password = hashed
            if usuario.auth_provider == 'google':
                usuario.auth_provider = 'google+local'

        db.session.commit()
        db.session.refresh(usuario)
        
        session['nombre'] = usuario.nombre
        session['ubicacion'] = usuario.ubicacion
        session['titulo_perfil'] = usuario.titulo_perfil
        session['biografia'] = usuario.biografia
        session['intereses'] = usuario.intereses

    return redirect(url_for('perfil'))

@app.route('/api/usuarios/me/foto', methods=['PUT', 'POST'])
def actualizar_foto_perfil():
    if 'usuario_id' not in session:
        return jsonify({'success': False, 'error': 'No has iniciado sesión.'}), 401
    
    foto_perfil_file = request.files.get('foto_perfil')
    if not foto_perfil_file or not foto_perfil_file.filename:
        return jsonify({'success': False, 'error': 'No se seleccionó ninguna imagen.'}), 400

    usuario = Usuario.query.get(session['usuario_id'])
    if not usuario:
        return jsonify({'success': False, 'error': 'Usuario no encontrado.'}), 404

    extension = foto_perfil_file.filename.split('.')[-1].lower()
    nombre_foto = f"{uuid.uuid4().hex}.{extension}"
    carpeta_destino = os.path.join(app.root_path, 'static', 'img', 'perfiles')
    os.makedirs(carpeta_destino, exist_ok=True)
    ruta_completa = os.path.join(carpeta_destino, nombre_foto)
    foto_perfil_file.save(ruta_completa)

    usuario.foto_perfil = nombre_foto
    db.session.commit()
    session['foto_perfil'] = nombre_foto

    return jsonify({'success': True, 'foto_perfil': nombre_foto})


# ==========================================================================
#  Foro comunitario
# ==========================================================================

@app.route('/foro')
def foro():
    if 'usuario_id' not in session:
        return redirect(url_for('login'))

    filtro = request.args.get('filtro', 'todos')
    
    if filtro == 'populares':
        posts = Foro.query.outerjoin(LikeForo).outerjoin(RespuestaForo).group_by(Foro.id)\
            .order_by((db.func.count(db.distinct(LikeForo.id)) + db.func.count(db.distinct(RespuestaForo.id))).desc()).all()
    else:
        posts = Foro.query.order_by(Foro.created_at.desc()).all()
    
    categorias = Categoria.query.all()
    
    return render_template('foro.html', posts=posts, categorias=categorias, filtro_activo=filtro)

@app.route('/foro/post/<int:post_id>')
def ver_post(post_id):
    if 'usuario_id' not in session:
        return redirect(url_for('login'))

    post = Foro.query.get(post_id)
    if not post:
        return redirect(url_for('foro'))
        
    respuestas = RespuestaForo.query.filter_by(post_id=post_id).order_by(RespuestaForo.created_at.asc()).all()
    likes_count = LikeForo.query.filter_by(post_id=post_id).count()
    liked = LikeForo.query.filter_by(post_id=post_id, usuario_id=session['usuario_id']).first() is not None
    relacionados = Foro.query.filter(Foro.categoria_id == post.categoria_id, Foro.id != post.id).order_by(Foro.created_at.desc()).limit(2).all()
        
    return render_template('post.html', post=post, respuestas=respuestas, likes_count=likes_count, liked=liked, relacionados=relacionados)

@app.route('/foro/nuevo')
def nuevo_post():
    if 'usuario_id' not in session:
        return redirect(url_for('login'))

    categorias = Categoria.query.all()
    return render_template('nuevo_post.html', categorias=categorias)

@app.route('/crear_post', methods=['POST'])
def crear_post():
    if 'usuario_id' not in session:
        return redirect(url_for('login'))

    titulo = request.form.get('titulo')
    categoria_id = request.form.get('categoria_id')
    contenido = request.form.get('contenido')
    imagen_file = request.files.get('imagen')

    if not titulo or not titulo.strip() or not categoria_id or not contenido or not contenido.strip():
        return redirect(url_for('nuevo_post'))

    nombre_imagen = None
    if imagen_file and imagen_file.filename:
        extension = imagen_file.filename.split('.')[-1].lower()
        nombre_imagen = f"post_{uuid.uuid4().hex}.{extension}"
        carpeta_destino = os.path.join(app.root_path, 'static', 'img', 'posts')
        os.makedirs(carpeta_destino, exist_ok=True)
        ruta_completa = os.path.join(carpeta_destino, nombre_imagen)
        imagen_file.save(ruta_completa)

    nuevo_post = Foro(
        titulo=titulo, 
        contenido=contenido, 
        categoria_id=categoria_id, 
        autor_id=session['usuario_id'], 
        imagen=nombre_imagen
    )
    db.session.add(nuevo_post)
    db.session.commit()

    actividad = Actividad(
        usuario_id=session['usuario_id'],
        tipo='post',
        descripcion=f'Publicó: {titulo[:80]}',
        referencia_id=nuevo_post.id
    )
    db.session.add(actividad)
    db.session.commit()

    return redirect(url_for('foro'))

@app.route('/crear_respuesta/<int:post_id>', methods=['POST'])
def crear_respuesta(post_id):
    if 'usuario_id' not in session:
        return redirect(url_for('login'))

    contenido = request.form.get('contenido')
    if contenido and len(contenido.strip()) > 10:
        respuesta = RespuestaForo(post_id=post_id, autor_id=session['usuario_id'], contenido=contenido)
        db.session.add(respuesta)
        actividad = Actividad(
            usuario_id=session['usuario_id'],
            tipo='respuesta',
            descripcion=f'Respondió en discusión #{post_id}',
            referencia_id=post_id
        )
        db.session.add(actividad)
        
        post = Foro.query.get(post_id)
        if post and post.autor_id != session['usuario_id']:
            noti = Notificacion(
                user_id=post.autor_id,
                titulo='Nuevo comentario en tu foro',
                mensaje=f'Revisar tu post: "{post.titulo[:30]}..."',
                url=url_for('ver_post', post_id=post.id) + '#respuestas'
            )
            db.session.add(noti)

        db.session.commit()

    return redirect(url_for('ver_post', post_id=post_id) + '#respuestas')

@app.route('/like_post/<int:post_id>', methods=['POST'])
def like_post(post_id):
    if 'usuario_id' not in session:
        return jsonify({'success': False, 'error': 'Inicia sesión para interactuar.'})

    usuario_id = session['usuario_id']
    like = LikeForo.query.filter_by(post_id=post_id, usuario_id=usuario_id).first()

    if like:
        db.session.delete(like)
        action = 'unliked'
    else:
        nuevo_like = LikeForo(post_id=post_id, usuario_id=usuario_id)
        db.session.add(nuevo_like)
        action = 'liked'
        
        # Notificar al autor del post
        post = Foro.query.get(post_id)
        if post and post.autor_id != usuario_id:
            usuario = Usuario.query.get(usuario_id)
            nombre_liker = usuario.nombre if usuario else 'Alguien'
            noti = Notificacion(
                user_id=post.autor_id,
                titulo='❤️ Le gustó tu publicación',
                mensaje=f'{nombre_liker} reaccionó a tu post: "{post.titulo[:30]}..."',
                url=url_for('ver_post', post_id=post.id)
            )
            db.session.add(noti)
        
    db.session.commit()
    total_likes = LikeForo.query.filter_by(post_id=post_id).count()

    return jsonify({'success': True, 'action': action, 'likes': total_likes, 'total': total_likes, 'liked': action == 'liked'})


# ==========================================================================
#  Notificaciones 
# ==========================================================================

@app.route('/api/notificaciones/unread')
def get_unread_notificaciones():
    if 'usuario_id' not in session:
        return jsonify({'success': False, 'error': 'No autenticado.'}), 401
    unread = Notificacion.query.filter_by(user_id=session['usuario_id'], leida=False).order_by(Notificacion.created_at.desc()).all()
    lista = [{'id': n.id, 'titulo': n.titulo, 'mensaje': n.mensaje, 'url': n.url, 'created_at': n.created_at.strftime("%d %b, %H:%M")} for n in unread]
    return jsonify({'success': True, 'count': len(lista), 'data': lista})

@app.route('/api/notificaciones/mark_read', methods=['POST'])
def mark_read_notificaciones():
    if 'usuario_id' not in session:
        return jsonify({'success': False})
    Notificacion.query.filter_by(user_id=session['usuario_id'], leida=False).update({'leida': True})
    db.session.commit()
    return jsonify({'success': True})

# ==========================================================================
#  Autenticación de usuarios
# ==========================================================================

@app.route('/logout')
def logout():
    session.clear()
    return redirect(url_for('login'))

@app.route('/handle_login', methods=['POST'])
def handle_login():
    email_usuario = request.form.get('email')
    password_usuario = request.form.get('password')

    if not email_usuario or not password_usuario:
        return jsonify({'success': False, 'error': 'El correo y la contraseña son obligatorios.'})

    usuario = Usuario.query.filter_by(email=email_usuario).first()

    valido = False
    if usuario:
        # Comparación directa para contraseñas sin hash (compatibilidad legacy)
        if usuario.password == password_usuario:
            valido = True
        elif usuario.password.startswith("$2y$") or usuario.password.startswith("$2b$") or usuario.password.startswith("$2a$"):
            try:
                import bcrypt
                hash_comparable = usuario.password.replace('$2y$', '$2b$', 1).encode('utf-8')
                if bcrypt.checkpw(password_usuario.encode('utf-8'), hash_comparable):
                    valido = True
            except ImportError:
                pass
            except Exception:
                pass
        elif check_password_hash(usuario.password, password_usuario):
            valido = True

    if valido:
        remember = request.form.get('remember-me')
        if remember == 'on':
            session.permanent = True
            app.permanent_session_lifetime = timedelta(days=30)
        else:
            session.permanent = False

        session['usuario_id'] = usuario.id
        session['nombre'] = usuario.nombre
        session['email'] = usuario.email
        session['foto_perfil'] = usuario.foto_perfil
        session['titulo_perfil'] = usuario.titulo_perfil
        session['biografia'] = usuario.biografia
        session['ubicacion'] = usuario.ubicacion
        session['intereses'] = usuario.intereses
            
        return jsonify({'success': True, 'redirect': url_for('index')})
    else:
        return jsonify({'success': False, 'error': 'Correo o contraseña incorrectos.'})

@app.route('/handle_registro', methods=['POST'])
def handle_registro():
    nombre_usuario = request.form.get('nombre')
    email_usuario = request.form.get('email')
    password_usuario = request.form.get('password')
    foto_perfil_file = request.files.get('foto_perfil')

    if not nombre_usuario or not email_usuario or not password_usuario:
        return jsonify({'success': False, 'error': 'Todos los campos son obligatorios.'})
        
    if len(nombre_usuario.strip()) <= 10:
        return jsonify({'success': False, 'error': 'El nombre completo debe tener más de 10 caracteres.'})
        
    if len(password_usuario) < 6:
        return jsonify({'success': False, 'error': 'La contraseña debe tener al menos 6 caracteres.'})

    if not foto_perfil_file:
        return jsonify({'success': False, 'error': 'La foto de perfil es obligatoria.'})

    existe = Usuario.query.filter_by(email=email_usuario).first()
    if existe:
        return jsonify({'success': False, 'error': 'El correo electrónico ya está registrado.'})

    extension = foto_perfil_file.filename.split('.')[-1].lower()
    nombre_foto = f"{uuid.uuid4().hex}.{extension}"
    carpeta_destino = os.path.join(app.root_path, 'static', 'img', 'perfiles')
    os.makedirs(carpeta_destino, exist_ok=True)
    ruta_completa = os.path.join(carpeta_destino, nombre_foto)
    foto_perfil_file.save(ruta_completa)

    # Usar bcrypt para compatibilidad con Laravel
    import bcrypt
    password_hasheado = bcrypt.hashpw(password_usuario.encode('utf-8'), bcrypt.gensalt()).decode('utf-8')

    nuevo_usuario = Usuario(nombre=nombre_usuario, email=email_usuario, password=password_hasheado, foto_perfil=nombre_foto)
    db.session.add(nuevo_usuario)
    db.session.commit()

    session['usuario_id'] = nuevo_usuario.id
    session['nombre'] = nuevo_usuario.nombre
    session['email'] = nuevo_usuario.email
    session['foto_perfil'] = nuevo_usuario.foto_perfil
    session['titulo_perfil'] = nuevo_usuario.titulo_perfil
    session['biografia'] = nuevo_usuario.biografia
    session['ubicacion'] = nuevo_usuario.ubicacion
    session['intereses'] = nuevo_usuario.intereses

    return jsonify({'success': True, 'redirect': url_for('index')})

# ==========================================================================
#  IA y NLP (Microservicio)
# ==========================================================================

@app.route('/ia/recomendaciones')
def vista_recomendaciones_ia():
    if 'usuario_id' not in session:
        return redirect(url_for('login'))

    try:
        sentimiento = http_requests.get('http://fastapi_app:6000/analisis/sentimiento', timeout=10).json().get('data', {"POS": 0, "NEG": 0, "NEU": 0, "total": 0})
    except Exception:
        sentimiento = {"POS": 0, "NEG": 0, "NEU": 0, "total": 0}
    
    max_sent = max(sentimiento, key=lambda k: sentimiento[k] if k != "total" else -1)
    
    if max_sent == "NEG":
        msgs = [
            "Notamos frustración con el reciclaje en la comunidad, revisa esta guía básica para no estresarte.",
            "Muchos usuarios reportan falta de centros de acopio. ¡Conoce nuestro mapa interactivo!",
            "¿Dudas con la separación de residuos? Únete a los próximos talleres gratuitos en Querétaro."
        ]
    elif max_sent == "POS":
        msgs = [
            "¡Gran entusiasmo en nuestra comunidad! Sigue compartiendo tus logros Zero Waste.",
            "Aprovecha esta motivación colectiva para organizar una brigada de limpieza.",
            "Tus casos de éxito inspiran: publica fotos de tu compostera y etiqueta tus consejos."
        ]
    else:
        msgs = [
            "Infórmate sobre nuevas regulaciones de plásticos de un solo uso en la región.",
            "Mantén el equilibrio: reduce, reutiliza y recicla.",
            "Descubre eventos locales de sustentabilidad este mes y asiste con tus amigos."
        ]

    return render_template('recomendaciones.html', sentimiento=sentimiento, recomendaciones_ia=msgs, puntos=get_puntos_con_promedio())

# ==========================================================================
#  Manejadores de errores HTTP
# ==========================================================================

@app.errorhandler(404)
def page_not_found(e):
    return render_template('404.html'), 404

@app.errorhandler(500)
def internal_server_error(e):
    return render_template('500.html'), 500

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=True)