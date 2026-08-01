#!/usr/bin/env bash
set -Eeuo pipefail

# Applies only the reviewed impact/rewards migration after creating a full
# logical backup. Run this script on the production Droplet from the repo root.

readonly EXPECTED_ROOT="/opt/ZeroWaste"
readonly MIGRATION="2026_07_31_000000_create_impact_and_rewards_tables"
readonly MIGRATION_PATH="database/migrations/${MIGRATION}.php"
readonly BACKUP_ROOT="${ZEROWASTE_BACKUP_ROOT:-/opt/zerowaste-backups}"
readonly POSTGRES_CLIENT_IMAGE="${POSTGRES_CLIENT_IMAGE:-postgres:17-alpine}"
readonly TIMESTAMP="$(date -u +%Y%m%dT%H%M%SZ)"
readonly BACKUP_FILE="zerowaste-pre-impact-${TIMESTAMP}.dump"
readonly INVENTORY_BEFORE="schema-before-impact-${TIMESTAMP}.txt"
readonly INVENTORY_AFTER="schema-after-impact-${TIMESTAMP}.txt"

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd -P)"

fail() {
  printf 'ERROR: %s\n' "$*" >&2
  exit 1
}

if [[ "$PROJECT_ROOT" != "$EXPECTED_ROOT" && "${ALLOW_NONSTANDARD_ROOT:-0}" != "1" ]]; then
  fail "run from ${EXPECTED_ROOT}; set ALLOW_NONSTANDARD_ROOT=1 only for a reviewed staging path"
fi

cd "$PROJECT_ROOT"

[[ -f "laravel_zerowaste/.env" ]] || fail "laravel_zerowaste/.env is missing"
[[ -f "laravel_zerowaste/${MIGRATION_PATH}" ]] || fail "reviewed migration file is missing"
[[ -f "scripts/supabase_schema_inventory.sql" ]] || fail "schema inventory SQL is missing"

command -v docker >/dev/null || fail "docker is not installed"
command -v curl >/dev/null || fail "curl is not installed"
command -v sha256sum >/dev/null || fail "sha256sum is not installed"

docker compose config --quiet

laravel_container="$(docker compose ps -q laravel1)"
[[ -n "$laravel_container" ]] || fail "laravel1 container is not running"
[[ "$(docker inspect -f '{{.State.Running}}' "$laravel_container")" == "true" ]] \
  || fail "laravel1 container is not running"

install -d -m 0700 "$BACKUP_ROOT"
[[ "$(df -Pk "$BACKUP_ROOT" | awk 'NR==2 {print $4}')" -ge 1048576 ]] \
  || fail "less than 1 GiB is available for the database backup"

run_postgres_client() {
  docker run --rm \
    --network "container:${laravel_container}" \
    --env-file "$PROJECT_ROOT/laravel_zerowaste/.env" \
    -e "BACKUP_TIMESTAMP=${TIMESTAMP}" \
    -v "$PROJECT_ROOT/scripts:/work/scripts:ro" \
    -v "$BACKUP_ROOT:/work/backups" \
    "$POSTGRES_CLIENT_IMAGE" "$@"
}

readonly DB_ENV_SETUP='if [ -n "${DB_URL:-}" ]; then
  export PGDATABASE="$DB_URL"
else
  export PGHOST="${DB_HOST:?DB_HOST is required when DB_URL is empty}"
  export PGPORT="${DB_PORT:-5432}"
  export PGDATABASE="${DB_DATABASE:?DB_DATABASE is required when DB_URL is empty}"
  export PGUSER="${DB_USERNAME:?DB_USERNAME is required when DB_URL is empty}"
  export PGPASSWORD="${DB_PASSWORD:?DB_PASSWORD is required when DB_URL is empty}"
  export PGSSLMODE="${DB_SSLMODE:-require}"
fi'

printf 'Creating read-only schema inventory...\n'
run_postgres_client sh -ceu "${DB_ENV_SETUP}
psql -X -v ON_ERROR_STOP=1 --file=/work/scripts/supabase_schema_inventory.sql > /work/backups/${INVENTORY_BEFORE}"

printf 'Creating full logical backup...\n'
run_postgres_client sh -ceu "${DB_ENV_SETUP}
pg_dump --format=custom --no-owner --no-privileges --file=/work/backups/${BACKUP_FILE}"

[[ -s "${BACKUP_ROOT}/${BACKUP_FILE}" ]] || fail "pg_dump did not create a non-empty backup"
sha256sum "${BACKUP_ROOT}/${BACKUP_FILE}" > "${BACKUP_ROOT}/${BACKUP_FILE}.sha256"
chmod 0600 \
  "${BACKUP_ROOT}/${BACKUP_FILE}" \
  "${BACKUP_ROOT}/${BACKUP_FILE}.sha256" \
  "${BACKUP_ROOT}/${INVENTORY_BEFORE}"

printf 'Backup: %s\n' "${BACKUP_ROOT}/${BACKUP_FILE}"
printf 'Applying only %s...\n' "$MIGRATION"
docker compose exec -T laravel1 \
  php artisan migrate \
  --path="$MIGRATION_PATH" \
  --force \
  --no-interaction

printf 'Verifying schema and seed data...\n'
schema_result="$(run_postgres_client sh -ceu "${DB_ENV_SETUP}
psql -X -A -t -v ON_ERROR_STOP=1 -c \"SELECT
  (SELECT count(*) FROM information_schema.columns WHERE table_schema='public' AND table_name='posts' AND column_name IN ('aprobado','aprobado_por','aprobado_at')),
  (SELECT count(*) FROM information_schema.tables WHERE table_schema='public' AND table_name IN ('reglas_puntos','saldos_puntos','movimientos_puntos','recompensas','canjes','historial_canjes','tokens_qr_recoleccion')),
  (SELECT count(*) FROM reglas_puntos WHERE codigo IN ('RECOLECCION_QR','EVENTO_CONFIRMADO','POST_APROBADO','RESPUESTA_VALIDA','RESENA_PUNTO')),
  (SELECT count(*) FROM recompensas);\"")"

IFS='|' read -r approval_columns impact_tables point_rules rewards <<< "$schema_result"
[[ "$approval_columns" == "3" ]] || fail "expected 3 post approval columns; found ${approval_columns}"
[[ "$impact_tables" == "7" ]] || fail "expected 7 impact tables; found ${impact_tables}"
[[ "$point_rules" -ge 5 ]] || fail "expected at least 5 point rules; found ${point_rules}"
[[ "$rewards" -ge 5 ]] || fail "expected at least 5 rewards; found ${rewards}"

run_postgres_client sh -ceu "${DB_ENV_SETUP}
psql -X -v ON_ERROR_STOP=1 --file=/work/scripts/supabase_schema_inventory.sql > /work/backups/${INVENTORY_AFTER}"
chmod 0600 "${BACKUP_ROOT}/${INVENTORY_AFTER}"

printf 'Checking production endpoints...\n'
for endpoint in \
  /api/health \
  /api/ready \
  /api/foro/posts \
  /api/impacto/ranking \
  /api/impacto/recompensas \
  /media/recompensas/termo_reutilizable.png
do
  curl --fail --silent --show-error --output /dev/null \
    "https://www.zerowaste-qro.com${endpoint}"
  printf 'OK %s\n' "$endpoint"
done

docker compose ps
printf 'Migration and verification completed successfully.\n'
printf 'Keep these files until the release is formally accepted:\n'
printf '  %s\n' \
  "${BACKUP_ROOT}/${BACKUP_FILE}" \
  "${BACKUP_ROOT}/${BACKUP_FILE}.sha256" \
  "${BACKUP_ROOT}/${INVENTORY_BEFORE}" \
  "${BACKUP_ROOT}/${INVENTORY_AFTER}"
