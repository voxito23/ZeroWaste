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
        self.assertEqual(publishers, {"caddy", "node_exporter", "cadvisor", "nginx_exporter", "redis_exporter"})
        self.assertEqual(set(self.compose["services"]["caddy"]["ports"]), {"80:80", "443:443"})
        for name in publishers - {"caddy"}:
            for binding in self.compose["services"][name]["ports"]:
                self.assertTrue(binding.startswith("${MONITORING_NODE_ADDRESS:-127.0.0.1}:"), binding)

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

    def test_load_balancer_endpoints_are_public_and_dependency_safe(self):
        source = (ROOT / "fast_api" / "app" / "main.py").read_text(encoding="utf-8")
        self.assertIn('@app.get("/load-balancer-health"', source)
        self.assertIn('@app.get("/load-balancer-ready"', source)
        self.assertIn('connection.execute(text("SELECT 1"))', source)
        self.assertNotIn("create_all", source)
        self.assertIn("location = /load-balancer-health", self.nginx)
        self.assertIn("location = /load-balancer-ready", self.nginx)

    def test_grafana_has_no_literal_password(self):
        password = self.compose["services"]["grafana"]["environment"]["GF_SECURITY_ADMIN_PASSWORD"]
        self.assertIn("GRAFANA_ADMIN_PASSWORD", password)

    def test_canonical_private_monitoring_targets_are_versioned(self):
        targets_root = ROOT / "prometheus" / "targets"
        origins = yaml.safe_load((targets_root / "origins.yml").read_text(encoding="utf-8"))
        nodes = yaml.safe_load((targets_root / "nodes.yml").read_text(encoding="utf-8"))
        origin_labels = [entry["labels"] for entry in origins]
        self.assertEqual({labels["node_role"] for labels in origin_labels}, {"primary", "secondary"})
        self.assertEqual({labels["probe"] for labels in origin_labels}, {"health", "ready"})
        self.assertTrue(all(entry["targets"][0].startswith("http://10.77.0.") for entry in origins))
        self.assertTrue(all(entry["targets"][0].startswith("10.77.0.2:") for entry in nodes))

    def test_compose_uses_service_dns_and_scalable_names(self):
        for name, service in self.compose["services"].items():
            self.assertNotIn("container_name", service, name)
        self.assertIn("laravel", self.compose["services"])
        self.assertNotIn("laravel1", self.compose["services"])
        self.assertNotIn("laravel2", self.compose["services"])
        self.assertNotIn("admin", self.compose["services"])
        self.assertNotIn("admin2", self.compose["services"])

    def test_production_has_no_source_or_vendor_mounts(self):
        forbidden_targets = {"/app", "/var/www/html", "/var/www/html/vendor"}
        for name in ("cliente", "fast_api", "laravel"):
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
        reward_mounts = [
            volume
            for volume in media["volumes"]
            if isinstance(volume, dict)
            and volume.get("target") == "/data/media/recompensas"
        ]
        self.assertEqual(reward_mounts, [])

    def test_legacy_dynamic_media_routes_redirect_to_canonical_service(self):
        for route_name in (
            "legacy_forum",
            "legacy_profiles",
            "legacy_events",
            "legacy_point_direct",
            "legacy_points",
        ):
            self.assertIn(route_name, self.caddy)

    def test_mobile_links_bypass_flask_and_reach_fastapi(self):
        matcher = "@mobile_links path /app/* /.well-known/assetlinks.json"
        self.assertIn(matcher, self.caddy)
        mobile_block = self.caddy.split(matcher, 1)[1].split("@api path", 1)[0]
        self.assertIn("reverse_proxy fast_api:6000", mobile_block)
        self.assertNotIn("reverse_proxy cliente:5000", mobile_block)

    def test_public_mobile_map_config_can_bypass_a_restarting_nginx(self):
        matcher = "@mobile_config path /api/mobile/config"
        self.assertIn(matcher, self.caddy)
        config_block = self.caddy.split(matcher, 1)[1].split("@api path", 1)[0]
        self.assertIn("rewrite * /mobile/config", config_block)
        self.assertIn("reverse_proxy fast_api:6000", config_block)

    def test_laravel_sessions_are_shared_in_redis(self):
        for name in ("laravel",):
            environment = self.compose["services"][name]["environment"]
            self.assertEqual(environment["SESSION_DRIVER"], "redis")
            self.assertEqual(environment["SESSION_CONNECTION"], "default")
            self.assertEqual(environment["SESSION_PATH"], "${SESSION_PATH:-/}")


if __name__ == "__main__":
    unittest.main()
