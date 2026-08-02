"""Forum content, avatar, like and mobile composer contracts without production writes."""

from __future__ import annotations

import importlib.util
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]


def load_module(name: str, path: Path):
    spec = importlib.util.spec_from_file_location(name, path)
    if spec is None or spec.loader is None:
        raise RuntimeError(f"Unable to load {path.name}")
    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)
    return module


class ForumContentTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.fast = load_module(
            "zerowaste_forum_content_fast",
            ROOT / "fast_api/app/services/forum_content.py",
        )
        cls.flask = load_module(
            "zerowaste_forum_content_flask",
            ROOT / "flask_zerowaste/forum_content.py",
        )

    def test_normal_text_emoji_and_controls(self):
        for module in (self.fast, self.flask):
            normalizer = getattr(module, "validate_comment", None) or module.normalize_comment
            self.assertEqual("Me gusta reciclar 🌱", normalizer("  Me gusta reciclar 🌱\r\n"))
            self.assertEqual("Comentario válido", normalizer("Comentario\x00 válido"))

    def test_rejects_html_tailwind_css_empty_and_too_long(self):
        unsafe = (
            "<div class=\"flex-grow\">contenido contaminado</div>",
            "--tw-ring-color: rgb(1 2 3); contenido contaminado",
            "<script>alert(1)</script>",
            "   ",
            "x" * 1001,
        )
        for module in (self.fast, self.flask):
            normalizer = getattr(module, "validate_comment", None) or module.normalize_comment
            for value in unsafe:
                with self.subTest(module=module.__name__, value=value[:20]):
                    with self.assertRaises(ValueError):
                        normalizer(value)

    def test_historic_invalid_comment_is_explicitly_marked(self):
        clean, invalid = self.fast.safe_comment_for_output(
            '<div class="flex-grow">--tw-border-opacity: 1</div>'
        )
        self.assertTrue(invalid)
        self.assertEqual("Contenido retirado por tener un formato inválido.", clean)


class ForumSourceContractsTests(unittest.TestCase):
    def test_likes_are_authenticated_idempotent_and_unique(self):
        router = (ROOT / "fast_api/app/routers/foro.py").read_text(encoding="utf-8")
        models = (ROOT / "fast_api/app/models/domain_models.py").read_text(encoding="utf-8")
        self.assertIn('@router.put("/posts/{post_id}/like"', router)
        self.assertIn('@router.delete("/posts/{post_id}/like"', router)
        self.assertGreaterEqual(router.count("Depends(get_current_user)"), 2)
        self.assertIn("IntegrityError", router)
        self.assertIn('UniqueConstraint("usuario_id", "post_id"', models)
        self.assertNotIn("usuario_id: int", router.split("# Sistema de likes", 1)[1])

    def test_mobile_composer_keyboard_and_optimistic_like_contract(self):
        post_screen = (ROOT / "mobile_app/screens/PostScreen.js").read_text(encoding="utf-8")
        comments_modal = (ROOT / "mobile_app/components/forum/CommentsModal.js").read_text(encoding="utf-8")
        forum_screen = (ROOT / "mobile_app/screens/ForumScreen.js").read_text(encoding="utf-8")
        for expected in (
            "KeyboardAvoidingView",
            "FlatList",
            "SafeAreaView",
            "multiline",
            "maxLength={1000}",
            "submittingRef.current",
        ):
            self.assertIn(expected, comments_modal)
        for expected in (
            "api.put(`/foro/posts/${postId}/like`)",
            "api.delete(`/foro/posts/${postId}/like`)",
            "likePending",
        ):
            self.assertIn(expected, post_screen)
        self.assertIn("CommentsModal", post_screen)
        self.assertNotIn("<FlatList", post_screen)
        self.assertNotIn("/respuestas`)", post_screen)
        self.assertIn("likeRequestRef.current", post_screen)
        self.assertIn("pendingLikesRef", forum_screen)
        self.assertIn("api.put(`/foro/posts/${post.id}/like`)", forum_screen)
        self.assertIn("api.delete(`/foro/posts/${post.id}/like`)", forum_screen)

    def test_web_never_renders_comment_html_as_active_markup(self):
        flask_post = (ROOT / "flask_zerowaste/templates/post.html").read_text(encoding="utf-8")
        flask_app = (ROOT / "flask_zerowaste/app.py").read_text(encoding="utf-8")
        laravel_posts = (
            ROOT / "laravel_zerowaste/resources/views/admin/posts/index.blade.php"
        ).read_text(encoding="utf-8")
        self.assertNotIn("r.contenido | safe", flask_post)
        self.assertIn("editor.innerText.trim()", flask_post)
        self.assertIn("normalize_comment", flask_app)
        self.assertIn("escapeHtml(c.contenido)", laravel_posts)

    def test_cleanup_is_guarded_and_defaults_to_rollback(self):
        cleanup = (ROOT / "scripts/propuesta_limpieza_foro.sql").read_text(encoding="utf-8")
        report = (ROOT / "scripts/reportar_integridad_foro.sql").read_text(encoding="utf-8")
        self.assertIn("WHERE id = 4", cleanup)
        self.assertIn("ROLLBACK", cleanup)
        self.assertNotIn("COMMIT;", cleanup)
        self.assertNotIn("UPDATE", report.upper())
        self.assertNotIn("DELETE", report.upper())


if __name__ == "__main__":
    unittest.main()
