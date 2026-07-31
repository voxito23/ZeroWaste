# Laravel active/active redundancy

ZeroWaste runs two Laravel containers from the same immutable Docker image and
the same versioned source directory:

- `admin` and `admin2` serve the normal route concurrently through Nginx
  equal-weight round-robin balancing.
- If either replica fails, Nginx temporarily removes it and retries safely on
  the surviving replica.
- The explicit `/2` route prefers `admin2` and falls back to `admin`.
- `/zw-interno/2/` is an explicit external alias for `admin2`. Nginx translates
  it to the original Laravel prefix and rewrites generated links back to `/2`.
  Internally, the backup deliberately accepts `/zw-interno/*` so automatic
  failover can forward the unchanged primary request without producing a 404.

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

## Short equal-distribution test

This test sends ten application requests. With both replicas healthy, the
equal-weight round-robin upstream sends five requests to `admin` and five to
`admin2`. A unique user agent avoids counting Prometheus/Blackbox probes.

Run each command separately:

```bash
docker-compose ps admin admin2 nginx_api
```

```bash
TEST_START=$(date -u +%Y-%m-%dT%H:%M:%SZ)
```

```bash
for i in $(seq 1 10); do curl -sS -A ZeroWaste-Balance-Test -o /dev/null -w "request=$i status=%{http_code}\n" https://www.zerowaste-qro.com/zw-interno/login; done
```

```bash
docker-compose logs --since="$TEST_START" admin | grep 'ZeroWaste-Balance-Test' | wc -l
```

```bash
docker-compose logs --since="$TEST_START" admin2 | grep 'ZeroWaste-Balance-Test' | wc -l
```

Expected result: `5` for `admin` and `5` for `admin2`. If a replica is
unhealthy, the surviving replica receives all requests; that is expected
failover behavior. Browser refreshes are not a deterministic balancing test
because one page load can create several HTTP requests.
