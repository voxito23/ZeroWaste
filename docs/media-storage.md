# Almacenamiento compartido de medios ZeroWaste

## Contrato canónico

Supabase conserva únicamente metadatos. Los binarios viven fuera de los
contenedores y se sirven por HTTPS desde un único servicio estático.

| Categoría | Valor recomendado en base de datos | URL pública |
|---|---|---|
| Foro | `media/foro/<uuid>.<ext>` o `<uuid>.<ext>` | `https://www.zerowaste-qro.com/media/foro/<archivo>` |
| Perfiles | `media/perfiles/<uuid>.<ext>` o `<uuid>.<ext>` | `https://www.zerowaste-qro.com/media/perfiles/<archivo>` |
| Recompensas | `media/recompensas/<archivo>` o `<archivo>` | `https://www.zerowaste-qro.com/media/recompensas/<archivo>` |
| Campañas | `media/campanas/<uuid>.<ext>` o `<uuid>.<ext>` | `https://www.zerowaste-qro.com/media/campanas/<archivo>` |
| Eventos | `media/eventos/<uuid>.<ext>` o `<uuid>.<ext>` | `https://www.zerowaste-qro.com/media/eventos/<archivo>` |
| Puntos de acopio | `media/puntos/<uuid>.<ext>` o `<uuid>.<ext>` | `https://www.zerowaste-qro.com/media/puntos/<archivo>` |

La categoría `puntos` es una extensión necesaria descubierta durante la
auditoría: la tabla `locations` ya tiene el campo `imagen`. No se crea una
columna nueva.

Los campos existentes que deben reutilizarse son `posts.imagen`,
`usuarios.foto_perfil`, `locations.imagen`, `campaigns.imagen_url`,
`eventos.imagen_url` y `recompensas.imagen`. Nunca deben almacenar rutas como
`/app`, `/var/www`, `/data`, una IP, `localhost`, Base64 ni una URL HTTP.

Los aliases `/images/recompensas/*` y `/img/perfiles/*` son sólo compatibilidad
de lectura. Los clientes nuevos deben usar `/media/*`.

## Directorios de DigitalOcean

No se crean desde Compose. Antes del primer despliegue, un administrador debe
crearlos manualmente:

```sh
sudo install -d -o root -g zerowaste-media -m 2750 /opt/ZeroWaste/shared/media
sudo install -d -o root -g zerowaste-media -m 2770 /opt/ZeroWaste/shared/media/foro
sudo install -d -o root -g zerowaste-media -m 2770 /opt/ZeroWaste/shared/media/perfiles
sudo install -d -o root -g zerowaste-media -m 2770 /opt/ZeroWaste/shared/media/recompensas
sudo install -d -o root -g zerowaste-media -m 2770 /opt/ZeroWaste/shared/media/campanas
sudo install -d -o root -g zerowaste-media -m 2770 /opt/ZeroWaste/shared/media/eventos
sudo install -d -o root -g zerowaste-media -m 2770 /opt/ZeroWaste/shared/media/puntos
```

Crear previamente el grupo de sistema con el GID configurado en Compose (por
defecto `2000`):

```sh
sudo groupadd --system --gid 2000 zerowaste-media
```

Si el GID `2000` ya está ocupado, se debe elegir otro y definir `MEDIA_GID` en
el `.env` raíz no versionado. No se recomienda `777`. El bit setgid (`2` en
`2770`) mantiene el grupo en archivos y subdirectorios nuevos. Los procesos que
escriben reciben el grupo suplementario; `media_static` monta el árbol como
solo lectura.

Para normalizar archivos existentes sin hacerlos públicos para otros usuarios:

```sh
sudo find /opt/ZeroWaste/shared/media -type d -exec chmod 2770 {} \;
sudo find /opt/ZeroWaste/shared/media -type f -exec chmod 0660 {} \;
sudo chgrp -R zerowaste-media /opt/ZeroWaste/shared/media
```

## Imágenes de recompensas

Colocar manualmente estos cinco archivos en
`/opt/ZeroWaste/shared/media/recompensas/`:

- `termo_reutilizable.png`
- `bolsa_reutilizable.png`
- `kit_botes_separacion.png`
- `kit_cubiertos_reutilizables.png`
- `compostera_domestica.png`

No se copian automáticamente desde la imagen Docker y no se guardan en
Supabase.

## Inventario y relación con metadatos

El inventario no cambia archivos ni conecta a la base:

```sh
python scripts/inventory_media.py --include-legacy > media-inventory.json
```

En DigitalOcean se puede indicar cada raíz explícitamente:

```sh
python scripts/inventory_media.py \
  --root foro=/opt/ZeroWaste/shared/media/foro \
  --root perfiles=/opt/ZeroWaste/shared/media/perfiles \
  --root recompensas=/opt/ZeroWaste/shared/media/recompensas \
  --root campanas=/opt/ZeroWaste/shared/media/campanas \
  --root eventos=/opt/ZeroWaste/shared/media/eventos \
  --root puntos=/opt/ZeroWaste/shared/media/puntos \
  > media-inventory.json
```

Opcionalmente acepta un export JSON read-only, agrupado por tabla. Sólo marca
una relación cuando el basename coincide exactamente:

```json
{
  "posts": [{"id": 1, "imagen": "media/foro/uuid.webp"}],
  "usuarios": [{"id": 2, "foto_perfil": "uuid.jpg"}]
}
```

```sh
python scripts/inventory_media.py --include-legacy --metadata metadata.json
```

Los UUID de los archivos históricos no codifican el ID del post o usuario. Una
coincidencia ausente o ambigua requiere corrección manual; nunca se debe asignar
una imagen por orden, fecha aproximada o similitud visual.

## Migración conservadora desde volúmenes anteriores

En producción, la migración completa y repetible se ejecuta con nombres en
español. Copia sin sobrescribir publicaciones, perfiles, campañas, eventos,
puntos y recompensas desde los árboles históricos y desde los volúmenes Docker
anteriores que todavía existan:

```sh
cd /opt/ZeroWaste
bash scripts/migrar_medios_produccion.sh
```

Los inventarios, respaldos de volúmenes y posibles conflictos quedan en
`/opt/zerowaste-backups`. Un conflicto conserva el archivo canónico existente
y registra ambas rutas; nunca decide por fecha o nombre parecido.

Antes de retirar `foro_media` y `perfiles_compartidos`, inventariar los
volúmenes reales con `docker volume ls` y adaptar sus nombres de proyecto. El
siguiente patrón copia sin sobrescribir (`cp -an`); debe ejecutarse por separado
para cada volumen después de revisar origen y destino:

```sh
docker run --rm \
  --mount source=zerowaste_foro_media,target=/source,readonly \
  --mount type=bind,source=/opt/ZeroWaste/shared/media/foro,target=/destination \
  alpine:3.20 sh -c 'cp -an /source/. /destination/'
```

Repetir para perfiles, volver a ejecutar el inventario y comparar cantidad,
tamaño y SHA-256. No borrar los volúmenes anteriores durante el despliegue.

## Verificación HTTPS

Usar un nombre que exista realmente en cada categoría:

```sh
curl -fsSI https://www.zerowaste-qro.com/media/foro/ARCHIVO_REAL
curl -fsSI https://www.zerowaste-qro.com/media/perfiles/ARCHIVO_REAL
curl -fsSI https://www.zerowaste-qro.com/media/recompensas/termo_reutilizable.png
curl -sS -o /dev/null -w '%{http_code}\n' https://www.zerowaste-qro.com/media/foro/NO_EXISTE.png
```

Los archivos existentes deben responder `200`, `Content-Type` correcto,
`Cache-Control` y `X-Content-Type-Options: nosniff`. Un archivo inexistente o
una ruta de directorio debe responder `404`, nunca un stack trace.
