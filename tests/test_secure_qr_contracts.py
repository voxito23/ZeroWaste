import sys
from pathlib import Path

import unittest

ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT / "fast_api"))

from app.services.qr_tokens import (  # noqa: E402
    COLLECTION_PREFIX,
    POINT_PREFIX,
    QrContentError,
    new_token,
    parse_content,
    public_content,
    token_hash,
)


class SecureQrContractsTest(unittest.TestCase):
    def test_point_and_collection_tokens_are_distinct_and_opaque(self):
        point = new_token("recycling_point")
        collection = new_token("collection")
        self.assertTrue(point.startswith(POINT_PREFIX))
        self.assertTrue(collection.startswith(COLLECTION_PREFIX))
        self.assertGreaterEqual(len(point), 45)
        self.assertNotEqual(token_hash(point), token_hash(collection))

    def test_https_point_content_parses_without_exposing_an_id(self):
        token = new_token("recycling_point")
        content = public_content(token)
        parsed = parse_content(content)
        self.assertEqual(parsed.kind, "recycling_point")
        self.assertEqual(parsed.token, token)
        self.assertIn("/q/p/", content)

    def test_external_qr_has_exact_public_error(self):
        with self.assertRaises(QrContentError) as context:
            parse_content("https://example.com/anything")
        self.assertEqual(context.exception.code, "NOT_ZEROWASTE_QR")
        self.assertEqual(context.exception.detail, "Este código QR no pertenece a ZeroWaste.")

    def test_mismatched_route_is_treated_as_tampered(self):
        token = new_token("collection")
        with self.assertRaises(QrContentError) as context:
            parse_content(f"https://www.zerowaste-qro.com/q/p/{token}")
        self.assertEqual(context.exception.code, "QR_TAMPERED")

    def test_point_qr_lifecycle_is_serialized_and_has_one_active_constraint(self):
        router = (ROOT / "fast_api" / "app" / "routers" / "qr.py").read_text(encoding="utf-8")
        migration = (ROOT / "laravel_zerowaste" / "database" / "migrations" / "2026_08_01_000001_add_secure_point_qr_management.php").read_text(encoding="utf-8")
        self.assertIn("pg_advisory_xact_lock", router)
        self.assertGreaterEqual(router.count("_lock_point_qr(db, point_id)"), 3)
        self.assertIn("uq_point_qr_one_active", migration)
        self.assertIn("WHERE active = TRUE", migration)

    def test_laravel_qr_client_supports_safe_key_rotation(self):
        service = (ROOT / "laravel_zerowaste" / "app" / "Services" / "FastApiQrService.php").read_text(encoding="utf-8")
        self.assertIn("array_unique($keys)", service)
        self.assertIn("foreach ($this->keys() as $key)", service)
        self.assertIn("[401, 403]", service)
        self.assertNotIn("Log::", service)


if __name__ == "__main__":
    unittest.main()
