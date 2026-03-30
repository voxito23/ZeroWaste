from flask_sqlalchemy import SQLAlchemy
from datetime import datetime

db = SQLAlchemy()

class Usuario(db.Model):
    __tablename__ = 'usuarios'
    id = db.Column(db.Integer, primary_key=True)
    nombre = db.Column(db.String(100), nullable=False)
    email = db.Column(db.String(100), unique=True, nullable=False)
    password = db.Column(db.String(255), nullable=False)
    foto_perfil = db.Column(db.String(255), default='perfil_default.png')
    titulo_perfil = db.Column(db.String(100), default='Usuario Eco-consciente')
    biografia = db.Column(db.Text, nullable=True)
    ubicacion = db.Column(db.String(100), default='Querétaro')
    intereses = db.Column(db.String(255), nullable=True)
    is_admin = db.Column(db.Boolean, default=False)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    
    # Relaciones con el foro
    posts = db.relationship('Foro', backref='autor_rel', lazy=True)
    respuestas = db.relationship('RespuestaForo', backref='autor_rel', lazy=True)

class Categoria(db.Model):
    __tablename__ = 'categorias'
    id = db.Column(db.Integer, primary_key=True)
    nombre = db.Column(db.String(100), nullable=False)

class PuntoMapa(db.Model):
    __tablename__ = 'locations'
    id = db.Column(db.Integer, primary_key=True)
    nombre = db.Column(db.String(150), nullable=False)
    direccion = db.Column(db.Text, nullable=False)
    latitud = db.Column(db.Numeric(10, 8), nullable=False)
    longitud = db.Column(db.Numeric(11, 8), nullable=False)
    tipo = db.Column(db.String(100), nullable=False)
    materiales = db.Column(db.Text, nullable=True)
    imagen = db.Column(db.String(255), default='default_punto.png')
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    
    calificaciones = db.relationship('CalificacionPunto', backref='punto', lazy=True)

class CalificacionPunto(db.Model):
    __tablename__ = 'calificaciones_puntos'
    id = db.Column(db.Integer, primary_key=True)
    location_id = db.Column(db.Integer, db.ForeignKey('locations.id'), nullable=False)
    usuario_id = db.Column(db.Integer, db.ForeignKey('usuarios.id'), nullable=False)
    estrellas = db.Column(db.Integer, nullable=False) # Escala de 1 a 5
    comentario = db.Column(db.Text, nullable=True)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)

class Evento(db.Model):
    __tablename__ = 'eventos'
    id = db.Column(db.Integer, primary_key=True)
    titulo = db.Column(db.String(150), nullable=False)
    lugar = db.Column(db.String(200), nullable=False)
    fecha_inicio = db.Column(db.DateTime, nullable=False)
    fecha_fin = db.Column(db.DateTime, nullable=False)
    descripcion = db.Column(db.Text, nullable=False)
    tipo_etiqueta = db.Column(db.String(50), nullable=True)
    imagen_url = db.Column(db.String(255), nullable=True)
    link_evento = db.Column(db.String(255), nullable=True)

class Foro(db.Model):
    __tablename__ = 'posts'
    id = db.Column(db.Integer, primary_key=True)
    titulo = db.Column(db.String(200), nullable=False)
    contenido = db.Column(db.Text, nullable=False)
    categoria_id = db.Column(db.Integer, db.ForeignKey('categorias.id'), nullable=False)
    autor_id = db.Column(db.Integer, db.ForeignKey('usuarios.id'), nullable=False)
    imagen = db.Column(db.String(255), nullable=True)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    
    categoria_rel = db.relationship('Categoria', backref='posts')
    respuestas = db.relationship('RespuestaForo', backref='post', lazy=True)
    likes = db.relationship('LikeForo', backref='post_rel', lazy=True)

class RespuestaForo(db.Model):
    __tablename__ = 'respuestas'
    id = db.Column(db.Integer, primary_key=True)
    post_id = db.Column(db.Integer, db.ForeignKey('posts.id'), nullable=False)
    autor_id = db.Column(db.Integer, db.ForeignKey('usuarios.id'), nullable=False)
    contenido = db.Column(db.Text, nullable=False)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)

class LikeForo(db.Model):
    __tablename__ = 'likes_foro'
    id = db.Column(db.Integer, primary_key=True)
    usuario_id = db.Column(db.Integer, db.ForeignKey('usuarios.id'), nullable=False)
    post_id = db.Column(db.Integer, db.ForeignKey('posts.id'), nullable=False)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    
    __table_args__ = (
        db.UniqueConstraint('usuario_id', 'post_id', name='uq_usuario_post_like'),
    )

class Campaign(db.Model):
    __tablename__ = 'campaigns'
    id = db.Column(db.Integer, primary_key=True)
    nombre = db.Column(db.String(150), nullable=False)
    lugar = db.Column(db.String(200), nullable=True)
    fecha_inicio = db.Column(db.DateTime, nullable=True)
    fecha_fin = db.Column(db.DateTime, nullable=True)
    descripcion = db.Column(db.Text, nullable=False)
    tipo_etiqueta = db.Column(db.String(50), nullable=True)
    imagen_url = db.Column(db.String(255), nullable=True)
    link_evento = db.Column(db.String(255), nullable=True)
    recompensa_puntos = db.Column(db.Integer, default=0)
    activa = db.Column(db.Boolean, default=True)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)

class Material(db.Model):
    __tablename__ = 'materials'
    id = db.Column(db.Integer, primary_key=True)
    nombre = db.Column(db.String(100), nullable=False)
    tipo = db.Column(db.String(50), nullable=False)
    unidades_medida = db.Column(db.String(20), nullable=False)
    valor_puntos = db.Column(db.Integer, default=0)

class Actividad(db.Model):
    __tablename__ = 'actividades'
    id = db.Column(db.Integer, primary_key=True)
    usuario_id = db.Column(db.Integer, db.ForeignKey('usuarios.id'), nullable=False)
    tipo = db.Column(db.String(50), nullable=False)
    descripcion = db.Column(db.String(255), nullable=False)
    referencia_id = db.Column(db.Integer, nullable=True)
    fecha_creacion = db.Column(db.DateTime, default=datetime.utcnow)
