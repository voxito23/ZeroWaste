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

    def test_redemption_status_is_human_readable_and_never_returns_generic_conflict(self):
        controller = (ROOT / "laravel_zerowaste" / "app" / "Http" / "Controllers" / "ImpactAdminController.php").read_text(encoding="utf-8")
        view = (ROOT / "laravel_zerowaste" / "resources" / "views" / "admin" / "impacto" / "canjes.blade.php").read_text(encoding="utf-8")
        update = controller[controller.index("function updateRedemption"):controller.index("public function rules")]
        self.assertNotIn("abort(409", update)
        self.assertIn("ValidationException::withMessages", update)
        self.assertIn("$row->estado === $data['estado']", update)
        self.assertIn("'EN_PREPARACION' => ['label' => 'En preparación'", view)
        self.assertIn("'LISTA_PARA_ENTREGAR' => ['label' => 'Lista para entregar'", view)
        self.assertIn("data-status-icon", view)

    def test_reward_uses_professional_spanish_datetime_picker(self):
        fields = (ROOT / "laravel_zerowaste" / "resources" / "views" / "admin" / "impacto" / "partials" / "reward-fields.blade.php").read_text(encoding="utf-8")
        self.assertIn("data-reward-calendar", fields)
        self.assertIn("enableTime: true", fields)
        self.assertIn("locale: 'es'", fields)
        self.assertIn("disableMobile: true", fields)

    def test_qr_failures_are_classified_without_logging_tokens(self):
        service = (ROOT / "laravel_zerowaste" / "app" / "Services" / "FastApiQrService.php").read_text(encoding="utf-8")
        controller = (ROOT / "laravel_zerowaste" / "app" / "Http" / "Controllers" / "PointQrController.php").read_text(encoding="utf-8")
        for variable in ["SYSTEM_API_KEY", "FASTAPI_INTERNAL_URL", "QR_TOKEN_ENCRYPTION_KEY"]:
            self.assertIn(variable, service)
        self.assertIn("'location_id' => $locationId", controller)
        for unsafe in ["token_ciphertext", "X-API-Key", "response_body"]:
            self.assertNotIn(unsafe, controller)
        self.assertIn("explode(',', $configured)", service)
        self.assertIn("getenv('SYSTEM_API_KEY')", service)
        self.assertIn("getenv('FASTAPI_INTERNAL_URL')", service)

    def test_qr_services_share_the_same_compose_integration_contract(self):
        compose = (ROOT / "docker-compose.yml").read_text(encoding="utf-8")
        shared_environment = compose.split("x-app-environment:", 1)[1].split("services:", 1)[0]
        self.assertIn("SYSTEM_API_KEY: ${SYSTEM_API_KEY:-}", shared_environment)
        self.assertIn("FASTAPI_INTERNAL_URL: ${FASTAPI_INTERNAL_URL:-http://fast_api:6000}", shared_environment)

    def test_laravel_media_volume_is_prepared_with_shared_group(self):
        compose = (ROOT / "docker-compose.yml").read_text(encoding="utf-8")
        dockerfile = (ROOT / "laravel_zerowaste" / "Dockerfile").read_text(encoding="utf-8")
        entrypoint = (ROOT / "laravel_zerowaste" / "docker" / "entrypoint.sh").read_text(encoding="utf-8")
        self.assertIn("MEDIA_GID: ${MEDIA_GID:-2000}", compose)
        self.assertIn('ENTRYPOINT ["zerowaste-laravel-entrypoint"]', dockerfile)
        self.assertIn('media_root" = "/data/media"', entrypoint)
        self.assertIn('-m 2770', entrypoint)
        self.assertNotIn('chmod 777', entrypoint)

    def test_admin_avatar_supports_fifteen_megabytes_end_to_end(self):
        controller = (ROOT / "laravel_zerowaste" / "app" / "Http" / "Controllers" / "AdminProfileController.php").read_text(encoding="utf-8")
        media = (ROOT / "laravel_zerowaste" / "app" / "Support" / "Media.php").read_text(encoding="utf-8")
        php = (ROOT / "laravel_zerowaste" / "docker" / "php-production.ini").read_text(encoding="utf-8")
        nginx = (ROOT / "nginx" / "api.conf").read_text(encoding="utf-8")
        self.assertIn("max:15360", controller)
        self.assertIn("15 * 1024 * 1024", controller)
        self.assertIn("int $maximumBytes", media)
        self.assertIn("File::ensureDirectoryExists", media)
        self.assertIn("is_writable($directory)", media)
        self.assertNotIn("is_writable(Media::directory('perfiles'))", controller)
        self.assertIn("Media::discard(basename(parse_url($previousImage", controller)
        self.assertIn("upload_max_filesize = 16M", php)
        self.assertIn("post_max_size = 18M", php)
        self.assertIn("client_max_body_size 20m", nginx)

    def test_user_avatar_edit_shows_real_limits_and_recovers_from_failures(self):
        controller = (ROOT / "laravel_zerowaste" / "app" / "Http" / "Controllers" / "UserController.php").read_text(encoding="utf-8")
        view = (ROOT / "laravel_zerowaste" / "resources" / "views" / "admin" / "usuarios_edit.blade.php").read_text(encoding="utf-8")
        update = controller[controller.index("public function update(Request $request, User $user)"):controller.index("public function destroy(User $user)")]
        self.assertIn("max:15360", update)
        self.assertIn("'foto_perfil.mimes'", update)
        self.assertIn("Log::error('No fue posible guardar la foto del usuario", update)
        self.assertIn("withErrors([", update)
        self.assertIn("Media::discard(\n                basename(parse_url($previousImage", update)
        self.assertIn("accept=\"image/jpeg,image/png,image/webp\"", view)
        self.assertIn("15 * 1024 * 1024", update)
        self.assertIn("Máx. 15 MB", view)
        self.assertNotIn("Máx 250MB", view)
        self.assertIn("file.size > 15 * 1024 * 1024", view)
        self.assertIn("@error('foto_perfil')", view)

    def test_user_creation_is_not_blocked_by_optional_avatar_storage(self):
        controller = (ROOT / "laravel_zerowaste" / "app" / "Http" / "Controllers" / "UserController.php").read_text(encoding="utf-8")
        model = (ROOT / "laravel_zerowaste" / "app" / "Models" / "User.php").read_text(encoding="utf-8")
        media = (ROOT / "laravel_zerowaste" / "app" / "Support" / "Media.php").read_text(encoding="utf-8")
        view = (ROOT / "laravel_zerowaste" / "resources" / "views" / "admin" / "usuarios_create.blade.php").read_text(encoding="utf-8")
        store = controller[controller.index("public function store(Request $request)"):controller.index("public function edit(User $user)")]
        self.assertIn("'foto_perfil' => 'nullable|image", store)
        self.assertIn("$data['foto_perfil'] = 'perfil_default.png'", store)
        self.assertIn("$data['email_verified_at'] = now()", store)
        self.assertIn("Log::error('No fue posible crear el usuario", store)
        self.assertNotIn("throw $error", store)
        self.assertIn("'email_verified_at'", model)
        self.assertIn("stream_copy_to_stream", media)
        self.assertIn("'.upload-'", media)
        self.assertNotIn("$file->move", media)
        self.assertIn("Opcional · JPEG, PNG o WebP · Máx. 15 MB", view)
        self.assertNotIn("Debes subir una fotografía de perfil", view)
        self.assertIn("value=\"{{ old('nombre') }}\"", view)

    def test_user_search_uses_real_postgres_columns_and_keeps_ajax_errors_inline(self):
        controller = (ROOT / "laravel_zerowaste" / "app" / "Http" / "Controllers" / "UserController.php").read_text(encoding="utf-8")
        view = (ROOT / "laravel_zerowaste" / "resources" / "views" / "admin" / "usuarios.blade.php").read_text(encoding="utf-8")
        index = controller[controller.index("public function index(Request $request)"):controller.index("public function checkEmail")]
        self.assertIn("where('nombre', 'ilike'", index)
        self.assertIn("orWhere('email', 'ilike'", index)
        self.assertNotIn("where('name'", index)
        self.assertIn("if (!response.ok) throw new Error", view)
        self.assertIn('id="userTableError"', view)
        self.assertIn("tableError.classList.remove('hidden')", view)

    def test_movements_have_filters_local_time_and_admin_csv(self):
        controller = (ROOT / "laravel_zerowaste" / "app" / "Http" / "Controllers" / "ImpactAdminController.php").read_text(encoding="utf-8")
        view = (ROOT / "laravel_zerowaste" / "resources" / "views" / "admin" / "impacto" / "movimientos.blade.php").read_text(encoding="utf-8")
        for field in ["referencia", "desde", "hasta", "tipo"]:
            self.assertIn(field, controller)
        self.assertIn("streamDownload", controller)
        self.assertIn("America/Mexico_City", view)

    def test_forum_post_deletion_cleans_dependencies_in_one_transaction(self):
        controller = (ROOT / "laravel_zerowaste" / "app" / "Http" / "Controllers" / "PostController.php").read_text(encoding="utf-8")
        view = (ROOT / "laravel_zerowaste" / "resources" / "views" / "admin" / "posts" / "index.blade.php").read_text(encoding="utf-8")
        destroy = controller[controller.index("function destroy("):controller.index("public function approve")]
        self.assertIn("DB::transaction", destroy)
        self.assertIn("lockForUpdate()", destroy)
        for table in ["notificaciones", "likes_foro", "respuestas", "posts"]:
            self.assertIn(f"DB::table('{table}')", destroy)
        self.assertIn("forum_post.deleted", destroy)
        self.assertIn("Media::discard($image, 'foro')", destroy)
        self.assertLess(destroy.index("DB::transaction"), destroy.index("Media::discard"))
        self.assertNotIn("$post->delete()", destroy)
        self.assertIn("form?.requestSubmit()", view)
        self.assertNotIn("document.getElementById(formId).submit()", view)

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
        two_nodes_path = dashboards / "zerowaste-two-droplets.json"
        service_health_path = dashboards / "service-health.json"
        two_nodes = two_nodes_path.read_text(encoding="utf-8")
        service_health = service_health_path.read_text(encoding="utf-8")
        two_nodes_data = json.loads(two_nodes)
        service_health_data = json.loads(service_health)
        node_health = next(panel for panel in two_nodes_data["panels"] if panel["id"] == 3)
        for target in node_health["targets"]:
            self.assertIn("vector(2)", target["expr"])
        self.assertEqual(
            node_health["fieldConfig"]["defaults"]["mappings"][0]["options"]["2"]["text"],
            "SIN TARGET",
        )
        node_summary = next(panel for panel in two_nodes_data["panels"] if panel["id"] == 4)
        self.assertIn("vector(-1)", node_summary["targets"][0]["expr"])
        self.assertIn('count(probe_success{job="blackbox-origins"', node_summary["targets"][1]["expr"])
        failover_mapping = node_summary["fieldConfig"]["overrides"][1]["properties"][0]["value"][0]["options"]
        self.assertEqual(failover_mapping["2"]["text"], "SIN DATOS")
        for panel_id in (2, 3, 4, 5, 6, 7, 11, 12, 13):
            panel = next(panel for panel in service_health_data["panels"] if panel["id"] == panel_id)
            for target in panel["targets"]:
                self.assertIn("vector(2)", target["expr"])
        self.assertIn('job=\\"blackbox-public\\"', service_health)
        self.assertNotIn('job=\\"blackbox-https\\"', service_health)
        self.assertNotIn("laravel1", two_nodes + service_health)
        self.assertNotIn("laravel2", two_nodes + service_health)
        prometheus = (ROOT / "prometheus" / "prometheus.yml").read_text(encoding="utf-8")
        for job in ["blackbox-public", "blackbox-internal", "blackbox-media-route", "blackbox-origins"]:
            self.assertIn(f"job_name: {job}", prometheus)


if __name__ == "__main__":
    unittest.main()
