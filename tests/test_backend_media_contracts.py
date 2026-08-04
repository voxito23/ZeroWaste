"""Backend media and service-contract tests that never connect to a database."""

from __future__ import annotations

import importlib.util
import io
import os
import re
import tempfile
import unittest
from pathlib import Path
from types import SimpleNamespace
from unittest.mock import patch

from PIL import Image


ROOT = Path(__file__).resolve().parents[1]


def load_module(name: str, path: Path):
    spec = importlib.util.spec_from_file_location(name, path)
    if spec is None or spec.loader is None:
        raise RuntimeError(f"Unable to load test module: {path.name}")
    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)
    return module


class BackendMediaContractTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.fastapi_media = load_module(
            "zerowaste_fastapi_media",
            ROOT / "fast_api" / "app" / "services" / "media.py",
        )
        cls.flask_media = load_module(
            "zerowaste_flask_media",
            ROOT / "flask_zerowaste" / "media.py",
        )

    def test_media_url_contract(self):
        base = "https://www.zerowaste-qro.com/media"
        for media in (self.fastapi_media, self.flask_media):
            build = media.build_public_media_url
            self.assertEqual(f"{base}/foro/post.jpg", build("post.jpg", "foro"))
            self.assertEqual(f"{base}/puntos/punto.webp", build("/media/puntos/punto.webp"))
            self.assertEqual(
                f"{base}/recompensas/termo.png",
                build("/images/recompensas/termo.png"),
            )
            self.assertEqual(
                f"{base}/perfiles/avatar.jpg", build("/img/perfiles/avatar.jpg")
            )
            self.assertEqual(
                "https://cdn.example.com/image.png",
                build("https://cdn.example.com/image.png"),
            )
            self.assertEqual(
                f"{base}/foro/legacy.jpg",
                build(
                    "https://www.zerowaste-qro.com/static/img/posts/legacy.jpg",
                    "foro",
                ),
            )
            self.assertEqual(
                f"{base}/campanas/campania.jpg",
                build("https://zerowaste-qro.com/static/img/eventos/campania.jpg", "campanas"),
            )
            self.assertEqual(
                f"{base}/puntos/punto_123.jpg",
                build("/static/img/punto_123.jpg", "puntos"),
            )
            self.assertEqual(
                f"{base}/puntos/acopio.jpg",
                build("carpeta/antigua/acopio.jpg", "puntos"),
            )
            avatar = media.build_public_avatar_url
            self.assertEqual(f"{base}/perfiles/avatar.jpg", avatar("/img/perfiles/avatar.jpg"))
            self.assertIsNone(avatar(None))
            self.assertIsNone(avatar(""))
            self.assertIsNone(avatar("default.png"))
            self.assertIsNone(avatar("perfil_default.png"))

            for unsafe in (
                "javascript:alert(1)",
                "file:///etc/passwd",
                "http://example.com/image.jpg",
                "https://localhost/image.jpg",
                "/data/media/foro/internal.jpg",
                "../escape.jpg",
                "C:\\media\\private.jpg",
            ):
                with self.subTest(unsafe=unsafe):
                    self.assertIsNone(build(unsafe, "foro"))

    def test_fastapi_image_storage_uses_uuid_and_category(self):
        stream = io.BytesIO()
        Image.new("RGB", (8, 8), (0, 128, 64)).save(stream, format="PNG")
        with tempfile.TemporaryDirectory(dir=ROOT) as temp_dir:
            with patch.dict(os.environ, {"MEDIA_ROOT": temp_dir}):
                filename = self.fastapi_media.save_media_image(
                    stream.getvalue(), "puntos"
                )
            self.assertRegex(filename, r"^[0-9a-f]{32}\.png$")
            self.assertTrue((Path(temp_dir) / "puntos" / filename).is_file())
            self.assertFalse((Path(temp_dir) / "eventos" / filename).exists())

    def test_fastapi_image_storage_rejects_non_image(self):
        with tempfile.TemporaryDirectory(dir=ROOT) as temp_dir:
            with patch.dict(os.environ, {"MEDIA_ROOT": temp_dir}):
                with self.assertRaises(self.fastapi_media.MediaValidationError):
                    self.fastapi_media.save_media_image(b"not-an-image", "foro")

    def test_profile_image_limit_is_15mb_without_raising_forum_limit(self):
        stream = io.BytesIO()
        Image.new("RGB", (8, 8), (0, 128, 64)).save(stream, format="PNG")
        content = stream.getvalue() + b"\0" * (self.fastapi_media.MAX_IMAGE_BYTES + 1)
        with tempfile.TemporaryDirectory(dir=ROOT) as temp_dir:
            with patch.dict(os.environ, {"MEDIA_ROOT": temp_dir}):
                with self.assertRaises(self.fastapi_media.MediaValidationError):
                    self.fastapi_media.save_media_image(content, "foro")
                filename = self.fastapi_media.save_media_image(
                    content,
                    "perfiles",
                    maximum_bytes=self.fastapi_media.MAX_PROFILE_IMAGE_BYTES,
                )
        self.assertTrue(filename.endswith(".png"))

    def test_profile_images_are_resized_for_fast_mobile_delivery(self):
        stream = io.BytesIO()
        Image.new("RGB", (2048, 1536), (0, 128, 64)).save(stream, format="JPEG", quality=92)
        with tempfile.TemporaryDirectory(dir=ROOT) as temp_dir:
            with patch.dict(os.environ, {"MEDIA_ROOT": temp_dir}):
                filename = self.fastapi_media.save_media_image(
                    stream.getvalue(),
                    "perfiles",
                    maximum_bytes=self.fastapi_media.MAX_PROFILE_IMAGE_BYTES,
                )
            with Image.open(Path(temp_dir) / "perfiles" / filename) as stored:
                self.assertLessEqual(max(stored.size), self.fastapi_media.PROFILE_IMAGE_MAX_DIMENSION)

    def test_new_forum_images_are_resized_and_encoded_as_webp(self):
        stream = io.BytesIO()
        Image.new("RGB", (2400, 1500), (0, 128, 64)).save(stream, format="PNG")
        content = stream.getvalue()
        with tempfile.TemporaryDirectory(dir=ROOT) as temp_dir:
            with patch.dict(os.environ, {"MEDIA_ROOT": temp_dir}):
                fastapi_filename = self.fastapi_media.save_media_image(content, "foro")
                flask_filename = self.flask_media.save_uploaded_image(
                    SimpleNamespace(stream=io.BytesIO(content)), "foro"
                )
            for filename in (fastapi_filename, flask_filename):
                self.assertTrue(filename.endswith(".webp"))
                with Image.open(Path(temp_dir) / "foro" / filename) as stored:
                    self.assertEqual(stored.format, "WEBP")
                    self.assertLessEqual(max(stored.size), 1600)

    def test_backend_source_contracts_are_safe(self):
        domain_models = (ROOT / "fast_api/app/models/domain_models.py").read_text(encoding="utf-8")
        foro_block = domain_models.split("class Foro", 1)[1].split("class RespuestaForo", 1)[0]
        rules_block = domain_models.split("class ReglaPuntos", 1)[1].split("class SaldoPuntos", 1)[0]
        self.assertIn("aprobado = Column", foro_block)
        self.assertIn("aprobado_por = Column", foro_block)
        self.assertIn('foreign_keys="Foro.autor_id"', domain_models)
        self.assertIn("foreign_keys=[autor_id]", foro_block)
        self.assertIn("foreign_keys=[aprobado_por]", foro_block)
        self.assertNotIn("aprobado = Column", rules_block)

        map_router = (ROOT / "fast_api/app/routers/mapa.py").read_text(encoding="utf-8")
        self.assertIn("math.isfinite", map_router)
        self.assertIn('imagen=getattr(punto, "imagen", None)', map_router)
        self.assertIn(
            '"puntos"',
            (ROOT / "fast_api/app/services/media.py").read_text(encoding="utf-8"),
        )

        flask_app = (ROOT / "flask_zerowaste/app.py").read_text(encoding="utf-8")
        self.assertNotIn("fastapi_app", flask_app)
        self.assertIn("FASTAPI_INTERNAL_URL", flask_app)
        self.assertNotIn("static', 'img', 'posts'", flask_app)

        dashboard = (
            ROOT / "laravel_zerowaste/app/Http/Controllers/DashboardController.php"
        ).read_text(encoding="utf-8")
        self.assertNotIn("fastapi_app", dashboard)
        self.assertNotIn("FASTAPI_KEY", dashboard)
        services = (ROOT / "laravel_zerowaste/config/services.php").read_text(encoding="utf-8")
        self.assertIn("FASTAPI_INTERNAL_URL", services)
        self.assertIn("SYSTEM_API_KEY", services)

        users_router = (ROOT / "fast_api/app/routers/usuarios.py").read_text(encoding="utf-8")
        profile_update = users_router.split('def actualizar_perfil(', 1)[1].split('@router.put("/perfil/password"', 1)[0]
        self.assertIn("foto_perfil: Optional[UploadFile] = File(None)", profile_update)
        self.assertIn('save_media_image(content, "perfiles", maximum_bytes=MAX_PROFILE_IMAGE_BYTES)', profile_update)
        self.assertIn("UsuarioResponse.model_validate(current_user)", profile_update)

    def test_maintenance_scripts_do_not_embed_or_print_credentials(self):
        create_script = (ROOT / "fast_api/scripts/crear_admin.py").read_text(encoding="utf-8")
        check_script = (ROOT / "fast_api/scripts/check_users.py").read_text(encoding="utf-8")

        self.assertIn('require_env("ADMIN_EMAIL")', create_script)
        self.assertIn('require_env("ADMIN_PASSWORD")', create_script)
        self.assertNotIn('getenv("ADMIN_EMAIL",', create_script)
        self.assertNotIn("u.email", check_script)
        self.assertNotIn("u.password", check_script)

    def test_profile_views_attempt_real_avatar_before_initial_fallback(self):
        views = [
            ROOT / "laravel_zerowaste/resources/views/layouts/admin.blade.php",
            ROOT / "laravel_zerowaste/resources/views/admin/partials/usuarios_table.blade.php",
            ROOT / "flask_zerowaste/templates/perfil.html",
            ROOT / "flask_zerowaste/templates/includes/header.html",
        ]
        for view in views:
            source = view.read_text(encoding="utf-8")
            self.assertIn("avatar", source.lower())
            self.assertIn("onerror", source)
            self.assertNotIn("/media/perfiles/default.png", source)
            self.assertNotIn("/static/img/perfiles/default.png", source)


if __name__ == "__main__":
    unittest.main()
