"""
Paquete de routers — importa los módulos de cada endpoint.
"""

from . import auth, usuarios, foro, mapa, eventos, analisis, formularios

__all__ = ["auth", "usuarios", "foro", "mapa", "eventos", "analisis", "formularios"]
