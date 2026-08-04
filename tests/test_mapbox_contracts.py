"""Regression checks for Mapbox configuration without using real tokens."""

from pathlib import Path
import unittest


ROOT = Path(__file__).resolve().parents[1]


class MapboxContractTests(unittest.TestCase):
    def test_web_maps_do_not_embed_placeholders_or_read_env_in_blades(self):
        flask_map = (ROOT / "flask_zerowaste/templates/mapa.html").read_text(encoding="utf-8")
        laravel_index = (
            ROOT / "laravel_zerowaste/resources/views/admin/mapa/index.blade.php"
        ).read_text(encoding="utf-8")
        laravel_create = (
            ROOT / "laravel_zerowaste/resources/views/admin/mapa/create.blade.php"
        ).read_text(encoding="utf-8")
        combined = "\n".join((flask_map, laravel_index, laravel_create))
        self.assertNotIn("YOUR_MAPBOX_TOKEN_HERE", combined)
        self.assertNotIn("env('MAPBOX_TOKEN'", combined)
        self.assertIn("ZeroWasteMapbox.createMap", combined)

    def test_mobile_map_has_independent_readiness_and_error_states(self):
        source = (ROOT / "mobile_app/screens/MapScreen.js").read_text(encoding="utf-8")
        for state in (
            "tokenReady",
            "mapMounted",
            "mapReady",
            "styleLoading",
            "pointsLoading",
            "pointsReady",
            "mapError",
            "pointsError",
            "permissionState",
            "locationError",
            "searching",
            "selectedResult",
        ):
            self.assertIn(state, source)
        self.assertIn("onDidFinishLoadingStyle={handleMapReady}", source)
        self.assertIn("onMapLoadingError={handleMapLoadingError}", source)
        self.assertIn("initializeMapbox()", source)
        self.assertIn("{tokenReady ? (", source)
        self.assertNotIn("tokenReady && mapboxConfigured", source)
        self.assertIn("pointerEvents={mapError ? 'auto' : 'none'}", source)
        self.assertIn("styleURL={MAP_2D_STYLE_URL}", source)
        self.assertNotIn("Mapbox.StyleImport", source)

        config = (ROOT / "mobile_app/utils/mapbox.js").read_text(encoding="utf-8")
        self.assertIn("MAP_2D_STYLE_URL = Mapbox.StyleURL.Street", config)
        self.assertIn("Promise.resolve(Mapbox.setAccessToken(MAPBOX_PUBLIC_TOKEN))", config)
        self.assertIn("configureMapbox", config)
        self.assertNotIn("Mapbox.setAccessToken(MAPBOX_TOKEN);", source)

    def test_mobile_can_load_only_the_public_mapbox_token_at_runtime(self):
        router = (ROOT / "fast_api/app/routers/mobile_links.py").read_text(encoding="utf-8")
        middleware = (ROOT / "fast_api/app/security/api_key_auth.py").read_text(encoding="utf-8")
        screen = (ROOT / "mobile_app/screens/MapScreen.js").read_text(encoding="utf-8")
        self.assertIn('@router.get("/mobile/config"', router)
        self.assertIn('os.getenv("MAPBOX_PUBLIC_TOKEN"', router)
        self.assertNotIn("MAPBOX_SECRET_TOKEN", router)
        self.assertIn('"/mobile/config"', middleware)
        self.assertIn('"/api/mobile/config"', middleware)
        self.assertIn("api.get('/mobile/config')", screen)

    def test_docker_passes_one_public_mapbox_token_to_laravel_and_flask(self):
        compose = (ROOT / "docker-compose.yml").read_text(encoding="utf-8")
        anchor = compose.split("x-app-environment:", 1)[1].split("services:", 1)[0]
        self.assertIn("MAPBOX_PUBLIC_TOKEN: ${MAPBOX_PUBLIC_TOKEN:-}", anchor)

        laravel = (ROOT / "laravel_zerowaste/config/services.php").read_text(encoding="utf-8")
        flask = (ROOT / "flask_zerowaste/app.py").read_text(encoding="utf-8")
        self.assertIn("env('MAPBOX_PUBLIC_TOKEN'", laravel)
        self.assertIn("os.getenv('MAPBOX_PUBLIC_TOKEN')", flask)

    def test_points_contract_is_numeric_and_explicitly_active(self):
        schemas = (ROOT / "fast_api/app/models/schemas.py").read_text(encoding="utf-8")
        response = schemas.split("class PuntoMapaResponse", 1)[1].split(
            "# Esquemas del mapa — calificaciones", 1
        )[0]
        self.assertIn("latitud: float", response)
        self.assertIn("longitud: float", response)
        self.assertIn("activo: bool = True", response)


if __name__ == "__main__":
    unittest.main()
