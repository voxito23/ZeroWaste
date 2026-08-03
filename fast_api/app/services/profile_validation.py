"""Canonical validation for user-editable profile fields."""

from __future__ import annotations


PROFILE_TITLES = frozenset({
    "Usuario Eco-consciente",
    "Entusiasta del Desarrollo Sostenible",
    "Activista Ambiental",
    "Ingeniero Ambiental",
    "Estudiante Comprometido con el Medio Ambiente",
    "Promotor de Reciclaje",
    "Educador Ecológico",
    "Voluntario Verde",
    "Emprendedor Sustentable",
    "Líder Comunitario Ecológico",
})


def _clean(value: str) -> str:
    return value.strip()


def _reject_markup(value: str, label: str) -> None:
    if "<" in value or ">" in value:
        raise ValueError(f"{label} no puede contener etiquetas o símbolos < >.")


def validate_profile_name(value: str) -> str:
    clean = " ".join(_clean(value).split())
    if len(clean) < 10:
        raise ValueError("El nombre debe tener al menos 10 caracteres.")
    if len(clean) > 50:
        raise ValueError("El nombre puede tener como máximo 50 caracteres.")
    _reject_markup(clean, "El nombre")
    return clean


def validate_profile_location(value: str) -> str:
    clean = " ".join(_clean(value).split())
    if len(clean) < 10:
        raise ValueError("La ubicación debe tener al menos 10 caracteres.")
    if len(clean) > 50:
        raise ValueError("La ubicación puede tener como máximo 50 caracteres.")
    _reject_markup(clean, "La ubicación")
    return clean


def validate_profile_title(value: str) -> str:
    clean = _clean(value)
    if clean not in PROFILE_TITLES:
        raise ValueError("Selecciona un título de perfil válido.")
    return clean


def validate_profile_bio(value: str) -> str:
    clean = _clean(value)
    if not clean:
        raise ValueError("La biografía debe tener al menos 1 carácter.")
    if len(clean) > 100:
        raise ValueError("La biografía puede tener como máximo 100 caracteres.")
    _reject_markup(clean, "La biografía")
    return clean
