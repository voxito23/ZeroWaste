# Cloudflare active/passive Load Balancing

This is a separately billed add-on. The owner must manually open Load Balancing, review price, configure billing, confirm purchase, then create the following. No API token is used.

Monitor `zerowaste-health`: HTTPS, GET, port 443, `/load-balancer-health`, Host `www.zerowaste-qro.com`, expect 200, no redirect/auth. Choose conservative timeout/retries and multiple consecutive down/up checks to avoid oscillation.

Pool `zerowaste-primary`: origin `app-01`, address `<PRIMARY_DROPLET_IPV4>`, port 443, enabled, monitor above. Pool `zerowaste-secondary`: origin `app-02`, address `134.122.23.135`, port 443, enabled, same monitor.

Load Balancer hostname `www.zerowaste-qro.com`: ordered pools primary then secondary, steering Off/Failover, session affinity disabled. Remove any conflicting www A/CNAME; do not create manual round-robin. Root permanently redirects to www preserving path/query. Verify secondary first. Disable the secondary origin or LB to roll back without deleting pools.
