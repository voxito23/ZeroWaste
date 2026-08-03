# Secondary Droplet installation

Target: `zerowaste-app-02`, public IPv4 `134.122.23.135`, path `/opt/ZeroWaste`. All actions are manual on DigitalOcean; this repository performs no SSH or deployment.

1. Update Ubuntu; keep the local SSH configuration when apt asks.
2. Create `deploy`, install its authorized SSH key, and install/enable Docker from Docker's official repository.
3. Clone the repository into `/opt/ZeroWaste`, checkout `main`, and compare `git rev-parse HEAD` with the approved commit.
4. Create configuration safely: `sudo install -m 600 -o root -g root /dev/null /opt/ZeroWaste/.env.node`. Edit through a secure root session; set `INSTANCE_NAME=zerowaste-app-02`, `NODE_ROLE=secondary`, and the public base URL. Transfer shared secrets through an approved secret channel—not Git or shell history. APP_KEY/JWT_SECRET/database/Redis/cookies/S3/mail/OAuth must match primary.
5. Allow Caddy to obtain its own public ACME certificate once an authorized DNS hostname points to this node; configure firewall and WireGuard, and create external Prometheus target files from examples.
6. Run Compose explicitly with `--env-file .env.node`, build, and start the base services (and optional safe worker profile). Do not enable `primary`.
7. Check health/readiness, storage, images and sessions through an explicitly authorized test hostname. There is no automatic public failover configured.

Manual deployment commands for either node:

```bash
cd /opt/ZeroWaste
git fetch origin
git checkout main
git pull --ff-only origin main
git rev-parse HEAD
docker compose --env-file .env.node config --quiet
docker compose --env-file .env.node build
docker compose --env-file .env.node up -d
docker compose --env-file .env.node ps
docker compose --env-file .env.node logs --tail=200
```

Update secondary and validate it first, then primary and Grafana. Retain the prior known-good commit/images for rollback; never use `git reset --hard`.
