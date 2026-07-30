"""Regression checks for production startup safety.

These tests inspect source/configuration only. They never import an application,
open a database connection, or execute a migration.
"""

import ast
from pathlib import Path
import unittest


ROOT = Path(__file__).resolve().parents[1]


class StartupSafetyTests(unittest.TestCase):
    def test_flask_startup_never_calls_create_all(self):
        source = (ROOT / "flask_zerowaste" / "app.py").read_text(encoding="utf-8")
        tree = ast.parse(source)
        calls = [
            node
            for node in ast.walk(tree)
            if isinstance(node, ast.Call)
            and isinstance(node.func, ast.Attribute)
            and node.func.attr == "create_all"
        ]
        self.assertEqual([], calls, "Flask startup must never create database schema")

    def test_container_startup_has_no_schema_commands(self):
        startup_files = [
            ROOT / "docker-compose.yml",
            ROOT / "fast_api" / "Dockerfile",
            ROOT / "flask_zerowaste" / "Dockerfile",
            ROOT / "laravel_zerowaste" / "Dockerfile",
        ]
        forbidden = (
            "create_all",
            "metadata.create_all",
            "artisan migrate",
            "migrate --force",
            "alembic upgrade",
            "db:seed",
            "init_db.py",
        )
        for path in startup_files:
            content = path.read_text(encoding="utf-8").lower()
            for token in forbidden:
                self.assertNotIn(token, content, f"Unsafe startup token {token!r} in {path}")

    def test_fastapi_production_command_has_no_reload(self):
        dockerfile = (ROOT / "fast_api" / "Dockerfile").read_text(encoding="utf-8")
        self.assertNotIn('"--reload"', dockerfile)
        self.assertIn('"--proxy-headers"', dockerfile)

    def test_flask_uses_two_gunicorn_workers(self):
        dockerfile = (ROOT / "flask_zerowaste" / "Dockerfile").read_text(encoding="utf-8")
        self.assertIn('"gunicorn"', dockerfile)
        self.assertIn('"--workers", "2"', dockerfile)

    def test_healthchecks_are_read_only(self):
        fastapi = (ROOT / "fast_api" / "app" / "main.py").read_text(encoding="utf-8")
        flask = (ROOT / "flask_zerowaste" / "app.py").read_text(encoding="utf-8")
        self.assertIn('text("SELECT 1")', fastapi)
        self.assertIn("text('SELECT 1')", flask)
        for content in (fastapi, flask):
            self.assertNotIn("CREATE TABLE", content.upper())
            self.assertNotIn("ALTER TABLE", content.upper())

    def test_proxy_preserves_public_scheme_and_api_prefix(self):
        caddy = (ROOT / "Caddyfile").read_text(encoding="utf-8")
        self.assertIn("handle_path /api/*", caddy)
        self.assertIn("header_up X-Forwarded-Proto {scheme}", caddy)
        self.assertIn("header_up X-Forwarded-Prefix /api", caddy)

    def test_secret_examples_do_not_contain_fallback_values(self):
        expected_blank = {
            ROOT / "fast_api" / ".env.example": (
                "DATABASE_URL=",
                "JWT_SECRET_KEY=",
                "SYSTEM_API_KEY=",
            ),
            ROOT / "flask_zerowaste" / ".env.example": (
                "DATABASE_URL=",
                "SECRET_KEY=",
            ),
            ROOT / "laravel_zerowaste" / ".env.example": (
                "APP_KEY=",
                "DB_URL=",
                "DB_HOST=",
                "DB_DATABASE=",
                "DB_USERNAME=",
                "DB_PASSWORD=",
            ),
        }
        for path, lines in expected_blank.items():
            content_lines = set(path.read_text(encoding="utf-8").splitlines())
            for line in lines:
                self.assertIn(line, content_lines, f"Expected blank example setting in {path}: {line}")

if __name__ == "__main__":
    unittest.main()
