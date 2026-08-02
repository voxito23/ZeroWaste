import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]


class AuthPhaseContractsTest(unittest.TestCase):
    def test_google_flow_uses_backend_pkce_state_nonce_and_one_time_handoff(self):
        source = (ROOT / "fast_api" / "app" / "routers" / "auth_external.py").read_text(encoding="utf-8")
        for required in ["code_challenge_method", '"S256"', "state_hash", "nonce_hash", "verify_oauth2_token", 'claims.get("aud")', 'claims.get("iss")', 'claims.get("email_verified")', "handoff_hash", "used_at"]:
            self.assertIn(required, source)
        self.assertIn("provider_subject=provider_sub", source)
        self.assertNotIn("tokeninfo?id_token", source)

    def test_mobile_uses_system_browser_and_contains_no_client_secret(self):
        source = (ROOT / "mobile_app" / "screens" / "LoginScreen.js").read_text(encoding="utf-8")
        self.assertIn("Linking.openURL(data.authorization_url)", source)
        self.assertIn("zerowaste://auth/google", source)
        self.assertNotIn("GOOGLE_CLIENT_SECRET", source)
        self.assertNotIn("WebView", source)

    def test_mobile_recovers_when_the_system_browser_is_cancelled(self):
        source = (ROOT / "mobile_app" / "screens" / "LoginScreen.js").read_text(encoding="utf-8")
        self.assertIn("AppState.addEventListener", source)
        self.assertIn("googleBrowserPending", source)
        self.assertIn("setGoogleLoading(false)", source)
        self.assertIn("Acceso cancelado", source)

    def test_email_owner_uses_resend_https_not_smtp(self):
        source = (ROOT / "fast_api" / "app" / "services" / "transactional_email.py").read_text(encoding="utf-8")
        self.assertIn("https://api.resend.com/emails", source)
        templates = (ROOT / "fast_api" / "app" / "services" / "email_templates.py").read_text(encoding="utf-8")
        self.assertIn("Verifica tu correo en ZeroWaste", templates)
        self.assertNotIn("smtplib", source)
        self.assertIn("RATE_LIMITED", source)
        self.assertIn("revoked_at", source)

    def test_email_verification_otp_is_derived_and_never_returned_to_mobile(self):
        service = (ROOT / "fast_api" / "app" / "services" / "transactional_email.py").read_text(encoding="utf-8")
        router = (ROOT / "fast_api" / "app" / "routers" / "auth_external.py").read_text(encoding="utf-8")
        registration = (ROOT / "fast_api" / "app" / "routers" / "auth.py").read_text(encoding="utf-8")
        self.assertIn("EMAIL_OTP_SECRET", service)
        self.assertIn("hmac.new", service)
        self.assertIn("% 1_000_000:06d", service)
        self.assertIn('"/email/verificar-otp"', router)
        self.assertIn("secrets.compare_digest", router)
        self.assertIn("with_for_update()", router)
        self.assertNotIn('"otp":', registration)
        self.assertNotIn('"verification_otp":', registration)

    def test_mobile_registration_opens_professional_otp_screen(self):
        register = (ROOT / "mobile_app" / "screens" / "RegisterScreen.js").read_text(encoding="utf-8")
        screen = (ROOT / "mobile_app" / "screens" / "EmailVerificationScreen.js").read_text(encoding="utf-8")
        navigator = (ROOT / "mobile_app" / "navigation" / "AuthNavigator.js").read_text(encoding="utf-8")
        self.assertIn("navigation.navigate('VerifyEmail'", register)
        self.assertIn("/auth/email/verificar-otp", screen)
        self.assertIn("textContentType=\"oneTimeCode\"", screen)
        self.assertIn("Enviar un código nuevo", screen)
        self.assertIn('name="VerifyEmail"', navigator)

    def test_google_verified_email_skips_verification_email(self):
        source = (ROOT / "fast_api" / "app" / "routers" / "auth_external.py").read_text(encoding="utf-8")
        google_creation = source[source.index("user = Usuario("):source.index("db.add(user)")]
        self.assertIn("email_verified_at=now", google_creation)
        self.assertNotIn("send_verification", google_creation)

    def test_google_account_linking_reuses_distributed_login_throttle(self):
        source = (ROOT / "fast_api" / "app" / "routers" / "auth_external.py").read_text(encoding="utf-8")
        start = source.index("def google_link")
        end = source.index("def _verification_page")
        linking = source[start:end]
        self.assertIn("get_client_ip(request)", linking)
        self.assertIn("throttle.assert_allowed", linking)
        self.assertIn("throttle.record_failure", linking)
        self.assertIn("throttle.clear", linking)


if __name__ == "__main__":
    unittest.main()
