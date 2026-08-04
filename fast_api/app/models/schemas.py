"""
Esquemas Pydantic para serialización / validación de datos.
Cubre TODAS las entidades del proyecto ZeroWaste.
"""

from datetime import datetime
from typing import Literal, Optional, List
from decimal import Decimal
from pydantic import BaseModel, EmailStr, Field, field_validator, model_validator

from app.services.media import build_public_avatar_url, build_public_media_url
from app.services.forum_content import safe_comment_for_output, validate_comment, validate_forum_text


# Esquemas de token JWT

class Token(BaseModel):
    access_token: str
    token_type: str = "bearer"

    model_config = {
        "json_schema_extra": {
            "examples": [
                {
                    "access_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiJ2aWNoZHpAZ2...",
                    "token_type": "bearer"
                }
            ]
        }
    }


class TokenData(BaseModel):
    email: Optional[str] = None


# Esquemas de usuario

class UsuarioBase(BaseModel):
    nombre: str
    email: EmailStr


class UsuarioCreate(UsuarioBase):
    password: str
    foto_perfil: Optional[str] = "perfil_default.png"

    model_config = {
        "json_schema_extra": {
            "examples": [
                {
                    "nombre": "Juan Pérez",
                    "email": "juan@gmail.com",
                    "password": "Contraseña123",
                    "foto_perfil": "perfil_default.png",
                }
            ]
        }
    }


class UsuarioUpdate(BaseModel):
    nombre: Optional[str] = None
    ubicacion: Optional[str] = None
    titulo_perfil: Optional[str] = None
    biografia: Optional[str] = None
    intereses: Optional[str] = None
    foto_perfil: Optional[str] = None

    model_config = {
        "json_schema_extra": {
            "examples": [
                {
                    "nombre": "Juan Pérez Actualizado",
                    "ubicacion": "Querétaro, México",
                    "titulo_perfil": "Activista Ambiental",
                    "biografia": "Apasionado por el medio ambiente y el reciclaje.",
                    "intereses": "Reciclaje, Compostaje, Energía Solar",
                    "foto_perfil": "perfil_juan.png",
                }
            ]
        }
    }


class UsuarioResponse(UsuarioBase):
    id: int
    foto_perfil: Optional[str] = None
    avatar_url: Optional[str] = None
    titulo_perfil: Optional[str] = None
    biografia: Optional[str] = None
    ubicacion: Optional[str] = None
    intereses: Optional[str] = None
    rol: Optional[str] = "usuario"
    edad: Optional[int] = None
    licencia_conducir: Optional[str] = None
    created_at: Optional[datetime] = None

    @model_validator(mode="after")
    def populate_avatar_url(self):
        self.avatar_url = build_public_avatar_url(self.foto_perfil)
        return self

    model_config = {
        "from_attributes": True,
        "json_schema_extra": {
            "examples": [
                {
                    "nombre": "Juan Pérez",
                    "email": "juan@gmail.com",
                    "id": 1,
                    "foto_perfil": "perfil_juan.png",
                    "titulo_perfil": "Activista Ambiental",
                    "biografia": "Apasionado por el medio ambiente y el reciclaje.",
                    "ubicacion": "Querétaro, México",
                    "intereses": "Reciclaje, Compostaje, Energía Solar",
                    "created_at": "2026-05-15T14:30:00Z"
                }
            ]
        }
    }


class LoginRequest(BaseModel):
    email: EmailStr
    password: str

    model_config = {
        "json_schema_extra": {
            "examples": [
                {
                    "email": "admin@ejemplo.com",
                    "password": "Contraseña123",
                }
            ]
        }
    }


# Esquemas de categoría

class CategoriaResponse(BaseModel):
    id: int
    nombre: str

    model_config = {
        "from_attributes": True,
        "json_schema_extra": {
            "examples": [
                {
                    "id": 1,
                    "nombre": "Reciclaje de Plásticos"
                }
            ]
        }
    }


# Esquemas del foro — publicaciones

class ForumAuthor(BaseModel):
    id: int
    nombre: str
    avatar_url: Optional[str] = None

    @model_validator(mode="after")
    def populate_public_avatar_url(self):
        self.avatar_url = build_public_avatar_url(self.avatar_url)
        return self

class PostCreate(BaseModel):
    titulo: str = Field(min_length=3, max_length=200)
    contenido: str = Field(min_length=3, max_length=5000)
    categoria_id: int
    imagen: Optional[str] = None

    @field_validator("titulo", mode="before")
    @classmethod
    def validate_title(cls, value: object) -> str:
        return validate_forum_text(value, field_name="El título", minimum=3, maximum=200)

    @field_validator("contenido", mode="before")
    @classmethod
    def validate_body(cls, value: object) -> str:
        return validate_forum_text(value, field_name="El contenido", minimum=3, maximum=5000)

    model_config = {
        "json_schema_extra": {
            "examples": [
                {
                    "titulo": "Tips para compostar en casa",
                    "contenido": "Hoy quiero compartir mi experiencia compostando residuos orgánicos en un departamento pequeño.",
                    "categoria_id": 1,
                    "imagen": "compostaje_tips.jpg",
                }
            ]
        }
    }


class PostUpdate(BaseModel):
    titulo: Optional[str] = Field(default=None, min_length=3, max_length=200)
    contenido: Optional[str] = Field(default=None, min_length=3, max_length=5000)
    categoria_id: Optional[int] = None
    imagen: Optional[str] = None

    @field_validator("titulo", mode="before")
    @classmethod
    def validate_optional_title(cls, value: object):
        return None if value is None else validate_forum_text(value, field_name="El título", minimum=3, maximum=200)

    @field_validator("contenido", mode="before")
    @classmethod
    def validate_optional_body(cls, value: object):
        return None if value is None else validate_forum_text(value, field_name="El contenido", minimum=3, maximum=5000)

    model_config = {
        "json_schema_extra": {
            "examples": [
                {
                    "titulo": "Tips actualizados para compostar en casa",
                    "contenido": "Actualización: Después de 3 meses, aquí están mis resultados con el compostaje.",
                    "categoria_id": 1,
                    "imagen": "compostaje_resultados.jpg",
                }
            ]
        }
    }


class PostResponse(BaseModel):
    id: int
    type: Literal["forum_post"] = "forum_post"
    titulo: str
    contenido: str
    categoria_id: int
    autor_id: int
    imagen: Optional[str] = None

    image_url: Optional[str] = None
    created_at: Optional[datetime] = None

    @model_validator(mode="after")
    def populate_image_url(self):
        self.image_url = build_public_media_url(self.imagen, "foro")
        return self

    model_config = {
        "from_attributes": True,
        "json_schema_extra": {
            "examples": [
                {
                    "id": 42,
                    "titulo": "Tips para compostar en casa",
                    "contenido": "Hoy quiero compartir mi experiencia compostando residuos orgánicos en un departamento pequeño.",
                    "categoria_id": 1,
                    "autor_id": 5,
                    "imagen": "compostaje_tips.jpg",
                    "created_at": "2026-05-20T10:15:30Z"
                }
            ]
        }
    }


class PostDetailResponse(PostResponse):
    autor_nombre: Optional[str] = None
    autor_foto: Optional[str] = None
    avatar_url: Optional[str] = None
    categoria_nombre: Optional[str] = None
    total_respuestas: int = 0
    total_likes: int = 0
    comments_count: int = 0
    likes_count: int = 0
    liked_by_me: bool = False
    author: Optional[ForumAuthor] = None

    @model_validator(mode="after")
    def populate_author_avatar_url(self):
        self.avatar_url = build_public_avatar_url(self.autor_foto)
        return self

    model_config = {
        "from_attributes": True,
        "json_schema_extra": {
            "examples": [
                {
                    "id": 42,
                    "titulo": "Tips para compostar en casa",
                    "contenido": "Hoy quiero compartir mi experiencia compostando residuos...",
                    "categoria_id": 1,
                    "autor_id": 5,
                    "imagen": "compostaje_tips.jpg",
                    "created_at": "2026-05-20T10:15:30Z",
                    "autor_nombre": "Ana Sánchez",
                    "autor_foto": "foto_ana.jpg",
                    "categoria_nombre": "Compostaje",
                    "total_respuestas": 15,
                    "total_likes": 240
                }
            ]
        }
    }


# Esquemas del foro — respuestas

class RespuestaCreate(BaseModel):
    contenido: str = Field(min_length=11, max_length=1000)
    parent_comment_id: Optional[int] = Field(default=None, ge=1)

    @field_validator("contenido", mode="before")
    @classmethod
    def validate_plain_content(cls, value: object) -> str:
        return validate_comment(value)

    model_config = {
        "json_schema_extra": {
            "examples": [
                {
                    "contenido": "¡Excelente post! Yo uso un bote de 20 litros y funciona perfecto.",
                }
            ]
        }
    }


class RespuestaResponse(BaseModel):
    id: int
    post_id: int
    autor_id: int
    aprobado: bool = False
    parent_comment_id: Optional[int] = None
    contenido: str
    created_at: Optional[datetime] = None
    autor_nombre: Optional[str] = None
    autor_foto: Optional[str] = None
    avatar_url: Optional[str] = None
    author: Optional[ForumAuthor] = None
    contenido_invalido: bool = False

    @model_validator(mode="after")
    def populate_safe_comment_and_avatar(self):
        self.contenido, self.contenido_invalido = safe_comment_for_output(self.contenido)
        self.avatar_url = build_public_avatar_url(self.autor_foto)
        return self

    model_config = {
        "from_attributes": True,
        "json_schema_extra": {
            "examples": [
                {
                    "id": 105,
                    "post_id": 42,
                    "autor_id": 8,
                    "contenido": "¡Excelente post! Yo uso un bote de 20 litros y funciona perfecto.",
                    "created_at": "2026-05-21T08:30:00Z",
                    "autor_nombre": "Carlos Gómez"
                }
            ]
        }
    }


# Esquemas del foro — likes

class LikeResponse(BaseModel):
    liked: bool
    likes_count: int = Field(ge=0)
    total: int = Field(ge=0)


# Esquemas del mapa — puntos de reciclaje

class PuntoMapaCreate(BaseModel):
    nombre: str
    direccion: str
    latitud: Decimal = Field(ge=-90, le=90, allow_inf_nan=False)
    longitud: Decimal = Field(ge=-180, le=180, allow_inf_nan=False)
    tipo: str
    materiales: Optional[str] = None
    imagen: Optional[str] = None
    horario: Optional[str] = None
    responsable: Optional[str] = None
    activo: bool = True

    model_config = {
        "json_schema_extra": {
            "examples": [
                {
                    "nombre": "Centro de Reciclaje Querétaro Norte",
                    "direccion": "Av. Universidad 500, Col. Centro, Querétaro",
                    "latitud": 20.5937,
                    "longitud": -100.3906,
                    "tipo": "Centro de acopio",
                    "materiales": "PET, Cartón, Vidrio, Aluminio",
                }
            ]
        }
    }


class PuntoMapaUpdate(BaseModel):
    nombre: Optional[str] = None
    direccion: Optional[str] = None
    latitud: Optional[Decimal] = Field(default=None, ge=-90, le=90, allow_inf_nan=False)
    longitud: Optional[Decimal] = Field(default=None, ge=-180, le=180, allow_inf_nan=False)
    tipo: Optional[str] = None
    materiales: Optional[str] = None
    imagen: Optional[str] = None
    horario: Optional[str] = None
    responsable: Optional[str] = None
    activo: Optional[bool] = None

    model_config = {
        "json_schema_extra": {
            "examples": [
                {
                    "nombre": "Centro de Reciclaje Querétaro Norte (Actualizado)",
                    "direccion": "Av. Universidad 500, Col. Centro, Querétaro, QRO",
                    "latitud": 20.5937,
                    "longitud": -100.3906,
                    "tipo": "Centro de acopio municipal",
                    "materiales": "PET, Cartón, Vidrio, Aluminio, Electrónicos",
                }
            ]
        }
    }


class PuntoMapaResponse(BaseModel):
    id: int
    nombre: str
    direccion: str
    latitud: float
    longitud: float
    tipo: str
    materiales: Optional[str] = None
    imagen: Optional[str] = "default_punto.png"
    image_url: Optional[str] = None
    activo: bool = True
    horario: Optional[str] = None
    responsable: Optional[str] = None
    promedio: float = 0.0
    total_reviews: int = 0

    @model_validator(mode="after")
    def populate_image_url(self):
        self.image_url = build_public_media_url(self.imagen, "puntos")
        return self

    model_config = {
        "from_attributes": True,
        "json_schema_extra": {
            "examples": [
                {
                    "id": 1,
                    "nombre": "Centro de Reciclaje Querétaro Norte",
                    "direccion": "Av. Universidad 500, Col. Centro, Querétaro",
                    "latitud": 20.5937,
                    "longitud": -100.3906,
                    "tipo": "Centro de acopio",
                    "materiales": "PET, Cartón, Vidrio",
                    "imagen": "default_punto.png",
                    "promedio": 4.5,
                    "total_reviews": 12
                }
            ]
        }
    }


# Esquemas del mapa — calificaciones

class CalificacionCreate(BaseModel):
    estrellas: int  # Escala de 1 a 5

    model_config = {
        "json_schema_extra": {
            "examples": [
                {
                    "estrellas": 4,
                }
            ]
        }
    }


class CalificacionResponse(BaseModel):
    success: bool
    promedio: float
    total: int

    model_config = {
        "json_schema_extra": {
            "examples": [
                {
                    "success": True,
                    "promedio": 4.5,
                    "total": 12
                }
            ]
        }
    }


# Esquemas de eventos

class EventoCreate(BaseModel):
    titulo: str
    fecha_inicio: datetime
    fecha_fin: datetime
    lugar: str
    descripcion: str
    tipo_etiqueta: Optional[str] = None
    imagen_url: Optional[str] = None
    link_evento: Optional[str] = None

    model_config = {
        "json_schema_extra": {
            "examples": [
                {
                    "titulo": "Limpieza del Parque Querétaro 2026",
                    "fecha_inicio": "2026-05-15T09:00:00",
                    "lugar": "Parque Querétaro 2000, Querétaro",
                    "descripcion": "Jornada comunitaria de limpieza y reforestación en el parque principal.",
                    "tipo_etiqueta": "Limpieza",
                    "imagen_url": "limpieza_parque.jpg",
                    "link_evento": "https://zerowaste.com/eventos/limpieza-parque",
                }
            ]
        }
    }


class EventoUpdate(BaseModel):
    titulo: Optional[str] = None
    fecha_inicio: Optional[datetime] = None
    fecha_fin: Optional[datetime] = None
    lugar: Optional[str] = None
    descripcion: Optional[str] = None
    tipo_etiqueta: Optional[str] = None
    imagen_url: Optional[str] = None
    link_evento: Optional[str] = None

    model_config = {
        "json_schema_extra": {
            "examples": [
                {
                    "titulo": "Limpieza del Parque Querétaro 2026 (Reprogramado)",
                    "fecha_inicio": "2026-06-01T10:00:00",
                    "lugar": "Parque Querétaro 2000, Querétaro",
                    "descripcion": "Evento reprogramado. Jornada comunitaria de limpieza y reforestación.",
                    "tipo_etiqueta": "Limpieza",
                    "imagen_url": "limpieza_parque_v2.jpg",
                    "link_evento": "https://zerowaste.com/eventos/limpieza-parque-v2",
                }
            ]
        }
    }


class EventoResponse(BaseModel):
    id: int
    titulo: Optional[str] = ""
    fecha_inicio: Optional[datetime] = None
    lugar: Optional[str] = ""
    fecha_fin: Optional[datetime] = None
    descripcion: Optional[str] = ""
    tipo_etiqueta: Optional[str] = None
    imagen_url: Optional[str] = None
    cover_url: Optional[str] = None
    link_evento: Optional[str] = None
    activa: Optional[bool] = True

    @model_validator(mode="after")
    def populate_cover_url(self):
        self.cover_url = build_public_media_url(self.imagen_url, "eventos")
        return self

    model_config = {
        "from_attributes": True,
        "json_schema_extra": {
            "examples": [
                {
                    "id": 15,
                    "titulo": "Limpieza del Parque Querétaro 2026",
                    "fecha_inicio": "2026-05-15T09:00:00Z",
                    "lugar": "Parque Querétaro 2000, Querétaro",
                    "fecha_fin": "2026-05-15T13:00:00Z",
                    "descripcion": "Jornada comunitaria de limpieza y reforestación en el parque principal.",
                    "tipo_etiqueta": "Limpieza",
                    "imagen_url": "limpieza_parque.jpg",
                    "link_evento": "https://zerowaste.com/eventos/limpieza-parque"
                }
            ]
        }
    }


# Esquemas de Campañas

class CampaignCreate(BaseModel):
    nombre: str
    lugar: Optional[str] = None
    fecha_inicio: Optional[datetime] = None
    fecha_fin: Optional[datetime] = None
    descripcion: str
    tipo_etiqueta: Optional[str] = None
    imagen_url: Optional[str] = None
    link_evento: Optional[str] = None
    recompensa_puntos: int = 0
    activa: bool = True

    model_config = {
        "json_schema_extra": {
            "examples": [
                {
                    "nombre": "Campaña de Reciclaje Primavera",
                    "lugar": "Centro de acopio principal",
                    "fecha_inicio": "2026-06-01T00:00:00",
                    "fecha_fin": "2026-06-30T23:59:59",
                    "descripcion": "Trae tus reciclables durante junio y gana doble puntaje.",
                    "tipo_etiqueta": "Reciclaje",
                    "recompensa_puntos": 50,
                    "activa": True
                }
            ]
        }
    }


class CampaignUpdate(BaseModel):
    nombre: Optional[str] = None
    lugar: Optional[str] = None
    fecha_inicio: Optional[datetime] = None
    fecha_fin: Optional[datetime] = None
    descripcion: Optional[str] = None
    tipo_etiqueta: Optional[str] = None
    imagen_url: Optional[str] = None
    link_evento: Optional[str] = None
    recompensa_puntos: Optional[int] = None
    activa: Optional[bool] = None

    model_config = {
        "json_schema_extra": {
            "examples": [
                {
                    "nombre": "Campaña de Reciclaje Primavera (Extendida)",
                    "fecha_fin": "2026-07-15T23:59:59",
                    "descripcion": "Ampliamos la fecha por éxito total. Gana doble puntaje.",
                    "recompensa_puntos": 100
                }
            ]
        }
    }


class CampaignResponse(BaseModel):
    id: int
    nombre: Optional[str] = ""
    lugar: Optional[str] = None
    fecha_inicio: Optional[datetime] = None
    fecha_fin: Optional[datetime] = None
    descripcion: Optional[str] = ""
    tipo_etiqueta: Optional[str] = None
    imagen_url: Optional[str] = None
    cover_url: Optional[str] = None
    link_evento: Optional[str] = None
    recompensa_puntos: Optional[int] = 0
    activa: Optional[bool] = True
    created_at: Optional[datetime] = None

    @model_validator(mode="after")
    def populate_cover_url(self):
        self.cover_url = build_public_media_url(self.imagen_url, "campanas")
        return self

    model_config = {
        "from_attributes": True,
        "json_schema_extra": {
            "examples": [
                {
                    "id": 1,
                    "nombre": "Campaña de Reciclaje Primavera",
                    "lugar": "Centro de acopio principal",
                    "fecha_inicio": "2026-06-01T00:00:00Z",
                    "fecha_fin": "2026-06-30T23:59:59Z",
                    "descripcion": "Trae tus reciclables durante junio y gana doble puntaje.",
                    "tipo_etiqueta": "Reciclaje",
                    "imagen_url": "campana_primavera.jpg",
                    "link_evento": "https://zerowaste.com/campanas/primavera",
                    "recompensa_puntos": 50,
                    "activa": True,
                    "created_at": "2026-05-20T10:00:00Z"
                }
            ]
        }
    }


# Esquemas de respuestas genéricas

class MessageResponse(BaseModel):
    """Respuesta genérica para operaciones que retornan un mensaje."""
    success: bool
    message: str

    model_config = {
        "json_schema_extra": {
            "examples": [
                {
                    "success": True,
                    "message": "Operación realizada con éxito."
                }
            ]
        }
    }


# Esquemas de Formularios (Migrados desde Flask)

class ContactMessageCreate(BaseModel):
    nombre: str
    email: EmailStr
    ubicacion: Optional[str] = None
    mensaje: str

    model_config = {
        "json_schema_extra": {
            "examples": [
                {
                    "nombre": "Ana López",
                    "email": "ana@empresa.com",
                    "ubicacion": "Qro, México",
                    "mensaje": "Me gustaría cotizar la recolección industrial de PET."
                }
            ]
        }
    }

class PasswordResetRequestCreate(BaseModel):
    email: EmailStr

    model_config = {
        "json_schema_extra": {
            "examples": [
                {
                    "email": "juan@gmail.com"
                }
            ]
        }
    }

# Esquemas de Solicitudes de Recolección a Domicilio

class SolicitudRecoleccionCreate(BaseModel):
    latitud: float = Field(ge=-90, le=90)
    longitud: float = Field(ge=-180, le=180)
    direccion: str = Field(min_length=5, max_length=500)
    materiales: str = Field(min_length=2, max_length=500)
    cantidad_estimada: str = Field(min_length=1, max_length=100)
    notas: Optional[str] = Field(default=None, max_length=1000)
    scheduled_at: datetime

class SolicitudRecoleccionResponse(BaseModel):
    id: int
    usuario_id: int
    latitud: float
    longitud: float
    direccion: str
    materiales: Optional[str] = None
    cantidad_estimada: Optional[str] = None
    notas: Optional[str] = None
    scheduled_at: Optional[datetime] = None
    folio: Optional[str] = None
    estado: str
    recolector_id: Optional[int] = None
    calificacion_recolector: Optional[int] = None
    comentario_recolector: Optional[str] = None
    created_at: datetime
    updated_at: datetime

    model_config = {
        "from_attributes": True
    }

class CalificarRecolectorRequest(BaseModel):
    calificacion: int # 1 a 5
    comentario: Optional[str] = None

