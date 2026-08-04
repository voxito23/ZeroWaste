
# Runbook de producción y rollback ZeroWaste

## Condiciones previas

1. El árbol Git debe estar limpio o los cambios locales deben estar
   identificados; nunca usar `git reset --hard`.
2. Los `.env` de FastAPI, Flask y Laravel deben apuntar al mismo proyecto
   Supabase. `python scripts/check_env_contract.py` reporta sólo estados y una
   huella no reversible.
3. `GRAFANA_ADMIN_PASSWORD` debe existir fuera de Git.
4. `APP_KEY` de Laravel debe ser la misma en ambas réplicas.
5. Las carpetas y permisos de `docs/media-storage.md` deben existir.
6. Inventariar y copiar conservadoramente los volúmenes legacy antes de cambiar
   el montaje de medios.

No ejecutar migraciones como parte del arranque de contenedores.

## Activación controlada de impacto y recompensas

La migración `2026_07_31_000000_create_impact_and_rewards_tables` es necesaria
para el foro moderado, ranking y recompensas. Se ejecuta manualmente una sola
vez, después del despliegue del código, mediante un script que crea inventario
y respaldo completo antes de modificar el esquema:

```sh
cd /opt/ZeroWaste
git pull --ff-only origin main
bash scripts/desplegar_produccion.sh
```

El despliegue detecta automáticamente `docker compose` moderno o
`docker-compose` clásico. Primero migra conservadoramente los medios históricos
sin sobrescribir archivos, reconstruye los servicios y después aplica
exclusivamente la migración mediante `--path`; no ejecuta otras migraciones
pendientes. Guarda respaldos, su SHA-256 y los inventarios anterior/posterior
en `/opt/zerowaste-backups` con permisos restringidos. No eliminar esos
archivos hasta aceptar formalmente el despliegue.

Si falla antes de imprimir `Despliegue de producción terminado correctamente`,
conservar la salida y los respaldos y no repetir comandos de
esquema manualmente. Revisar primero:

```sh
docker compose logs --tail=200 laravel fast_api nginx_api
```

## Validación previa local o en staging

```sh
bash scripts/desplegar_produccion.sh
```

El archivo de desarrollo es explícito y no se aplica en producción:

```sh
docker compose -f docker-compose.yml -f docker-compose.dev.yml up --build
```

## Despliegue manual en DigitalOcean

Cada Droplet usa `/opt/ZeroWaste/.env.node` con permisos `600`. No copiar un
`.env` dentro de la imagen ni versionarlo. Los comandos directos de Compose deben
incluir `--env-file .env.node`; el helper de despliegue lo añade automáticamente
cuando el archivo existe.

```sh
cd /opt/ZeroWaste
git pull --ff-only origin main
bash scripts/desplegar_produccion.sh
```

No usar `docker compose down -v`; eliminaría volúmenes administrados. No
ejecutar `artisan migrate`, `db.create_all()`, DDL ni backfills automáticamente.

## Pruebas posteriores

```sh
curl -fsS https://www.zerowaste-qro.com/api/health
curl -fsS https://www.zerowaste-qro.com/api/ready
curl -fsS https://www.zerowaste-qro.com/api/mapa/puntos
curl -fsS https://www.zerowaste-qro.com/api/foro/posts
curl -fsSI https://www.zerowaste-qro.com/zw-interno/login
curl -fsSI https://www.zerowaste-qro.com/grafana/
curl -fsSI https://www.zerowaste-qro.com/media/recompensas/termo_reutilizable.png
```

## Actualización aislada de Grafana y Prometheus

Los dashboards y los archivos de targets son montajes de solo lectura. Grafana
recarga el dashboard provisionado, pero Prometheus necesita recrearse cuando
cambia `prometheus.yml`; los targets `file_sd` se refrescan después sin reiniciar.
Esta operación no reconstruye aplicaciones, no ejecuta migraciones y debe
realizarse únicamente en el servidor principal con autorización explícita:

```sh
cd /opt/ZeroWaste
test -r prometheus/targets/origins.yml
test -r prometheus/targets/nodes.yml
docker compose --env-file .env.node --profile primary-monitoring config --quiet
docker compose --env-file .env.node --profile primary-monitoring up -d --no-deps --force-recreate blackbox prometheus grafana
docker compose --env-file .env.node --profile primary-monitoring ps blackbox prometheus grafana
```

Después se deben comprobar `blackbox-origins`, `blackbox-internal` y
`blackbox-media-route` desde Prometheus/Grafana. Un valor `SIN TARGET` o
`SIN MÉTRICA` significa configuración ausente; `NO LISTO` o `CAÍDO` significa
que el target sí existe y el probe falló.

Para failover Laravel, iniciar sesión normalmente, identificar la réplica en
logs sin registrar cookies, detener una sola réplica y continuar la navegación:

```sh
docker compose stop laravel
docker compose ps laravel nginx_api
docker compose up -d --no-deps laravel
```

Validar que sesión y CSRF sobreviven; no copiar ni imprimir las cookies.

## Rollback sin borrar datos

1. Identificar el commit anterior conocido y crear un commit de reversión con
   `git revert <commit>`. No reescribir historia ni usar force push.
2. Reconstruir únicamente los servicios afectados con el Compose revertido.
3. Conservar `/opt/ZeroWaste/shared/media` y los volúmenes legacy intactos.
4. Si se revierte `media_static`, mantener temporalmente los aliases y mounts
   previos hasta verificar que ningún cliente depende del nuevo endpoint.
5. Revertir Caddy y Nginx juntos si cambian nombres/puertos; validar sus
   configuraciones antes de recargar.
6. Laravel1 y Laravel2 siempre deben usar exactamente la misma imagen durante
   rollback.
7. Una migración preparada pero no aplicada no requiere rollback de DB. Si una
   migración fuese autorizada en el futuro, ejecutar primero su SQL de rollback
   revisado y un respaldo; nunca `migrate:reset`, `refresh` o `fresh`.
8. El rollback móvil de JavaScript se entrega por el flujo normal de Metro/OTA
   autorizado; no requiere desinstalar, limpiar datos ni crear otra Development
   Build para cambios puramente JS.

## Límites del diseño

Dos contenedores Laravel protegen contra una caída de proceso, no contra la
caída completa del Droplet, Docker, Nginx o Redis. cAdvisor y Node Exporter
requieren acceso privilegiado/read-only al host y deben permanecer sin puertos
públicos. Prometheus tampoco se publica; Grafana sólo se accede mediante Caddy.
