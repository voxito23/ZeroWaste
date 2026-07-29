"""
Paquete de routers — importa los módulos de cada endpoint.
"""

from . import auth, usuarios, foro, mapa, eventos, analisis, formularios, campanas, recoleccion, firewall_monitor

__all__ = ["auth", "usuarios", "foro", "mapa", "eventos", "analisis", "formularios", "campanas", "recoleccion", "firewall_monitor"]
