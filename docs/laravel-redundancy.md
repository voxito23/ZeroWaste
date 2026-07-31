# Laravel active/passive redundancy

ZeroWaste runs two Laravel containers from the same immutable Docker image and
the same versioned source directory:

- `admin` (`laravel_admin`) is the primary upstream.
- `admin2` (`laravel_admin_2`) is the passive backup.
- `nginx_api` detects connection and gateway failures and sends a safe retry to
  the backup.
- `/zw-interno/2/` routes explicitly to `admin2` for validation and diagnostics.

Both containers use the external PostgreSQL database, Redis cache/rate-limit
state, the same application environment, and the shared profile volume. No
application port is published on the host.

This protects against a Laravel process/container failure. It is not host-level
high availability: the Droplet, Nginx, Redis and the Docker daemon remain single
points of failure. Host-level HA requires a second Droplet and a load balancer.

## Zero-downtime deployment order

Build the common image first. Start and validate the backup before reloading
Nginx. Recreate the primary only after the backup is healthy.

```bash
docker-compose build admin admin2
docker-compose up -d --no-deps admin2
docker-compose ps admin2
docker-compose exec nginx_api nginx -t
docker-compose exec nginx_api nginx -s reload
docker-compose up -d --no-deps admin
docker-compose ps admin admin2 nginx_api
```

## Failover test

Run the public request before and after stopping the primary. Restore the
primary immediately after the test.

```bash
curl -fsS -o /dev/null -w 'before=%{http_code}\n' https://www.zerowaste-qro.com/zw-interno/login
docker-compose stop admin
curl -fsS -o /dev/null -w 'backup=%{http_code}\n' https://www.zerowaste-qro.com/zw-interno/login
docker-compose logs --tail=20 nginx_api
docker-compose up -d --no-deps admin
docker-compose ps admin admin2 nginx_api
```

Expected result: both HTTP checks return `200`, and the Nginx access log shows
`admin2`'s upstream address while the primary is stopped.

The secondary can also be checked without stopping the primary:

```bash
curl -fsS -o /dev/null -w 'secondary=%{http_code}\n' https://www.zerowaste-qro.com/zw-interno/2/login
```
