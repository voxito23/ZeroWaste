import pathlib
import unittest

import yaml


ROOT = pathlib.Path(__file__).resolve().parents[1]


class InfrastructureSecurityTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.compose = yaml.safe_load((ROOT / "docker-compose.yml").read_text(encoding="utf-8"))
        cls.caddy = (ROOT / "Caddyfile").read_text(encoding="utf-8")
        cls.nginx = (ROOT / "nginx" / "api.conf").read_text(encoding="utf-8")

    def test_only_caddy_publishes_host_ports(self):
        publishers = {
            name for name, service in self.compose["services"].items() if service.get("ports")
        }
        self.assertEqual(publishers, {"caddy"})
        self.assertEqual(set(self.compose["services"]["caddy"]["ports"]), {"80:80", "443:443"})

    def test_private_networks_are_internal(self):
        for network in ("app_network", "monitoring_network", "data_network"):
            self.assertTrue(self.compose["networks"][network].get("internal"), network)

    def test_redis_is_private_and_persistent(self):
        redis = self.compose["services"]["redis"]
        self.assertNotIn("ports", redis)
        self.assertIn("redis_data:/data", redis["volumes"])
        self.assertIn("healthcheck", redis)

    def test_caddy_blocks_internal_monitoring_paths(self):
        for path in ("/metrics", "/api/metrics", "/prometheus", "/stub_status"):
            self.assertIn(path, self.caddy)
        self.assertNotIn("reverse_proxy prometheus", self.caddy)

    def test_nginx_is_gateway_for_api_and_hides_metrics(self):
        self.assertIn("location /api/", self.nginx)
        self.assertIn("proxy_pass http://fastapi_backend/", self.nginx)
        self.assertIn("location = /api/metrics { return 404; }", self.nginx)
        self.assertIn("listen 8081", self.nginx)
        self.assertIn("location = /stub_status", self.nginx)
        self.assertIn("stub_status;", self.nginx)

    def test_grafana_has_no_literal_password(self):
        password = self.compose["services"]["grafana"]["environment"]["GF_SECURITY_ADMIN_PASSWORD"]
        self.assertIn("GRAFANA_ADMIN_PASSWORD", password)

    def test_compose_uses_service_dns_and_scalable_names(self):
        for name, service in self.compose["services"].items():
            self.assertNotIn("container_name", service, name)
        self.assertIn("laravel1", self.compose["services"])
        self.assertIn("laravel2", self.compose["services"])
        self.assertNotIn("admin", self.compose["services"])
        self.assertNotIn("admin2", self.compose["services"])

    def test_production_has_no_source_or_vendor_mounts(self):
        forbidden_targets = {"/app", "/var/www/html", "/var/www/html/vendor"}
        for name in ("cliente", "fast_api", "laravel1", "laravel2"):
            volumes = self.compose["services"][name].get("volumes", [])
            for volume in volumes:
                target = volume.get("target") if isinstance(volume, dict) else volume.split(":")[-1]
                self.assertNotIn(target, forbidden_targets, f"{name}: {target}")

    def test_media_is_private_and_read_only_at_delivery(self):
        media = self.compose["services"]["media_static"]
        self.assertNotIn("ports", media)
        self.assertEqual(media["networks"], ["media_network"])
        media_mount = next(
            volume
            for volume in media["volumes"]
            if isinstance(volume, dict) and volume.get("target") == "/data/media"
        )
        self.assertTrue(media_mount["read_only"])
        self.assertTrue(self.compose["networks"]["media_network"]["internal"])

    def test_laravel_sessions_are_shared_in_redis(self):
        for name in ("laravel1", "laravel2"):
            environment = self.compose["services"][name]["environment"]
            self.assertEqual(environment["SESSION_DRIVER"], "redis")
            self.assertEqual(environment["SESSION_CONNECTION"], "default")
            self.assertEqual(environment["SESSION_PATH"], "/zw-interno")


if __name__ == "__main__":
    unittest.main()
