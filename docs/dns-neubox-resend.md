# DNS de ZeroWaste: Neubox, Google y Resend

## Arquitectura vigente

Neubox conserva registro y DNS. Caddy administra HTTPS público en DigitalOcean. Google recibe el correo corporativo en `zerowaste-qro.com`; Resend envía correo transaccional y usa `send.zerowaste-qro.com` como Return-Path. No se utiliza Cloudflare ni SMTP desde los Droplets.

## Inventario confirmado

Nameservers: `ns301.cloud-mx-ns.net` a `ns305.cloud-mx-ns.net`; no cambiarlos. A raíz: `137.184.42.51`, TTL 14400. A `flako`: `68.183.142.8`, TTL aproximado 14440. CNAME `www`, `mail` y `ftp` al dominio raíz; CNAME `auth` a `zerowaste-57c55.web.app`. No modificar sin comprobar consumidores y rollback.

Google publica en el dominio raíz MX prioridad 1 `smtp.google.com`, verificación TXT y DKIM bajo `google._domainkey`. Resend publica en `send` MX prioridad 10 hacia `feedback-smtp.us-east-1.amazonses.com`, SPF `v=spf1 include:amazonses.com ~all` y DKIM bajo `resend._domainkey`. DMARC es `v=DMARC1; p=none;`. Los valores de verificación y claves públicas se omiten deliberadamente.

## Diagnóstico

- Falta públicamente un SPF en el dominio raíz. Si Google Admin confirma que Workspace envía como `@zerowaste-qro.com`, agregar un único TXT con el valor exacto recomendado allí; para Google solamente suele ser `v=spf1 include:_spf.google.com ~all`. Nunca crear dos SPF en el mismo hostname.
- `links.zerowaste-qro.com` no existe. Es opcional y solo se agrega con el CNAME exacto que muestre Resend al habilitar tracking.
- Existe un DS público: DNSSEC está delegado. No modificarlo sin comparar primero con Neubox.
- Mantener DMARC en `p=none` hasta confirmar SPF, DKIM y alignment de Google y Resend. `rua` requiere primero un buzón real preparado.

## Validación en paneles

En Google Admin comprobar dominio verificado, Gmail activo, MX esperado, DKIM autenticando, SPF recomendado, usuarios y alias. No cambiar un MX funcional a ciegas. En Resend > Domains abrir el dominio exacto y clasificar DKIM, SPF, MX/Return-Path y tracking como Verified, Pending, Failed o Missing; confirmar región y dominio del From. Copiar registros exactamente desde el panel.

## PowerShell (ejecutar por separado)

```powershell
Resolve-DnsName zerowaste-qro.com -Type A
Resolve-DnsName www.zerowaste-qro.com -Type CNAME
Resolve-DnsName zerowaste-qro.com -Type NS
Resolve-DnsName zerowaste-qro.com -Type MX
Resolve-DnsName zerowaste-qro.com -Type TXT
Resolve-DnsName send.zerowaste-qro.com -Type MX
Resolve-DnsName send.zerowaste-qro.com -Type TXT
Resolve-DnsName resend._domainkey.zerowaste-qro.com -Type TXT
Resolve-DnsName links.zerowaste-qro.com -Type CNAME
Resolve-DnsName google._domainkey.zerowaste-qro.com -Type TXT
Resolve-DnsName _dmarc.zerowaste-qro.com -Type TXT
Resolve-DnsName zerowaste-qro.com -Type DS
```

`DNS name does not exist` es esperado para tracking desactivado o DNSSEC ausente; primero se clasifica si el registro es obligatorio.

## Pruebas y rollback

Después de cualquier cambio autorizado, esperar TTL, repetir consultas, enviar un único correo a una cuenta controlada y revisar `SPF=pass`, `DKIM=pass`, `DMARC=pass` y alignment. No hacer envíos masivos. Para revertir, restaurar exactamente el registro previamente exportado desde Neubox; nunca borrar MX/DKIM/SPF de Google o Resend por aproximación. No modificar nameservers.
