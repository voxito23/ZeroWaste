# ZeroWaste: contexto del proyecto

Actualizado: 2026-08-01. Este documento describe el estado local de `main` en
`C:\ZeroWaste`. No confirma que las migraciones preparadas estén aplicadas en
Supabase ni que el código local esté desplegado en DigitalOcean.

## Estado Git

- Rama: `main`, limpia al iniciar esta auditoría y 9 commits delante de
  `origin/main`.
- Respaldo de cambio de cuenta: `6b3ff00 Respaldo antes de cambiar cuenta`.
- Fase QR: `b249f01 feat(qr): add secure recycling point QR management`.
- Fase recolecciones: `ede3b3a feat(collections): add scheduled collection and
  one-time QR flow`.
- Fase autenticación: `6cb8062 feat(auth): add mobile Google sign-in and reliable
  email verification`.
- Fase administrativa: `02f97eb feat(admin): polish rewards points and QR
  administration`.
- Correcciones posteriores: `f392ff9` (concurrencia e idempotencia) y `c5c4d8f`
  (validación PostgreSQL en staging local).

## Arquitectura

### Entrada y proxy

- `Caddyfile`: terminación HTTPS para `https://www.zerowaste-qro.com`, rutas
  públicas y acceso a Grafana.
- `nginx/api.conf`: proxy/balanceo de API y dos réplicas Laravel.
- `nginx/media.conf`: publicación del almacenamiento persistente bajo `/media/`.
- `docker-compose.yml`: Flask público, FastAPI, Laravel redundante, Nginx, Caddy,
  Redis, Prometheus y Grafana.
- `docker-compose.dev.yml`: sobreescrituras explícitas de desarrollo.
- `docker-compose.staging.yml`: PostgreSQL local aislado; no es Supabase.

### Aplicaciones

- `fast_api/`: backend móvil canónico. FastAPI, SQLAlchemy y PostgreSQL. Expone
  autenticación, mapa, QR, recolecciones, puntos, recompensas, foro y contenido.
- `laravel_zerowaste/`: Laravel 12/PHP 8.2. Panel `/zw-interno`; las rutas
  administrativas usan middleware `auth` y `admin` y CSRF de Laravel.
- `mobile_app/`: Expo SDK 56, React Native 0.85.3 y Development Client existente.
  El package Android es `com.vic45.mobile_app`; no cambiarlo.
- `flask_zerowaste/`: sitio web público y flujo Google/Firebase heredado. No debe
  convertirse en backend móvil ni duplicar la lógica canónica de FastAPI.
- PostgreSQL/Supabase: base compartida. Redis: sesiones, caché y coordinación.
- Medios: origen público canónico `https://www.zerowaste-qro.com/media/`.

## Funciones existentes

### QR de puntos

- Tokens opacos `zw1p_...`, con entropía criptográfica, hash SHA-256 y contenido
  público `https://www.zerowaste-qro.com/q/p/<token>`.
- FastAPI genera, valida, revoca, regenera, lista historial y renderiza PNG/SVG.
- Laravel consume FastAPI mediante `FastApiQrService`; no mantiene otro algoritmo.
- El panel puede crear/editar/desactivar/reactivar/retirar puntos y administrar QR.
- El móvil valida por `/api/qr/validar` y abre `PointDetailScreen` de forma nativa.
- Escanear un QR estático no concede puntos automáticamente. La regla
  `VISITA_PUNTO_QR` se propone desactivada y con cero puntos.

### Recolecciones

- Horario inicial almacenado: lunes, miércoles y viernes, 10:00–14:00,
  `America/Mexico_City`; los demás días están inactivos.
- FastAPI calcula disponibilidad, zona horaria, intervalos, capacidad y excepciones.
- Laravel administra horarios en `/zw-interno/recolecciones/horarios`.
- `CreateCollectionScreen` consulta disponibilidad y conserva el formulario ante
  errores.
- Los QR `zw1c_...` pertenecen a una solicitud, expiran y son de un solo uso.
- La confirmación autentica, valida rol/estado/vencimiento, bloquea filas, completa
  la solicitud, invalida el QR y aplica `RECOLECCION_QR` una sola vez.

### Autenticación y correo

- FastAPI prepara OAuth móvil de Google con state, nonce, PKCE, validación de
  issuer/audience/expiración/email verificado, `sub` estable y JWT ZeroWaste.
- Existen tablas propuestas para cuentas vinculadas y estados OAuth.
- Flask conserva una integración Google/Firebase anterior; sus secretos nunca se
  copian al móvil.
- FastAPI es propietario del correo de autenticación móvil y usa Resend por HTTPS
  443. No usa SMTP para este flujo.
- Los tokens de verificación se almacenan como hash, expiran, son de un uso,
  revocables y tienen rate limit de reenvío.
- El proveedor no queda operativo hasta configurar en servidor un remitente y una
  cuenta Resend verificados. Los DNS exactos deben copiarse del proveedor.

### Administración e impacto

- Tienda con filtros, tarjetas, alta/edición/retiro, stock, límite, orden e imagen.
- Canjes con bloqueo de filas e idempotencia para evitar doble descuento.
- Reglas de puntos con historial; movimientos con filtros, paginación, CSV y hora
  local; auditoría administrativa sin tokens ni secretos.
- Avatar administrativo usa la URL pública de medios y fallback por inicial.

### Funciones anteriores que no deben rehacerse

Development Build, EAS Project ID, package Android, branding, SafeArea, barra
inferior, mapa, foro, imágenes, ranking, puntos, tienda, redundancia Laravel,
Redis, Nginx, Caddy, Prometheus, Grafana, Media Static y Supabase.

## Migraciones y SQL preparados

No aplicar sin autorización explícita y respaldo:

- `2026_08_01_000001_add_secure_point_qr_management.php`
- `2026_08_01_000002_add_collection_schedules_and_one_time_qr.php`
- `2026_08_01_000003_add_google_oauth_and_email_verification.php`
- `2026_08_01_000004_add_admin_audit_and_reward_history.php`
- `2026_08_01_000005_add_redemption_idempotency.php`
- Propuesta legible: `docs/phase-2026-08-professional.sql` (termina en `ROLLBACK`).

Entidades propuestas: `point_qr_codes`, `collection_schedules`,
`schedule_exceptions`, `oauth_accounts`, `oauth_login_states`,
`email_verification_tokens`, `point_rule_history` y `audit_logs`, además de
extensiones conservadoras de tablas existentes.

## Variables requeridas (nombres solamente)

- QR/integración: `QR_TOKEN_ENCRYPTION_KEY`, `SYSTEM_API_KEY`,
  `FASTAPI_INTERNAL_URL`.
- Google: los Client IDs autorizados, redirect URI y configuración indicada en
  los `.env.example`; nunca Client Secret dentro de `mobile_app`.
- Correo: `RESEND_API_KEY`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`, `SUPPORT_EMAIL`,
  `EMAIL_OTP_SECRET`.
- Infraestructura: `GRAFANA_ADMIN_PASSWORD` y el resto del contrato documentado en
  `.env.example`. No registrar valores en este archivo.

## Comandos seguros habituales

```powershell
cd C:\ZeroWaste
git status --short
git status -sb
git log -15 --oneline
git diff --stat
docker compose config --quiet
python scripts/scan_tracked_secrets.py
```

```powershell
cd C:\ZeroWaste\mobile_app
npm run test:media
npm run test:map
npm run test:navigation
npx expo start --dev-client --clear
```

El último comando inicia un servidor persistente y debe ejecutarse cuando se vaya
a probar la Development Build. No usar Expo Go. No ejecutar `eas init`.

Para staging local aislado, consultar `docs/local-staging.md`. Para producción y
rollback, consultar `docs/production-runbook.md`. Los scripts de despliegue y
migración no son comandos de diagnóstico y requieren autorización.

## Problemas conocidos y validación pendiente

- `docker compose config --quiet` necesita `GRAFANA_ADMIN_PASSWORD` fuera de Git;
  sin esa variable la interpolación falla de manera segura.
- El entorno virtual raíz no contiene `pytest`; las pruebas Python deben ejecutarse
  en un entorno/contenedor con dependencias instaladas.
- PHP no está disponible en el host Windows actual; validar Laravel en su contenedor
  o en staging, no afirmar que pasó `php -l` localmente.
- Las tres suites Node actuales pasan, pero son principalmente contractuales.
- Google móvil abre el navegador del sistema mediante APIs existentes, pero el
  flujo debe comprobarse de extremo a extremo con Client ID y redirect registrados.
  `expo-auth-session` y `expo-web-browser` no están declarados actualmente.
- El escaneo de secretos señaló una URL de base de datos en una prueba contractual;
  debe mantenerse inequívocamente ficticia y sin credenciales reales.
- Las pruebas de concurrencia, bloqueos y migraciones deben ejecutarse contra el
  PostgreSQL staging aislado antes de autorizar producción.
- No consta en esta auditoría si las migraciones profesionales ya existen en
  Supabase; comprobarlo únicamente con inventario seguro y autorización.

## Próximos pasos

1. Revisar y fortalecer pruebas funcionales de QR, horarios, idempotencia y roles
   contra staging PostgreSQL.
2. Validar el recorrido OAuth móvil completo y decidir si las APIs actuales bastan;
   si se añade una dependencia nativa, ejecutar Expo Doctor y pedir autorización
   antes de generar otra Development Build.
3. Configurar Resend y DNS con valores reales proporcionados por el proveedor;
   realizar una prueba controlada sin registrar destinatarios ni tokens.
4. Revisar las migraciones y el SQL con respaldo e inventario de esquema; no
   aplicarlos todavía.
5. Ejecutar QA visual en móvil y Laravel (responsive, oscuro, errores, loading,
   impresión QR y accesibilidad).
6. Preparar despliegue y rollback usando el runbook, siempre con autorización.

## Garantías de esta auditoría

No se leyó ni mostró ningún `.env`, no se aplicaron migraciones, no se modificó
Supabase, no se generó una Development Build, no se hizo push y no se desplegó en
DigitalOcean.
