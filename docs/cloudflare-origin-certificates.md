# Cloudflare Origin CA certificates

After the zone is Active, set SSL/TLS to **Full (strict)**. Manually issue a distinct Origin CA certificate/key for each Droplet covering `zerowaste-qro.com` and `*.zerowaste-qro.com`. Store them outside Git/image at `/opt/ZeroWaste/secrets/cloudflare-origin.pem` and `.key`; root owns both and the key is mode 600. Mount read-only through `.env.node` paths. Never reuse a node's private key on the other node or print either value.

Caddy accepts root, www and the temporary backup hostname and forwards Host/X-Forwarded headers. Trust `CF-Connecting-IP` only after the firewall limits origin traffic to current official Cloudflare ranges. Rollback to the previous per-node certificate files, not Flexible TLS.
