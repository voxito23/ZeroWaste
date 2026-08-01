"""Contratos estáticos del flujo de fotografía de perfil en la app móvil."""

from pathlib import Path
import unittest


ROOT = Path(__file__).resolve().parents[1]


class MobileProfileMediaTests(unittest.TestCase):
    def test_registration_picker_sends_selected_profile_photo(self):
        source = (ROOT / "mobile_app/screens/RegisterScreen.js").read_text(encoding="utf-8")
        self.assertIn("requestMediaLibraryPermissionsAsync", source)
        self.assertIn("form.append('foto_perfil'", source)
        self.assertIn("api.post('/auth/registro', form)", source)

    def test_profile_editor_sends_photo_and_empty_text_fields(self):
        source = (ROOT / "mobile_app/screens/EditProfileScreen.js").read_text(encoding="utf-8")
        self.assertIn("form.append('ubicacion', location.trim())", source)
        self.assertIn("form.append('biografia', bio.trim())", source)
        self.assertIn("form.append('foto_perfil'", source)
        self.assertIn("api.put('/usuarios/perfil/actualizar', form)", source)


if __name__ == "__main__":
    unittest.main()
