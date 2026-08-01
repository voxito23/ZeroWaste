"""Static safeguards for the one-off production schema deployment."""

from pathlib import Path
import unittest


ROOT = Path(__file__).resolve().parents[1]


class ProductionMigrationScriptTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.source = (ROOT / "scripts/deploy_impact_schema.sh").read_text(
            encoding="utf-8"
        )

    def test_applies_only_the_reviewed_migration(self):
        self.assertIn(
            'MIGRATION="2026_07_31_000000_create_impact_and_rewards_tables"',
            self.source,
        )
        self.assertIn('--path="$MIGRATION_PATH"', self.source)
        self.assertNotIn("migrate:fresh", self.source)
        self.assertNotIn("migrate:refresh", self.source)
        self.assertNotIn("migrate:reset", self.source)

    def test_creates_backup_before_running_migration(self):
        backup_position = self.source.index("pg_dump --format=custom")
        migration_position = self.source.index("php artisan migrate")
        self.assertLess(backup_position, migration_position)
        self.assertIn("sha256sum", self.source)
        self.assertIn("--no-owner --no-privileges", self.source)

    def test_inventory_is_read_only_and_credentials_are_not_printed(self):
        inventory = (ROOT / "scripts/supabase_schema_inventory.sql").read_text(
            encoding="utf-8"
        )
        self.assertIn("BEGIN TRANSACTION READ ONLY", inventory)
        self.assertNotIn('printf \'%s\' "$DB_URL"', self.source)
        self.assertNotIn("set -x", self.source)

    def test_verifies_schema_and_public_endpoints(self):
        self.assertIn("approval_columns", self.source)
        self.assertIn("impact_tables", self.source)
        self.assertIn("/api/foro/posts", self.source)
        self.assertIn("/api/impacto/recompensas", self.source)

    def test_supports_modern_and_legacy_compose(self):
        self.assertIn("detectar_compose", self.source)
        self.assertIn("ejecutar_compose", self.source)
        self.assertNotIn("docker compose config --quiet", self.source)


if __name__ == "__main__":
    unittest.main()
