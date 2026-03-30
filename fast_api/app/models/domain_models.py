"""
Modelos SQLAlchemy "espejo" de los modelos Flask.
Los __tablename__ coinciden EXACTAMENTE con las tablas que ya existen en PostgreSQL.
NO se ejecuta create_all() — las tablas ya fueron creadas por Flask / Laravel.
"""

from datetime import datetime, timedelta
from sqlalchemy import (
    Column, Integer, String, Text, DateTime, Boolean, Numeric,
    ForeignKey, UniqueConstraint,
)
from sqlalchemy.orm import relationship, Session
from app.data.database import Base


# Modelo de usuario
class Usuario(Base):
    __tablename__ = "usuarios"

    id = Column(Integer, primary_key=True, index=True)
    nombre = Column(String(100), nullable=False)
    email = Column(String(100), unique=True, nullable=False, index=True)
    password = Column(String(255), nullable=False)
    foto_perfil = Column(String(255), default="perfil_default.png")
    titulo_perfil = Column(String(100), default="Usuario Eco-consciente")
    biografia = Column(Text, nullable=True)
    ubicacion = Column(String(100), default="Querétaro")
    intereses = Column(String(255), nullable=True)
    is_admin = Column(Boolean, default=False)
    created_at = Column(DateTime, default=datetime.utcnow)

    posts = relationship("Foro", back_populates="autor_rel")
    respuestas = relationship("RespuestaForo", back_populates="autor_rel")
    actividades = relationship("Actividad", back_populates="usuario", cascade="all, delete-orphan")


# Modelo de categoría
class Categoria(Base):
    __tablename__ = "categorias"

    id = Column(Integer, primary_key=True, index=True)
    nombre = Column(String(100), nullable=False)

    posts = relationship("Foro", back_populates="categoria_rel")


# Modelo de punto de reciclaje en el mapa
class PuntoMapa(Base):
    __tablename__ = "locations"

    id = Column(Integer, primary_key=True, index=True)
    nombre = Column(String(150), nullable=False)
    direccion = Column(Text, nullable=False)
    latitud = Column(Numeric(10, 8), nullable=False)
    longitud = Column(Numeric(11, 8), nullable=False)
    tipo = Column(String(100), nullable=False)
    materiales = Column(Text, nullable=True)
    created_at = Column(DateTime, default=datetime.utcnow)

    calificaciones = relationship("CalificacionPunto", back_populates="punto")


# Modelo de calificación de punto
class CalificacionPunto(Base):
    __tablename__ = "calificaciones_puntos"

    id = Column(Integer, primary_key=True, index=True)
    location_id = Column(Integer, ForeignKey("locations.id"), nullable=False)
    usuario_id = Column(Integer, ForeignKey("usuarios.id"), nullable=False)
    estrellas = Column(Integer, nullable=False)
    created_at = Column(DateTime, default=datetime.utcnow)

    punto = relationship("PuntoMapa", back_populates="calificaciones")


# Modelo antiguo removido


# Modelo de publicación del foro
class Foro(Base):
    __tablename__ = "posts"

    id = Column(Integer, primary_key=True, index=True)
    titulo = Column(String(200), nullable=False)
    contenido = Column(Text, nullable=False)
    categoria_id = Column(Integer, ForeignKey("categorias.id"), nullable=False)
    autor_id = Column(Integer, ForeignKey("usuarios.id"), nullable=False)
    imagen = Column(String(255), nullable=True)
    created_at = Column(DateTime, default=datetime.utcnow)

    categoria_rel = relationship("Categoria", back_populates="posts")
    autor_rel = relationship("Usuario", back_populates="posts")
    respuestas = relationship("RespuestaForo", back_populates="post")
    likes = relationship("LikeForo", back_populates="post_rel")


# Modelo de respuesta del foro
class RespuestaForo(Base):
    __tablename__ = "respuestas"

    id = Column(Integer, primary_key=True, index=True)
    post_id = Column(Integer, ForeignKey("posts.id"), nullable=False)
    autor_id = Column(Integer, ForeignKey("usuarios.id"), nullable=False)
    contenido = Column(Text, nullable=False)
    created_at = Column(DateTime, default=datetime.utcnow)

    post = relationship("Foro", back_populates="respuestas")
    autor_rel = relationship("Usuario", back_populates="respuestas")


# Modelo de like en el foro
class LikeForo(Base):
    __tablename__ = "likes_foro"

    id = Column(Integer, primary_key=True, index=True)
    usuario_id = Column(Integer, ForeignKey("usuarios.id"), nullable=False)
    post_id = Column(Integer, ForeignKey("posts.id"), nullable=False)
    created_at = Column(DateTime, default=datetime.utcnow)

    post_rel = relationship("Foro", back_populates="likes")

    __table_args__ = (
        UniqueConstraint("usuario_id", "post_id", name="uq_usuario_post_like"),
    )


# Modelo de campaña
class Campaign(Base):
    __tablename__ = "campaigns"

    id = Column(Integer, primary_key=True, index=True)
    nombre = Column(String(150), nullable=False)
    descripcion = Column(Text, nullable=False)
    recompensa_puntos = Column(Integer, default=0)
    activa = Column(Boolean, default=True)
    created_at = Column(DateTime, default=datetime.utcnow)


# Modelo de material reciclable
class Material(Base):
    __tablename__ = "materials"

    id = Column(Integer, primary_key=True, index=True)
    nombre = Column(String(100), nullable=False)
    tipo = Column(String(50), nullable=False)
    unidades_medida = Column(String(20), nullable=False)
    valor_puntos = Column(Integer, default=0)


# Modelo de actividad de usuario
class Actividad(Base):
    __tablename__ = "actividades"

    id = Column(Integer, primary_key=True, index=True)
    usuario_id = Column(Integer, ForeignKey("usuarios.id"), nullable=False)
    tipo = Column(String(50), nullable=False) # ej: "post", "respuesta", "contacto"
    descripcion = Column(String(255), nullable=False)
    fecha_creacion = Column(DateTime, default=datetime.utcnow)

    usuario = relationship("Usuario", back_populates="actividades")

# TAREA 1: MODELO EVENTO
class Evento(Base):
    __tablename__ = 'eventos'
    id = Column(Integer, primary_key=True, index=True)
    titulo = Column(String(150), nullable=False)
    lugar = Column(String(200), nullable=False)
    fecha_inicio = Column(DateTime, nullable=False)
    fecha_fin = Column(DateTime, nullable=False)
    descripcion = Column(Text, nullable=False)
    tipo_etiqueta = Column(String(50), nullable=True)
    imagen_url = Column(String(255), nullable=True)

# SEEDER BÁSICO DE EVENTOS, FORO Y CATEGORÍAS
def seed_database(db: Session):
    categorias_nombres = ['Compostaje', 'Estilo de Vida', 'Reciclaje', 'Eventos', 'Dudas']
    for nombre in categorias_nombres:
        if not db.query(Categoria).filter_by(nombre=nombre).first():
            db.add(Categoria(nombre=nombre))
    db.commit()

    if not db.query(Evento).first():
        eventos_seed = [
            Evento(titulo="Mega Jornada de Acopio", lugar="Parque Bicentenario, Qro.", fecha_inicio=datetime.utcnow() + timedelta(days=2), fecha_fin=datetime.utcnow() + timedelta(days=2, hours=5), descripcion="Trae tus electrónicos y plásticos PET para reciclaje masivo.", tipo_etiqueta="Acopio", imagen_url="acopio.png"),
            Evento(titulo="Programa Ecomunidad", lugar="Centro Cívico, Querétaro", fecha_inicio=datetime.utcnow() + timedelta(days=5), fecha_fin=datetime.utcnow() + timedelta(days=5, hours=3), descripcion="Talleres de sostenibilidad y composta comunitaria para vecinos.", tipo_etiqueta="Educación", imagen_url="ecomunidad.png"),
            Evento(titulo="Reforestación Autóctona", lugar="Cerro del Tambor, Qro", fecha_inicio=datetime.utcnow() + timedelta(days=10), fecha_fin=datetime.utcnow() + timedelta(days=10, hours=4), descripcion="Unidos para rescatar áreas verdes con árboles endémicos.", tipo_etiqueta="Voluntariado", imagen_url="reforestacion.png")
        ]
        db.add_all(eventos_seed)
        db.commit()

    if not db.query(Foro).first():
        posts_seed = [
            Foro(titulo="¿Cómo empezar con el compostaje en departamento?", contenido="Quiero iniciar pero tengo poco espacio.", categoria_id=1, autor_id=1),
            Foro(titulo="Guía de reciclaje en el centro", contenido="Chicos, les comparto los centros de acopio activos.", categoria_id=3, autor_id=1),
            Foro(titulo="Alternativas al vidrio en el súper", contenido="Me ha costado mucho dejar de comprar empaques plásticos.", categoria_id=2, autor_id=1)
        ]
        db.add_all(posts_seed)
        db.commit()

    if not db.query(PuntoMapa).first():
        puntos_seed = [
            PuntoMapa(nombre="Punto Verde Alameda", latitud=20.5881, longitud=-100.3899, direccion="Alameda Hidalgo, Centro Histórico, Querétaro", materiales="PET, Cartón, Vidrio, Aluminio", tipo="Centro Principal"),
            PuntoMapa(nombre="Centro de Acopio UAQ", latitud=20.5932, longitud=-100.4127, direccion="Universidad Autónoma de Querétaro, Campus Centro", materiales="PET, Papel, Cartón, Electrónicos", tipo="Centro Principal"),
            PuntoMapa(nombre="Tierra Com", latitud=20.6185, longitud=-100.4052, direccion="Col. Álamos, Querétaro", materiales="Orgánicos, Composta, Residuos de jardín", tipo="Contenedor Público"),
            PuntoMapa(nombre="Recicla Qro", latitud=20.6340, longitud=-100.4480, direccion="Blvd. B. Quintana, Col. Arboledas", materiales="Plástico, Vidrio, Metal, Textiles", tipo="Centro Principal")
        ]
        db.add_all(puntos_seed)
        db.commit()
