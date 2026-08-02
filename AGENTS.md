# Reglas de trabajo para ZeroWaste

Estas reglas aplican a todo el repositorio.

## Antes de editar

1. Leer `PROJECT_CONTEXT.md`, `git status -sb`, `git log -10 --oneline` y el diff
   relevante.
2. Preservar cambios del usuario y no reescribir historia.
3. Inspeccionar la implementación real antes de crear módulos, rutas, servicios,
   tablas, pantallas o aplicaciones.
4. No leer, imprimir ni copiar archivos `.env`. Usar `.env.example` y mencionar
   solamente nombres de variables.
5. Ejecutar `docker compose config --quiet` para evitar mostrar secretos
   interpolados.

## Límites obligatorios

- No ejecutar `migrate:fresh`, `migrate:reset`, `migrate:refresh`,
  `db.create_all()`, `DROP`, `TRUNCATE` ni DDL contra Supabase.
- No aplicar migraciones ni desplegar a DigitalOcean sin autorización explícita.
- No hacer push. Crear commits solo cuando el usuario lo pida o el encargo lo
  autorice expresamente y las pruebas relacionadas pasen.
- No usar `git reset --hard`, force push ni eliminar cambios ajenos.
- No crear otra app Expo, no ejecutar `eas init` y no cambiar
  `com.vic45.mobile_app`.
- No crear otra Development Build por cambios JS, estilos, navegación o servicios.
  Una dependencia/plugin/configuración nativa requiere primero diff, Expo Doctor,
  justificación y autorización.
- No duplicar Flask, FastAPI o Laravel. FastAPI es el backend móvil canónico;
  Laravel consume sus operaciones QR internas; Flask conserva el sitio público.
- No abrir QR desconocidos en navegador ni WebView. Nunca registrar tokens QR,
  JWT, cookies, contraseñas, secretos o SQL sensible.

## Contratos que deben preservarse

- API pública: `https://www.zerowaste-qro.com/api`.
- Panel: `https://www.zerowaste-qro.com/zw-interno`.
- Medios: `https://www.zerowaste-qro.com/media/`.
- QR punto: `/q/p/zw1p_<token-opaco>`; reutilizable, revocable y sin puntos por
  escaneo salvo regla explícita.
- QR recolección: `/q/c/zw1c_<token-opaco>`; expira, un uso, ligado a solicitud,
  rol y transacción idempotente.
- Error externo móvil exacto:
  `Error: este código QR no pertenece a ZeroWaste.`
- Horario inicial: lunes, miércoles y viernes, 10:00–14:00,
  `America/Mexico_City`; la autoridad es backend, no el dispositivo.
- `RECOLECCION_QR` toma puntos de la regla activa. El móvil nunca decide el monto.
- Google se valida en FastAPI y produce JWT ZeroWaste; nunca guardar Client Secret
  en móvil ni usar el token Google como sesión permanente.
- FastAPI es propietario del correo de autenticación móvil; Resend usa HTTPS 443.

## Estilo de implementación

- Reutilizar modelos, servicios, componentes y tokens visuales existentes.
- Mantener la paleta verde oscuro/esmeralda/menta, blanco, gris azulado y rojo solo
  para error o eliminación.
- Implementar validación cliente y servidor, mensajes inline, loading, bloqueo de
  doble envío, alertas, confirmaciones visuales y conservación de datos inválidos.
- Toda mutación administrativa requiere `auth`, rol admin, CSRF y auditoría sin
  contenido sensible.
- Manejar concurrencia con transacciones, locks, restricciones únicas e
  idempotencia. No confiar en bloqueos de React Native.
- Usar `America/Mexico_City` para presentación y reglas locales; conservar UTC en
  almacenamiento.
- No inventar credenciales, registros DNS, datos productivos ni estados de
  despliegue.

## Pruebas y entrega

- Ejecutar primero pruebas enfocadas y luego la suite disponible.
- Distinguir prueba contractual de prueba funcional; no afirmar validación PHP si
  PHP no está disponible ni validación DB si no se usó PostgreSQL.
- Para Python, usar el entorno/contenedor que realmente tenga dependencias. No
  instalar paquetes globalmente sin necesidad.
- Para Laravel, preferir el contenedor o staging aislado si el host no tiene PHP.
- Para móvil, ejecutar las suites Node y Expo Doctor cuando cambien dependencias o
  configuración Expo.
- Antes de commit: `git status`, `git diff --stat`, pruebas relevantes, escaneo de
  secretos y confirmación de que `.env`/Supabase no se modificaron.
- La entrega debe listar archivos, pruebas reales, pruebas bloqueadas, variables
  pendientes, migraciones propuestas, riesgos, despliegue y rollback.

## Referencias

- Estado y comandos: `PROJECT_CONTEXT.md`.
- Esquema propuesto: `docs/phase-2026-08-professional.sql`.
- Staging aislado: `docs/local-staging.md`.
- Correo: `docs/email-provider-setup.md`.
- Producción/rollback: `docs/production-runbook.md`.
