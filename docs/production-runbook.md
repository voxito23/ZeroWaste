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

## Validación previa local o en staging

```sh
docker compose config --quiet
docker compose config --services
docker compose config --volumes
docker compose config --networks
docker compose build
docker compose up -d
docker compose ps
docker compose logs --tail=200
```

El archivo de desarrollo es explícito y no se aplica en producción:

```sh
docker compose -f docker-compose.yml -f docker-compose.dev.yml up --build
```

## Despliegue manual en DigitalOcean

```sh
cd /opt/ZeroWaste
git pull origin main
docker compose config --quiet
docker compose up --build -d
docker compose ps
docker compose logs --tail=200
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

Para failover Laravel, iniciar sesión normalmente, identificar la réplica en
logs sin registrar cookies, detener una sola réplica y continuar la navegación:

```sh
docker compose stop laravel1
docker compose ps laravel1 laravel2 nginx_api
docker compose up -d --no-deps laravel1
docker compose stop laravel2
docker compose ps laravel1 laravel2 nginx_api
docker compose up -d --no-deps laravel2
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

