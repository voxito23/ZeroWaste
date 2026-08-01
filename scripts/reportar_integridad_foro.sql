-- Auditoría exclusivamente READ ONLY para ejecutar antes de aprobar limpiezas.
BEGIN TRANSACTION READ ONLY;

SELECT id, post_id, autor_id, length(contenido) AS longitud, created_at
FROM public.respuestas
WHERE contenido ~* '<[[:space:]]*(div|style|script|html|body)[[:space:]>]'
   OR contenido ILIKE '%--tw-%'
   OR contenido ILIKE '%.flex-grow{%'
ORDER BY id;

SELECT usuario_id, post_id, count(*) AS duplicados
FROM public.likes_foro
GROUP BY usuario_id, post_id
HAVING count(*) > 1
ORDER BY post_id, usuario_id;

SELECT tc.constraint_name, string_agg(kcu.column_name, ',' ORDER BY kcu.ordinal_position) AS columnas
FROM information_schema.table_constraints AS tc
JOIN information_schema.key_column_usage AS kcu
  ON tc.constraint_name = kcu.constraint_name
 AND tc.constraint_schema = kcu.constraint_schema
WHERE tc.constraint_schema = 'public'
  AND tc.table_name = 'likes_foro'
  AND tc.constraint_type = 'UNIQUE'
GROUP BY tc.constraint_name
ORDER BY tc.constraint_name;

ROLLBACK;
