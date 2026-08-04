"""Canonical public media URLs and validated persistent image storage."""

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
ZEROWASTE_PUBLIC_HOSTS = {"zerowaste-qro.com", "www.zerowaste-qro.com"}
MAX_IMAGE_BYTES = 5 * 1024 * 1024
MAX_PROFILE_IMAGE_BYTES = 15 * 1024 * 1024
MAX_IMAGE_DIMENSION = 6000
PROFILE_IMAGE_MAX_DIMENSION = 1024
FORUM_IMAGE_MAX_DIMENSION = 1600

MEDIA_CATEGORIES = {
    "foro",
    "perfiles",
    "recompensas",
    "campanas",
    "eventos",
    "puntos",
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
    "static/img/campanas/": "campanas",
    "static/img/puntos/": "puntos",
    "img/eventos/": "eventos",
    "img/mapa/": "puntos",
    "api/foro/posts/imagenes/": "foro",
    "api/foro/perfiles/": "perfiles",
}

_IMAGE_FORMATS = {
    "JPEG": ("jpg", "image/jpeg"),
    "PNG": ("png", "image/png"),
    "WEBP": ("webp", "image/webp"),
}

_DEFAULT_AVATAR_FILENAMES = {"default.png", "perfil_default.png"}


class MediaValidationError(ValueError):
    """Raised when an uploaded media file is unsafe or unsupported."""

    def __init__(self, message: str, *, status_code: int = 422) -> None:
        super().__init__(message)
        self.status_code = status_code


def _normalized_category(category: str | None) -> str | None:
    if category is None:
        return None
    normalized = category.strip().lower().replace("campañas", "campanas")
    return normalized if normalized in MEDIA_CATEGORIES else None


def _is_public_hostname(hostname: str | None) -> bool:
    if not hostname:
        return False
    hostname = hostname.rstrip(".").lower()
    if hostname == "localhost" or hostname.endswith((".localhost", ".local")):
        return False
    try:
        address = ipaddress.ip_address(hostname)
    except ValueError:
        # Single-label names are normally Docker/internal service names.
        return "." in hostname and re.fullmatch(r"[a-z0-9.-]+", hostname) is not None
    return not (
        address.is_private
        or address.is_loopback
        or address.is_link_local
        or address.is_reserved
        or address.is_unspecified
    )


def _public_base() -> str:
    configured = os.getenv("PUBLIC_MEDIA_BASE_URL", DEFAULT_PUBLIC_MEDIA_BASE).strip()
    parsed = urlsplit(configured)
    if parsed.scheme.lower() != "https" or not _is_public_hostname(parsed.hostname):
        return DEFAULT_PUBLIC_MEDIA_BASE
    return configured.rstrip("/")


def build_public_media_url(path: object, category: str | None = None) -> str | None:
    """Return one HTTPS media URL without leaking container or host paths.

    Existing HTTPS URLs remain usable. Legacy ZeroWaste URL prefixes are mapped
    to the canonical ``/media/<category>/...`` layout. A bare filename requires
    an explicit category so callers cannot silently classify it incorrectly.
    """

    if path is None:
        return None
    value = str(path).strip()
    if not value or any(ord(char) < 32 for char in value):
        return None

    parsed = urlsplit(value)
    absolute_fallback: str | None = None
    if parsed.scheme:
        scheme = parsed.scheme.lower()
        hostname = (parsed.hostname or "").rstrip(".").lower()
        own_public_url = hostname in ZEROWASTE_PUBLIC_HOSTS
        if (
            scheme not in {"http", "https"}
            or (scheme != "https" and not own_public_url)
            or parsed.username
            or parsed.password
        ):
            return None
        if not _is_public_hostname(parsed.hostname):
            return None
        try:
            port = parsed.port
        except ValueError:
            return None
        safe_netloc = f"[{hostname}]" if ":" in hostname else hostname
        if port is not None:
            safe_netloc = f"{safe_netloc}:{port}"
        clean_path = quote(unquote(parsed.path), safe="/%:@-._~!$&'()*+,;=")
        clean_query = quote(unquote(parsed.query), safe="=&%:@-._~!$'()*+,;/?:")
        clean_fragment = quote(unquote(parsed.fragment), safe="%:@-._~!$&'()*+,;=/?")
        safe_absolute = urlunsplit(("https", safe_netloc, clean_path, clean_query, clean_fragment))
        if not own_public_url:
            return safe_absolute
        absolute_fallback = safe_absolute if scheme == "https" else None
        value = parsed.path

    if value.startswith(("//", "\\\\")) or ":\\" in value:
        return None

    decoded = unquote(value.replace("\\", "/")).lstrip("/")
    if decoded.startswith(("data/", "app/", "var/", "opt/", "home/", "tmp/")):
        return None
    parts = PurePosixPath(decoded).parts
    if not parts or any(part in {"", ".", ".."} for part in parts):
        return None

    selected_category = _normalized_category(category)
    relative_name: str | None = None

    if decoded.startswith("media/"):
        media_parts = PurePosixPath(decoded).parts
        if len(media_parts) < 3:
            return None
        selected_category = _normalized_category(media_parts[1])
        relative_name = "/".join(media_parts[2:])
    else:
        for prefix, legacy_category in _LEGACY_PREFIXES.items():
            if decoded.startswith(prefix):
                selected_category = legacy_category
                relative_name = decoded[len(prefix):]
                break

    if relative_name is None:
        # The old shared eventos directory held campaigns too; the caller's
        # explicit category wins so new URLs never mix those two domains.
        if decoded.startswith("static/img/eventos/"):
            relative_name = decoded[len("static/img/eventos/"):]
            selected_category = selected_category or "eventos"
        elif selected_category:
            relative_name = PurePosixPath(decoded).name

    if not selected_category or not relative_name:
        return absolute_fallback
    relative_parts = PurePosixPath(unquote(relative_name)).parts
    if not relative_parts or any(part in {"", ".", ".."} for part in relative_parts):
        return None

    safe_relative = quote("/".join(relative_parts), safe="@-._~")
    return f"{_public_base()}/{selected_category}/{safe_relative}"


def build_public_avatar_url(path: object) -> str | None:
    """Return a real profile image URL, or ``None`` for legacy placeholders."""

    if path is None:
        return None
    value = str(path).strip()
    filename = value.replace("\\", "/").split("?", 1)[0].rstrip("/").rsplit("/", 1)[-1].lower()
    if not filename or filename in _DEFAULT_AVATAR_FILENAMES:
        return None
    return build_public_media_url(value, "perfiles")


def media_directory(category: str) -> Path:
    """Resolve a writable persistent category directory."""

    normalized = _normalized_category(category)
    if not normalized:
        raise ValueError("Categoría de medios no permitida.")
    override = os.getenv(_CATEGORY_ENV[normalized], "").strip()
    root = Path(os.getenv("MEDIA_ROOT", "/data/media"))
    return Path(override) if override else root / normalized


def save_media_image(content: bytes, category: str, *, maximum_bytes: int = MAX_IMAGE_BYTES) -> str:
    """Validate and normalize image bytes, then persist them under a UUID name."""

    normalized_category = _normalized_category(category)
    if normalized_category is None:
        raise ValueError("Categoría de medios no permitida.")
    if maximum_bytes < 1 or maximum_bytes > MAX_PROFILE_IMAGE_BYTES:
        raise ValueError("El límite interno de imagen no es válido.")
    if not content or len(content) > maximum_bytes:
        maximum_mb = maximum_bytes // (1024 * 1024)
        raise MediaValidationError(
            f"La imagen debe pesar como máximo {maximum_mb} MB.", status_code=413
        )

    try:
        probe = Image.open(io.BytesIO(content))
        probe.verify()
        source = Image.open(io.BytesIO(content))
        source.load()
        source = ImageOps.exif_transpose(source)
        image_format = (source.format or probe.format or "").upper()
        format_config = _IMAGE_FORMATS.get(image_format)
        if not format_config:
            raise MediaValidationError(
                "Usa una imagen JPEG, PNG o WebP.", status_code=415
            )
        if source.width > MAX_IMAGE_DIMENSION or source.height > MAX_IMAGE_DIMENSION:
            raise MediaValidationError("La imagen excede las dimensiones permitidas.")
        delivery_max_dimension = {
            "perfiles": PROFILE_IMAGE_MAX_DIMENSION,
            "foro": FORUM_IMAGE_MAX_DIMENSION,
        }.get(normalized_category)
        if delivery_max_dimension and max(source.size) > delivery_max_dimension:
            source.thumbnail(
                (delivery_max_dimension, delivery_max_dimension),
                Image.Resampling.LANCZOS,
            )
    except MediaValidationError:
        raise
    except (Image.DecompressionBombError, UnidentifiedImageError, OSError, ValueError):
        raise MediaValidationError(
            "El archivo no es una imagen válida.", status_code=415
        ) from None

    output_format = "WEBP" if normalized_category == "foro" else image_format
    extension, _mime = _IMAGE_FORMATS[output_format]
    destination_dir = media_directory(normalized_category)
    destination_dir.mkdir(parents=True, exist_ok=True)
    filename = f"{uuid4().hex}.{extension}"
    destination = destination_dir / filename

    if output_format == "JPEG":
        clean = source.convert("RGB")
        clean.save(
            destination,
            format="JPEG",
            quality=86 if normalized_category == "perfiles" else 88,
            optimize=normalized_category != "perfiles",
        )
    elif output_format == "PNG":
        clean = source.copy()
        clean.save(destination, format="PNG", optimize=normalized_category != "perfiles")
    else:
        clean = source.convert("RGBA" if source.mode in {"RGBA", "LA"} or "transparency" in source.info else "RGB")
        clean.save(
            destination,
            format="WEBP",
            quality=82 if normalized_category == "foro" else (86 if normalized_category == "perfiles" else 88),
            method=2 if normalized_category == "perfiles" else 4,
        )
    try:
        destination.chmod(0o660)
    except OSError:
        destination.unlink(missing_ok=True)
        raise MediaValidationError(
            "No fue posible aplicar permisos seguros al archivo.", status_code=500
        ) from None
    return filename


def remove_media_file(filename: str | None, category: str) -> None:
    """Remove only a just-created UUID media file during transaction rollback."""

    if not filename or Path(filename).name != filename:
        return
    try:
        (media_directory(category) / filename).unlink(missing_ok=True)
    except OSError:
        # Cleanup failure must never hide the original database error.
        pass
