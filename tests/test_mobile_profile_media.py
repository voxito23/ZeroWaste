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

    def test_cached_profile_and_images_render_without_waiting_for_the_network(self):
        profile = (ROOT / "mobile_app/screens/ProfileScreen.js").read_text(encoding="utf-8")
        avatar = (ROOT / "mobile_app/components/ui/UserAvatar.js").read_text(encoding="utf-8")
        remote = (ROOT / "mobile_app/components/ui/RemoteImage.js").read_text(encoding="utf-8")
        home = (ROOT / "mobile_app/screens/HomeScreen.js").read_text(encoding="utf-8")
        editorial = (ROOT / "mobile_app/data/editorialContent.js").read_text(encoding="utf-8")
        self.assertIn("useState(!user)", profile)
        self.assertIn("useRef(Boolean(user))", profile)
        self.assertIn("loadedAvatarUris", avatar)
        self.assertIn("cache: 'force-cache'", avatar)
        self.assertIn("loadedRemoteUris", remote)
        self.assertIn("resizeMethod=\"resize\"", remote)
        self.assertIn("EDITORIAL_IMAGES[item.id] ? null", home)
        for filename in ("plasticos-mobile.jpg", "aguah-mobile.jpg", "solar-mobile.jpg", "composta-mobile.jpg"):
            self.assertIn(filename, editorial)
            self.assertLess((ROOT / "mobile_app/assets/images" / filename).stat().st_size, 350_000)
            self.assertLess((ROOT / "flask_zerowaste/static/img" / filename).stat().st_size, 350_000)

    def test_forum_picker_enforces_the_backend_limit_before_upload(self):
        upload = (ROOT / "mobile_app/utils/imageUpload.js").read_text(encoding="utf-8")
        composer = (ROOT / "mobile_app/screens/CreatePostScreen.js").read_text(encoding="utf-8")
        self.assertIn("MAX_FORUM_IMAGE_BYTES = 5 * 1024 * 1024", upload)
        self.assertIn("validatePickedForumImage", upload)
        self.assertIn("validatePickedForumImage(result.assets[0])", composer)
        self.assertIn("quality: 0.45", composer)


if __name__ == "__main__":
    unittest.main()
