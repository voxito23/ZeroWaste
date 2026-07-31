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
        self.assertIn("location = /stub_status { stub_status; }", self.nginx)

    def test_grafana_has_no_literal_password(self):
        password = self.compose["services"]["grafana"]["environment"]["GF_SECURITY_ADMIN_PASSWORD"]
        self.assertIn("GRAFANA_ADMIN_PASSWORD", password)


if __name__ == "__main__":
    unittest.main()
