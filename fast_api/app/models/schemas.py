"""
Esquemas Pydantic para serialización / validación de datos.
Cubre TODAS las entidades del proyecto ZeroWaste.
"""

from datetime import datetime
from typing import Optional, List
from decimal import Decimal
from pydantic import BaseModel, EmailStr


# Esquemas de token JWT

class Token(BaseModel):
    access_token: str
    token_type: str = "bearer"


class TokenData(BaseModel):
    email: Optional[str] = None


# Esquemas de usuario

class UsuarioBase(BaseModel):
    nombre: str
    email: EmailStr


class UsuarioCreate(UsuarioBase):
    password: str
    foto_perfil: Optional[str] = "perfil_default.png"


class UsuarioUpdate(BaseModel):
    nombre: Optional[str] = None
    ubicacion: Optional[str] = None
    titulo_perfil: Optional[str] = None
    biografia: Optional[str] = None
    intereses: Optional[str] = None
    foto_perfil: Optional[str] = None


class UsuarioResponse(UsuarioBase):
    id: int
    foto_perfil: Optional[str] = None
    titulo_perfil: Optional[str] = None
    biografia: Optional[str] = None
    ubicacion: Optional[str] = None
    intereses: Optional[str] = None
    created_at: Optional[datetime] = None

    model_config = {"from_attributes": True}


class LoginRequest(BaseModel):
    email: EmailStr
    password: str


# Esquemas de categoría

class CategoriaResponse(BaseModel):
    id: int
    nombre: str

    model_config = {"from_attributes": True}


# Esquemas del foro — publicaciones

class PostCreate(BaseModel):
    titulo: str
    contenido: str
    categoria_id: int
    imagen: Optional[str] = None


class PostUpdate(BaseModel):
    titulo: Optional[str] = None
    contenido: Optional[str] = None
    categoria_id: Optional[int] = None
    imagen: Optional[str] = None


class PostResponse(BaseModel):
    id: int
    titulo: str
    contenido: str
    categoria_id: int
    autor_id: int
    imagen: Optional[str] = None
    created_at: Optional[datetime] = None

    model_config = {"from_attributes": True}


class PostDetailResponse(PostResponse):
    autor_nombre: Optional[str] = None
    categoria_nombre: Optional[str] = None
    total_respuestas: int = 0
    total_likes: int = 0


# Esquemas del foro — respuestas

class RespuestaCreate(BaseModel):
    contenido: str


class RespuestaResponse(BaseModel):
    id: int
    post_id: int
    autor_id: int
    contenido: str
    created_at: Optional[datetime] = None
    autor_nombre: Optional[str] = None

    model_config = {"from_attributes": True}


# Esquemas del foro — likes

class LikeResponse(BaseModel):
    success: bool
    action: str  # Valores posibles: "liked" o "unliked"
    total_likes: int


# Esquemas del mapa — puntos de reciclaje

class PuntoMapaCreate(BaseModel):
    nombre: str
    direccion: str
    latitud: Decimal
    longitud: Decimal
    tipo: str
    materiales: Optional[str] = None


class PuntoMapaUpdate(BaseModel):
    nombre: Optional[str] = None
    direccion: Optional[str] = None
    latitud: Optional[Decimal] = None
    longitud: Optional[Decimal] = None
    tipo: Optional[str] = None
    materiales: Optional[str] = None


class PuntoMapaResponse(BaseModel):
    id: int
    nombre: str
    direccion: str
    latitud: float
    longitud: float
    tipo: str
    materiales: Optional[str] = None
    promedio: float = 0.0
    total_reviews: int = 0

    model_config = {"from_attributes": True}


# Esquemas del mapa — calificaciones

class CalificacionCreate(BaseModel):
    estrellas: int  # Escala de 1 a 5


class CalificacionResponse(BaseModel):
    success: bool
    promedio: float
    total: int


# Esquemas de eventos

class EventoCreate(BaseModel):
    titulo: str
    fecha_inicio: datetime
    ubicacion: str
    descripcion: str
    categoria: Optional[str] = None
    imagen: Optional[str] = None
    link_unirse: Optional[str] = None


class EventoUpdate(BaseModel):
    titulo: Optional[str] = None
    fecha_inicio: Optional[datetime] = None
    ubicacion: Optional[str] = None
    descripcion: Optional[str] = None
    categoria: Optional[str] = None
    imagen: Optional[str] = None
    link_unirse: Optional[str] = None


class EventoResponse(BaseModel):
    id: int
    titulo: str
    fecha_inicio: datetime
    ubicacion: str
    descripcion: str
    categoria: Optional[str] = None
    imagen: Optional[str] = None
    link_unirse: Optional[str] = None

    model_config = {"from_attributes": True}


# Esquemas de respuestas genéricas

class MessageResponse(BaseModel):
    """Respuesta genérica para operaciones que retornan un mensaje."""
    success: bool
    message: str
