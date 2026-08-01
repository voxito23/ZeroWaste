#!/usr/bin/env python3
"""Comprueba que las URLs de medios publicadas por la API respondan por HTTPS."""

from __future__ import annotations

import json
import sys
import urllib.error
import urllib.request
from collections.abc import Iterable


ORIGEN = "https://www.zerowaste-qro.com"
ENDPOINTS = (
    "/api/foro/posts",
    "/api/mapa/puntos",
    "/api/campanas",
    "/api/eventos",
    "/api/impacto/recompensas",
    "/api/impacto/ranking",
)
CAMPOS_URL = {"image_url", "avatar_url", "cover_url"}


def obtener_json(ruta: str):
    solicitud = urllib.request.Request(
        f"{ORIGEN}{ruta}", headers={"User-Agent": "ZeroWaste-verificador-medios/1"}
    )
    with urllib.request.urlopen(solicitud, timeout=20) as respuesta:
        if respuesta.status != 200:
            raise RuntimeError(f"{ruta} respondió HTTP {respuesta.status}")
        return json.load(respuesta)


def recorrer_urls(valor) -> Iterable[str]:
    if isinstance(valor, dict):
        for clave, contenido in valor.items():
            if clave in CAMPOS_URL and isinstance(contenido, str) and contenido:
                yield contenido
            else:
                yield from recorrer_urls(contenido)
    elif isinstance(valor, list):
        for elemento in valor:
            yield from recorrer_urls(elemento)


def comprobar_url(url: str) -> None:
    if not url.startswith(f"{ORIGEN}/media/"):
        if url.startswith("https://"):
            print(f"AVISO URL externa no comprobada: {url}")
            return
        raise RuntimeError(f"URL no canónica o insegura: {url}")
    solicitud = urllib.request.Request(
        url,
        method="HEAD",
        headers={"User-Agent": "ZeroWaste-verificador-medios/1"},
    )
    with urllib.request.urlopen(solicitud, timeout=20) as respuesta:
        tipo = respuesta.headers.get("Content-Type", "")
        if respuesta.status != 200 or not tipo.startswith("image/"):
            raise RuntimeError(
                f"medio inválido: HTTP {respuesta.status}, Content-Type={tipo}, URL={url}"
            )


def main() -> int:
    urls: set[str] = {
        f"{ORIGEN}/media/perfiles/default.png",
        f"{ORIGEN}/media/perfiles/perfil_default.png",
        f"{ORIGEN}/media/recompensas/termo_reutilizable.png",
    }
    errores: list[str] = []
    for endpoint in ENDPOINTS:
        try:
            datos = obtener_json(endpoint)
            encontradas = set(recorrer_urls(datos))
            urls.update(encontradas)
            print(f"OK contrato {endpoint}: {len(encontradas)} URL(s)")
        except Exception as exc:  # Se agregan todos los fallos antes de salir.
            errores.append(f"{endpoint}: {exc}")

    for url in sorted(urls):
        try:
            comprobar_url(url)
            print(f"OK imagen {url}")
        except (OSError, RuntimeError, urllib.error.HTTPError) as exc:
            errores.append(str(exc))

    if errores:
        print("Fallaron las siguientes comprobaciones:", file=sys.stderr)
        for error in errores:
            print(f"- {error}", file=sys.stderr)
        return 1
    print(f"Verificación completa: {len(urls)} imagen(es) disponibles.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
