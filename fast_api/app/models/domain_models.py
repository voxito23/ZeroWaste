"""
Modelos SQLAlchemy "espejo" de los modelos Flask.
Los __tablename__ coinciden EXACTAMENTE con las tablas que ya existen en PostgreSQL.
NO se ejecuta create_all() — las tablas ya fueron creadas por Flask / Laravel.
"""

from datetime import datetime, timedelta, timezone
from sqlalchemy import (
    Column, Integer, String, Text, DateTime, Date, Time, Boolean, Numeric,
    ForeignKey, UniqueConstraint, CheckConstraint,
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
    firebase_uid = Column(String(255), nullable=True, unique=True)
    auth_provider = Column(String(50), default='local')
    profile_completed = Column(Boolean, default=True)
    bloqueado = Column(Boolean, default=False)
    rol = Column(String(50), default='usuario')
    edad = Column(Integer, nullable=True)
    licencia_conducir = Column(String(100), nullable=True)
    created_at = Column(DateTime, default=lambda: datetime.now(timezone.utc))
    email_verified_at = Column(DateTime(timezone=True), nullable=True)

    posts = relationship(
        "Foro",
        back_populates="autor_rel",
        foreign_keys="Foro.autor_id",
    )
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
    activo = Column(Boolean, nullable=False, default=True)
    horario = Column(String(255), nullable=True)
    responsable = Column(String(150), nullable=True)
    deleted_at = Column(DateTime(timezone=True), nullable=True, index=True)
    created_at = Column(DateTime, default=lambda: datetime.now(timezone.utc))

    calificaciones = relationship("CalificacionPunto", back_populates="punto")


class PointQrCode(Base):
    __tablename__ = "point_qr_codes"

    id = Column(Integer, primary_key=True)
    location_id = Column(Integer, ForeignKey("locations.id", ondelete="CASCADE"), nullable=False, index=True)
    token_hash = Column(String(64), nullable=False, unique=True, index=True)
    token_ciphertext = Column(Text, nullable=False)
    version = Column(Integer, nullable=False, default=1)
    active = Column(Boolean, nullable=False, default=True, index=True)
    generated_at = Column(DateTime, default=lambda: datetime.now(timezone.utc), nullable=False)
    regenerated_at = Column(DateTime, nullable=True)
    revoked_at = Column(DateTime, nullable=True)
    created_by = Column(Integer, ForeignKey("usuarios.id", ondelete="SET NULL"), nullable=True)
    created_at = Column(DateTime, default=lambda: datetime.now(timezone.utc), nullable=False)

    point = relationship("PuntoMapa")


# Modelo de calificación de punto
class CalificacionPunto(Base):
    __tablename__ = "calificaciones_puntos"

    id = Column(Integer, primary_key=True, index=True)
    location_id = Column(Integer, ForeignKey("locations.id"), nullable=False)
    usuario_id = Column(Integer, ForeignKey("usuarios.id"), nullable=False)
    estrellas = Column(Integer, nullable=False)
    comentario = Column(Text, nullable=True)
    created_at = Column(DateTime, default=lambda: datetime.now(timezone.utc))

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
    aprobado = Column(Boolean, nullable=False, default=False)
    aprobado_por = Column(Integer, ForeignKey("usuarios.id"), nullable=True)
    aprobado_at = Column(DateTime, nullable=True)
    created_at = Column(DateTime, default=lambda: datetime.now(timezone.utc))

    categoria_rel = relationship("Categoria", back_populates="posts")
    autor_rel = relationship(
        "Usuario",
        back_populates="posts",
        foreign_keys=[autor_id],
    )
    aprobador_rel = relationship("Usuario", foreign_keys=[aprobado_por])
    respuestas = relationship("RespuestaForo", back_populates="post")
    likes = relationship("LikeForo", back_populates="post_rel")


# Modelo de respuesta del foro
class RespuestaForo(Base):
    __tablename__ = "respuestas"

    id = Column(Integer, primary_key=True, index=True)
    post_id = Column(Integer, ForeignKey("posts.id"), nullable=False)
    autor_id = Column(Integer, ForeignKey("usuarios.id"), nullable=False)
    contenido = Column(Text, nullable=False)
    created_at = Column(DateTime, default=lambda: datetime.now(timezone.utc))

    post = relationship("Foro", back_populates="respuestas")
    autor_rel = relationship("Usuario", back_populates="respuestas")


# Modelo de like en el foro
class LikeForo(Base):
    __tablename__ = "likes_foro"

    id = Column(Integer, primary_key=True, index=True)
    usuario_id = Column(Integer, ForeignKey("usuarios.id"), nullable=False)
    post_id = Column(Integer, ForeignKey("posts.id"), nullable=False)
    created_at = Column(DateTime, default=lambda: datetime.now(timezone.utc))

    post_rel = relationship("Foro", back_populates="likes")

    __table_args__ = (
        UniqueConstraint("usuario_id", "post_id", name="uq_usuario_post_like"),
    )


# Modelo de campaña
class Campaign(Base):
    __tablename__ = "campaigns"

    id = Column(Integer, primary_key=True, index=True)
    nombre = Column(String(150), nullable=False)
    lugar = Column(String(200), nullable=True)
    fecha_inicio = Column(DateTime, nullable=True)
    fecha_fin = Column(DateTime, nullable=True)
    descripcion = Column(Text, nullable=False)
    tipo_etiqueta = Column(String(50), nullable=True)
    imagen_url = Column(String(255), nullable=True)
    link_evento = Column(String(500), nullable=True)
    recompensa_puntos = Column(Integer, default=0)
    activa = Column(Boolean, default=True)
    created_at = Column(DateTime, default=lambda: datetime.now(timezone.utc))


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
    fecha_creacion = Column(DateTime, default=lambda: datetime.now(timezone.utc))

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
    link_evento = Column(String(500), nullable=True)
    activa = Column(Boolean, default=True)


# MODELO NOTIFICACION
class Notificacion(Base):
    __tablename__ = 'notificaciones'
    id = Column(Integer, primary_key=True, index=True)
    user_id = Column(Integer, ForeignKey("usuarios.id"), nullable=False)
    titulo = Column(String(255), nullable=False)
    mensaje = Column(Text, nullable=False)
    url = Column(String(255), nullable=True)
    leida = Column(Boolean, default=False)
    created_at = Column(DateTime, default=lambda: datetime.now(timezone.utc))


# TAREA 2: MODELOS DE CONTACTO Y RECUPERACIÓN (Migrados desde Flask)
class ContactMessage(Base):
    __tablename__ = 'contact_messages'
    
    id = Column(Integer, primary_key=True, index=True)
    nombre = Column(String(150), nullable=False)
    email = Column(String(150), nullable=False)
    ubicacion = Column(String(200), nullable=True)
    mensaje = Column(Text, nullable=False)
    estado = Column(String(30), default='pendiente')
    respuesta_admin = Column(Text, nullable=True)
    usuario_id = Column(Integer, nullable=True)
    created_at = Column(DateTime, default=lambda: datetime.now(timezone.utc))

    replies = relationship("ContactReply", back_populates="contact_message")


class ContactReply(Base):
    __tablename__ = 'contact_replies'

    id = Column(Integer, primary_key=True, index=True)
    contact_message_id = Column(Integer, ForeignKey("contact_messages.id"), nullable=False)
    sender = Column(String(10), nullable=False)  # 'user' or 'admin'
    mensaje = Column(Text, nullable=False)
    created_at = Column(DateTime, default=lambda: datetime.now(timezone.utc))

    contact_message = relationship("ContactMessage", back_populates="replies")


class PasswordResetRequest(Base):
    __tablename__ = 'password_reset_requests'
    
    id = Column(Integer, primary_key=True, index=True)
    email = Column(String(150), nullable=False)
    temp_password_hash = Column(String(255), nullable=True)
    expires_at = Column(DateTime, nullable=True)
    usado = Column(Boolean, default=False)
    estado = Column(String(30), default='pendiente')
    notas = Column(Text, nullable=True)
    created_at = Column(DateTime, default=lambda: datetime.now(timezone.utc))


# Modelo de Solicitud de Recolección a Domicilio
class SolicitudRecoleccion(Base):
    __tablename__ = "solicitudes_recoleccion"

    id = Column(Integer, primary_key=True, index=True)
    usuario_id = Column(Integer, ForeignKey("usuarios.id", ondelete="CASCADE"), nullable=False)
    latitud = Column(Numeric(10, 8), nullable=False)
    longitud = Column(Numeric(11, 8), nullable=False)
    direccion = Column(Text, nullable=False)
    materiales = Column(Text, nullable=True)
    cantidad_estimada = Column(String(100), nullable=True)
    notas = Column(Text, nullable=True)
    scheduled_at = Column(DateTime(timezone=True), nullable=True, index=True)
    folio = Column(String(30), nullable=True, unique=True, index=True)
    estado = Column(String(50), default='pendiente') # pendiente, completada, cancelada
    recolector_id = Column(Integer, ForeignKey("usuarios.id", ondelete="SET NULL"), nullable=True)
    calificacion_recolector = Column(Integer, nullable=True)
    comentario_recolector = Column(Text, nullable=True)
    created_at = Column(DateTime, default=lambda: datetime.now(timezone.utc))
    updated_at = Column(DateTime, default=lambda: datetime.now(timezone.utc), onupdate=lambda: datetime.now(timezone.utc))

    # Relaciones
    usuario_rel = relationship("Usuario", foreign_keys=[usuario_id])
    recolector_rel = relationship("Usuario", foreign_keys=[recolector_id])


class ReglaPuntos(Base):
    __tablename__ = "reglas_puntos"
    id = Column(Integer, primary_key=True)
    codigo = Column(String(60), nullable=False, unique=True, index=True)
    descripcion = Column(String(255), nullable=False)
    puntos = Column(Integer, nullable=False)
    limite_diario = Column(Integer, nullable=True)
    activa = Column(Boolean, nullable=False, default=True)
    updated_by = Column(Integer, ForeignKey("usuarios.id"), nullable=True)
    created_at = Column(DateTime, default=lambda: datetime.now(timezone.utc))
    updated_at = Column(DateTime, default=lambda: datetime.now(timezone.utc), onupdate=lambda: datetime.now(timezone.utc))
    __table_args__ = (CheckConstraint("puntos >= 0", name="ck_reglas_puntos_no_negativos"),)


class SaldoPuntos(Base):
    __tablename__ = "saldos_puntos"
    usuario_id = Column(Integer, ForeignKey("usuarios.id"), primary_key=True)
    puntos_disponibles = Column(Integer, nullable=False, default=0)
    impacto_historico = Column(Integer, nullable=False, default=0)
    updated_at = Column(DateTime, default=lambda: datetime.now(timezone.utc), onupdate=lambda: datetime.now(timezone.utc))
    __table_args__ = (
        CheckConstraint("puntos_disponibles >= 0", name="ck_saldo_no_negativo"),
        CheckConstraint("impacto_historico >= 0", name="ck_impacto_no_negativo"),
    )


class MovimientoPuntos(Base):
    __tablename__ = "movimientos_puntos"
    id = Column(Integer, primary_key=True)
    usuario_id = Column(Integer, ForeignKey("usuarios.id"), nullable=False, index=True)
    tipo = Column(String(30), nullable=False)
    cantidad = Column(Integer, nullable=False)
    saldo_anterior = Column(Integer, nullable=False)
    saldo_nuevo = Column(Integer, nullable=False)
    impacto_anterior = Column(Integer, nullable=False)
    impacto_nuevo = Column(Integer, nullable=False)
    referencia_tipo = Column(String(50), nullable=True)
    referencia_id = Column(String(100), nullable=True)
    regla_id = Column(Integer, ForeignKey("reglas_puntos.id"), nullable=True)
    descripcion = Column(String(255), nullable=False)
    administrador_id = Column(Integer, ForeignKey("usuarios.id"), nullable=True)
    created_at = Column(DateTime, default=lambda: datetime.now(timezone.utc), index=True)
    __table_args__ = (
        UniqueConstraint("usuario_id", "referencia_tipo", "referencia_id", "regla_id", name="uq_movimiento_recompensa"),
    )


class Recompensa(Base):
    __tablename__ = "recompensas"
    id = Column(Integer, primary_key=True)
    nombre = Column(String(150), nullable=False)
    descripcion = Column(Text, nullable=False)
    costo_puntos = Column(Integer, nullable=False)
    stock = Column(Integer, nullable=False, default=0)
    imagen = Column(String(255), nullable=True)
    activa = Column(Boolean, nullable=False, default=True)
    limite_por_usuario = Column(Integer, nullable=True)
    orden = Column(Integer, nullable=False, default=0)
    available_at = Column(DateTime(timezone=True), nullable=True)
    deleted_at = Column(DateTime(timezone=True), nullable=True, index=True)
    created_at = Column(DateTime, default=lambda: datetime.now(timezone.utc))
    updated_at = Column(DateTime, default=lambda: datetime.now(timezone.utc), onupdate=lambda: datetime.now(timezone.utc))
    __table_args__ = (
        CheckConstraint("costo_puntos > 0", name="ck_recompensa_costo_positivo"),
        CheckConstraint("stock >= 0", name="ck_recompensa_stock_no_negativo"),
    )


class Canje(Base):
    __tablename__ = "canjes"
    id = Column(Integer, primary_key=True)
    usuario_id = Column(Integer, ForeignKey("usuarios.id"), nullable=False, index=True)
    recompensa_id = Column(Integer, ForeignKey("recompensas.id"), nullable=False)
    cantidad = Column(Integer, nullable=False, default=1)
    puntos_utilizados = Column(Integer, nullable=False)
    estado = Column(String(30), nullable=False, default="SOLICITADA")
    administrador_id = Column(Integer, ForeignKey("usuarios.id"), nullable=True)
    created_at = Column(DateTime, default=lambda: datetime.now(timezone.utc), index=True)
    updated_at = Column(DateTime, default=lambda: datetime.now(timezone.utc), onupdate=lambda: datetime.now(timezone.utc))
    recompensa = relationship("Recompensa")


class TokenQrRecoleccion(Base):
    __tablename__ = "tokens_qr_recoleccion"
    id = Column(Integer, primary_key=True)
    solicitud_id = Column(Integer, ForeignKey("solicitudes_recoleccion.id"), nullable=False, unique=True)
    token_hash = Column(String(64), nullable=False, unique=True, index=True)
    token_ciphertext = Column(Text, nullable=True)
    version = Column(Integer, nullable=False, default=1)
    status = Column(String(20), nullable=False, default="active", index=True)
    expires_at = Column(DateTime, nullable=False, index=True)
    used_at = Column(DateTime, nullable=True)
    used_by = Column(Integer, ForeignKey("usuarios.id"), nullable=True)
    invalidated_at = Column(DateTime, nullable=True)
    created_at = Column(DateTime, default=lambda: datetime.now(timezone.utc))


class CollectionSchedule(Base):
    __tablename__ = "collection_schedules"
    id = Column(Integer, primary_key=True)
    weekday = Column(Integer, nullable=False, unique=True)
    active = Column(Boolean, nullable=False, default=False)
    starts_at = Column(Time, nullable=False)
    ends_at = Column(Time, nullable=False)
    interval_minutes = Column(Integer, nullable=False, default=60)
    capacity_per_interval = Column(Integer, nullable=False, default=10)
    updated_by = Column(Integer, ForeignKey("usuarios.id"), nullable=True)
    updated_at = Column(DateTime(timezone=True), default=lambda: datetime.now(timezone.utc))


class ScheduleException(Base):
    __tablename__ = "schedule_exceptions"
    id = Column(Integer, primary_key=True)
    exception_date = Column(Date, nullable=False, index=True)
    kind = Column(String(30), nullable=False, default="closed")
    starts_at = Column(Time, nullable=True)
    ends_at = Column(Time, nullable=True)
    capacity_per_interval = Column(Integer, nullable=True)
    reason = Column(String(255), nullable=False)
    active = Column(Boolean, nullable=False, default=True)
    created_by = Column(Integer, ForeignKey("usuarios.id"), nullable=True)
    created_at = Column(DateTime(timezone=True), default=lambda: datetime.now(timezone.utc))


class OauthAccount(Base):
    __tablename__ = "oauth_accounts"
    id = Column(Integer, primary_key=True)
    usuario_id = Column(Integer, ForeignKey("usuarios.id", ondelete="CASCADE"), nullable=False, index=True)
    provider = Column(String(30), nullable=False)
    provider_subject = Column(String(255), nullable=False)
    provider_email = Column(String(255), nullable=True)
    linked_at = Column(DateTime(timezone=True), default=lambda: datetime.now(timezone.utc), nullable=False)
    last_login_at = Column(DateTime(timezone=True), nullable=True)
    __table_args__ = (
        UniqueConstraint("provider", "provider_subject", name="uq_oauth_provider_subject"),
        UniqueConstraint("usuario_id", "provider", name="uq_oauth_user_provider"),
    )


class OauthLoginState(Base):
    __tablename__ = "oauth_login_states"
    id = Column(Integer, primary_key=True)
    state_hash = Column(String(64), nullable=False, unique=True, index=True)
    verifier_ciphertext = Column(Text, nullable=False)
    nonce_hash = Column(String(64), nullable=False)
    handoff_hash = Column(String(64), nullable=True, unique=True, index=True)
    claims_ciphertext = Column(Text, nullable=True)
    usuario_id = Column(Integer, ForeignKey("usuarios.id", ondelete="CASCADE"), nullable=True)
    status = Column(String(30), nullable=False, default="pending", index=True)
    expires_at = Column(DateTime(timezone=True), nullable=False, index=True)
    used_at = Column(DateTime(timezone=True), nullable=True)
    created_at = Column(DateTime(timezone=True), default=lambda: datetime.now(timezone.utc), nullable=False)


class EmailVerificationToken(Base):
    __tablename__ = "email_verification_tokens"
    id = Column(Integer, primary_key=True)
    usuario_id = Column(Integer, ForeignKey("usuarios.id", ondelete="CASCADE"), nullable=False, index=True)
    token_hash = Column(String(64), nullable=False, unique=True, index=True)
    expires_at = Column(DateTime(timezone=True), nullable=False, index=True)
    used_at = Column(DateTime(timezone=True), nullable=True)
    revoked_at = Column(DateTime(timezone=True), nullable=True)
    provider_message_id = Column(String(255), nullable=True)
    sent_at = Column(DateTime(timezone=True), nullable=True)
    created_at = Column(DateTime(timezone=True), default=lambda: datetime.now(timezone.utc), nullable=False)
