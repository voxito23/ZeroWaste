#!/usr/bin/env python3
"""Inventario de medios ZeroWaste sin modificar archivos ni base de datos.

El script emite JSON por stdout. Si se proporciona un export JSON de metadatos,
solo propone relaciones por coincidencia exacta del nombre/ruta almacenado; nunca
deduce relaciones por parecido, fecha o posición.
"""

from __future__ import annotations

import argparse
import hashlib
import json
import os
from datetime import datetime, timezone
from pathlib import Path
from typing import Any, Iterable


PROJECT_ROOT = Path(__file__).resolve().parents[1]
DEFAULT_ROOTS = {
    "foro": PROJECT_ROOT / "shared" / "media" / "foro",
    "perfiles": PROJECT_ROOT / "shared" / "media" / "perfiles",
    "recompensas": PROJECT_ROOT / "shared" / "media" / "recompensas",
    "campanas": PROJECT_ROOT / "shared" / "media" / "campanas",
    "eventos": PROJECT_ROOT / "shared" / "media" / "eventos",
    "puntos": PROJECT_ROOT / "shared" / "media" / "puntos",
}
LEGACY_ROOTS = (
    ("foro", PROJECT_ROOT / "flask_zerowaste" / "static" / "img" / "posts"),
    ("perfiles", PROJECT_ROOT / "flask_zerowaste" / "static" / "img" / "perfiles"),
    ("perfiles", PROJECT_ROOT / "laravel_zerowaste" / "public" / "img" / "perfiles"),
    ("perfiles", PROJECT_ROOT / "fast_api" / "static" / "perfiles"),
    ("perfiles", PROJECT_ROOT / "fast_api" / "static" / "img" / "perfiles"),
    ("recompensas", PROJECT_ROOT / "laravel_zerowaste" / "public" / "images" / "recompensas"),
    ("recompensas", PROJECT_ROOT / "mobile_app" / "assets" / "recompensas"),
    ("eventos", PROJECT_ROOT / "flask_zerowaste" / "static" / "img" / "eventos"),
    ("eventos", PROJECT_ROOT / "laravel_zerowaste" / "public" / "img" / "eventos"),
)
MEDIA_FIELDS = {
    "posts": ("imagen",),
    "usuarios": ("foto_perfil",),
    "locations": ("imagen",),
    "campaigns": ("imagen_url",),
    "eventos": ("imagen_url",),
    "recompensas": ("imagen",),
}


def sha256_file(path: Path) -> str:
    digest = hashlib.sha256()
    with path.open("rb") as handle:
        for chunk in iter(lambda: handle.read(1024 * 1024), b""):
            digest.update(chunk)
    return digest.hexdigest()


def canonical_basename(value: Any) -> str | None:
    if not isinstance(value, str) or not value.strip():
        return None
    clean = value.strip().replace("\\", "/").split("?", 1)[0].split("#", 1)[0]
    return clean.rstrip("/").rsplit("/", 1)[-1] or None


def load_metadata(path: Path | None) -> dict[str, list[dict[str, Any]]]:
    if path is None:
        return {}
    payload = json.loads(path.read_text(encoding="utf-8"))
    if not isinstance(payload, dict):
        raise ValueError("El export de metadatos debe ser un objeto JSON por tabla.")
    return {
        table: rows
        for table, rows in payload.items()
        if table in MEDIA_FIELDS and isinstance(rows, list)
    }


def exact_candidates(
    filename: str,
    metadata: dict[str, list[dict[str, Any]]],
) -> list[dict[str, Any]]:
    matches: list[dict[str, Any]] = []
    for table, fields in MEDIA_FIELDS.items():
        for row in metadata.get(table, []):
            if not isinstance(row, dict):
                continue
            for field in fields:
                if canonical_basename(row.get(field)) == filename:
                    matches.append({"table": table, "id": row.get("id"), "field": field})
    return matches


def iter_files(category: str, root: Path, source: str) -> Iterable[dict[str, Any]]:
    if not root.exists():
        return
    resolved_root = root.resolve()
    for path in sorted(root.rglob("*"), key=lambda item: item.as_posix().lower()):
        if path.is_symlink() or not path.is_file() or path.name.startswith("."):
            continue
        resolved = path.resolve()
        if resolved_root not in resolved.parents:
            continue
        stat = path.stat()
        yield {
            "category": category,
            "source": source,
            "path": path.relative_to(PROJECT_ROOT).as_posix()
            if PROJECT_ROOT in path.resolve().parents
            else str(path),
            "name": path.name,
            "extension": path.suffix.lower(),
            "size_bytes": stat.st_size,
            "modified_utc": datetime.fromtimestamp(stat.st_mtime, timezone.utc).isoformat(),
            "sha256": sha256_file(path),
        }


def parse_root(value: str) -> tuple[str, Path]:
    category, separator, raw_path = value.partition("=")
    if not separator or not category.strip() or not raw_path.strip():
        raise argparse.ArgumentTypeError("Usa --root categoria=/ruta/a/carpeta")
    return category.strip(), Path(os.path.expandvars(raw_path.strip())).expanduser()


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument(
        "--root",
        action="append",
        type=parse_root,
        help="Raíz adicional o alternativa: categoria=/ruta (repetible).",
    )
    parser.add_argument(
        "--include-legacy",
        action="store_true",
        help="Incluye los árboles históricos versionados para preparar la migración.",
    )
    parser.add_argument(
        "--metadata",
        type=Path,
        help="Export JSON read-only por tabla; solo se harán coincidencias exactas.",
    )
    args = parser.parse_args()

    roots = list((args.root or DEFAULT_ROOTS.items()))
    sources = [(category, root, "canonical") for category, root in roots]
    if args.include_legacy:
        sources.extend((category, root, "legacy") for category, root in LEGACY_ROOTS)

    metadata = load_metadata(args.metadata)
    files: list[dict[str, Any]] = []
    missing_roots: list[str] = []
    for category, root, source in sources:
        if not root.exists():
            missing_roots.append(str(root))
            continue
        for item in iter_files(category, root, source):
            candidates = exact_candidates(item["name"], metadata)
            item["relationship_status"] = (
                "unmatched" if not candidates else "exact" if len(candidates) == 1 else "ambiguous"
            )
            item["relationship_candidates"] = candidates
            files.append(item)

    duplicate_groups: dict[str, list[str]] = {}
    for item in files:
        duplicate_groups.setdefault(item["sha256"], []).append(item["path"])

    report = {
        "generated_at_utc": datetime.now(timezone.utc).isoformat(),
        "read_only": True,
        "metadata_loaded": bool(metadata),
        "missing_roots": missing_roots,
        "summary": {
            "files": len(files),
            "bytes": sum(item["size_bytes"] for item in files),
            "unmatched": sum(item["relationship_status"] == "unmatched" for item in files),
            "ambiguous": sum(item["relationship_status"] == "ambiguous" for item in files),
        },
        "duplicate_groups": [
            {"sha256": digest, "paths": paths}
            for digest, paths in sorted(duplicate_groups.items())
            if len(paths) > 1
        ],
        "files": files,
    }
    json.dump(report, fp=os.sys.stdout, ensure_ascii=False, indent=2)
    os.sys.stdout.write("\n")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
