"""Static contracts for mobile push, notification history and comment deep links."""

from pathlib import Path
import unittest


ROOT = Path(__file__).resolve().parents[1]


class MobileNotificationContractsTests(unittest.TestCase):
    def test_backend_owns_tokens_preferences_history_and_delivery(self):
        router = (ROOT / "fast_api/app/routers/notifications.py").read_text(encoding="utf-8")
        service = (ROOT / "fast_api/app/services/push_notifications.py").read_text(encoding="utf-8")
        models = (ROOT / "fast_api/app/models/domain_models.py").read_text(encoding="utf-8")
        self.assertIn('@router.post("/devices/push-token"', router)
        self.assertIn('@router.get("/devices/push-status"', router)
        self.assertIn('@router.get("/notifications"', router)
        self.assertIn('@router.patch("/preferences/notifications"', router)
        self.assertIn("class DevicePushToken", models)
        self.assertIn("class NotificationPreference", models)
        self.assertIn("preference.push_enabled", service)
        self.assertIn("preference.in_app_enabled", service)
        self.assertIn("DeviceNotRegistered", service)
        self.assertIn("_persist_delivery_results", service)
        self.assertIn("TransportError:", service)

    def test_mobile_handles_foreground_background_and_cold_start_contracts(self):
        mobile = (ROOT / "mobile_app/services/mobileNotifications.js").read_text(encoding="utf-8")
        navigator = (ROOT / "mobile_app/navigation/AppNavigator.js").read_text(encoding="utf-8")
        self.assertIn("setNotificationHandler", mobile)
        self.assertIn("getExpoPushTokenAsync", mobile)
        self.assertIn("addNotificationResponseReceivedListener", navigator)
        self.assertIn("getLastNotificationResponseAsync", navigator)
        self.assertIn("highlightCommentId", mobile)
        self.assertIn("getPushRegistrationStatus", mobile)
        self.assertNotIn("Linking.openURL", mobile)

    def test_schema_change_is_prepared_but_not_applied_by_runtime(self):
        migration = ROOT / "laravel_zerowaste/database/migrations/2026_08_02_000001_add_mobile_notifications_and_comment_replies.php"
        main = (ROOT / "fast_api/app/main.py").read_text(encoding="utf-8")
        self.assertTrue(migration.exists())
        self.assertNotIn("create_all", main)


if __name__ == "__main__":
    unittest.main()
