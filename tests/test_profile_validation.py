"""Profile validation stays aligned between Flask, FastAPI and mobile."""

from __future__ import annotations

import importlib.util
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]


def load_validation_module():
    path = ROOT / "fast_api/app/services/profile_validation.py"
    spec = importlib.util.spec_from_file_location("zerowaste_profile_validation", path)
    if spec is None or spec.loader is None:
        raise RuntimeError("No fue posible cargar profile_validation.py")
    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)
    return module


class ProfileValidationTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.validation = load_validation_module()

    def test_flask_lengths_are_enforced_by_fastapi(self):
        with self.assertRaisesRegex(ValueError, "al menos 10"):
            self.validation.validate_profile_name("Ana")
        with self.assertRaisesRegex(ValueError, "al menos 10"):
            self.validation.validate_profile_location("Qro")
        with self.assertRaisesRegex(ValueError, "al menos 1"):
            self.validation.validate_profile_bio("   ")
        with self.assertRaisesRegex(ValueError, "máximo 100"):
            self.validation.validate_profile_bio("x" * 101)

    def test_title_must_come_from_the_flask_catalog(self):
        self.assertEqual(
            self.validation.validate_profile_title("Promotor de Reciclaje"),
            "Promotor de Reciclaje",
        )
        with self.assertRaisesRegex(ValueError, "título de perfil válido"):
            self.validation.validate_profile_title("Administrador")

    def test_valid_fields_are_normalized_and_markup_is_rejected(self):
        self.assertEqual(
            self.validation.validate_profile_name("  Víctor   Rodríguez  "),
            "Víctor Rodríguez",
        )
        self.assertEqual(
            self.validation.validate_profile_location(" Querétaro, Qro. "),
            "Querétaro, Qro.",
        )
        with self.assertRaisesRegex(ValueError, "etiquetas"):
            self.validation.validate_profile_bio("<b>Eco</b>")


if __name__ == "__main__":
    unittest.main()
