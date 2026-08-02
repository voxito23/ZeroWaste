# Proxy propuesto para enlaces móviles

No se aplicó esta configuración. Las rutas `/app/*` y
`/.well-known/assetlinks.json` deben llegar a FastAPI, no al cliente Flask.

En Caddy, antes del `handle` general de Flask:

```caddyfile
@mobile_links path /app/* /.well-known/assetlinks.json
handle @mobile_links {
    reverse_proxy nginx_api:8080
}
```

En `nginx/api.conf`, antes de `location / { return 404; }`:

```nginx
location /app/ {
    proxy_pass http://fastapi_backend;
    include /etc/nginx/proxy_params;
}

location = /.well-known/assetlinks.json {
    proxy_pass http://fastapi_backend;
    include /etc/nginx/proxy_params;
}
```

Antes de habilitarlo se debe configurar
`ANDROID_APP_LINKS_SHA256_CERT_FINGERPRINT` con la huella real de la firma de
producción y verificar `https://www.zerowaste-qro.com/.well-known/assetlinks.json`.
Después se cambia `EXPO_PUBLIC_MOBILE_LINKS_READY=true` y se genera la
Development/production build autorizada. El rollback consiste en retirar ambos
matchers del proxy y volver `EXPO_PUBLIC_MOBILE_LINKS_READY=false`; los enlaces
compartidos seguirán usando `zerowaste://`.
