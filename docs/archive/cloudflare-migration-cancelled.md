# Plan cancelado. ZeroWaste continuará utilizando Neubox para DNS, Caddy para HTTPS y Resend para correo transaccional.

## Estado

El 1 de agosto de 2026 el propietario descartó definitivamente la incorporación de Cloudflare. No se creó zona, no se cambiaron nameservers, no se emitieron certificados Origin CA y no se contrató Load Balancing. Los documentos anteriores proponían onboarding, migración DNS desde Neubox, certificados Origin CA, firewall de origen y balanceo activo/pasivo; esas instrucciones quedan anuladas y no deben ejecutarse.

## Arquitectura vigente

- Neubox sigue siendo registrador y DNS autoritativo con `ns301` a `ns305.cloud-mx-ns.net`.
- Caddy obtiene y renueva certificados públicos ACME directamente en cada Droplet.
- Google administra recepción, buzones y respuestas del dominio raíz.
- Resend administra solamente correo transaccional mediante API HTTPS 443.
- Cada Droplet ejecuta una sola imagen Laravel. No existe balanceo público entre Droplets autorizado actualmente.

## Opciones futuras, no autorizadas

Si se solicita failover entre Droplets, evaluar por separado DigitalOcean Load Balancer, un mecanismo DNS compatible con Neubox o un balanceador externo aprobado. No contratar, configurar ni desplegar ninguno sin autorización expresa.

## Rollback documental

La evidencia completa permanece en el historial de Git anterior a este archivo. Recuperarla no autoriza su ejecución. Cualquier cambio futuro debe comenzar con una nueva auditoría DNS y una decisión escrita del propietario.
