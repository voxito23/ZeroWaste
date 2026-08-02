# Cloudflare onboarding from Neubox

Manual Cloudflare action only. Create an account at the official Cloudflare site with an accessible email, verify it, enable 2FA and store recovery codes. Choose **Add/Onboard a domain**, enter only `zerowaste-qro.com` (not `https://`, `www`, `/api` or `/zw-interno`), choose the needed DNS plan and allow the DNS scan. Do not change nameservers yet.

Compare every scanned record with the Neubox inventory. Public HTTP(S) hosts may be **Proxied**; mail-related hosts must remain **DNS only**. MX and TXT are not proxied. Preserve mail and service validations. Cloudflare is DNS/proxy/WAF/LB; Neubox remains registrar and renewal provider. Do not transfer, unlock, request EPP, repurchase, or edit contacts.

Only after an exact inventory, mail verification and DNSSEC review, copy the two zone-specific nameservers shown by Cloudflare as `<CLOUDFLARE_NAMESERVER_1>` and `<CLOUDFLARE_NAMESERVER_2>`. Never use tutorial or guessed nameservers. Verify Active status and DNS/email before proceeding. Rollback: correct Cloudflare records first; if unavoidable, restore the previously recorded Neubox nameservers and recheck web and mail.
