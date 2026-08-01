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

    def test_email_owner_uses_resend_https_not_smtp(self):
        source = (ROOT / "fast_api" / "app" / "services" / "transactional_email.py").read_text(encoding="utf-8")
        self.assertIn("https://api.resend.com/emails", source)
        self.assertIn("Verifica tu correo en ZeroWaste", (ROOT / "fast_api" / "app" / "routers" / "auth.py").read_text(encoding="utf-8") + source)
        self.assertNotIn("smtplib", source)
        self.assertIn("RATE_LIMITED", source)
        self.assertIn("revoked_at", source)

    def test_google_verified_email_skips_verification_email(self):
        source = (ROOT / "fast_api" / "app" / "routers" / "auth_external.py").read_text(encoding="utf-8")
        google_creation = source[source.index("user = Usuario("):source.index("db.add(user)")]
        self.assertIn("email_verified_at=now", google_creation)
        self.assertNotIn("send_verification", google_creation)


if __name__ == "__main__":
    unittest.main()
