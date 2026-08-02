# Two-Droplet deployment status

`zerowaste-app-01` (`137.184.42.51`) and `zerowaste-app-02` (`134.122.23.135`) remain in separate DigitalOcean accounts. Each runs the same commit, Compose project and exactly one `laravel` service. Shared Supabase, external Redis/Valkey and S3-compatible media preserve state across nodes.

No public cross-account load balancer is currently authorized or configured. Neubox remains authoritative DNS and the root A record points to the primary. Caddy manages public ACME certificates on each node. The secondary may be prepared and monitored privately, but DNS does not automatically fail over to it.

Future options require a separate decision: DigitalOcean Load Balancer where account boundaries permit it, a Neubox-compatible DNS failover facility, or an approved external load balancer. Do not claim automatic failover until one is selected and tested.
