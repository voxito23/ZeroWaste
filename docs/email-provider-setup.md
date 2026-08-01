# Correo transaccional de ZeroWaste

FastAPI es el propietario único de los correos de autenticación móvil. La integración preparada usa la API HTTPS de Resend en el puerto 443; no usa SMTP ni abre los puertos 25, 465, 587 o 2525.

Variables de servidor pendientes:

- `RESEND_API_KEY`
- `MAIL_FROM_ADDRESS=noreply@zerowaste-qro.com`
- `MAIL_FROM_NAME=ZeroWaste`
- `SUPPORT_EMAIL=soporte@zerowaste-qro.com`

En Resend se debe agregar y verificar `zerowaste-qro.com`. Después, copia exactamente desde el panel del proveedor los registros SPF y DKIM que genere para la cuenta. Configura DMARC según la política elegida por el propietario del dominio y confirma el Return-Path mostrado por Resend. No se incluyen valores DNS de ejemplo porque serían incorrectos para una cuenta real.

Antes de producción:

1. Verificar el dominio y remitente en Resend.
2. Copiar sin cambios los registros SPF/DKIM indicados por Resend al DNS.
3. Validar DMARC, Return-Path, From y Reply-To.
4. Cargar la API key como secreto de DigitalOcean, nunca en Git.
5. Ejecutar una prueba a una dirección controlada y revisar el ID del proveedor en logs sin imprimir destinatario, token ni contenido sensible.
