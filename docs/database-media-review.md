
# Revisión de base de datos para medios

La revisión del código encontró campos reutilizables en todas las entidades que
necesitan imágenes. Por ello, esta fase no agrega columnas ni una migración de
medios:

| Tabla           | Campo existente | Valor recomendado                       |
| --------------- | --------------- | --------------------------------------- |
| `posts`       | `imagen`      | nombre o`media/foro/<archivo>`        |
| `usuarios`    | `foto_perfil` | nombre o`media/perfiles/<archivo>`    |
| `locations`   | `imagen`      | nombre o`media/puntos/<archivo>`      |
| `campaigns`   | `imagen_url`  | nombre o`media/campanas/<archivo>`    |
| `eventos`     | `imagen_url`  | nombre o`media/eventos/<archivo>`     |
| `recompensas` | `imagen`      | nombre o`media/recompensas/<archivo>` |

`image_url`, `avatar_url` y `cover_url` son campos del contrato HTTP calculados,
no columnas adicionales.

La migración existente
`laravel_zerowaste/database/migrations/2026_07_31_000000_create_impact_and_rewards_tables.php`
agrega aprobación del foro y las tablas de impacto/recompensas. El modelo
FastAPI debe reflejar esas columnas en `Foro`; no corresponde crear esas
columnas en `reglas_puntos`. Antes de autorizar esa migración en Supabase se
debe ejecutar `scripts/supabase_schema_inventory.sql`, revisar si el esquema
está completo o parcial y preparar un respaldo. La migración no se ejecuta en
esta fase.

`scripts/supabase_media_metadata_export.sql` contiene solamente `SELECT` de ID
y campo de archivo. Su salida puede convertirse a JSON para
`scripts/inventory_media.py --metadata`; no contiene nombres ni correos. Un
backfill sólo es seguro cuando la relación de basename es exacta y única.

Agregar MIME/tamaño sería una mejora futura opcional, no un requisito para
servir los binarios correctamente. Si se decide persistirlos, debe prepararse
una migración separada, nullable, con rollback que elimine únicamente las
columnas nuevas, y no aplicarla sin autorización expresa.
