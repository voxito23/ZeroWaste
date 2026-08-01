"""Plain-text validation for the Flask forum compatibility UI."""

from __future__ import annotations

import re


MIN_COMMENT_LENGTH = 11
MAX_COMMENT_LENGTH = 1000
_CONTROL_CHARACTERS = re.compile(r"[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]")
_ACTIVE_HTML = re.compile(
    r"<\s*/?\s*(?:!doctype|html|head|body|script|style|iframe|object|embed|link|meta|div|span|form|input|button|svg)\b",
    re.IGNORECASE,
)
_TAILWIND_DUMP = re.compile(r"--tw-|\.flex-grow\s*\{|var\(--tw-", re.IGNORECASE)


def is_invalid_comment(value: object) -> bool:
    return not isinstance(value, str) or bool(
        _ACTIVE_HTML.search(value) or _TAILWIND_DUMP.search(value) or len(value) > MAX_COMMENT_LENGTH
    )


def normalize_comment(value: object) -> str:
    if not isinstance(value, str):
        raise ValueError("La respuesta debe ser texto.")
    content = _CONTROL_CHARACTERS.sub(
        "", value.replace("\r\n", "\n").replace("\r", "\n")
    ).strip()
    if len(content) < MIN_COMMENT_LENGTH:
        raise ValueError("La respuesta debe tener más de 10 caracteres.")
    if len(content) > MAX_COMMENT_LENGTH:
        raise ValueError(f"La respuesta no puede superar {MAX_COMMENT_LENGTH} caracteres.")
    if is_invalid_comment(content):
        raise ValueError("La respuesta debe enviarse como texto plano, sin HTML ni CSS.")
    return content
