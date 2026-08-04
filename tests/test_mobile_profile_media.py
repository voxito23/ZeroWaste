"""Contratos estáticos del flujo de fotografía de perfil en la app móvil."""

from pathlib import Path
import unittest


ROOT = Path(__file__).resolve().parents[1]


class MobileProfileMediaTests(unittest.TestCase):
    def test_mobile_and_fastapi_allow_15mb_only_for_profile_images(self):
        mobile = (ROOT / "mobile_app/utils/imageUpload.js").read_text(encoding="utf-8")
        media = (ROOT / "fast_api/app/services/media.py").read_text(encoding="utf-8")
        users = (ROOT / "fast_api/app/routers/usuarios.py").read_text(encoding="utf-8")
        forum = (ROOT / "fast_api/app/routers/foro.py").read_text(encoding="utf-8")
        self.assertIn("MAX_PROFILE_IMAGE_BYTES = 15 * 1024 * 1024", mobile)
        self.assertIn("MAX_PROFILE_IMAGE_BYTES = 15 * 1024 * 1024", media)
        self.assertIn("PROFILE_IMAGE_MAX_DIMENSION = 1024", media)
        self.assertIn('normalized_category == "perfiles"', media)
        self.assertIn('maximum_bytes=MAX_PROFILE_IMAGE_BYTES', users)
        self.assertIn("MAX_IMAGE_BYTES + 1", forum)

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
        self.assertIn("api.put('/usuarios/perfil/actualizar', form, { timeout: PROFILE_UPLOAD_TIMEOUT_MS })", source)
        self.assertIn("PROFILE_UPLOAD_TIMEOUT_MS = 45_000", source)


if __name__ == "__main__":
    unittest.main()
