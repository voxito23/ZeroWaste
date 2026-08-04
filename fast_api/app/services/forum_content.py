"""Plain-text validation and safe serialization for forum comments."""

from __future__ import annotations

import re
from html.parser import HTMLParser


MIN_COMMENT_LENGTH = 11
MAX_COMMENT_LENGTH = 1000
INVALID_COMMENT_PLACEHOLDER = "Contenido retirado por tener un formato inválido."

_CONTROL_CHARACTERS = re.compile(r"[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]")
_ACTIVE_HTML = re.compile(
    r"<\s*/?\s*(?:!doctype|html|head|body|script|style|iframe|object|embed|link|meta|div|span|form|input|button|svg)\b",
    re.IGNORECASE,
)
_TAILWIND_DUMP = re.compile(r"--tw-|\.flex-grow\s*\{|var\(--tw-", re.IGNORECASE)
_BLOCK_TAGS = {"article", "blockquote", "div", "h1", "h2", "h3", "h4", "h5", "h6", "ol", "p", "section", "table", "tr", "ul"}


class _ForumPlainTextParser(HTMLParser):
    """Turn historic editor markup into readable text without activating HTML."""

    def __init__(self) -> None:
        super().__init__(convert_charrefs=True)
        self.parts: list[str] = []
        self.ignored_depth = 0

    def handle_starttag(self, tag: str, attrs) -> None:
        tag = tag.lower()
        if tag in {"script", "style"}:
            self.ignored_depth += 1
        elif not self.ignored_depth and tag == "br":
            self.parts.append("\n")
        elif not self.ignored_depth and tag == "li":
            self.parts.append("\n• ")
        elif not self.ignored_depth and tag in _BLOCK_TAGS:
            self.parts.append("\n")

    def handle_endtag(self, tag: str) -> None:
        tag = tag.lower()
        if tag in {"script", "style"} and self.ignored_depth:
            self.ignored_depth -= 1
        elif not self.ignored_depth and (tag == "li" or tag in _BLOCK_TAGS):
            self.parts.append("\n")

    def handle_data(self, data: str) -> None:
        if not self.ignored_depth:
            self.parts.append(data)


def forum_text_for_output(value: object) -> str:
    """Safely normalize plain text and legacy rich-editor HTML for API output."""
    if not isinstance(value, str):
        return ""
    parser = _ForumPlainTextParser()
    try:
        parser.feed(_CONTROL_CHARACTERS.sub("", value))
        parser.close()
        text = "".join(parser.parts)
    except Exception:
        text = re.sub(r"<[^>]*>", "", value)
    text = text.replace("\r", "")
    text = re.sub(r"[ \t]+\n", "\n", text)
    text = re.sub(r"\n[ \t]+", "\n", text)
    text = re.sub(r"\n{3,}", "\n\n", text)
    text = re.sub(r"[ \t]{2,}", " ", text)
    return text.strip()


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
