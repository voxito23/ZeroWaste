# Laravel redundancy model

Each Droplet runs exactly one `laravel` service from `zerowaste-laravel:${APP_VERSION}`. There are no in-node Laravel replicas. Both nodes share APP_KEY, cookie configuration and external Redis, while only the primary scheduler profile is active. No public failover mechanism is currently authorized; see `cross-account-failover.md`.
