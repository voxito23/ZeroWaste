-- Auditoría de esquema exclusivamente read-only. No crea tablas ni funciones.
BEGIN TRANSACTION READ ONLY;

SELECT
    table_schema,
    table_name,
    column_name,
    data_type,
    is_nullable,
    character_maximum_length
FROM information_schema.columns
WHERE table_schema = 'public'
  AND table_name IN (
      'posts',
      'usuarios',
      'locations',
      'campaigns',
      'eventos',
      'recompensas',
      'reglas_puntos',
      'saldos_puntos',
      'movimientos_puntos',
      'canjes',
      'historial_canjes'
  )
ORDER BY table_name, ordinal_position;

SELECT
    tc.table_name,
    tc.constraint_name,
    tc.constraint_type,
    kcu.column_name,
    ccu.table_name AS referenced_table,
    ccu.column_name AS referenced_column
FROM information_schema.table_constraints AS tc
LEFT JOIN information_schema.key_column_usage AS kcu
  ON tc.constraint_name = kcu.constraint_name
 AND tc.constraint_schema = kcu.constraint_schema
LEFT JOIN information_schema.constraint_column_usage AS ccu
  ON tc.constraint_name = ccu.constraint_name
 AND tc.constraint_schema = ccu.constraint_schema
WHERE tc.constraint_schema = 'public'
  AND tc.table_name IN (
      'posts', 'usuarios', 'locations', 'campaigns', 'eventos', 'recompensas'
  )
ORDER BY tc.table_name, tc.constraint_name, kcu.ordinal_position;

SELECT
    table_name,
    column_name,
    data_type
FROM information_schema.columns
WHERE table_schema = 'public'
  AND (
      lower(column_name) LIKE '%image%'
      OR lower(column_name) LIKE '%imagen%'
      OR lower(column_name) LIKE '%foto%'
      OR lower(column_name) LIKE '%avatar%'
      OR lower(column_name) LIKE '%media%'
      OR lower(column_name) LIKE '%portada%'
  )
ORDER BY table_name, column_name;

ROLLBACK;
