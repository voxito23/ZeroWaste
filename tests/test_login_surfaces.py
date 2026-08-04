from pathlib import Path
import unittest


ROOT = Path(__file__).resolve().parents[1]


class LoginSurfaceTests(unittest.TestCase):
    def test_mobile_handles_429_and_retry_after(self):
        source = (ROOT / "mobile_app" / "screens" / "LoginScreen.js").read_text(encoding="utf-8")
        self.assertIn("error.response?.status === 429", source)
        self.assertIn("retry-after", source)
        self.assertIn("retry_after", source)
        self.assertIn("disabled={loading || retryAfter > 0}", source)
        self.assertIn("setPassword('')", source)

    def test_global_fastapi_limiter_is_redis_backed(self):
        source = (ROOT / "fast_api" / "app" / "main.py").read_text(encoding="utf-8")
        self.assertIn("storage_uri=REDIS_URL", source)
        self.assertIn("app.add_middleware(SlowAPIMiddleware)", source)
        self.assertIn('default_limits=["60/minute"]', source)
        self.assertNotIn('storage_uri="memory://"', source)

    def test_docs_are_restricted_to_configured_superadmin(self):
        source = (ROOT / "fast_api" / "app" / "routers" / "docs_auth.py").read_text(encoding="utf-8")
        jwt_auth = (ROOT / "fast_api" / "app" / "security" / "jwt_auth.py").read_text(encoding="utf-8")
        auth = (ROOT / "fast_api" / "app" / "routers" / "auth.py").read_text(encoding="utf-8")
        self.assertIn('scope != "docs:superadmin"', source)
        self.assertIn('"scope": "docs:superadmin"', source)
        self.assertIn("get_login_throttle()", source)
        self.assertIn('os.getenv("ADMIN_EMAIL"', jwt_auth)
        self.assertIn('scheme_name="AdministradorPrincipal"', jwt_auth)
        self.assertIn("current_email != principal", jwt_auth)
        self.assertIn("normalized_email != principal", auth)
        self.assertIn('"scope": "api:superadmin"', auth)

    def test_swagger_does_not_persist_tokens_or_offer_api_keys_in_query(self):
        swagger = (ROOT / "fast_api" / "app" / "templates" / "custom_swagger.html").read_text(encoding="utf-8")
        api_key = (ROOT / "fast_api" / "app" / "security" / "api_key_auth.py").read_text(encoding="utf-8")
        self.assertIn("persistAuthorization: false", swagger)
        self.assertIn('scheme_name="IntegracionInterna"', api_key)
        self.assertNotIn("APIKeyQuery", api_key)

    def test_fastapi_admin_mutations_use_the_principal_dependency(self):
        mapa = (ROOT / "fast_api" / "app" / "routers" / "mapa.py").read_text(encoding="utf-8")
        eventos = (ROOT / "fast_api" / "app" / "routers" / "eventos.py").read_text(encoding="utf-8")
        map_admin_section = mapa[mapa.index('@router.post(\n    "/puntos"'):mapa.index("# Calificaciones de puntos")]
        event_admin_section = eventos[eventos.index('@router.post(\n    ""'):]
        self.assertEqual(map_admin_section.count("Depends(get_current_admin_user)"), 3)
        self.assertEqual(event_admin_section.count("Depends(get_current_admin_user)"), 3)

    def test_laravel_login_uses_shared_rate_limiter(self):
        controller = (ROOT / "laravel_zerowaste" / "app" / "Http" / "Controllers" / "AuthController.php").read_text(encoding="utf-8")
        self.assertIn("RateLimiter::hit($accountKey, 300)", controller)
        self.assertIn("RateLimiter::attempts($accountKey) >= 5", controller)
        self.assertIn("RateLimiter::attempts($ipKey) >= 5", controller)
        self.assertIn("Cache::put($accountLockKey, time() + 60, 60)", controller)
        self.assertIn("Demasiados intentos. Espera un minuto", controller)
        self.assertIn("Usuario o contraseña incorrectos.", controller)

    def test_all_password_login_surfaces_limit_to_five(self):
        fastapi = (ROOT / "fast_api" / "app" / "security" / "login_throttle.py").read_text(encoding="utf-8")
        flask = (ROOT / "flask_zerowaste" / "app.py").read_text(encoding="utf-8")
        self.assertIn("account_limit: int = 5", fastapi)
        self.assertIn("ip_limit: int = 5", fastapi)
        login_block = flask[flask.index("@app.route('/login'"):flask.index("@app.route('/registro'")]
        self.assertIn('@limiter.limit("5/minute")', login_block)

    def test_admin_routes_are_authenticated_and_logout_is_post(self):
        routes = (ROOT / "laravel_zerowaste" / "routes" / "web.php").read_text(encoding="utf-8")
        self.assertIn("middleware(['auth', 'admin'])", routes)
        self.assertIn("Route::post('/logout'", routes)
        self.assertNotIn("Route::get('/zw-interno/logout'", routes)


if __name__ == "__main__":
    unittest.main()
