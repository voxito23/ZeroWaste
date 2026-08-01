"""Canonical opaque QR token format shared by every ZeroWaste API flow."""

from __future__ import annotations

import base64
import hashlib
import os
import secrets
from dataclasses import dataclass
from urllib.parse import urlparse

PUBLIC_HOSTS = {"www.zerowaste-qro.com", "zerowaste-qro.com"}
PUBLIC_BASE_URL = "https://www.zerowaste-qro.com"
POINT_PREFIX = "zw1p_"
COLLECTION_PREFIX = "zw1c_"


@dataclass(frozen=True)
class ParsedQr:
    kind: str
    token: str


class QrContentError(ValueError):
    def __init__(self, code: str, detail: str):
        super().__init__(detail)
        self.code = code
        self.detail = detail


def new_token(kind: str) -> str:
    prefix = POINT_PREFIX if kind == "recycling_point" else COLLECTION_PREFIX
    return prefix + secrets.token_urlsafe(32)


def token_hash(token: str) -> str:
    return hashlib.sha256(token.encode("utf-8")).hexdigest()


def public_content(token: str) -> str:
    segment = "p" if token.startswith(POINT_PREFIX) else "c"
    return f"{PUBLIC_BASE_URL}/q/{segment}/{token}"


def parse_content(content: object) -> ParsedQr:
    raw = str(content or "").strip()
    if not raw:
        raise QrContentError("NOT_ZEROWASTE_QR", "Este código QR no pertenece a ZeroWaste.")

    parsed = urlparse(raw)
    token = ""
    expected_segment = ""
    if parsed.scheme == "https" and (parsed.hostname or "").lower() in PUBLIC_HOSTS:
        parts = [part for part in parsed.path.split("/") if part]
        if len(parts) == 3 and parts[0] == "q" and parts[1] in {"p", "c"} and not parsed.query and not parsed.fragment:
            expected_segment, token = parts[1], parts[2]
    elif parsed.scheme == "zerowaste" and parsed.netloc == "qr":
        parts = [part for part in parsed.path.split("/") if part]
        if len(parts) == 1 and not parsed.query and not parsed.fragment:
            token = parts[0]
            expected_segment = "p" if token.startswith(POINT_PREFIX) else "c"

    if not token:
        raise QrContentError("NOT_ZEROWASTE_QR", "Este código QR no pertenece a ZeroWaste.")
    if token.startswith(POINT_PREFIX) and expected_segment == "p":
        kind = "recycling_point"
    elif token.startswith(COLLECTION_PREFIX) and expected_segment == "c":
        kind = "collection"
    else:
        raise QrContentError("QR_TAMPERED", "Este código QR no es válido o fue modificado.")
    if len(token) < 45 or len(token) > 80:
        raise QrContentError("QR_TAMPERED", "Este código QR no es válido o fue modificado.")
    return ParsedQr(kind=kind, token=token)


def _fernet():
    from cryptography.fernet import Fernet

    secret = os.getenv("QR_TOKEN_ENCRYPTION_KEY", "").strip()
    if not secret:
        raise RuntimeError("Required environment variable is not configured: QR_TOKEN_ENCRYPTION_KEY")
    key = base64.urlsafe_b64encode(hashlib.sha256(secret.encode("utf-8")).digest())
    return Fernet(key)


def encrypt_token(token: str) -> str:
    return _fernet().encrypt(token.encode("utf-8")).decode("ascii")


def decrypt_token(ciphertext: str) -> str:
    from cryptography.fernet import InvalidToken

    try:
        return _fernet().decrypt(ciphertext.encode("ascii")).decode("utf-8")
    except InvalidToken as error:
        raise QrContentError("QR_TAMPERED", "Este código QR no es válido o fue modificado.") from error
