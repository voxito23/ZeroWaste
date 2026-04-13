from flask import Flask, render_template, request, redirect, url_for, jsonify, session
from flask_cors import CORS
from flask_mail import Mail, Message as MailMessage
from flask_limiter import Limiter
from flask_limiter.util import get_remote_address
from werkzeug.security import check_password_hash, generate_password_hash
from markupsafe import escape
import os
import re
import uuid
import string
import random
from sqlalchemy.sql import func
from datetime import datetime, timedelta
import requests as http_requests

# Solo permitir OAuth inseguro en desarrollo local
if os.environ.get('FLASK_DEBUG', 'false').lower() == 'true':
    os.environ['OAUTHLIB_INSECURE_TRANSPORT'] = '1'

from models import (db, Usuario, Categoria, PuntoMapa, CalificacionPunto,
                     Evento, Foro, RespuestaForo, LikeForo, Actividad,
                     Campaign, ContactMessage, ContactReply, PasswordResetRequest, Notificacion)
# IA delegada a FastAPI

# ==========================================================================
#  Constantes de Seguridad
# ==========================================================================
ALLOWED_IMAGE_EXTENSIONS = {'png', 'jpg', 'jpeg', 'gif', 'webp'}
MAX_UPLOAD_SIZE_MB = 50
EMAIL_REGEX = re.compile(r'^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$')

def allowed_file(filename):
    """Valida que la extensión del archivo sea una imagen permitida."""
    return '.' in filename and filename.rsplit('.', 1)[1].lower() in ALLOWED_IMAGE_EXTENSIONS

def sanitize_text(text, max_length=500):
    """Sanitiza texto de usuario para prevenir XSS."""
    if not text:
        return ''
    return str(escape(text.strip()))[:max_length]

# ==========================================================================
#  Inicialización de la App
# ==========================================================================
app = Flask(__name__)
CORS(app, supports_credentials=True, origins=[
    'http://localhost:5001',
    'http://localhost:8001',
    'http://167.99.239.121:5001',
    'http://167.99.239.121:8001',
])

# Secretos desde variables de entorno (NUNCA hardcoded)
app.secret_key = os.environ.get('SECRET_KEY', os.urandom(32).hex())
app.permanent_session_lifetime = timedelta(days=30)
app.config['SESSION_COOKIE_NAME'] = 'zerowaste_session'
app.config['SESSION_COOKIE_HTTPONLY'] = True
app.config['SESSION_COOKIE_SAMESITE'] = 'Lax'
app.config['SESSION_COOKIE_SECURE'] = os.environ.get('HTTPS_ENABLED', 'false').lower() == 'true'
app.config['MAX_CONTENT_LENGTH'] = MAX_UPLOAD_SIZE_MB * 1024 * 1024  # 5MB max upload
app.config['PERMANENT_SESSION_LIFETIME'] = timedelta(days=30)  # Para "Recordarme"

app.config['SQLALCHEMY_DATABASE_URI'] = os.environ.get('DATABASE_URL', 'postgresql://postgres:postgrespassword@127.0.0.1:5432/zerowaste_db')
app.config['SQLALCHEMY_TRACK_MODIFICATIONS'] = False

# ===== Rate Limiter =====
limiter = Limiter(
    key_func=get_remote_address,
    app=app,
    default_limits=["200 per minute"],
    storage_uri="memory://",
)

# ===== Configuración Flask-Mail (Gmail SMTP) =====
app.config['MAIL_SERVER'] = 'smtp.gmail.com'
app.config['MAIL_PORT'] = 587
app.config['MAIL_USE_TLS'] = True
app.config['MAIL_USE_SSL'] = False
app.config['MAIL_USERNAME'] = os.environ.get('MAIL_USERNAME')
app.config['MAIL_PASSWORD'] = os.environ.get('MAIL_PASSWORD')
app.config['MAIL_DEFAULT_SENDER'] = ('Zero Waste', os.environ.get('MAIL_USERNAME', 'noreply@zerowaste.com'))
app.config['MAIL_TIMEOUT'] = 5

mail = Mail(app)

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
    if 'usuario_id' in session:
        return redirect(url_for('index'))
    return render_template('login.html')

@app.route('/login', methods=['GET', 'POST'])
@limiter.limit("10/minute")
def login():
    if request.method == 'POST':
        email = request.form.get('email', '').strip()
        password = request.form.get('password', '')
        remember = request.form.get('remember-me') == 'on'
        
        usuario = Usuario.query.filter_by(email=email).first()
        if not usuario:
            return jsonify({'success': False, 'error': 'Credenciales inválidas.'}), 401
            
        import bcrypt
        if isinstance(usuario.password, str) and bcrypt.checkpw(password.encode('utf-8'), usuario.password.encode('utf-8')):
            if remember:
                session.permanent = True
            else:
                session.permanent = False
            session['usuario_id'] = usuario.id
            session['nombre'] = usuario.nombre
            session['email'] = usuario.email
            session['foto_perfil'] = usuario.foto_perfil
            session['profile_completed'] = usuario.profile_completed
            return jsonify({'success': True, 'redirect': url_for('index')})
        else:
            return jsonify({'success': False, 'error': 'Credenciales inválidas.'}), 401
            
    if 'usuario_id' in session:
        return redirect(url_for('index'))
        
    return render_template('login.html')

@app.route('/registro', methods=['GET', 'POST'])
@limiter.limit("5/minute")
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
        app.logger.error(f'Error autenticación Firebase: {e}')
        return jsonify({'success': False, 'error': 'Error de verificación. Intenta de nuevo.'}), 500


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

    if nombre:
        if len(nombre) < 10: return redirect(url_for('completar_perfil_view'))
        usuario.nombre = nombre[:20]
    if ubicacion:
        usuario.ubicacion = ubicacion[:20]
    if titulo_perfil:
        usuario.titulo_perfil = titulo_perfil[:30]
    if biografia:
        usuario.biografia = biografia[:100]
    if intereses:
        usuario.intereses = intereses

    # Contraseña local opcional (bcrypt para compatibilidad con Laravel)
    if password_local and len(password_local) >= 6:
        import bcrypt
        hashed = bcrypt.hashpw(password_local.encode('utf-8'), bcrypt.gensalt()).decode('utf-8')
        usuario.password = hashed
        if usuario.auth_provider == 'google':
            usuario.auth_provider = 'google+local'

    # Foto de perfil (validación de extensión)
    if foto_perfil_file and foto_perfil_file.filename:
        if not allowed_file(foto_perfil_file.filename):
            return redirect(url_for('completar_perfil_view'))
        extension = foto_perfil_file.filename.rsplit('.', 1)[1].lower()
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
    return render_template('Acercade.html')

@app.route('/contacto')
def contacto():
    return render_template('contacto.html')


# ==========================================================================
#  API: Contacto y Recuperación de Contraseña
# ==========================================================================

def generar_password_temporal(longitud=8):
    """Genera una contraseña temporal alfanumérica legible."""
    caracteres = string.ascii_uppercase + string.digits
    # Evitar caracteres confusos (0/O, 1/I/l)
    caracteres = caracteres.replace('O', '').replace('0', '').replace('I', '').replace('1', '').replace('l', '')
    return ''.join(random.choices(caracteres, k=longitud))

@app.route('/forgot-password', methods=['POST'])
@limiter.limit("3/minute")
def forgot_password():
    """Genera contraseña temporal, la envía por email real."""
    data = request.get_json()
    email = (data.get('email', '') if data else '').strip().lower()

    # Validar formato de email
    import re
    email_regex = r'^[^\s@]+@[^\s@]+\.[^\s@]{2,}$'
    if not email or not re.match(email_regex, email):
        return jsonify({'success': False, 'error': 'Ingresa un correo electrónico válido con @ y dominio.'}), 400

    # Verificar que exista en la BD
    usuario = Usuario.query.filter(func.lower(Usuario.email) == email).first()
    if not usuario:
        return jsonify({'success': False, 'error': 'Este correo no está registrado en Zero Waste.'}), 404

    # Invalidar solicitudes anteriores del mismo email
    PasswordResetRequest.query.filter_by(email=email, usado=False).update({'usado': True})
    db.session.commit()

    # Generar contraseña temporal
    temp_password = generar_password_temporal(8)
    import bcrypt
    temp_hash = bcrypt.hashpw(temp_password.encode('utf-8'), bcrypt.gensalt()).decode('utf-8')

    # Guardar en BD con expiración de 15 minutos
    nueva_solicitud = PasswordResetRequest(
        email=email,
        temp_password_hash=temp_hash,
        expires_at=datetime.utcnow() + timedelta(minutes=15),
        usado=False,
        estado='enviado'
    )
    db.session.add(nueva_solicitud)
    db.session.commit()

    # Enviar email con la contraseña temporal
    try:
        html_body = f"""
        <table cellpadding="0" cellspacing="0" border="0" width="100%" style="background: #f3f4f6;">
            <tr>
                <td align="center" style="padding: 24px 12px;">
                    <table cellpadding="0" cellspacing="0" border="0" width="560" style="max-width: 560px; width: 100%; background: #ffffff; border-radius: 16px; overflow: hidden;">
                        
                        <!-- HEADER -->
                        <tr>
                            <td style="background-color: #064E3B; padding: 36px 24px 28px; text-align: center;">
                                <img src="cid:zerowaste_logo" alt="Zero Waste" width="64" height="64" style="display: block; margin: 0 auto 16px; border-radius: 14px;">
                                <h1 style="color: #ffffff; margin: 0 0 10px; font-size: 26px; font-weight: 800; letter-spacing: 2px; font-family: 'Segoe UI', Roboto, Arial, sans-serif;">ZERO WASTE</h1>
                                <table cellpadding="0" cellspacing="0" border="0" style="margin: 0 auto 14px;">
                                    <tr><td style="width: 50px; height: 3px; background-color: #00E096; border-radius: 2px;"></td></tr>
                                </table>
                                <p style="color: #a7f3d0; margin: 0; font-size: 12px; letter-spacing: 1.5px; text-transform: uppercase; font-family: 'Segoe UI', Roboto, Arial, sans-serif;">Recuperaci&oacute;n de Contrase&ntilde;a</p>
                            </td>
                        </tr>
                        
                        <!-- BODY -->
                        <tr>
                            <td style="padding: 32px 24px 24px;">
                                <p style="color: #1f2937; font-size: 16px; margin: 0 0 6px; font-family: 'Segoe UI', Roboto, Arial, sans-serif;">Hola <strong style="color: #064E3B;">{usuario.nombre}</strong>,</p>
                                <p style="color: #6b7280; font-size: 14px; line-height: 1.7; margin: 0 0 24px; font-family: 'Segoe UI', Roboto, Arial, sans-serif;">Recibimos tu solicitud para recuperar tu contrase&ntilde;a. Usa la siguiente contrase&ntilde;a temporal para iniciar sesi&oacute;n:</p>
                                
                                <!-- CODIGO TEMPORAL -->
                                <table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin: 0 0 24px;">
                                    <tr>
                                        <td style="background-color: #f0fdf4; border: 1px solid #d1fae5; border-radius: 12px; padding: 24px 16px; text-align: center;">
                                            <p style="color: #9ca3af; font-size: 11px; margin: 0 0 12px; text-transform: uppercase; letter-spacing: 2px; font-weight: 600; font-family: 'Segoe UI', Roboto, Arial, sans-serif;">Tu contrase&ntilde;a temporal</p>
                                            <table cellpadding="0" cellspacing="0" border="0" style="margin: 0 auto;">
                                                <tr>
                                                    <td style="background-color: #ffffff; border: 2px solid #10b981; border-radius: 10px; padding: 14px 24px;">
                                                        <p style="color: #064E3B; font-size: 32px; font-weight: 900; margin: 0; letter-spacing: 6px; font-family: 'Courier New', 'Lucida Console', monospace;">{temp_password}</p>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                                
                                <!-- WARNING -->
                                <table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin: 0 0 24px;">
                                    <tr>
                                        <td style="background-color: #fef3c7; border: 1px solid #fde68a; border-radius: 10px; padding: 14px 16px;">
                                            <table cellpadding="0" cellspacing="0" border="0" width="100%">
                                                <tr>
                                                    <td width="30" style="vertical-align: top; font-size: 18px;">&#9201;</td>
                                                    <td style="vertical-align: top;">
                                                        <p style="color: #92400e; font-size: 13px; font-weight: 700; margin: 0 0 2px; font-family: 'Segoe UI', Roboto, Arial, sans-serif;">Esta contrase&ntilde;a expira en 15 minutos.</p>
                                                        <p style="color: #a16207; font-size: 12px; margin: 0; font-family: 'Segoe UI', Roboto, Arial, sans-serif;">Si no solicitaste este cambio, ignora este correo.</p>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                                
                                <!-- INSTRUCCIONES -->
                                <p style="color: #1f2937; font-size: 14px; font-weight: 700; margin: 0 0 14px; font-family: 'Segoe UI', Roboto, Arial, sans-serif;">&iquest;C&oacute;mo usar tu c&oacute;digo?</p>
                                <table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin: 0 0 24px;">
                                    <tr>
                                        <td width="36" style="padding: 6px 10px 6px 0; vertical-align: top;">
                                            <table cellpadding="0" cellspacing="0" border="0"><tr><td style="width: 28px; height: 28px; background-color: #10b981; border-radius: 14px; color: #ffffff; font-size: 13px; font-weight: 700; text-align: center; line-height: 28px; font-family: Arial, sans-serif;">1</td></tr></table>
                                        </td>
                                        <td style="padding: 6px 0; vertical-align: middle; color: #4b5563; font-size: 13px; line-height: 1.5; font-family: 'Segoe UI', Roboto, Arial, sans-serif;">Ve a la <strong>p&aacute;gina de inicio de sesi&oacute;n</strong></td>
                                    </tr>
                                    <tr>
                                        <td width="36" style="padding: 6px 10px 6px 0; vertical-align: top;">
                                            <table cellpadding="0" cellspacing="0" border="0"><tr><td style="width: 28px; height: 28px; background-color: #10b981; border-radius: 14px; color: #ffffff; font-size: 13px; font-weight: 700; text-align: center; line-height: 28px; font-family: Arial, sans-serif;">2</td></tr></table>
                                        </td>
                                        <td style="padding: 6px 0; vertical-align: middle; color: #4b5563; font-size: 13px; line-height: 1.5; font-family: 'Segoe UI', Roboto, Arial, sans-serif;">Ingresa tu correo y esta <strong>contrase&ntilde;a temporal</strong></td>
                                    </tr>
                                    <tr>
                                        <td width="36" style="padding: 6px 10px 6px 0; vertical-align: top;">
                                            <table cellpadding="0" cellspacing="0" border="0"><tr><td style="width: 28px; height: 28px; background-color: #10b981; border-radius: 14px; color: #ffffff; font-size: 13px; font-weight: 700; text-align: center; line-height: 28px; font-family: Arial, sans-serif;">3</td></tr></table>
                                        </td>
                                        <td style="padding: 6px 0; vertical-align: middle; color: #4b5563; font-size: 13px; line-height: 1.5; font-family: 'Segoe UI', Roboto, Arial, sans-serif;">Se te pedir&aacute; <strong>crear una nueva contrase&ntilde;a</strong></td>
                                    </tr>
                                </table>
                                
                                <!-- DIVIDER -->
                                <table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin: 0 0 16px;">
                                    <tr><td style="border-top: 1px solid #e5e7eb; font-size: 0; line-height: 0;">&nbsp;</td></tr>
                                </table>
                                <p style="color: #9ca3af; font-size: 11px; text-align: center; margin: 0; line-height: 1.6; font-family: 'Segoe UI', Roboto, Arial, sans-serif;">Este correo fue enviado autom&aacute;ticamente. Si tienes dudas, cont&aacute;ctanos desde la secci&oacute;n de Contacto en nuestra plataforma.</p>
                            </td>
                        </tr>
                        
                        <!-- FOOTER -->
                        <tr>
                            <td style="background-color: #022C22; padding: 20px 24px; text-align: center;">
                                <table cellpadding="0" cellspacing="0" border="0" style="margin: 0 auto;">
                                    <tr>
                                        <td style="vertical-align: middle; padding-right: 8px;">
                                            <img src="cid:zerowaste_logo" alt="ZW" width="20" height="20" style="display: block; border-radius: 4px;">
                                        </td>
                                        <td style="vertical-align: middle;">
                                            <p style="color: #6ee7b7; font-size: 12px; font-weight: 600; margin: 0; font-family: 'Segoe UI', Roboto, Arial, sans-serif;">Zero Waste</p>
                                        </td>
                                    </tr>
                                </table>
                                <p style="color: #6ee7b7; font-size: 11px; margin: 8px 0 0; font-family: 'Segoe UI', Roboto, Arial, sans-serif;">Clasificar y reciclar para un futuro m&aacute;s verde &middot; &copy; 2026</p>
                            </td>
                        </tr>
                        
                    </table>
                </td>
            </tr>
        </table>
        """
        msg = MailMessage(
            subject='🔑 Tu contraseña temporal — Zero Waste',
            recipients=[email],
            html=html_body
        )
        # Adjuntar logo como imagen inline (CID) para que Gmail lo muestre
        logo_path = os.path.join(app.root_path, 'static', 'img', 'logo_texture.png')
        with open(logo_path, 'rb') as logo_file:
            msg.attach(
                'logo_texture.png',
                'image/png',
                logo_file.read(),
                'inline',
                headers={'Content-ID': '<zerowaste_logo>'}
            )
        print(f"==================================", flush=True)
        print(f"[RECOVERY] EMAIL: {email}", flush=True)
        print(f"[RECOVERY] PASSWORD. TEMP: {temp_password}", flush=True)
        print(f"==================================", flush=True)

        import threading
        def send_async_email(app_instance, msg):
            with app_instance.app_context():
                try:
                    mail.send(msg)
                except Exception as e:
                    app_instance.logger.error(f'Error enviando email asíncrono de recuperación: {e}')

        thread = threading.Thread(target=send_async_email, args=(app, msg))
        thread.start()

    except Exception as e:
        app.logger.error(f'Error preparando email de recuperación: {e}')
        return jsonify({'success': False, 'error': 'Error interno preparando el correo.'}), 500

    # Siempre retornamos éxito instantáneamente; el envío real ocurre en background o DO lo bloquea silenciosamente en el log.
    return jsonify({'success': True, 'message': 'Se envió una contraseña temporal a tu correo.'})

@app.route('/ajax/mis_mensajes_contacto')
def mis_mensajes_contacto():
    """Devuelve los mensajes de contacto del usuario logueado con hilo de respuestas."""
    if 'usuario_id' not in session:
        return jsonify({'success': False, 'error': 'No autenticado.'}), 401
    
    usuario = Usuario.query.get(session['usuario_id'])
    if not usuario:
        return jsonify({'success': False, 'error': 'Usuario no encontrado.'}), 404
    
    mensajes = ContactMessage.query.filter(
        db.or_(ContactMessage.email == usuario.email, ContactMessage.usuario_id == usuario.id)
    ).order_by(ContactMessage.created_at.desc()).all()
    
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


@app.route('/ajax/responder_contacto/<int:msg_id>', methods=['POST'])
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
        sentimiento = http_requests.get('http://fastapi_app:6000/analisis/sentimiento', timeout=30).json().get('data', {"POS": 0, "NEG": 0, "NEU": 0, "total": 0})
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
@limiter.limit("20/minute")
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

@app.route('/ajax/recomendaciones_ia')
def recomendaciones_ia():
    try:
        puntos = get_puntos_con_promedio()
        return jsonify({'success': True, 'recomendaciones': puntos})
    except Exception as e:
        app.logger.error(f'Error en recomendaciones IA: {e}')
        return jsonify({'success': False, 'error': 'Error al obtener recomendaciones.'})


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
    password_actual = request.form.get('password_actual')
    
    if not nombre or not nombre.strip():
        return redirect(url_for('perfil'))
    
    usuario = Usuario.query.get(session['usuario_id'])
    if usuario:
        if len(nombre) >= 10:
            usuario.nombre = nombre[:20]
        usuario.ubicacion = ubicacion[:20] if ubicacion else ""
        usuario.titulo_perfil = titulo_perfil[:30] if titulo_perfil else ""
        usuario.biografia = biografia[:100] if biografia else ""
        usuario.intereses = intereses
        
        if foto_perfil_file and foto_perfil_file.filename:
            if not allowed_file(foto_perfil_file.filename):
                if request.headers.get('Accept') == 'application/json':
                    return jsonify({'success': False, 'error': 'Formato de imagen no permitido. Usa PNG, JPG, GIF o WebP.'}), 400
                return redirect(url_for('perfil'))
            extension = foto_perfil_file.filename.rsplit('.', 1)[1].lower()
            nombre_foto = f"{uuid.uuid4().hex}.{extension}"
            carpeta_destino = os.path.join(app.root_path, 'static', 'img', 'perfiles')
            os.makedirs(carpeta_destino, exist_ok=True)
            ruta_completa = os.path.join(carpeta_destino, nombre_foto)
            foto_perfil_file.save(ruta_completa)
            usuario.foto_perfil = nombre_foto
            session['foto_perfil'] = nombre_foto

        if password and len(password) >= 6:
            import bcrypt
            # Si el usuario ya tiene contraseña local, verificar la anterior
            tiene_password_local = usuario.password and (
                usuario.password.startswith('$2y$') or 
                usuario.password.startswith('$2b$') or 
                usuario.password.startswith('$2a$')
            )
            if tiene_password_local:
                if not password_actual:
                    if request.headers.get('Accept') == 'application/json':
                        return jsonify({'success': False, 'error': 'Debes ingresar tu contraseña actual para establecer una nueva.'}), 400
                    return redirect(url_for('perfil'))
                else:
                    hash_comparable = usuario.password.replace('$2y$', '$2b$', 1).encode('utf-8')
                    if bcrypt.checkpw(password_actual.encode('utf-8'), hash_comparable):
                        hashed = bcrypt.hashpw(password.encode('utf-8'), bcrypt.gensalt()).decode('utf-8')
                        usuario.password = hashed
                    else:
                        if request.headers.get('Accept') == 'application/json':
                            return jsonify({'success': False, 'error': 'La contraseña actual no es correcta.'}), 400
                        return redirect(url_for('perfil'))
            else:
                # Usuario de Google sin contraseña local - permitir crear una nueva sin verificar
                hashed = bcrypt.hashpw(password.encode('utf-8'), bcrypt.gensalt()).decode('utf-8')
                usuario.password = hashed
                if getattr(usuario, 'auth_provider', '') == 'google':
                    usuario.auth_provider = 'google+local'

        db.session.commit()
        db.session.refresh(usuario)
        
        session['nombre'] = usuario.nombre
        session['ubicacion'] = usuario.ubicacion
        session['titulo_perfil'] = usuario.titulo_perfil
        session['biografia'] = usuario.biografia
        session['intereses'] = usuario.intereses

    if request.headers.get('Accept') == 'application/json':
        return jsonify({'success': True, 'redirect': url_for('perfil')})
    return redirect(url_for('perfil'))

@app.route('/api/usuarios/me/foto', methods=['PUT', 'POST'])
@limiter.limit("10/minute")
def actualizar_foto_perfil():
    if 'usuario_id' not in session:
        return jsonify({'success': False, 'error': 'No has iniciado sesión.'}), 401
    
    foto_perfil_file = request.files.get('foto_perfil')
    if not foto_perfil_file or not foto_perfil_file.filename:
        return jsonify({'success': False, 'error': 'No se seleccionó ninguna imagen.'}), 400

    if not allowed_file(foto_perfil_file.filename):
        return jsonify({'success': False, 'error': 'Formato no permitido. Usa PNG, JPG, GIF o WebP.'}), 400

    usuario = Usuario.query.get(session['usuario_id'])
    if not usuario:
        return jsonify({'success': False, 'error': 'Usuario no encontrado.'}), 404

    extension = foto_perfil_file.filename.rsplit('.', 1)[1].lower()
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
        if not allowed_file(imagen_file.filename):
            return redirect(url_for('nuevo_post'))
        extension = imagen_file.filename.rsplit('.', 1)[1].lower()
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

@app.route('/ajax/notificaciones/unread')
def get_unread_notificaciones():
    if 'usuario_id' not in session:
        return jsonify({'success': False, 'error': 'No autenticado.'}), 401
    unread = Notificacion.query.filter(
        Notificacion.user_id == session['usuario_id'],
        db.or_(Notificacion.leida == False, Notificacion.leida == None)
    ).order_by(Notificacion.created_at.desc()).all()
    lista = [{'id': n.id, 'titulo': n.titulo, 'mensaje': n.mensaje, 'url': n.url, 'created_at': datetime_mx_filter(n.created_at)} for n in unread]
    return jsonify({'success': True, 'count': len(lista), 'data': lista})

@app.route('/ajax/notificaciones/mark_read', methods=['POST'])
def mark_read_notificaciones():
    if 'usuario_id' not in session:
        return jsonify({'success': False})
    Notificacion.query.filter(
        Notificacion.user_id == session['usuario_id'],
        db.or_(Notificacion.leida == False, Notificacion.leida == None)
    ).update({'leida': True})
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
@limiter.limit("5/minute")
def handle_login():
    email_usuario = request.form.get('email', '').strip()
    password_usuario = request.form.get('password')

    if not email_usuario or not password_usuario:
        return jsonify({'success': False, 'error': 'El correo y la contraseña son obligatorios.'})

    usuario = Usuario.query.filter_by(email=email_usuario).first()

    valido = False
    es_temporal = False

    if usuario:
        # Solo aceptar contraseñas hasheadas (bcrypt o pbkdf2) — SIN fallback plaintext
        if usuario.password.startswith("$2y$") or usuario.password.startswith("$2b$") or usuario.password.startswith("$2a$"):
            try:
                import bcrypt
                hash_comparable = usuario.password.replace('$2y$', '$2b$', 1).encode('utf-8')
                if bcrypt.checkpw(password_usuario.encode('utf-8'), hash_comparable):
                    valido = True
            except Exception:
                pass
        elif usuario.password.startswith('pbkdf2:'):
            try:
                if check_password_hash(usuario.password, password_usuario):
                    valido = True
            except Exception:
                pass

        # Si no coincide con la contraseña normal, verificar contraseña temporal
        if not valido:
            import bcrypt
            reset_req = PasswordResetRequest.query.filter_by(
                email=email_usuario, usado=False
            ).order_by(PasswordResetRequest.created_at.desc()).first()

            if reset_req and reset_req.temp_password_hash and reset_req.expires_at:
                if datetime.utcnow() <= reset_req.expires_at:
                    try:
                        temp_hash = reset_req.temp_password_hash.replace('$2y$', '$2b$', 1).encode('utf-8')
                        if bcrypt.checkpw(password_usuario.encode('utf-8'), temp_hash):
                            valido = True
                            es_temporal = True
                    except Exception:
                        pass
                else:
                    return jsonify({'success': False, 'error': 'La contraseña temporal ha expirado. Solicita una nueva.'})

    if valido:
        remember = request.form.get('remember-me')
        if remember == 'on':
            session.permanent = True
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

        # Si usó contraseña temporal, redirigir a cambiar contraseña
        if es_temporal:
            session['reset_temporal'] = True
            return jsonify({'success': True, 'redirect': url_for('cambiar_contrasena_view')})
            
        return jsonify({'success': True, 'redirect': url_for('index')})
    else:
        return jsonify({'success': False, 'error': 'Correo o contraseña incorrectos.'})

@app.route('/handle_registro', methods=['POST'])
@limiter.limit("3/minute")
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

    if not allowed_file(foto_perfil_file.filename):
        return jsonify({'success': False, 'error': 'Formato de imagen no permitido. Usa PNG, JPG, GIF o WebP.'}), 400

    if not EMAIL_REGEX.match(email_usuario):
        return jsonify({'success': False, 'error': 'Formato de correo electrónico inválido.'}), 400

    existe = Usuario.query.filter_by(email=email_usuario).first()
    if existe:
        return jsonify({'success': False, 'error': 'El correo electrónico ya está registrado.'})

    extension = foto_perfil_file.filename.rsplit('.', 1)[1].lower()
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
        sentimiento = http_requests.get('http://fastapi_app:6000/analisis/sentimiento', timeout=30).json().get('data', {"POS": 0, "NEG": 0, "NEU": 0, "total": 0})
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
#  Cambiar Contraseña (tras usar contraseña temporal)
# ==========================================================================

@app.route('/cambiar-contrasena')
def cambiar_contrasena_view():
    if 'usuario_id' not in session:
        return redirect(url_for('login'))
    if not session.get('reset_temporal'):
        return redirect(url_for('index'))
    return render_template('cambiar_contrasena.html')

@app.route('/cambiar-contrasena', methods=['POST'])
def cambiar_contrasena_save():
    if 'usuario_id' not in session:
        return jsonify({'success': False, 'error': 'Sesión expirada.'}), 401

    data = request.get_json()
    temp_password = data.get('temp_password', '').strip() if data else ''
    nueva_password = data.get('nueva_password', '').strip() if data else ''
    confirmar_password = data.get('confirmar_password', '').strip() if data else ''

    if not temp_password or not nueva_password or not confirmar_password:
        return jsonify({'success': False, 'error': 'Todos los campos son obligatorios.'}), 400

    if len(nueva_password) < 6:
        return jsonify({'success': False, 'error': 'La nueva contraseña debe tener al menos 6 caracteres.'}), 400

    if nueva_password != confirmar_password:
        return jsonify({'success': False, 'error': 'Las contraseñas no coinciden.'}), 400

    usuario = Usuario.query.get(session['usuario_id'])
    if not usuario:
        return jsonify({'success': False, 'error': 'Usuario no encontrado.'}), 404

    # Verificar la contraseña temporal
    import bcrypt
    reset_req = PasswordResetRequest.query.filter_by(
        email=usuario.email, usado=False
    ).order_by(PasswordResetRequest.created_at.desc()).first()

    if not reset_req or not reset_req.temp_password_hash:
        return jsonify({'success': False, 'error': 'No hay solicitud de recuperación activa.'}), 400

    if datetime.utcnow() > reset_req.expires_at:
        return jsonify({'success': False, 'error': 'La contraseña temporal ha expirado. Solicita una nueva.'}), 400

    try:
        temp_hash = reset_req.temp_password_hash.replace('$2y$', '$2b$', 1).encode('utf-8')
        if not bcrypt.checkpw(temp_password.encode('utf-8'), temp_hash):
            return jsonify({'success': False, 'error': 'La contraseña temporal no es correcta.'}), 400
    except Exception:
        return jsonify({'success': False, 'error': 'Error al verificar. Intenta de nuevo.'}), 500

    # Actualizar la contraseña del usuario
    nueva_hash = bcrypt.hashpw(nueva_password.encode('utf-8'), bcrypt.gensalt()).decode('utf-8')
    usuario.password = nueva_hash
    
    # Marcar la solicitud como usada
    reset_req.usado = True
    reset_req.estado = 'completado'
    db.session.commit()

    # Limpiar la sesión temporal
    session.pop('reset_temporal', None)

    return jsonify({'success': True, 'message': 'Contraseña actualizada exitosamente.', 'redirect': url_for('login')})

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
    is_debug = os.environ.get('FLASK_DEBUG', 'false').lower() == 'true'
    app.run(host='0.0.0.0', port=5000, debug=is_debug)