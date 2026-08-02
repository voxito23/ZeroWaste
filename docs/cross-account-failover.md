# Cross-account failover

## Architecture

Cloudflare DNS/HTTPS and Load Balancing send normal traffic to `zerowaste-app-01` in account A and fail over to `zerowaste-app-02` in account B. Both run the same `main` commit and Compose project: Caddy, Nginx, one Laravel, FastAPI, Flask, media gateway and exporters. Supabase, external Redis/Valkey, S3-compatible media, email API and OAuth are shared.

The primary alone enables profiles `primary` and `primary-monitoring`; the secondary runs the base application and exporters. `monitoring-standby` is manual. Never run two schedulers without a distributed lock.

## Local automatic work versus manual work

- Local: validate Compose/configuration, build images, tests and documentation.
- Neubox: inventory DNS, handle DNSSEC and replace nameservers manually.
- Cloudflare: onboard zone, Origin CA, firewall and paid Load Balancing manually.
- DigitalOcean: install/configure each Droplet manually; never move either node between accounts.

Required before rollout: `<PRIMARY_DROPLET_IPV4>`, Cloudflare nameservers, two Origin CA pairs, shared service credentials and administrative IP. Roll back code with a known-good Git commit using normal checkout (never reset hard), then rebuild; DNS rollback is documented in the Neubox guide.
