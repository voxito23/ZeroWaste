import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]


class ResendTransactionalContracts(unittest.TestCase):
    def test_fastapi_is_canonical_resend_owner(self):
        service = (ROOT / "fast_api/app/services/transactional_email.py").read_text(encoding="utf-8")
        for value in ("https://api.resend.com/emails", "RESEND_FROM_EMAIL", "RESEND_REPLY_TO", '"text"', "Idempotency-Key", "User-Agent"):
            self.assertIn(value, service)
        self.assertNotIn("smtplib", service)

    def test_all_required_templates_have_html_and_plain_text(self):
        templates = (ROOT / "fast_api/app/services/email_templates.py").read_text(encoding="utf-8")
        for kind in ("verification", "password_reset", "password_changed", "collection_confirmed", "collection_status", "redemption_status", "google_account", "admin_alert"):
            self.assertIn(f'"{kind}"', templates)
        self.assertIn("html=html_body", templates)
        self.assertIn("text=text_body", templates)

    def test_password_reset_is_single_use_and_fastapi_owned(self):
        router = (ROOT / "fast_api/app/routers/formularios.py").read_text(encoding="utf-8")
        flask = (ROOT / "flask_zerowaste/app.py").read_text(encoding="utf-8")
        for marker in ("secrets.token_urlsafe", "digest(token)", "with_for_update()", 'record.usado = True', 'record.estado = "completado"', "PASSWORD_RESET_TTL_MINUTES"):
            self.assertIn(marker, router)
        self.assertIn("FASTAPI_INTERNAL_URL", flask)
        self.assertNotIn("[RECOVERY] PASSWORD TEMP", flask)
        self.assertNotIn("[RECOVERY] EMAIL", flask)

    def test_examples_contain_no_key_shaped_placeholder(self):
        for path in (ROOT / "fast_api/.env.example", ROOT / "flask_zerowaste/.env.example", ROOT / "laravel_zerowaste/.env.example", ROOT / ".env.node.example"):
            source = path.read_text(encoding="utf-8")
            self.assertNotIn("re_xxxxxxxxx", source, str(path))
            for name in ("RESEND_API_KEY", "RESEND_FROM_EMAIL", "RESEND_FROM_NAME", "RESEND_REPLY_TO", "PUBLIC_BASE_URL", "EMAIL_VERIFICATION_TTL_MINUTES", "PASSWORD_RESET_TTL_MINUTES"):
                self.assertIn(name + "=", source, f"{path}: {name}")


if __name__ == "__main__":
    unittest.main()
