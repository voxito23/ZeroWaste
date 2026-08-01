import base64
import hashlib
import os


def digest(value: str) -> str:
    return hashlib.sha256(value.encode("utf-8")).hexdigest()


def _fernet():
    from cryptography.fernet import Fernet

    secret = os.getenv("AUTH_TOKEN_ENCRYPTION_KEY", "").strip()
    if not secret:
        raise RuntimeError("Required environment variable is not configured: AUTH_TOKEN_ENCRYPTION_KEY")
    return Fernet(base64.urlsafe_b64encode(hashlib.sha256(secret.encode("utf-8")).digest()))


def encrypt(value: str) -> str:
    return _fernet().encrypt(value.encode("utf-8")).decode("ascii")


def decrypt(value: str) -> str:
    return _fernet().decrypt(value.encode("ascii")).decode("utf-8")
