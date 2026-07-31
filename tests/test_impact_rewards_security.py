"""Static checks for impact, rewards, QR and forum media safeguards.

These tests never connect to Supabase and never execute migrations.
"""

from pathlib import Path
import unittest


ROOT = Path(__file__).resolve().parents[1]


class ImpactRewardsSecurityTests(unittest.TestCase):
    def test_qr_uses_random_single_use_token_instead_of_numeric_id(self):
        backend = (ROOT / "fast_api/app/routers/recoleccion.py").read_text(encoding="utf-8")
        mobile = (ROOT / "mobile_app/screens/ScannerScreen.js").read_text(encoding="utf-8")
        self.assertIn("secrets.token_urlsafe", backend)
        self.assertIn("hashlib.sha256", backend)
        self.assertIn("qr.used_at", backend)
        self.assertIn("/recolecciones/completar-qr", mobile)
        self.assertNotIn("return isNaN(num) ? 1", mobile)

    def test_points_separate_historical_impact_from_available_balance(self):
        model = (ROOT / "fast_api/app/models/domain_models.py").read_text(encoding="utf-8")
        redemption = (ROOT / "fast_api/app/routers/impacto.py").read_text(encoding="utf-8")
        self.assertIn("puntos_disponibles", model)
        self.assertIn("impacto_historico", model)
        self.assertIn("balance.puntos_disponibles -= total", redemption)
        self.assertNotIn("balance.impacto_historico -=", redemption)

    def test_reward_and_point_operations_use_database_locks(self):
        source = (ROOT / "fast_api/app/routers/impacto.py").read_text(encoding="utf-8")
        self.assertGreaterEqual(source.count("with_for_update()"), 2)
        migration = (ROOT / "laravel_zerowaste/database/migrations/2026_07_31_000000_create_impact_and_rewards_tables.php").read_text(encoding="utf-8")
        self.assertIn("uq_movimiento_recompensa", migration)

    def test_forum_upload_restricts_size_type_and_filename(self):
        source = (ROOT / "fast_api/app/routers/foro.py").read_text(encoding="utf-8")
        self.assertIn("MAX_POST_IMAGE_BYTES = 5 * 1024 * 1024", source)
        self.assertIn('ALLOWED_POST_IMAGE_FORMATS', source)
        self.assertIn("uuid.uuid4().hex", source)
        self.assertIn("os.path.basename", source)

    def test_new_schema_is_not_applied_during_startup(self):
        compose = (ROOT / "docker-compose.yml").read_text(encoding="utf-8").lower()
        dockerfile = (ROOT / "fast_api/Dockerfile").read_text(encoding="utf-8").lower()
        for content in (compose, dockerfile):
            self.assertNotIn("artisan migrate", content)
            self.assertNotIn("create_all", content)


if __name__ == "__main__":
    unittest.main()
