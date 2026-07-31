# ZeroWaste security and observability

## Traffic and trust boundaries

Only Caddy publishes ports 80/443. Caddy forwards application traffic to the
internal Nginx gateway. Nginx routes `/api` to FastAPI, `/zw-interno` to
Laravel, and the remaining site to Flask. Redis, Prometheus and every exporter
have no host port. Grafana is available only through `/grafana/`.

`app_network`, `data_network`, and `monitoring_network` are internal. Backends
also join `backend_egress` because they must reach external Supabase; removing
that egress would break PostgreSQL connectivity. This is logical isolation on
one Droplet, not the two physical servers that some rubrics may require.

## Required external variables

- `GRAFANA_ADMIN_PASSWORD`: strong Grafana administrator password.
- Existing backend database/JWT/application secrets remain in each server
  `.env`; never copy them into Compose or Git.

`REDIS_URL=redis://redis:6379/0` is an internal service locator, not a user
credential. Redis has no published port and is reachable only on private
Docker networks.

## Login lockout

FastAPI stores anonymous SHA-256 key identifiers in Redis. Five invalid
credentials lock an account for 60 seconds; a wider per-IP limit mitigates
credential spraying. Redis Lua scripts make the transition atomic across API
replicas. Laravel uses its Redis-backed RateLimiter for its separate admin
authentication surface. No password, email, JWT, or client IP becomes a
Prometheus label.

## Monitoring

Prometheus scrapes FastAPI, Nginx exporter, Node Exporter, cAdvisor, Blackbox
Exporter, and itself. Blackbox checks public HTTPS endpoints without
credentials and records TLS expiry. Grafana is provisioned from files.

cAdvisor requires broad read-only host/Docker mounts plus privilege to inspect
containers. Keep it unexposed, pin its image, and reassess this access when a
less privileged runtime integration is available.

## Manual firewall verification (DigitalOcean; do not run locally)

```sh
sudo ufw status numbered
sudo ss -lntup
```

Public inbound rules should contain only SSH 22/tcp, HTTP 80/tcp, and HTTPS
443/tcp. Inspect Prometheus through an SSH tunnel if direct review is needed:

```sh
ssh -L 9090:127.0.0.1:9090 root@SERVER_IP
```

Prometheus currently has no host binding; for a temporary tunnel-only review,
add `127.0.0.1:9090:9090` explicitly and remove it afterwards.

## Scaling FastAPI

Start with one instance. Before adding replicas, calculate SQLAlchemy pool
capacity against the Supabase pooler limit: `replicas × (pool_size +
max_overflow)` plus Laravel and Flask connections must remain safely below the
available limit. Redis already coordinates login lockouts across replicas.
