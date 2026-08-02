# Email DNS verification

Run before and after delegation.

```powershell
Resolve-DnsName zerowaste-qro.com -Type MX
Resolve-DnsName zerowaste-qro.com -Type TXT
Resolve-DnsName _dmarc.zerowaste-qro.com -Type TXT
Resolve-DnsName www.zerowaste-qro.com
Resolve-DnsName zerowaste-qro.com -Type NS
```

```bash
dig MX zerowaste-qro.com
dig TXT zerowaste-qro.com
dig TXT _dmarc.zerowaste-qro.com
dig A www.zerowaste-qro.com
dig NS zerowaste-qro.com
```

Confirm MX priorities, SPF, existing DKIM/DMARC, DNS-only mail hosts and validations. Do not change the email provider. If any result differs from the signed inventory, stop and correct DNS; rollback delegation only when correction cannot restore service.
