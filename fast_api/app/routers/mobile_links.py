"""Independent mobile deep-link fallback. It deliberately does not render Flask templates."""

from __future__ import annotations

import html
import os
import re

from fastapi import APIRouter, HTTPException
from fastapi.responses import HTMLResponse, JSONResponse


router = APIRouter(tags=["Enlaces móviles"])
KINDS = {"posts", "articles", "news", "points"}
IDENTIFIER = re.compile(r"^[A-Za-z0-9-]{1,120}$")
FINGERPRINT = re.compile(r"^(?:[A-Fa-f0-9]{2}:){31}[A-Fa-f0-9]{2}$")


@router.get("/mobile/config", include_in_schema=False)
def mobile_public_config():
    """Return only values that are intentionally safe to embed in the mobile client."""
    mapbox_token = os.getenv("MAPBOX_PUBLIC_TOKEN", "").strip()
    return {
        "mapbox_public_token": mapbox_token if mapbox_token.startswith("pk.") else None,
        "mapbox_ready": mapbox_token.startswith("pk."),
    }


@router.get("/app/{kind}/{identifier}", response_class=HTMLResponse, include_in_schema=False)
def mobile_fallback(kind: str, identifier: str):
    if kind not in KINDS or not IDENTIFIER.fullmatch(identifier):
        raise HTTPException(status_code=404, detail="Contenido móvil no disponible.")
    deep_link = f"zerowaste://{kind}/{identifier}"
    safe_link = html.escape(deep_link, quote=True)
    safe_kind = html.escape(kind)
    return HTMLResponse(f"""<!doctype html><html lang=\"es\"><head><meta charset=\"utf-8\"><meta name=\"viewport\" content=\"width=device-width,initial-scale=1\"><meta name=\"robots\" content=\"noindex\"><title>Abrir en ZeroWaste</title><style>body{{margin:0;background:#ecfdf5;color:#0f172a;font-family:system-ui,sans-serif;display:grid;min-height:100vh;place-items:center}}main{{max-width:420px;margin:24px;padding:32px;border:1px solid #a7f3d0;border-radius:28px;background:#fff;box-shadow:0 20px 50px #064e3b20;text-align:center}}h1{{color:#064e3b}}a{{display:block;margin-top:24px;padding:15px;border-radius:16px;background:#047857;color:#fff;font-weight:800;text-decoration:none}}p{{line-height:1.55;color:#475569}}</style></head><body><main><h1>ZeroWaste</h1><p>Este enlace corresponde a contenido de tipo {safe_kind} en la aplicación móvil.</p><a href=\"{safe_link}\">Abrir en ZeroWaste</a><p>Si la aplicación no está instalada, vuelve cuando tengas acceso a la app oficial.</p></main></body></html>""")


@router.get("/.well-known/assetlinks.json", include_in_schema=False)
def android_asset_links():
    fingerprint = os.getenv("ANDROID_APP_LINKS_SHA256_CERT_FINGERPRINT", "").strip()
    if not FINGERPRINT.fullmatch(fingerprint):
        raise HTTPException(status_code=503, detail="Android App Links todavía no está configurado.")
    return JSONResponse([{
        "relation": ["delegate_permission/common.handle_all_urls"],
        "target": {
            "namespace": "android_app",
            "package_name": "com.vic45.mobile_app",
            "sha256_cert_fingerprints": [fingerprint],
        },
    }])
