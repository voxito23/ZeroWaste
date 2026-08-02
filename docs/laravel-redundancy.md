# Laravel redundancy model

Each Droplet runs exactly one `laravel` service from `zerowaste-laravel:${APP_VERSION}`. Redundancy exists across the two DigitalOcean accounts and Cloudflare performs active/passive failover; there are no in-node Laravel replicas or sticky sessions. Both nodes share APP_KEY, cookie configuration and external Redis, while only the primary scheduler profile is active. See `cross-account-failover.md` and `secondary-droplet-setup.md`.
