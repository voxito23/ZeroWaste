from flask import Flask, render_template, request, redirect, url_for, jsonify, session
from flask_cors import CORS
from werkzeug.security import check_password_hash, generate_password_hash
import os
import uuid
from sqlalchemy.sql import func
from datetime import datetime, timedelta

try:
    import google_auth_oauthlib.flow
    import requests
except ImportError:
    pass

os.environ['OAUTHLIB_INSECURE_TRANSPORT'] = '1'
CLIENT_SECRETS_FILE = "client_secret.json"
SCOPES = ['https://www.googleapis.com/auth/userinfo.email', 'https://www.googleapis.com/auth/userinfo.profile', 'openid']

from models import db, Usuario, Categoria, PuntoMapa, CalificacionPunto, Evento, Foro, RespuestaForo, LikeForo

app = Flask(__name__)
CORS(app, supports_credentials=True)
app.secret_key = 'super_secreta_zerowaste_2026'

# Configuración de SQLAlchemy
app.config['SQLALCHEMY_DATABASE_URI'] = os.environ.get('DATABASE_URL', 'postgresql://postgres:postgrespassword@127.0.0.1:5432/zerowaste_db')
app.config['SQLALCHEMY_TRACK_MODIFICATIONS'] = False

db.init_app(app)

with app.app_context():
    db.create_all()

# ==========================================================================
#  Rutas de navegación básica
# ==========================================================================

@app.route('/')
def root():
    return render_template('login.html')

@app.route('/login')
def login():
    return render_template('login.html')

@app.route('/registro')
def registro():
    return render_template('registro.html')

@app.route('/login/google')
def login_google():
    try:
        flow = google_auth_oauthlib.flow.Flow.from_client_secrets_file(
            CLIENT_SECRETS_FILE, scopes=SCOPES)
        flow.redirect_uri = url_for('callback_google', _external=True)
        authorization_url, state = flow.authorization_url(
            access_type='offline',
            include_granted_scopes='true')
        session['state'] = state
        return redirect(authorization_url)
    except Exception as e:
        print(f"Redirigiendo a inicio de sesión simulado por ausencia de client_secret.json: {e}")
        return redirect(url_for('callback_google_dummy'))

@app.route('/callback/google')
def callback_google():
    state = session.get('state')
    try:
        flow = google_auth_oauthlib.flow.Flow.from_client_secrets_file(
            CLIENT_SECRETS_FILE, scopes=SCOPES, state=state)
        flow.redirect_uri = url_for('callback_google', _external=True)
        
        authorization_response = request.url
        flow.fetch_token(authorization_response=authorization_response)
        
        credentials = flow.credentials
        response = requests.get('https://www.googleapis.com/oauth2/v1/userinfo', headers={'Authorization': f'Bearer {credentials.token}'})
        user_info = response.json()
        
        email = user_info.get('email')
        name = user_info.get('name')
        picture = user_info.get('picture', 'perfil_default.png')
        
        usuario = Usuario.query.filter_by(email=email).first()
        if not usuario:
            usuario = Usuario(
                nombre=name,
                email=email,
                password=generate_password_hash(str(uuid.uuid4())),
                foto_perfil=picture
            )
            db.session.add(usuario)
            db.session.commit()
            
        session['usuario_id'] = usuario.id
        session['usuario_nombre'] = usuario.nombre
        session['usuario_foto'] = usuario.foto_perfil
        return redirect(url_for('index'))
    except Exception as e:
        print(f"Error en la autenticación de Google: {e}")
        return redirect(url_for('login', error='google_auth_failed'))

@app.route('/callback/google/dummy')
def callback_google_dummy():
    email = "google.user@example.com"
    name = "Usuario Google (Dummy)"
    picture = "https://cdn-icons-png.flaticon.com/512/2991/2991148.png"
    
    usuario = Usuario.query.filter_by(email=email).first()
    if not usuario:
        usuario = Usuario(
            nombre=name, 
            email=email, 
            password=generate_password_hash(str(uuid.uuid4())), 
            foto_perfil=picture
        )
        db.session.add(usuario)
        db.session.commit()
        
    session['usuario_id'] = usuario.id
    session['usuario_nombre'] = usuario.nombre
    session['usuario_foto'] = usuario.foto_perfil
    return redirect(url_for('index'))

@app.route('/inicio')
def index():
    if 'usuario_id' not in session:
        return redirect(url_for('login'))
    
    eventos = Evento.query.order_by(db.desc(Evento.fecha_inicio)).limit(3).all()
    return render_template('index.html', eventos=eventos)

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
        puntos.append({
            'id': punto.id,
            'nombre': punto.nombre,
            'direccion': punto.direccion,
            'latitud': float(punto.latitud),
            'longitud': float(punto.longitud),
            'tipo': punto.tipo,
            'materiales': punto.materiales,
            'promedio': float(f"{float(promedio or 0):.1f}"),
            'total_reviews': total
        })
    return puntos

@app.route('/mapa')
def mapa():
    puntos = get_puntos_con_promedio()
    return render_template('mapa.html', puntos=puntos)

@app.route('/recomendaciones')
def recomendaciones():
    puntos = get_puntos_con_promedio()
    return render_template('recomendaciones.html', puntos=puntos)

@app.route('/calificar_punto', methods=['POST'])
def calificar_punto():
    if 'usuario_id' not in session:
        return jsonify({'success': False, 'error': 'Inicia sesión para calificar.'})
    
    data = request.get_json()
    location_id = data.get('location_id')
    estrellas = data.get('estrellas')
    
    if not location_id or not estrellas or int(estrellas) < 1 or int(estrellas) > 5:
        return jsonify({'success': False, 'error': 'Datos inválidos.'})
    
    try:
        calificacion = CalificacionPunto.query.filter_by(location_id=location_id, usuario_id=session['usuario_id']).first()
        if calificacion:
            calificacion.estrellas = int(estrellas)
        else:
            nueva_calificacion = CalificacionPunto(location_id=location_id, usuario_id=session['usuario_id'], estrellas=int(estrellas))
            db.session.add(nueva_calificacion)
        db.session.commit()
        
        promedio = db.session.query(func.avg(CalificacionPunto.estrellas)).filter_by(location_id=location_id).scalar()
        total = db.session.query(func.count(CalificacionPunto.id)).filter_by(location_id=location_id).scalar()
        
        return jsonify({
            'success': True,
            'promedio': float(f"{float(promedio or 0):.1f}"),
            'total': total or 0
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

    return render_template('perfil.html', mis_posts=mis_posts, mis_respuestas=mis_respuestas)

@app.route('/editar_perfil', methods=['POST'])
def editar_perfil():
    if 'usuario_id' not in session:
        return redirect(url_for('login'))

    nombre = request.form.get('nombre')
    ubicacion = request.form.get('ubicacion')
    titulo_perfil = request.form.get('titulo_perfil')
    biografia = request.form.get('biografia')
    
    # CRÍTICO: Recibir múltiples checkboxes
    intereses_lista = request.form.getlist('intereses')
    intereses = ", ".join(intereses_lista) if intereses_lista else ""

    foto_perfil_file = request.files.get('foto_perfil')
    
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
            import uuid
            nombre_foto = f"{uuid.uuid4().hex}.{extension}"
            carpeta_destino = os.path.join(app.root_path, 'static', 'img', 'perfiles')
            os.makedirs(carpeta_destino, exist_ok=True)
            ruta_completa = os.path.join(carpeta_destino, nombre_foto)
            foto_perfil_file.save(ruta_completa)
            usuario.foto_perfil = nombre_foto
            session['foto_perfil'] = nombre_foto

        db.session.commit()
        db.session.refresh(usuario) # <--- Asegura que los datos se recarguen de la BD
        
        session['nombre'] = usuario.nombre
        session['ubicacion'] = usuario.ubicacion
        session['titulo_perfil'] = usuario.titulo_perfil
        session['biografia'] = usuario.biografia
        session['intereses'] = usuario.intereses

    return redirect(url_for('perfil'))


# ==========================================================================
#  Foro comunitario
# ==========================================================================

@app.route('/foro')
def foro():
    if 'usuario_id' not in session:
        return redirect(url_for('login'))

    posts = Foro.query.order_by(Foro.created_at.desc()).all()
    categorias = Categoria.query.all()
    
    return render_template('foro.html', posts=posts, categorias=categorias)

@app.route('/foro/post/<int:post_id>')
def ver_post(post_id):
    if 'usuario_id' not in session:
        return redirect(url_for('login'))

    post = Foro.query.get(post_id)
    if not post:
        return redirect(url_for('foro'))
        
    respuestas = RespuestaForo.query.filter_by(post_id=post_id).order_by(RespuestaForo.created_at.asc()).all()
    likes_count = LikeForo.query.filter_by(post_id=post_id).count()
        
    return render_template('post.html', post=post, respuestas=respuestas, likes_count=likes_count)

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

    return redirect(url_for('foro'))

@app.route('/crear_respuesta/<int:post_id>', methods=['POST'])
def crear_respuesta(post_id):
    if 'usuario_id' not in session:
        return redirect(url_for('login'))

    contenido = request.form.get('contenido')
    if contenido and len(contenido.strip()) > 10:
        respuesta = RespuestaForo(post_id=post_id, autor_id=session['usuario_id'], contenido=contenido)
        db.session.add(respuesta)
        db.session.commit()

    return redirect(url_for('ver_post', post_id=post_id))

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
        
    db.session.commit()
    total_likes = LikeForo.query.filter_by(post_id=post_id).count()

    return jsonify({'success': True, 'action': action, 'likes': total_likes})


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

    # Verificación de contraseña con compatibilidad para registros legacy
    valido = False
    if usuario:
        # Comparación directa para contraseñas sin hash (compatibilidad legacy)
        if usuario.password == password_usuario:
            valido = True
        elif check_password_hash(usuario.password, password_usuario):
            valido = True

    if valido:
        # Configuración de sesión persistente
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

    password_hasheado = generate_password_hash(password_usuario, method='pbkdf2:sha256', salt_length=8)

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
#  Manejadores de errores HTTP
# ==========================================================================

@app.errorhandler(404)
def page_not_found(e):
    # Renderiza la plantilla personalizada para error 404
    return render_template('404.html'), 404

@app.errorhandler(500)
def internal_server_error(e):
    # Renderiza la plantilla personalizada para error 500
    return render_template('500.html'), 500

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=True)