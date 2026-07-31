"""Static regression checks for the mobile-to-FastAPI security boundary."""

from pathlib import Path
import unittest


ROOT = Path(__file__).resolve().parents[1]


class MobileApiSecurityTests(unittest.TestCase):
    def test_mobile_client_has_no_secret_fallback_or_direct_supabase_import(self):
        axios_source = (ROOT / "mobile_app" / "api" / "axios.js").read_text(encoding="utf-8")
        screens = "\n".join(
            path.read_text(encoding="utf-8")
            for path in (ROOT / "mobile_app" / "screens").glob("*.js")
        )
        self.assertNotIn("EXPO_PUBLIC_API_KEY", axios_source)
        self.assertNotIn("10.0.2.2", axios_source)
        self.assertNotIn("localhost", axios_source)
        self.assertNotIn("../lib/supabase", screens)
        self.assertIn("useAuth.getState().logout()", axios_source)

    def test_system_api_key_is_not_global_mobile_authentication(self):
        main_source = (ROOT / "fast_api" / "app" / "main.py").read_text(encoding="utf-8")
        self.assertNotIn("add_middleware(ApiKeyMiddleware)", main_source)

    def test_forum_like_uses_authenticated_user(self):
        forum_source = (ROOT / "fast_api" / "app" / "routers" / "foro.py").read_text(encoding="utf-8")
        self.assertIn("current_user: Usuario = Depends(get_current_user)", forum_source)
        self.assertIn("usuario_id = current_user.id", forum_source)

    def test_collection_request_sends_coordinates(self):
        map_source = (ROOT / "mobile_app" / "screens" / "MapScreen.js").read_text(encoding="utf-8")
        self.assertIn("longitud: currentLocation[0]", map_source)
        self.assertIn("latitud: currentLocation[1]", map_source)


if __name__ == "__main__":
    unittest.main()
