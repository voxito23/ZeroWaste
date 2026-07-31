"""Shared-media URL normalization and safe image persistence for Flask."""

from __future__ import annotations

import io
import ipaddress
import os
import re
from pathlib import Path, PurePosixPath
from urllib.parse import quote, unquote, urlsplit, urlunsplit
from uuid import uuid4

from PIL import Image, ImageOps, UnidentifiedImageError


DEFAULT_PUBLIC_MEDIA_BASE = "https://www.zerowaste-qro.com/media"
MAX_IMAGE_BYTES = 5 * 1024 * 1024
MAX_IMAGE_DIMENSION = 6000
MEDIA_CATEGORIES = {
    "foro", "perfiles", "recompensas", "campanas", "eventos", "puntos"
}
_CATEGORY_ENV = {
    "foro": "FORUM_MEDIA_DIR",
    "perfiles": "PROFILE_MEDIA_DIR",
    "recompensas": "REWARDS_MEDIA_DIR",
    "campanas": "CAMPAIGNS_MEDIA_DIR",
    "eventos": "EVENTS_MEDIA_DIR",
    "puntos": "POINTS_MEDIA_DIR",
}
_LEGACY_PREFIXES = {
    "images/recompensas/": "recompensas",
    "img/perfiles/": "perfiles",
    "static/img/posts/": "foro",
    "static/img/perfiles/": "perfiles",
    "api/foro/posts/imagenes/": "foro",
    "api/foro/perfiles/": "perfiles",
}
_FORMATS = {"JPEG": "jpg", "PNG": "png", "WEBP": "webp"}


class MediaValidationError(ValueError):
    def __init__(self, message: str, *, status_code: int = 422) -> None:
        super().__init__(message)
        self.status_code = status_code


def _category(value):
    if value is None:
        return None
    normalized = str(value).strip().lower().replace("campañas", "campanas")
    return normalized if normalized in MEDIA_CATEGORIES else None


def _public_host(hostname):
    if not hostname:
        return False
    hostname = hostname.rstrip(".").lower()
    if hostname == "localhost" or hostname.endswith((".localhost", ".local")):
        return False
    try:
        address = ipaddress.ip_address(hostname)
    except ValueError:
        return "." in hostname and re.fullmatch(r"[a-z0-9.-]+", hostname) is not None
    return not (
        address.is_private or address.is_loopback or address.is_link_local
        or address.is_reserved or address.is_unspecified
    )


def _public_base():
    configured = os.environ.get(
        "PUBLIC_MEDIA_BASE_URL", DEFAULT_PUBLIC_MEDIA_BASE
    ).strip()
    parsed = urlsplit(configured)
    if parsed.scheme.lower() != "https" or not _public_host(parsed.hostname):
        return DEFAULT_PUBLIC_MEDIA_BASE
    return configured.rstrip("/")


def build_public_media_url(path, category=None):
    if path is None:
        return None
    value = str(path).strip()
    if not value or any(ord(char) < 32 for char in value):
        return None

    parsed = urlsplit(value)
    if parsed.scheme:
        if parsed.scheme.lower() != "https" or parsed.username or parsed.password:
            return None
        if not _public_host(parsed.hostname):
            return None
        try:
            port = parsed.port
        except ValueError:
            return None
        hostname = parsed.hostname or ""
        safe_netloc = f"[{hostname}]" if ":" in hostname else hostname
        if port is not None:
            safe_netloc = f"{safe_netloc}:{port}"
        clean_path = quote(unquote(parsed.path), safe="/%:@-._~!$&'()*+,;=")
        clean_query = quote(unquote(parsed.query), safe="=&%:@-._~!$'()*+,;/?:")
        clean_fragment = quote(unquote(parsed.fragment), safe="%:@-._~!$&'()*+,;=/?")
        return urlunsplit(("https", safe_netloc, clean_path, clean_query, clean_fragment))
    if value.startswith(("//", "\\\\")) or ":\\" in value:
        return None

    decoded = unquote(value.replace("\\", "/")).lstrip("/")
    if decoded.startswith(("data/", "app/", "var/", "opt/", "home/", "tmp/")):
        return None
    parts = PurePosixPath(decoded).parts
    if not parts or any(part in {"", ".", ".."} for part in parts):
        return None

    selected = _category(category)
    relative = None
    if decoded.startswith("media/"):
        media_parts = PurePosixPath(decoded).parts
        if len(media_parts) < 3:
            return None
        selected = _category(media_parts[1])
        relative = "/".join(media_parts[2:])
    else:
        for prefix, legacy_category in _LEGACY_PREFIXES.items():
            if decoded.startswith(prefix):
                selected = legacy_category
                relative = decoded[len(prefix):]
                break
    if relative is None:
        if decoded.startswith("static/img/eventos/"):
            relative = decoded[len("static/img/eventos/"):]
            selected = selected or "eventos"
        elif selected:
            relative = decoded
    if not selected or not relative:
        return None
    relative_parts = PurePosixPath(unquote(relative)).parts
    if not relative_parts or any(part in {"", ".", ".."} for part in relative_parts):
        return None
    safe_relative = quote("/".join(relative_parts), safe="@-._~")
    return f"{_public_base()}/{selected}/{safe_relative}"


def media_directory(category):
    normalized = _category(category)
    if not normalized:
        raise ValueError("Categoría de medios no permitida.")
    override = os.environ.get(_CATEGORY_ENV[normalized], "").strip()
    root = Path(os.environ.get("MEDIA_ROOT", "/data/media"))
    return Path(override) if override else root / normalized


def save_uploaded_image(upload, category):
    content = upload.stream.read(MAX_IMAGE_BYTES + 1)
    if not content or len(content) > MAX_IMAGE_BYTES:
        raise MediaValidationError(
            "La imagen debe pesar como máximo 5 MB.", status_code=413
        )
    try:
        probe = Image.open(io.BytesIO(content))
        probe.verify()
        source = Image.open(io.BytesIO(content))
        source.load()
        image_format = (source.format or probe.format or "").upper()
        extension = _FORMATS.get(image_format)
        if not extension:
            raise MediaValidationError(
                "Usa una imagen JPEG, PNG o WebP.", status_code=415
            )
        if source.width > MAX_IMAGE_DIMENSION or source.height > MAX_IMAGE_DIMENSION:
            raise MediaValidationError("La imagen excede las dimensiones permitidas.")
        source = ImageOps.exif_transpose(source)
    except MediaValidationError:
        raise
    except (Image.DecompressionBombError, UnidentifiedImageError, OSError, ValueError):
        raise MediaValidationError(
            "El archivo no es una imagen válida.", status_code=415
        ) from None

    directory = media_directory(category)
    directory.mkdir(parents=True, exist_ok=True)
    filename = f"{uuid4().hex}.{extension}"
    destination = directory / filename
    if image_format == "JPEG":
        source.convert("RGB").save(destination, "JPEG", quality=88, optimize=True)
    elif image_format == "PNG":
        source.copy().save(destination, "PNG", optimize=True)
    else:
        source.copy().save(destination, "WEBP", quality=88, method=4)
    try:
        destination.chmod(0o660)
    except OSError:
        destination.unlink(missing_ok=True)
        raise MediaValidationError(
            "No fue posible aplicar permisos seguros al archivo.", status_code=500
        ) from None
    return filename


def remove_media_file(filename, category):
    if not filename or Path(filename).name != filename:
        return
    try:
        (media_directory(category) / filename).unlink(missing_ok=True)
    except OSError:
        pass
