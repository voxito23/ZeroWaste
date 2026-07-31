# Laravel active/active redundancy

ZeroWaste runs `laravel1` and `laravel2` from the same
`zerowaste-laravel:${ZEROWASTE_IMAGE_TAG}` image. Both replicas use the same
versioned code and vendor directory baked into that image; production has no
source-code bind mount and no anonymous vendor volume.

Nginx uses equal-weight round-robin for `/zw-interno`. If a replica fails,
passive health handling removes it temporarily and retries safe requests on the
survivor. `/zw-interno/2/` remains a compatibility route that prefers
`laravel2` and falls back to `laravel1`.

Both replicas load the same untracked Laravel `.env` and therefore share
`APP_KEY` and Supabase. Compose additionally enforces:

- `SESSION_DRIVER=redis` and `SESSION_CONNECTION=default`.
- A single cookie name, domain and `/zw-interno` path.
- Secure, HttpOnly and SameSite=Lax cookies.
- Explicit Redis and cache prefixes.
- The same Redis service and persistent Redis volume.
- The same persistent media root and supplementary `MEDIA_GID`.

This protects against a Laravel process/container failure. Nginx, Redis, the
Docker daemon and the Droplet remain host-level single points of failure.

## Production validation order

Run each command separately. None of these commands runs a migration:

```bash
docker compose config
```

```bash
docker compose build laravel1 laravel2
```

```bash
docker compose up -d --no-deps laravel2
docker compose ps laravel2
```

```bash
docker compose exec nginx_api nginx -t
docker compose exec nginx_api nginx -s reload
```

```bash
docker compose up -d --no-deps laravel1
docker compose ps laravel1 laravel2 nginx_api
```

## Failover test

Perform this only during a controlled verification window and restore the
replica immediately:

```bash
curl -fsS -o /dev/null -w 'before=%{http_code}\n' https://www.zerowaste-qro.com/zw-interno/login
docker compose stop laravel1
curl -fsS -o /dev/null -w 'survivor=%{http_code}\n' https://www.zerowaste-qro.com/zw-interno/login
docker compose up -d --no-deps laravel1
docker compose ps laravel1 laravel2 nginx_api
```

Expected: both HTTP requests succeed and the second is served by `laravel2`.

## Shared-session test

Use a non-production test account in a browser or HTTP client that retains its
cookie jar:

1. Authenticate through the normal `/zw-interno/login` route.
2. Confirm that both replicas are healthy.
3. Stop the replica that handled the login request.
4. Request an authenticated page with the same cookie jar.
5. Confirm the session and CSRF-protected form remain valid.
6. Restore the stopped replica.

Do not use filesystem sessions or sticky sessions to make this test pass.
Redis must contain the shared session state.

## Observability limitation

The open-source Nginx `stub_status` endpoint provides aggregate connections and
request counts. It does not expose per-upstream 5xx rates, latency or exact
traffic distribution. Grafana therefore shows replica liveness, Blackbox
latency, Redis aggregates and cAdvisor resource data without inventing
per-replica HTTP metrics. Add Laravel instrumentation or a privacy-safe Nginx
log exporter before creating those panels.
