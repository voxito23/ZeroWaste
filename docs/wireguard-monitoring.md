# Private cross-account monitoring with WireGuard

The accounts do not share a VPC. Manually generate keys on each server (never commit/output private keys), copy the matching example to `/etc/wireguard/wg0.conf`, substitute peer public keys and real public IPs, set mode 600 and enable `wg-quick@wg0`. Primary is `10.77.0.1/30`; secondary is `10.77.0.2/30`. Permit UDP 51820 only between peer public IPs.

Set each node's `MONITORING_NODE_ADDRESS` to its WireGuard address when Docker supports binding after the interface is up. Prometheus on primary reaches secondary exporters through `10.77.0.2`, never public IP. The canonical `prometheus/targets/origins.yml` and `prometheus/targets/nodes.yml` files are versioned with the fixed `10.77.0.1/30` and `10.77.0.2/30` addresses so a normal code update cannot silently remove both nodes from Grafana. Keep the `.example.yml` files as templates only. If the private addressing changes, review and update both canonical files before deploying. Limit exporter binds/firewall to localhost/WireGuard; do not expose monitoring ports publicly.

Verify `wg show`, peer ping and each metrics endpoint from primary. If tunnel configuration fails, restore its previous config and keep public exporter ports closed. The secondary `monitoring-standby` profile is independent and is enabled manually only during a prolonged primary outage.
