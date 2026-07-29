"""
Router de monitoreo del Firewall — estadísticas, amenazas, bloqueo/desbloqueo de IPs.
Todos los endpoints requieren autenticación de administrador.
"""

from fastapi import APIRouter, Depends, HTTPException, status
from app.security.jwt_auth import get_current_admin_user
from app.models.domain_models import Usuario
from app.security.firewall import (
    get_firewall_stats,
    get_threat_log,
    unblock_ip,
    block_ip,
    _blocked_ips,
)

router = APIRouter(prefix="/firewall", tags=["Firewall / WAF"])


@router.get(
    "/stats",
    summary="Estadísticas del Firewall WAF",
    description="Devuelve métricas globales del firewall: requests totales, bloqueados, amenazas detectadas, IPs bloqueadas.",
)
def firewall_stats(admin: Usuario = Depends(get_current_admin_user)):
    return {"success": True, "data": get_firewall_stats()}


@router.get(
    "/threats",
    summary="Log de amenazas detectadas",
    description="Devuelve las últimas 500 amenazas detectadas por el firewall (SQL Injection, XSS, Rate Limit, etc.).",
)
def firewall_threats(admin: Usuario = Depends(get_current_admin_user)):
    return {"success": True, "data": get_threat_log()}


@router.post(
    "/block/{ip}",
    summary="Bloquear una IP manualmente",
    description="Bloquea una IP específica por 5 minutos (300 segundos por defecto).",
)
def firewall_block_ip(
    ip: str,
    duration: int = 300,
    admin: Usuario = Depends(get_current_admin_user),
):
    block_ip(ip, duration)
    return {"success": True, "message": f"IP {ip} bloqueada por {duration} segundos."}


@router.post(
    "/unblock/{ip}",
    summary="Desbloquear una IP manualmente",
    description="Elimina el bloqueo de una IP específica.",
)
def firewall_unblock_ip(
    ip: str,
    admin: Usuario = Depends(get_current_admin_user),
):
    if unblock_ip(ip):
        return {"success": True, "message": f"IP {ip} desbloqueada exitosamente."}
    raise HTTPException(
        status_code=status.HTTP_404_NOT_FOUND,
        detail=f"La IP {ip} no está en la lista de bloqueados.",
    )


@router.get(
    "/blocked",
    summary="Listar IPs bloqueadas",
    description="Devuelve la lista de IPs actualmente bloqueadas y su tiempo restante.",
)
def firewall_blocked_ips(admin: Usuario = Depends(get_current_admin_user)):
    import time

    now = time.time()
    ips = []
    for ip, until in list(_blocked_ips.items()):
        remaining = int(until - now)
        if remaining > 0:
            ips.append({"ip": ip, "seconds_remaining": remaining})
        else:
            del _blocked_ips[ip]
    return {"success": True, "data": ips}
