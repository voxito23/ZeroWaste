# ZeroWaste production infrastructure, security and observability

## Traffic and trust boundaries

Only Caddy publishes host ports: TCP 80 and TCP 443. The request paths are:

- `/api/*` and protected FastAPI documentation: Caddy → Nginx → FastAPI.
- `/zw-interno*`: Caddy → Nginx → `laravel1` / `laravel2`.
- `/grafana/*`: Caddy → Grafana.
- `/media/*`: Caddy → `media_static`.
- All remaining paths: Caddy → Flask (`cliente`).

Nginx uses port 8080 for application traffic and 8081 for liveness and
`stub_status`. Neither port is published on the host. Caddy's mutable admin API
is disabled. An internal read-only health/metrics listener uses port 2019 and
is scraped only through `monitoring_network`; `/metrics`, `/stub_status` and
Prometheus are blocked on the public site.

Networks have distinct responsibilities:

- `edge_network`: Caddy, Nginx, Grafana and Blackbox.
- `app_network`: application services and private Blackbox probes.
- `data_network`: Redis and database-dependent application services.
- `monitoring_network`: Prometheus, Grafana, Caddy metrics and exporters.
- `backend_egress`: only services that need Supabase or external APIs.
- `media_network`: an internal network containing only Caddy and
  `media_static`.

`media_network` is an intentional extra boundary. Blackbox does not join it.
Instead, Prometheus checks the complete public HTTPS media path with a
deliberately missing filename and expects a clean 404. This preserves the rule
that Caddy is the only client of `media_static` while still monitoring routing.

## Required untracked variables

Configure these outside Git before `docker compose config`:

```dotenv
GRAFANA_ADMIN_PASSWORD=<strong-random-password>
MEDIA_ROOT=/opt/ZeroWaste/shared/media
MEDIA_GID=2000
ZEROWASTE_IMAGE_TAG=<git-commit-or-release>
```

Laravel, Flask and FastAPI continue to load their own untracked `.env` files.
Never put database URLs, JWTs, APP_KEY, Mapbox tokens or API keys in Compose,
Dockerfiles, dashboards or labels.

## Persistent media directories on DigitalOcean

The deployment operator creates the directories manually. The repository does
not create or modify paths on the Droplet:

```bash
sudo groupadd --gid 2000 zerowaste-media
sudo install -d -o root -g zerowaste-media -m 2770 /opt/ZeroWaste/shared/media
sudo install -d -o root -g zerowaste-media -m 2770 /opt/ZeroWaste/shared/media/foro
sudo install -d -o root -g zerowaste-media -m 2770 /opt/ZeroWaste/shared/media/perfiles
sudo install -d -o root -g zerowaste-media -m 2770 /opt/ZeroWaste/shared/media/recompensas
sudo install -d -o root -g zerowaste-media -m 2770 /opt/ZeroWaste/shared/media/campanas
sudo install -d -o root -g zerowaste-media -m 2770 /opt/ZeroWaste/shared/media/eventos
sudo install -d -o root -g zerowaste-media -m 2770 /opt/ZeroWaste/shared/media/puntos
```

Directories use the setgid bit so newly created files inherit group 2000.
Compose adds `MEDIA_GID` as a supplementary group to readers and writers,
avoiding dependence on a host UID. The derived media image also records that
group for Nginx workers, so files can use mode `0660` and directories `2770`
without relying on world-readable permissions. Do not use `777`.

Place the supplied reward files exactly at:

```text
/opt/ZeroWaste/shared/media/recompensas/termo_reutilizable.png
/opt/ZeroWaste/shared/media/recompensas/bolsa_reutilizable.png
/opt/ZeroWaste/shared/media/recompensas/kit_botes_separacion.png
/opt/ZeroWaste/shared/media/recompensas/kit_cubiertos_reutilizables.png
/opt/ZeroWaste/shared/media/recompensas/compostera_domestica.png
```

The canonical public URL is
`https://www.zerowaste-qro.com/media/<categoria>/<archivo>`. Legacy profile,
forum and reward routes redirect to this HTTPS namespace; files are not copied.

FastAPI writes `foro` and `perfiles`. Laravel can manage the shared media root.
Flask currently retains write access only to `foro` and `perfiles` during the
transition; rewards, campaigns, events and map-point images are read-only.
`media_static` mounts the entire tree read-only.

Before replacing the previous named volumes, inventory and copy their contents
to the new host directories. Keep the old Docker volumes intact until HTTPS,
application and rollback checks pass.

## Upload boundary

Backend helpers validate a maximum file size of 5 MiB. Nginx and PHP accept 8
MiB request bodies to leave room for multipart form overhead. MIME, decoded
image type and safe UUID filename validation remain application responsibilities.

## Container hardening

Services use an init process, bounded JSON logs, PID/memory/CPU limits,
`no-new-privileges`, and dropped capabilities or read-only filesystems where
the upstream image supports them. Exceptions are deliberate:

- Apache keeps the capabilities required by its root master and writes only
  Laravel storage/cache plus mounted media.
- Redis must initialize ownership of its persistent data volume.
- cAdvisor is privileged and mounts broad host/Docker paths. A compromised
  cAdvisor could affect host confidentiality or the Docker daemon even though
  mounts are marked read-only.
- Node Exporter mounts the host root read-only and therefore has broad read
  visibility.
- Flask and FastAPI retain writable runtime filesystems for library caches;
  temporary ML/Matplotlib caches are redirected to tmpfs.

Neither cAdvisor, Node Exporter nor any other exporter is published on the host.

## Monitoring semantics

Prometheus scrapes Caddy, FastAPI, Grafana, Nginx Exporter, Redis Exporter,
Node Exporter, cAdvisor, Blackbox and itself. Blackbox checks:

- public HTTPS home, health, readiness, map, forum, Laravel and Grafana routes;
- private liveness for Caddy, Nginx, FastAPI, Flask, both Laravel replicas,
  Prometheus and Grafana;
- read-only Supabase readiness through FastAPI and Flask;
- canonical media delivery using an expected HTTPS 404.

The media probe intentionally does not require a known user file. Deployment
checks must additionally test real files for 200, MIME type, cache headers and
HTTPS. No personal identifier, cookie, JWT or media filename from a user is a
Prometheus label.

## Manual deployment

After directories, untracked variables and backups are prepared:

```bash
cd /opt/ZeroWaste
git pull origin main
docker compose config
docker compose up --build -d
docker compose ps
docker compose logs --tail=200
```

No migration is part of Compose startup. Do not run `migrate:fresh`, database
reset commands or `db.create_all()`.

## Development overlay

Production never loads source bind mounts implicitly. Local development uses:

```powershell
docker compose -f docker-compose.yml -f docker-compose.dev.yml up --build
```

The optional Tailwind watcher additionally requires `--profile tools`.

## Rollback

Use a revert commit or deploy the prior image tag; do not use
`git reset --hard`. Restore the previous Compose/Caddy/Nginx configuration and
recreate services without deleting media directories or legacy Docker volumes.
Database media migrations are not part of this infrastructure change.

## Host firewall verification

Run manually on the Droplet:

```bash
sudo ufw status numbered
sudo ss -lntup
```

Expected public inbound ports are SSH 22, HTTP 80 and HTTPS 443. Port 22 is a
host firewall concern and is not declared in Compose.
