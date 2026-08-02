# Delegating Neubox DNS to Cloudflare

Manual Neubox action. In Clients > Domains > My domains, select `zerowaste-qro.com`, Manage, then the UI labelled Change DNS/Nameservers/Name servers/Other DNS. Confirm Cloudflare contains the complete inventory first. Select custom nameservers, remove the old entries, enter exactly `<CLOUDFLARE_NAMESERVER_1>` and `<CLOUDFLARE_NAMESERVER_2>` without protocol/spaces, and save. UI wording may vary. Do not transfer, request EPP or edit contacts.

If DNSSEC is off, proceed. If it is on, stop: remove/disable the old DS in Neubox, wait and verify it is gone, change nameservers, wait for Cloudflare Active, enable Cloudflare DNSSEC, and copy its exact new DS to Neubox. Never invent key tag, algorithm, digest or type.

Validate with the commands in `email-dns-verification.md`, `curl -I https://www.zerowaste-qro.com`, and ensure NS match Cloudflare. Preserve the old nameservers for rollback; keep the Cloudflare zone and try correcting records before restoring delegation.
