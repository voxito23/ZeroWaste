# Future failover test plan — not currently enabled

Neubox remains authoritative and no automatic cross-Droplet load balancer is configured. Do not run production failover tests until the owner selects and authorizes DigitalOcean Load Balancer, Neubox-compatible DNS failover or another external balancer.

Once selected, the plan must verify primary and secondary health, shared Redis sessions/CSRF, S3 media, Supabase, recovery to primary, secondary-only failure and both-nodes failure. Record TTL/monitor intervals and never promise zero downtime without measurement. Roll back by disabling the newly selected mechanism and restoring the exported Neubox record; do not change nameservers, delete Droplets or modify Supabase.
