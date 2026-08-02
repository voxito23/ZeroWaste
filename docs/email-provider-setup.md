# Correo transaccional de ZeroWaste

FastAPI es el propietario único de los correos de autenticación móvil. La integración preparada usa la API HTTPS de Resend en el puerto 443; no usa SMTP ni abre los puertos 25, 465, 587 o 2525.

Variables de servidor pendientes:

- `RESEND_API_KEY`
- `RESEND_FROM_EMAIL` (debe pertenecer al dominio verificado)
- `RESEND_FROM_NAME=ZeroWaste`
- `RESEND_REPLY_TO` (buzón real cuando corresponda)
- `PUBLIC_BASE_URL=https://www.zerowaste-qro.com`
- `EMAIL_VERIFICATION_TTL_MINUTES=60`
- `PASSWORD_RESET_TTL_MINUTES=30`
- `SUPPORT_EMAIL=soporte@zerowaste-qro.com`
- `EMAIL_OTP_SECRET` (secreto aleatorio de servidor usado para derivar códigos OTP; nunca se comparte con el móvil)

En Resend se debe agregar y verificar `zerowaste-qro.com`. Después, copia exactamente desde el panel del proveedor los registros SPF y DKIM que genere para la cuenta. Configura DMARC según la política elegida por el propietario del dominio y confirma el Return-Path mostrado por Resend. No se incluyen valores DNS de ejemplo porque serían incorrectos para una cuenta real.

Antes de producción:

1. Verificar el dominio y remitente en Resend.
2. Copiar sin cambios los registros SPF/DKIM indicados por Resend al DNS.
3. Validar DMARC, Return-Path, From y Reply-To.
4. Cargar la API key como secreto de DigitalOcean, nunca en Git.
5. Ejecutar una prueba a una dirección controlada y revisar el ID del proveedor en logs sin imprimir destinatario, token ni contenido sensible.

## Prueba segura autorizada

1. Confirmar por presencia, sin imprimir, `RESEND_API_KEY` y `RESEND_FROM_EMAIL` en el gestor de secretos.
2. Comprobar salida HTTPS 443 hacia `api.resend.com`; no abrir 25, 465, 587 o 2525.
3. En Resend confirmar dominio y remitente `Verified` antes de enviar.
4. Enviar un único mensaje a una cuenta de prueba controlada y revisar encabezados para `SPF=pass`, `DKIM=pass`, `DMARC=pass` y alignment.
5. Probar verificación válida, token vencido, usado, reemplazado, reenvío limitado y error simulado del proveedor.
6. Probar recuperación y confirmación de contraseña; confirmar que Flask no produce un segundo correo.
7. No usar usuarios reales sin autorización ni ejecutar envíos masivos.

Rollback: mantener la entrega deshabilitada retirando temporalmente la variable del servicio afectado, restaurar el commit previo y conservar tokens pendientes revocados. No volver a SMTP ni registrar tokens, contraseñas o destinatarios.
