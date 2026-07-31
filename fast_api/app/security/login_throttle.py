"""Distributed login throttling backed by Redis.

Keys contain SHA-256 digests, never raw email addresses or IP addresses.  The
Lua script makes increment-and-lock atomic across every FastAPI replica.
"""

from __future__ import annotations

import hashlib
import ipaddress
import os
from dataclasses import dataclass

from fastapi import HTTPException, Request, status
from redis import Redis
from redis.exceptions import RedisError

from app.observability import LOGIN_BLOCKS, LOGIN_FAILURES


LOCK_MESSAGE = "Demasiados intentos. Espera un minuto antes de volver a intentarlo."
INVALID_MESSAGE = "Usuario o contraseña incorrectos."

_FAILURE_SCRIPT = """
local failures = redis.call('INCR', KEYS[1])
if failures == 1 then redis.call('EXPIRE', KEYS[1], ARGV[2]) end
if failures >= tonumber(ARGV[1]) then
  redis.call('SET', KEYS[2], '1', 'EX', ARGV[3])
  redis.call('DEL', KEYS[1])
  return tonumber(ARGV[3])
end
return 0
"""


@dataclass(frozen=True)
class ThrottlePolicy:
    account_limit: int = 5
    ip_limit: int = 20
    failure_window: int = 300
    lock_seconds: int = 60


class LoginThrottle:
    def __init__(self, client: Redis | None = None, policy: ThrottlePolicy | None = None):
        redis_url = os.getenv("REDIS_URL")
        if client is None and not redis_url:
            raise RuntimeError("Required environment variable is not configured: REDIS_URL")
        self.redis = client or Redis.from_url(
            redis_url, decode_responses=True, socket_connect_timeout=2, socket_timeout=2
        )
        self.policy = policy or ThrottlePolicy()

    @staticmethod
    def _digest(value: str) -> str:
        return hashlib.sha256(value.encode("utf-8")).hexdigest()

    def _keys(self, identifier: str, ip: str) -> tuple[str, str, str, str]:
        account = self._digest(identifier.strip().casefold())
        address = self._digest(ip)
        return (
            f"zw:login:account:{account}:failures",
            f"zw:login:account:{account}:lock",
            f"zw:login:ip:{address}:failures",
            f"zw:login:ip:{address}:lock",
        )

    def _ttl(self, key: str) -> int:
        ttl = int(self.redis.ttl(key))
        return max(ttl, 0)

    def assert_allowed(self, identifier: str, ip: str) -> None:
        keys = self._keys(identifier, ip)
        try:
            retry_after = max(self._ttl(keys[1]), self._ttl(keys[3]))
        except RedisError as exc:
            raise HTTPException(status_code=503, detail="Servicio de autenticación temporalmente no disponible.") from exc
        if retry_after > 0:
            self._raise_locked(retry_after)

    def record_failure(self, identifier: str, ip: str) -> None:
        account_fail, account_lock, ip_fail, ip_lock = self._keys(identifier, ip)
        try:
            account_ttl = int(self.redis.eval(
                _FAILURE_SCRIPT, 2, account_fail, account_lock,
                self.policy.account_limit, self.policy.failure_window, self.policy.lock_seconds,
            ))
            ip_ttl = int(self.redis.eval(
                _FAILURE_SCRIPT, 2, ip_fail, ip_lock,
                self.policy.ip_limit, self.policy.failure_window, self.policy.lock_seconds,
            ))
        except RedisError as exc:
            raise HTTPException(status_code=503, detail="Servicio de autenticación temporalmente no disponible.") from exc
        LOGIN_FAILURES.inc()
        retry_after = max(account_ttl, ip_ttl)
        if retry_after > 0:
            LOGIN_BLOCKS.inc()
            self._raise_locked(retry_after)

    def clear(self, identifier: str, ip: str) -> None:
        account_fail, _, _, _ = self._keys(identifier, ip)
        try:
            # A valid account clears its own failures. The broader IP spraying
            # counter intentionally remains until its TTL expires.
            self.redis.delete(account_fail)
        except RedisError as exc:
            raise HTTPException(status_code=503, detail="Servicio de autenticación temporalmente no disponible.") from exc

    @staticmethod
    def _raise_locked(retry_after: int) -> None:
        raise HTTPException(
            status_code=status.HTTP_429_TOO_MANY_REQUESTS,
            detail=LOCK_MESSAGE,
            headers={"Retry-After": str(max(retry_after, 1))},
        )


def get_client_ip(request: Request) -> str:
    """Resolve client IP only when the immediate peer is a trusted proxy."""
    peer = request.client.host if request.client else "0.0.0.0"
    configured = os.getenv(
        "TRUSTED_PROXY_CIDRS", "127.0.0.0/8,10.0.0.0/8,172.16.0.0/12,192.168.0.0/16"
    )
    trusted = [ipaddress.ip_network(item.strip()) for item in configured.split(",") if item.strip()]

    def is_trusted(value: str) -> bool:
        try:
            address = ipaddress.ip_address(value)
            return any(address in network for network in trusted)
        except ValueError:
            return False

    if not is_trusted(peer):
        return peer
    chain = [part.strip() for part in request.headers.get("x-forwarded-for", "").split(",") if part.strip()]
    chain.append(peer)
    for candidate in reversed(chain):
        if not is_trusted(candidate):
            return candidate
    return chain[0] if chain else peer


_throttle: LoginThrottle | None = None


def get_login_throttle() -> LoginThrottle:
    global _throttle
    if _throttle is None:
        _throttle = LoginThrottle()
    return _throttle
