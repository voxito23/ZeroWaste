# Staging local de PostgreSQL

Este entorno es independiente de Supabase y no sustituye ningún `.env` de producción.
Expone PostgreSQL únicamente en `127.0.0.1:55432`, usa un volumen dedicado y genera
su contraseña aleatoria dentro de `.local-staging/`, que está ignorado por Git.

## Ciclo de vida

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\local_staging.ps1 -Action Up
powershell -ExecutionPolicy Bypass -File .\scripts\local_staging.ps1 -Action Status
powershell -ExecutionPolicy Bypass -File .\scripts\local_staging.ps1 -Action Down
```

`Down` conserva la base. La eliminación completa requiere una confirmación explícita:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\local_staging.ps1 -Action Destroy -ConfirmDestroy
```

## Copia segura del esquema realizada

La inicialización validada para este proyecto usó `pg_dump --schema-only --schema=public --no-owner
--no-privileges`. No se copian filas, cuentas, correos, perfiles, puntos, archivos de
Storage ni contenido de Auth. El archivo generado queda dentro de
`.local-staging/public-schema.sql` y nunca debe confirmarse en Git.

En el equipo actual la fuente era el PostgreSQL Docker local heredado, no Supabase.
Ese contenedor se inició únicamente durante el dump y se detuvo inmediatamente después.

Las migraciones profesionales se aplican con rutas explícitas, desde `000001` hasta
`000005`. Nunca se usa `migrate:fresh`, `migrate:reset`, `migrate:refresh`, `DROP` o
`TRUNCATE` contra Supabase. El reinicio del esquema, cuando se necesita, ocurre
exclusivamente dentro del contenedor `zerowaste_staging_postgres`.
