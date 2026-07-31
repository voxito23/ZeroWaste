"""Application metrics with low-cardinality, privacy-safe labels."""

from prometheus_client import Counter, Gauge

LOGIN_FAILURES = Counter("zerowaste_login_failures_total", "Invalid login attempts")
LOGIN_BLOCKS = Counter("zerowaste_login_lockouts_total", "Login lockouts activated")
READINESS = Gauge("zerowaste_readiness", "Dependency readiness (1 ready, 0 unavailable)")
