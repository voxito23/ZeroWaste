import hashlib
import importlib.util
from pathlib import Path
import sys
import types
import unittest

from starlette.requests import Request
from starlette.responses import PlainTextResponse


ROOT = Path(__file__).resolve().parents[1]


class FakeMetric:
    def labels(self, **_labels):
        return self

    def inc(self):
        return None


def load_firewall_module():
    module_names = ("app", "app.observability", "app.security", "app.security.login_throttle")
    previous = {name: sys.modules.get(name) for name in module_names}
    app_module = types.ModuleType("app")
    app_module.__path__ = []
    security_module = types.ModuleType("app.security")
    security_module.__path__ = []
    observability_module = types.ModuleType("app.observability")
    observability_module.FIREWALL_BLOCKS = FakeMetric()
    login_module = types.ModuleType("app.security.login_throttle")

    def opaque_key(request):
        authorization = request.headers.get("authorization", "")
        return hashlib.sha256(authorization.encode("utf-8")).hexdigest()

    login_module.get_rate_limit_key = opaque_key
    sys.modules.update({
        "app": app_module,
        "app.observability": observability_module,
        "app.security": security_module,
        "app.security.login_throttle": login_module,
    })
    try:
        path = ROOT / "fast_api/app/security/firewall.py"
        spec = importlib.util.spec_from_file_location("firewall_under_test", path)
        module = importlib.util.module_from_spec(spec)
        spec.loader.exec_module(module)
        return module
    finally:
        for name, value in previous.items():
            if value is None:
                sys.modules.pop(name, None)
            else:
                sys.modules[name] = value


firewall_module = load_firewall_module()


def make_request(token: str):
    return Request({
        "type": "http",
        "http_version": "1.1",
        "method": "GET",
        "scheme": "https",
        "path": "/foro/posts",
        "raw_path": b"/foro/posts",
        "query_string": b"",
        "headers": [(b"authorization", f"Bearer {token}".encode("utf-8"))],
        "client": ("203.0.113.10", 443),
        "server": ("example.test", 443),
    })


class FirewallRateLimitContracts(unittest.TestCase):
    def test_rate_limit_is_opaque_and_does_not_temporarily_block_shared_ip(self):
        firewall = (ROOT / "fast_api/app/security/firewall.py").read_text(encoding="utf-8")
        rate_block = firewall.split("# 3. Rate limiting", 1)[1].split("# 4. Escanear URL", 1)[0]
        self.assertIn("get_rate_limit_key(request)", rate_block)
        self.assertNotIn("block_ip(ip)", rate_block)
        self.assertIn('headers={"Retry-After": "60"}', rate_block)
        self.assertIn('os.getenv("FIREWALL_RPM", "300")', firewall)


class FirewallRateLimitBehavior(unittest.IsolatedAsyncioTestCase):
    def setUp(self):
        self.original_limit = firewall_module.MAX_REQUESTS_PER_MINUTE
        firewall_module.MAX_REQUESTS_PER_MINUTE = 1
        firewall_module._request_log.clear()
        firewall_module._blocked_ips.clear()

    def tearDown(self):
        firewall_module.MAX_REQUESTS_PER_MINUTE = self.original_limit
        firewall_module._request_log.clear()
        firewall_module._blocked_ips.clear()

    async def test_sessions_behind_one_ip_are_independent_and_rate_limit_does_not_block_ip(self):
        middleware = firewall_module.FirewallMiddleware(lambda *_args, **_kwargs: None)

        async def ok(_request):
            return PlainTextResponse("ok")

        self.assertEqual((await middleware.dispatch(make_request("session-one"), ok)).status_code, 200)
        self.assertEqual((await middleware.dispatch(make_request("session-two"), ok)).status_code, 200)
        limited = await middleware.dispatch(make_request("session-one"), ok)
        self.assertEqual(limited.status_code, 429)
        self.assertEqual(limited.headers["Retry-After"], "60")
        self.assertNotIn("203.0.113.10", firewall_module._blocked_ips)


if __name__ == "__main__":
    unittest.main()
