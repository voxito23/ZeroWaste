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
    imagen = Column(String(255), default="default_punto.png")
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
    referencia_id = Column(Integer, nullable=True)
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


