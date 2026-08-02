# Origin firewall rollout

Manual DigitalOcean/host action. During transition retain SSH and testable 443. After Cloudflare is proven: allow 22 only from the administrative IP, 443 only from current official Cloudflare ranges, WireGuard UDP only peer-to-peer, and controlled ICMP if needed. Obtain Cloudflare ranges from its official source at execution time.

Never expose 3000, 5000, 6000, 6379, 8080, 8081, 9090, 9100, 9113, 9115 or 9121 publicly. Permit outbound HTTPS/DNS and required Supabase, external Redis, S3, mail API and OAuth traffic. Verify SSH, origin health through Cloudflare and WireGuard before tightening; rollback the firewall rules from the provider console if access is lost.
