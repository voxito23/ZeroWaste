import os
import sys
import unittest
from pathlib import Path
from unittest.mock import patch


ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT / "fast_api"))

from app.services.transactional_email import verification_otp  # noqa: E402


class EmailOtpTests(unittest.TestCase):
    def test_otp_is_six_digits_stable_and_secret_bound(self):
        token_hash = "a" * 64
        with patch.dict(os.environ, {"EMAIL_OTP_SECRET": "local-test-secret-one"}):
            first = verification_otp(token_hash)
            repeated = verification_otp(token_hash)
        with patch.dict(os.environ, {"EMAIL_OTP_SECRET": "local-test-secret-two"}):
            rotated = verification_otp(token_hash)

        self.assertRegex(first, r"^\d{6}$")
        self.assertEqual(first, repeated)
        self.assertNotEqual(first, rotated)


if __name__ == "__main__":
    unittest.main()
