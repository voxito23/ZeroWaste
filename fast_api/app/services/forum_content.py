"""Plain-text validation and safe serialization for forum comments."""

from __future__ import annotations

import re


MIN_COMMENT_LENGTH = 11
MAX_COMMENT_LENGTH = 1000
INVALID_COMMENT_PLACEHOLDER = "Contenido retirado por tener un formato inválido."

_CONTROL_CHARACTERS = re.compile(r"[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]")
_ACTIVE_HTML = re.compile(
    r"<\s*/?\s*(?:!doctype|html|head|body|script|style|iframe|object|embed|link|meta|div|span|form|input|button|svg)\b",
    re.IGNORECASE,
)
_TAILWIND_DUMP = re.compile(r"--tw-|\.flex-grow\s*\{|var\(--tw-", re.IGNORECASE)


def clean_plain_text(value: object) -> str:
    if not isinstance(value, str):
        raise ValueError("La respuesta debe ser texto.")
    normalized = value.replace("\r\n", "\n").replace("\r", "\n")
    return _CONTROL_CHARACTERS.sub("", normalized).strip()


def is_contaminated_comment(value: object) -> bool:
    if not isinstance(value, str):
        return True
    return bool(_ACTIVE_HTML.search(value) or _TAILWIND_DUMP.search(value))


def validate_comment(value: object) -> str:
    content = clean_plain_text(value)
    if len(content) < MIN_COMMENT_LENGTH:
        raise ValueError("La respuesta debe tener más de 10 caracteres.")
    if len(content) > MAX_COMMENT_LENGTH:
        raise ValueError(f"La respuesta no puede superar {MAX_COMMENT_LENGTH} caracteres.")
    if is_contaminated_comment(content):
        raise ValueError("La respuesta debe enviarse como texto plano, sin HTML ni CSS.")
    return content


def validate_forum_text(value: object, *, field_name: str, minimum: int, maximum: int) -> str:
    content = clean_plain_text(value)
    if len(content) < minimum:
        raise ValueError(f"{field_name} debe tener al menos {minimum} caracteres.")
    if len(content) > maximum:
        raise ValueError(f"{field_name} no puede superar {maximum} caracteres.")
    if is_contaminated_comment(content):
        raise ValueError(f"{field_name} debe enviarse como texto plano, sin HTML ni CSS.")
    return content


def safe_comment_for_output(value: object) -> tuple[str, bool]:
    try:
        content = clean_plain_text(value)
    except ValueError:
        return INVALID_COMMENT_PLACEHOLDER, True
    if is_contaminated_comment(content) or len(content) > MAX_COMMENT_LENGTH:
        return INVALID_COMMENT_PLACEHOLDER, True
    return content, False
