"""Application metrics with low-cardinality, privacy-safe labels."""

from prometheus_client import Counter, Gauge

LOGIN_FAILURES = Counter("zerowaste_login_failures_total", "Invalid login attempts")
LOGIN_BLOCKS = Counter("zerowaste_login_lockouts_total", "Login lockouts activated")
RATE_LIMITS = Counter("zerowaste_rate_limits_total", "Requests rejected by API rate limiting")
FIREWALL_BLOCKS = Counter("zerowaste_firewall_blocks_total", "Requests blocked by the application firewall", ["reason"])
READINESS = Gauge("zerowaste_readiness", "Dependency readiness (1 ready, 0 unavailable)")
