-- Export mínimo read-only para correlacionar nombres exactos con archivos.
-- Ejecutar únicamente después de confirmar con supabase_schema_inventory.sql
-- que las tablas y columnas existen. No devuelve nombres, emails ni contenido.
BEGIN TRANSACTION READ ONLY;

SELECT id, imagen FROM public.posts WHERE imagen IS NOT NULL AND btrim(imagen) <> '' ORDER BY id;
SELECT id, foto_perfil FROM public.usuarios WHERE foto_perfil IS NOT NULL AND btrim(foto_perfil) <> '' ORDER BY id;
SELECT id, imagen FROM public.locations WHERE imagen IS NOT NULL AND btrim(imagen) <> '' ORDER BY id;
SELECT id, imagen_url FROM public.campaigns WHERE imagen_url IS NOT NULL AND btrim(imagen_url) <> '' ORDER BY id;
SELECT id, imagen_url FROM public.eventos WHERE imagen_url IS NOT NULL AND btrim(imagen_url) <> '' ORDER BY id;
SELECT id, imagen FROM public.recompensas WHERE imagen IS NOT NULL AND btrim(imagen) <> '' ORDER BY id;

ROLLBACK;
