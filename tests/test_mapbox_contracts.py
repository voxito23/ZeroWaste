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
            "loadingMap",
            "loadingPoints",
            "pointsReady",
            "mapError",
            "pointsError",
            "locationPermission",
            "locationError",
        ):
            self.assertIn(state, source)
        self.assertIn("onDidFinishLoadingStyle={handleMapReady}", source)
        self.assertIn("onMapLoadingError={handleMapLoadingError}", source)

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
