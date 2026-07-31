#!/usr/bin/env python3
"""Comprueba el contrato de variables sin imprimir secretos ni destinos completos."""

from __future__ import annotations

import hashlib
import json
import re
from pathlib import Path
from urllib.parse import unquote, urlsplit


ROOT = Path(__file__).resolve().parents[1]


def read_env(path: Path) -> dict[str, str]:
    values: dict[str, str] = {}
    if not path.is_file():
        return values
    for raw_line in path.read_text(encoding="utf-8-sig").splitlines():
        line = raw_line.strip()
        if not line or line.startswith("#") or "=" not in line:
            continue
        name, value = line.split("=", 1)
        values[name.strip()] = value.strip().strip('"').strip("'")
    return values


def fingerprint(value: str | None) -> str | None:
    if not value:
        return None
    return hashlib.sha256(value.lower().encode("utf-8")).hexdigest()[:12]


def database_target(url: str | None, fallback: dict[str, str] | None = None) -> dict[str, object]:
    host = ""
    user = ""
    scheme = ""
    if url:
        parsed = urlsplit(url)
        host = parsed.hostname or ""
        user = unquote(parsed.username or "")
        scheme = parsed.scheme.split("+", 1)[0]
    elif fallback:
        host = fallback.get("DB_HOST", "")
        user = fallback.get("DB_USERNAME", "")
        scheme = fallback.get("DB_CONNECTION", "")

    local_names = {"localhost", "127.0.0.1", "::1", "db", "zerowaste_db"}
    if not host:
        kind = "missing"
    elif host.lower() in local_names:
        kind = "local_or_legacy"
    elif "supabase" in host.lower() or re.match(r"^postgres\.[a-z0-9]+$", user.lower()):
        kind = "supabase"
    else:
        kind = "external_other"

    marker = host
    direct_match = re.match(r"^db\.([^.]+)\.supabase\.co$", host.lower())
    pool_user = re.match(r"^postgres\.([^:]+)$", user.lower())
    if direct_match:
        marker = direct_match.group(1)
    elif pool_user:
        marker = pool_user.group(1)
    return {
        "present": bool(host),
        "postgres": scheme in {"postgres", "postgresql", "pgsql"},
        "target_kind": kind,
        "project_fingerprint": fingerprint(marker),
    }


def main() -> int:
    flask = read_env(ROOT / "flask_zerowaste" / ".env")
    fastapi = read_env(ROOT / "fast_api" / ".env")
    laravel = read_env(ROOT / "laravel_zerowaste" / ".env")
    mobile = read_env(ROOT / "mobile_app" / ".env")

    databases = {
        "flask": database_target(flask.get("DATABASE_URL")),
        "fastapi": database_target(fastapi.get("DATABASE_URL")),
        "laravel": database_target(
            laravel.get("DB_URL") or laravel.get("DATABASE_URL"), laravel
        ),
    }
    markers = {
        item["project_fingerprint"]
        for item in databases.values()
        if item["project_fingerprint"]
    }
    token = mobile.get("EXPO_PUBLIC_MAPBOX_TOKEN", "")
    report = {
        "redacted": True,
        "databases": databases,
        "same_database_fingerprint": len(markers) == 1,
        "laravel": {
            "app_key_present": bool(laravel.get("APP_KEY")),
            "session_driver_redis": laravel.get("SESSION_DRIVER") == "redis",
            "session_connection_present": bool(laravel.get("SESSION_CONNECTION")),
            "session_cookie_present": bool(laravel.get("SESSION_COOKIE")),
            "redis_prefix_present": bool(laravel.get("REDIS_PREFIX")),
            "cache_prefix_present": bool(laravel.get("CACHE_PREFIX")),
        },
        "mobile": {
            "mapbox_token_present": bool(token),
            "mapbox_token_public_format": token.startswith("pk."),
            "api_url_https": mobile.get("EXPO_PUBLIC_API_URL", "").startswith("https://"),
        },
    }
    print(json.dumps(report, ensure_ascii=False, indent=2))

    database_ok = all(
        item["postgres"] and item["target_kind"] == "supabase"
        for item in databases.values()
    )
    mobile_ok = report["mobile"]["mapbox_token_public_format"]
    return 0 if database_ok and mobile_ok else 2


if __name__ == "__main__":
    raise SystemExit(main())
