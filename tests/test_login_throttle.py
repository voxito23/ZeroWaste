import pathlib
import importlib.util
import sys
import types
import unittest

from fastapi import HTTPException

ROOT = pathlib.Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT / "fast_api"))

# Production installs redis-py from fast_api/requirements.txt.  The unit test
# injects only its tiny interface when the developer's global Python lacks it;
# no network service or package installation is required for these tests.
try:
    from redis.exceptions import RedisError
except ModuleNotFoundError:
    redis_module = types.ModuleType("redis")
    exceptions_module = types.ModuleType("redis.exceptions")

    class RedisError(Exception):
        pass

    class Redis:
        @classmethod
        def from_url(cls, *_args, **_kwargs):
            raise AssertionError("Tests must inject FakeRedis")

    redis_module.Redis = Redis
    exceptions_module.RedisError = RedisError
    sys.modules["redis"] = redis_module
    sys.modules["redis.exceptions"] = exceptions_module

module_path = ROOT / "fast_api" / "app" / "security" / "login_throttle.py"
spec = importlib.util.spec_from_file_location("login_throttle_under_test", module_path)
login_throttle_module = importlib.util.module_from_spec(spec)
sys.modules[spec.name] = login_throttle_module
spec.loader.exec_module(login_throttle_module)
LoginThrottle = login_throttle_module.LoginThrottle
ThrottlePolicy = login_throttle_module.ThrottlePolicy


class FakeRedis:
    def __init__(self):
        self.now = 0
        self.values = {}
        self.expires = {}

    def _expire(self, key):
        if key in self.expires and self.expires[key] <= self.now:
            self.values.pop(key, None)
            self.expires.pop(key, None)

    def ttl(self, key):
        self._expire(key)
        if key not in self.values:
            return -2
        return self.expires.get(key, self.now) - self.now

    def delete(self, *keys):
        for key in keys:
            self.values.pop(key, None)
            self.expires.pop(key, None)

    def eval(self, _script, _number_of_keys, failure_key, lock_key, limit, window, lock_seconds):
        self._expire(failure_key)
        self.values[failure_key] = self.values.get(failure_key, 0) + 1
        if self.values[failure_key] == 1:
            self.expires[failure_key] = self.now + int(window)
        if self.values[failure_key] >= int(limit):
            self.values[lock_key] = 1
            self.expires[lock_key] = self.now + int(lock_seconds)
            self.delete(failure_key)
            return int(lock_seconds)
        return 0

    def advance(self, seconds):
        self.now += seconds


class BrokenRedis(FakeRedis):
    def ttl(self, _key):
        raise RedisError("unavailable")


class LoginThrottleTests(unittest.TestCase):
    def setUp(self):
        self.redis = FakeRedis()
        self.throttle = LoginThrottle(
            client=self.redis,
            policy=ThrottlePolicy(account_limit=5, ip_limit=20, failure_window=300, lock_seconds=60),
        )

    def fail(self, count, email="user@example.test", ip="203.0.113.10"):
        error = None
        for _ in range(count):
            try:
                self.throttle.record_failure(email, ip)
            except HTTPException as exc:
                error = exc
        return error

    def test_first_four_failures_are_not_locked(self):
        self.assertIsNone(self.fail(4))
        self.throttle.assert_allowed("user@example.test", "203.0.113.10")

    def test_fifth_failure_locks_immediately_with_retry_after(self):
        error = self.fail(5)
        self.assertIsNotNone(error)
        self.assertEqual(error.status_code, 429)
        self.assertEqual(error.headers["Retry-After"], "60")
        self.assertIn("Espera un minuto", error.detail)

    def test_correct_credentials_cannot_bypass_active_lock(self):
        self.fail(5)
        with self.assertRaises(HTTPException) as raised:
            self.throttle.assert_allowed("user@example.test", "203.0.113.10")
        self.assertEqual(raised.exception.status_code, 429)

    def test_lock_expires_without_waiting_in_real_time(self):
        self.fail(5)
        self.redis.advance(60)
        self.throttle.assert_allowed("user@example.test", "203.0.113.10")

    def test_success_clears_failures(self):
        self.fail(4)
        self.throttle.clear("user@example.test", "203.0.113.10")
        self.assertIsNone(self.fail(4))

    def test_two_instances_share_the_same_redis_lock(self):
        other = LoginThrottle(client=self.redis, policy=self.throttle.policy)
        self.fail(5)
        with self.assertRaises(HTTPException) as raised:
            other.assert_allowed("user@example.test", "203.0.113.10")
        self.assertEqual(raised.exception.status_code, 429)

    def test_identifier_is_not_stored_in_redis_key(self):
        self.fail(1)
        self.assertFalse(any("user@example.test" in key for key in self.redis.values))

    def test_redis_outage_fails_closed_with_controlled_503(self):
        throttle = LoginThrottle(client=BrokenRedis())
        with self.assertRaises(HTTPException) as raised:
            throttle.assert_allowed("user@example.test", "203.0.113.10")
        self.assertEqual(raised.exception.status_code, 503)


if __name__ == "__main__":
    unittest.main()
