import unittest
import json
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]


class AdminPhaseContractsTest(unittest.TestCase):
    def test_new_admin_routes_remain_inside_auth_admin_group(self):
        routes = (ROOT / "laravel_zerowaste" / "routes" / "web.php").read_text(encoding="utf-8")
        group = routes[routes.index("Route::prefix($adminPrefix)->middleware(['auth', 'admin'])"):]
        for fragment in ["recompensas.store", "recompensas.destroy", "reglas.store", "movimientos.export", "mapa.qr.regenerate", "recolecciones.horarios"]:
            self.assertIn(fragment, group)

    def test_reward_retirement_is_soft_and_history_is_preserved(self):
        controller = (ROOT / "laravel_zerowaste" / "app" / "Http" / "Controllers" / "ImpactAdminController.php").read_text(encoding="utf-8")
        destroy = controller[controller.index("function destroyReward"):controller.index("private function validateReward")]
        self.assertIn("'deleted_at' => now()", destroy)
        self.assertNotIn("->delete()", destroy)
        self.assertIn("point_rule_history", controller)

    def test_reward_upload_is_not_written_as_a_database_column(self):
        controller = (ROOT / "laravel_zerowaste" / "app" / "Http" / "Controllers" / "ImpactAdminController.php").read_text(encoding="utf-8")
        self.assertGreaterEqual(controller.count("unset($data['imagen_archivo'])"), 2)
        self.assertIn("DB::transaction(function () use ($data, $request)", controller)
        self.assertIn("Media::discard($newImage, 'recompensas')", controller)

    def test_qr_failures_are_classified_without_logging_tokens(self):
        service = (ROOT / "laravel_zerowaste" / "app" / "Services" / "FastApiQrService.php").read_text(encoding="utf-8")
        controller = (ROOT / "laravel_zerowaste" / "app" / "Http" / "Controllers" / "PointQrController.php").read_text(encoding="utf-8")
        for variable in ["SYSTEM_API_KEY", "FASTAPI_INTERNAL_URL", "QR_TOKEN_ENCRYPTION_KEY"]:
            self.assertIn(variable, service)
        self.assertIn("'location_id' => $locationId", controller)
        for unsafe in ["token_ciphertext", "X-API-Key", "response_body"]:
            self.assertNotIn(unsafe, controller)

    def test_movements_have_filters_local_time_and_admin_csv(self):
        controller = (ROOT / "laravel_zerowaste" / "app" / "Http" / "Controllers" / "ImpactAdminController.php").read_text(encoding="utf-8")
        view = (ROOT / "laravel_zerowaste" / "resources" / "views" / "admin" / "impacto" / "movimientos.blade.php").read_text(encoding="utf-8")
        for field in ["referencia", "desde", "hasta", "tipo"]:
            self.assertIn(field, controller)
        self.assertIn("streamDownload", controller)
        self.assertIn("America/Mexico_City", view)

    def test_audit_logger_excludes_sensitive_fields(self):
        source = (ROOT / "laravel_zerowaste" / "app" / "Services" / "AuditLogger.php").read_text(encoding="utf-8")
        for field in ["password", "token", "jwt", "cookie", "secret"]:
            self.assertIn(field, source)

    def test_avatar_has_loading_and_initial_fallback(self):
        layout = (ROOT / "laravel_zerowaste" / "resources" / "views" / "layouts" / "admin.blade.php").read_text(encoding="utf-8")
        self.assertIn("avatar-fallback", layout)
        self.assertIn("animate-pulse", layout)
        self.assertIn("onerror=", layout)
        self.assertIn("avatar_url", layout)

    def test_grafana_dashboards_use_current_node_contracts(self):
        dashboards = ROOT / "grafana" / "dashboards"
        for path in dashboards.glob("*.json"):
            json.loads(path.read_text(encoding="utf-8"))
        two_nodes = (dashboards / "zerowaste-two-droplets.json").read_text(encoding="utf-8")
        service_health = (dashboards / "service-health.json").read_text(encoding="utf-8")
        self.assertIn("SIN TARGET / NO LISTO", two_nodes)
        self.assertIn('job=\\"blackbox-public\\"', service_health)
        self.assertNotIn('job=\\"blackbox-https\\"', service_health)
        self.assertNotIn("laravel1", two_nodes + service_health)
        self.assertNotIn("laravel2", two_nodes + service_health)
        prometheus = (ROOT / "prometheus" / "prometheus.yml").read_text(encoding="utf-8")
        for job in ["blackbox-public", "blackbox-internal", "blackbox-media-route", "blackbox-origins"]:
            self.assertIn(f"job_name: {job}", prometheus)


if __name__ == "__main__":
    unittest.main()
