import io
import json
import os
import sys
import unittest
from pathlib import Path
from unittest.mock import patch
from urllib.error import HTTPError

ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT / "fast_api"))

from app.services.email_templates import EmailContent, render  # noqa: E402
from app.services.transactional_email import EmailDeliveryError, send_resend  # noqa: E402


class _Response:
    def __init__(self, payload):
        self.payload = payload

    def __enter__(self):
        return self

    def __exit__(self, *_args):
        return False

    def read(self):
        return json.dumps(self.payload).encode("utf-8")


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

    def test_verification_template_highlights_six_digit_otp(self):
        content = render("verification", name="Usuario", action_url="https://www.zerowaste-qro.com/api/auth/email/verificar?token=opaque", otp_code="123456")
        self.assertIn("Código de verificación", content.html)
        self.assertIn("123456", content.html)
        self.assertIn("Código de verificación: 123456", content.text)

    def test_resend_requires_a_provider_message_id(self):
        content = EmailContent(subject="Prueba", html="<p>Prueba</p>", text="Prueba")
        environment = {"RESEND_API_KEY": "test-key", "RESEND_FROM_EMAIL": "correo@zerowaste-qro.com", "RESEND_FROM_NAME": "ZeroWaste"}
        with patch.dict(os.environ, environment), patch("app.services.transactional_email.urlopen", return_value=_Response({})):
            with self.assertRaises(EmailDeliveryError) as raised:
                send_resend("persona@example.com", content, idempotency_key="test/id")
        self.assertEqual("EMAIL_PROVIDER_INVALID_RESPONSE", raised.exception.code)

    def test_resend_maps_unverified_sender_without_exposing_provider_body(self):
        content = EmailContent(subject="Prueba", html="<p>Prueba</p>", text="Prueba")
        body = io.BytesIO(json.dumps({"name": "validation_error", "message": "The example.com domain is not verified."}).encode("utf-8"))
        provider_error = HTTPError("https://api.resend.com/emails", 403, "Forbidden", hdrs=None, fp=body)
        environment = {"RESEND_API_KEY": "test-key", "RESEND_FROM_EMAIL": "correo@example.com", "RESEND_FROM_NAME": "ZeroWaste"}
        try:
            with patch.dict(os.environ, environment), patch("app.services.transactional_email.urlopen", side_effect=provider_error):
                with self.assertRaises(EmailDeliveryError) as raised:
                    send_resend("persona@example.com", content, idempotency_key="test/id")
        finally:
            provider_error.close()
        self.assertEqual("EMAIL_SENDER_NOT_VERIFIED", raised.exception.code)
        self.assertNotIn("example.com", str(raised.exception))

    def test_password_reset_is_single_use_and_fastapi_owned(self):
        router = (ROOT / "fast_api/app/routers/formularios.py").read_text(encoding="utf-8")
        flask = (ROOT / "flask_zerowaste/app.py").read_text(encoding="utf-8")
        for marker in ("secrets.token_urlsafe", "digest(token)", "with_for_update()", 'record.usado = True', 'record.estado = "completado"', "PASSWORD_RESET_TTL_MINUTES"):
            self.assertIn(marker, router)
        self.assertIn('href="zerowaste://auth/login"', router)
        self.assertIn("Regresa a la aplicación de ZeroWaste e inicia sesión con tu contraseña nueva.", router)
        confirmation = router.split('content = render("password_changed"', 1)[1].split("send_resend", 1)[0]
        self.assertNotIn("PUBLIC_BASE_URL", confirmation)
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
