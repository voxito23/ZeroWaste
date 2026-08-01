-- PROPUESTA NO EJECUTADA. Por seguridad termina en ROLLBACK.
-- Cambiar ROLLBACK por COMMIT solamente después de revisar el reporte y aprobarlo.
BEGIN;

-- Caso observado el 2026-08-01: respuesta 4, post 1, autor 2.
-- La condición de firma evita modificar el registro si cambió desde la auditoría.
UPDATE public.respuestas
SET contenido = 'Contenido retirado por tener un formato inválido.'
WHERE id = 4
  AND post_id = 1
  AND autor_id = 2
  AND length(contenido) = 5591
  AND contenido ILIKE '%--tw-%'
  AND contenido ~* '<[[:space:]]*div[[:space:]>]';

-- Si existen duplicados históricos, conservar únicamente el like más antiguo.
WITH duplicados AS (
    SELECT id,
           row_number() OVER (
               PARTITION BY usuario_id, post_id
               ORDER BY created_at NULLS LAST, id
           ) AS posicion
    FROM public.likes_foro
)
DELETE FROM public.likes_foro AS likes
USING duplicados
WHERE likes.id = duplicados.id
  AND duplicados.posicion > 1;

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM pg_constraint
        WHERE conrelid = 'public.likes_foro'::regclass
          AND contype = 'u'
          AND conname = 'uq_usuario_post_like'
    ) THEN
        ALTER TABLE public.likes_foro
            ADD CONSTRAINT uq_usuario_post_like UNIQUE (usuario_id, post_id);
    END IF;
END $$;

-- Verificación previa a aprobar COMMIT.
SELECT id, post_id, autor_id, length(contenido) AS longitud
FROM public.respuestas
WHERE id = 4;

SELECT usuario_id, post_id, count(*) AS cantidad
FROM public.likes_foro
GROUP BY usuario_id, post_id
HAVING count(*) > 1;

ROLLBACK;
