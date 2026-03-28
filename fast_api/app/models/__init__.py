"""
Paquete de modelos — re-exporta ORM (domain_models) y Pydantic (schemas).
Permite:  from app.models import Usuario, UsuarioCreate, Token ...
"""

# Modelos SQLAlchemy (ORM)
from .domain_models import (
    Usuario,
    Categoria,
    PuntoMapa,
    CalificacionPunto,
    Evento,
    Foro,
    RespuestaForo,
    LikeForo,
    Campaign,
    Material,
)

# Esquemas Pydantic (validación y serialización)
from .schemas import (
    Token,
    TokenData,
    LoginRequest,
    UsuarioBase,
    UsuarioCreate,
    UsuarioUpdate,
    UsuarioResponse,
    CategoriaResponse,
    PostCreate,
    PostUpdate,
    PostResponse,
    PostDetailResponse,
    RespuestaCreate,
    RespuestaResponse,
    LikeResponse,
    PuntoMapaCreate,
    PuntoMapaUpdate,
    PuntoMapaResponse,
    CalificacionCreate,
    CalificacionResponse,
    EventoCreate,
    EventoUpdate,
    EventoResponse,
    MessageResponse,
)

__all__ = [
    # ORM
    "Usuario", "Categoria", "PuntoMapa", "CalificacionPunto",
    "Evento", "Foro", "RespuestaForo", "LikeForo",
    "Campaign", "Material",
    # Schemas
    "Token", "TokenData", "LoginRequest",
    "UsuarioBase", "UsuarioCreate", "UsuarioUpdate", "UsuarioResponse",
    "CategoriaResponse",
    "PostCreate", "PostUpdate", "PostResponse", "PostDetailResponse",
    "RespuestaCreate", "RespuestaResponse", "LikeResponse",
    "PuntoMapaCreate", "PuntoMapaUpdate", "PuntoMapaResponse",
    "CalificacionCreate", "CalificacionResponse",
    "EventoCreate", "EventoUpdate", "EventoResponse",
    "MessageResponse",
]
