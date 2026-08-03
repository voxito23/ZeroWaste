from pathlib import Path
import unittest


ROOT = Path(__file__).resolve().parents[1]


class LocalStagingContractsTest(unittest.TestCase):
    def test_postgres_is_local_isolated_and_persistent(self):
        compose = (ROOT / 'docker-compose.staging.yml').read_text(encoding='utf-8')
        self.assertIn('127.0.0.1:${STAGING_DB_PORT:-55432}:5432', compose)
        self.assertIn('internal: true', compose)
        self.assertIn('zerowaste_staging_postgres_data', compose)
        self.assertIn('POSTGRES_PASSWORD_FILE', compose)
        self.assertNotIn('POSTGRES_PASSWORD:', compose)

    def test_runtime_secrets_and_dumps_are_ignored(self):
        ignore = (ROOT / '.gitignore').read_text(encoding='utf-8')
        self.assertIn('/.local-staging/', ignore)
        script = (ROOT / 'scripts/local_staging.ps1').read_text(encoding='utf-8')
        self.assertIn("[Security.Cryptography.RandomNumberGenerator]::Create", script)
        self.assertIn("$rng.GetBytes($bytes)", script)
        self.assertNotIn('supabase.co', script.lower())

    def test_destroy_requires_explicit_confirmation(self):
        script = (ROOT / 'scripts/local_staging.ps1').read_text(encoding='utf-8')
        self.assertIn("if (-not $ConfirmDestroy)", script)
        self.assertIn("$resolvedState -ne", script)

    def test_postgres_boolean_seeds_use_sql_literals(self):
        schedules = (ROOT / 'laravel_zerowaste/database/migrations/2026_08_01_000002_add_collection_schedules_and_one_time_qr.php').read_text(encoding='utf-8')
        admin = (ROOT / 'laravel_zerowaste/database/migrations/2026_08_01_000004_add_admin_audit_and_reward_history.php').read_text(encoding='utf-8')
        self.assertIn("? 'TRUE' : 'FALSE'", schedules)
        self.assertIn("DB::raw('FALSE')", admin)

    def test_point_qr_unique_index_has_a_single_migration_owner(self):
        migrations = ROOT / 'laravel_zerowaste/database/migrations'
        sources = {
            path.name: path.read_text(encoding='utf-8')
            for path in migrations.glob('*.php')
        }
        create_statement = 'CREATE UNIQUE INDEX uq_point_qr_one_active'
        owners = [name for name, source in sources.items() if create_statement in source]
        self.assertEqual(
            owners,
            ['2026_08_01_000001_add_secure_point_qr_management.php'],
        )
        admin = sources['2026_08_01_000004_add_admin_audit_and_reward_history.php']
        self.assertNotIn('DROP INDEX IF EXISTS uq_point_qr_one_active', admin)

    def test_laravel_boolean_queries_and_writes_are_postgres_safe(self):
        controllers = [
            ROOT / 'laravel_zerowaste/app/Http/Controllers/CollectionScheduleController.php',
            ROOT / 'laravel_zerowaste/app/Http/Controllers/ImpactAdminController.php',
            ROOT / 'laravel_zerowaste/app/Http/Controllers/MapController.php',
            ROOT / 'laravel_zerowaste/app/Http/Controllers/PostController.php',
        ]
        source = '\n'.join(path.read_text(encoding='utf-8') for path in controllers)
        for unsafe in ("where('active', true)", "where('activa', true)", "where('activo', true)"):
            self.assertNotIn(unsafe, source)
        self.assertIn("whereRaw('active = TRUE')", source)
        self.assertIn("DB::raw($request->boolean('activo') ? 'TRUE' : 'FALSE')", source)


if __name__ == '__main__':
    unittest.main()
