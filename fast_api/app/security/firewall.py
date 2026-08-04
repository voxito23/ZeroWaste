"""
Firewall / WAF Middleware para FastAPI — ZeroWaste.
Protección contra ataques comunes: SQL Injection, XSS, path traversal,
rate limiting por IP, bloqueo de IPs sospechosas, y logging de requests.
"""

import os
import re
import time
import json
import logging
from datetime import datetime, timezone
from collections import defaultdict
from typing import Dict, List, Set

from fastapi import Request
from fastapi.responses import JSONResponse
from starlette.middleware.base import BaseHTTPMiddleware, RequestResponseEndpoint
from starlette.responses import Response
from app.observability import FIREWALL_BLOCKS
from app.security.login_throttle import get_rate_limit_key

# ── Configuración ──────────────────────────────────────────
MAX_REQUESTS_PER_MINUTE = int(os.getenv("FIREWALL_RPM", "300"))
BLOCK_DURATION_SECONDS = int(os.getenv("FIREWALL_BLOCK_SECS", "300"))  # 5 min
MAX_BODY_SIZE = 10 * 1024 * 1024  # 10MB

# Logger dedicado
firewall_logger = logging.getLogger("zerowaste.firewall")
firewall_logger.setLevel(logging.INFO)
if not firewall_logger.handlers:
    handler = logging.StreamHandler()
    handler.setFormatter(logging.Formatter(
        "[FIREWALL] %(asctime)s | %(levelname)s | %(message)s",
        datefmt="%Y-%m-%d %H:%M:%S"
    ))
    firewall_logger.addHandler(handler)

# ── Patrones maliciosos ────────────────────────────────────
SQL_INJECTION_PATTERNS = [
    r"(\b(SELECT|INSERT|UPDATE|DELETE|DROP|UNION|ALTER|CREATE|EXEC)\b.*\b(FROM|INTO|TABLE|WHERE|SET|VALUES)\b)",
    r"(--|#|/\*|\*/)",
    r"(\bOR\b\s+\d+\s*=\s*\d+)",
    r"(\bAND\b\s+\d+\s*=\s*\d+)",
    r"('\s*(OR|AND)\s+')",
]

XSS_PATTERNS = [
    r"(<script[^>]*>)",
    r"(javascript\s*:)",
    r"(on(load|error|click|mouseover|focus|blur|submit)\s*=)",
    r"(<iframe[^>]*>)",
    r"(<object[^>]*>)",
    r"(document\.(cookie|location|write))",
    r"(eval\s*\()",
    r"(alert\s*\()",
]

PATH_TRAVERSAL_PATTERNS = [
    r"(\.\./|\.\.\\)",
    r"(/etc/(passwd|shadow|hosts))",
    r"(\\windows\\system32)",
    r"(%2e%2e%2f|%2e%2e/|\.\.%2f)",
]

COMPILED_PATTERNS = []
for pattern_list in [SQL_INJECTION_PATTERNS, XSS_PATTERNS, PATH_TRAVERSAL_PATTERNS]:
    for p in pattern_list:
        try:
            COMPILED_PATTERNS.append(re.compile(p, re.IGNORECASE))
        except re.error:
            pass

# ── Almacenamiento en memoria ──────────────────────────────
# Estructura: {opaque_identity: [timestamp1, timestamp2, ...]}
_request_log: Dict[str, List[float]] = defaultdict(list)
_blocked_ips: Dict[str, float] = {}  # {ip: blocked_until_timestamp}
_threat_log: List[dict] = []  # Últimos N eventos de amenaza
_stats = {
    "total_requests": 0,
    "blocked_requests": 0,
    "threats_detected": 0,
    "started_at": datetime.now(timezone.utc).isoformat(),
}
MAX_THREAT_LOG = 500


def get_firewall_stats() -> dict:
    """Devuelve estadísticas del firewall."""
    return {
        **_stats,
        "blocked_ips_count": len(_blocked_ips),
        "blocked_ips": list(_blocked_ips.keys()),
        "active_connections": sum(1 for v in _request_log.values() if v),
        "recent_threats": _threat_log[-20:],  # Últimas 20 amenazas
    }


def get_threat_log() -> List[dict]:
    """Devuelve el log completo de amenazas."""
    return list(reversed(_threat_log))


def unblock_ip(ip: str) -> bool:
    """Desbloquea una IP manualmente."""
    if ip in _blocked_ips:
        del _blocked_ips[ip]
        firewall_logger.info(f"IP desbloqueada manualmente: {ip}")
        return True
    return False


def block_ip(ip: str, duration: int = BLOCK_DURATION_SECONDS) -> None:
    """Bloquea una IP por duración especificada."""
    _blocked_ips[ip] = time.time() + duration
    firewall_logger.warning(f"IP bloqueada: {ip} por {duration}s")


def _clean_old_requests(identity: str, window: float = 60.0):
    """Limpia requests antiguos fuera de la ventana de tiempo."""
    now = time.time()
    _request_log[identity] = [t for t in _request_log[identity] if now - t < window]


def _is_rate_limited(identity: str) -> bool:
    """Verifica si una identidad opaca excede el rate limit."""
    _clean_old_requests(identity)
    _request_log[identity].append(time.time())
    return len(_request_log[identity]) > MAX_REQUESTS_PER_MINUTE


def _is_blocked(ip: str) -> bool:
    """Verifica si una IP está bloqueada."""
    if ip not in _blocked_ips:
        return False
    if time.time() > _blocked_ips[ip]:
        del _blocked_ips[ip]
        return False
    return True


def _scan_for_threats(text: str) -> str | None:
    """Escanea texto buscando patrones maliciosos. Retorna el tipo de amenaza o None."""
    if not text:
        return None
    for pattern in COMPILED_PATTERNS:
        if pattern.search(text):
            return pattern.pattern
    return None


def _log_threat(ip: str, method: str, path: str, threat_type: str, detail: str):
    """Registra una amenaza detectada."""
    event = {
        "timestamp": datetime.now(timezone.utc).isoformat(),
        "ip": ip,
        "method": method,
        "path": path,
        "threat_type": threat_type,
        "detail": detail[:200],
    }
    _threat_log.append(event)
    if len(_threat_log) > MAX_THREAT_LOG:
        _threat_log.pop(0)
    _stats["threats_detected"] += 1
    firewall_logger.warning(f"AMENAZA | {ip} | {method} {path} | {threat_type}")


class FirewallMiddleware(BaseHTTPMiddleware):
    """
    Middleware WAF (Web Application Firewall) para ZeroWaste.
    - Rate limiting por IP
    - Bloqueo automático de IPs sospechosas
    - Detección de SQL Injection, XSS, Path Traversal
    - Logging de amenazas
    """

    EXEMPT_PATHS = {"/", "/api", "/api/", "/favicon.ico", "/metrics"}

    async def dispatch(self, request: Request, call_next: RequestResponseEndpoint) -> Response:
        _stats["total_requests"] += 1
        ip = request.headers.get("X-Real-IP") or request.headers.get("X-Forwarded-For", "").split(",")[0].strip() or request.client.host if request.client else "unknown"
        method = request.method
        path = request.url.path

        # 1. No bloquear preflight CORS
        if method == "OPTIONS":
            return await call_next(request)

        # 1.5 Rutas exentas del firewall (metrics, health, favicon)
        if path in self.EXEMPT_PATHS or path.startswith("/metrics"):
            return await call_next(request)

        # 2. Verificar si la IP está bloqueada
        if _is_blocked(ip):
            _stats["blocked_requests"] += 1
            FIREWALL_BLOCKS.labels(reason="temporary_block").inc()
            return JSONResponse(
                status_code=403,
                content={
                    "detail": "Acceso denegado: Tu IP ha sido bloqueada temporalmente por actividad sospechosa.",
                    "firewall": True,
                }
            )

        # 3. Rate limiting
        if _is_rate_limited(get_rate_limit_key(request)):
            _log_threat(ip, method, path, "RATE_LIMIT", f"Excedió {MAX_REQUESTS_PER_MINUTE} req/min")
            _stats["blocked_requests"] += 1
            FIREWALL_BLOCKS.labels(reason="rate_limit").inc()
            return JSONResponse(
                status_code=429,
                headers={"Retry-After": "60"},
                content={
                    "detail": f"Demasiadas solicitudes. Límite: {MAX_REQUESTS_PER_MINUTE}/min. Espera un momento antes de reintentar.",
                    "firewall": True,
                }
            )

        # 4. Escanear URL y query params por amenazas
        full_url = str(request.url)
        threat = _scan_for_threats(full_url)
        if threat:
            _log_threat(ip, method, path, "URL_ATTACK", f"Patrón detectado en URL: {threat[:100]}")
            block_ip(ip)
            _stats["blocked_requests"] += 1
            FIREWALL_BLOCKS.labels(reason="url_attack").inc()
            return JSONResponse(
                status_code=403,
                content={"detail": "Solicitud bloqueada por el firewall: contenido malicioso detectado.", "firewall": True}
            )

        # 5. Escanear headers sospechosos
        for header_name, header_value in request.headers.items():
            if header_name.lower() in ("authorization", "cookie", "x-api-key", "content-type", "accept", "user-agent", "host", "content-length", "connection"):
                continue
            threat = _scan_for_threats(header_value)
            if threat:
                _log_threat(ip, method, path, "HEADER_ATTACK", f"Header {header_name}: {threat[:80]}")
                _stats["blocked_requests"] += 1
                FIREWALL_BLOCKS.labels(reason="header_attack").inc()
                return JSONResponse(
                    status_code=403,
                    content={"detail": "Solicitud bloqueada: header sospechoso detectado.", "firewall": True}
                )

        # 6. Continuar con la solicitud
        try:
            response = await call_next(request)
        except Exception as e:
            firewall_logger.error(f"Error procesando request de {ip}: {str(e)[:200]}")
            raise

        return response
