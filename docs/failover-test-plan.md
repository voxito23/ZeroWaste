# Authorized failover test plan

Never run these production tests automatically. Record timestamps, active commit and rollback owner.

- A: with both nodes healthy, verify www is served by primary, all public probes and sessions work.
- B: during an approved window stop primary gateway; wait for configured consecutive failures, verify Cloudflare uses secondary, Redis-backed login/CSRF persists, S3 images and Supabase remain available.
- C: recover primary, wait for consecutive healthy checks, confirm traffic returns without duplicate schedulers.
- D: stop secondary while primary remains healthy; verify public service and Grafana warning.
- E: simulate both unavailable only in a safe environment; verify critical alert and generic error with no stack trace.

Test backup first at `https://respaldo.zerowaste-qro.com` (proxied, protected by Access/IP/auth, `X-Robots-Tag: noindex, nofollow`): `/`, `/api/health`, `/api/ready`, `/api/mapa/puntos`, `/zw-interno`, `/load-balancer-health`, `/load-balancer-ready`. Verify Flask/FastAPI/Laravel, TLS, headers, Redis, Supabase, S3, media, same commit and shared keys/cookies.

Rollback covers known-good Compose/Caddy/Nginx/app images, Prometheus/Grafana configs, Origin cert files, WireGuard config, external Redis/S3 selection and Cloudflare origin enablement. Revert code with a normal Git revert/checkout workflow and rebuild; never reset hard. For DNS, preserve old nameservers, correct records first, restore Neubox delegation only if indispensable, then verify site and mail. Never delete Droplets or change Supabase.
