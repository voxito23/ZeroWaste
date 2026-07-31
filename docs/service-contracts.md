# Contratos canónicos entre servicios ZeroWaste

Base pública de API: `https://www.zerowaste-qro.com/api`.

La autenticación móvil usa `Authorization: Bearer <JWT>`. Ningún cliente móvil
debe incluir una API key de sistema. Los campos legacy se mantienen durante la
transición, pero los clientes nuevos consumen los campos canónicos indicados.

## Foro

| Método y ruta | Auth | Request | Response relevante | Errores esperados |
|---|---|---|---|---|
| `GET /foro/posts` | Pública | — | Lista; `id`, `titulo`, `contenido`, `categoria_id`, `autor_id`, `image_url`, `avatar_url`, conteos; `imagen`/`autor_foto` temporales | `200`, `500` sólo como fallo no esperado |
| `GET /foro/posts/{id}` | Pública | ID entero | Mismo objeto con detalle | `404` si no existe/no está aprobado |
| `POST /foro/posts` | Bearer | JSON sin binario; título, contenido, categoría y metadata admitida | Post creado; queda sujeto a moderación | `401`, `422` |
| `POST /foro/posts/con-imagen` | Bearer | Multipart: `titulo`, `contenido`, `categoria_id`, `imagen` opcional JPEG/PNG/WebP, máximo 5 MiB | Post con `image_url` canónica | `401`, `413`, `415`, `422` |
| `POST /foro/posts/{id}/respuestas` | Bearer | JSON `{ "contenido": "..." }` | Respuesta creada | `401`, `404`, `422` |

Los binarios del foro se obtienen de `/media/foro/<archivo>`. Las rutas
FastAPI históricas de archivos son compatibilidad, no almacenamiento nuevo.

## Mapa

`GET /mapa/puntos` es público y devuelve una lista con este contrato:

```json
{
  "id": 1,
  "nombre": "Punto de acopio",
  "direccion": "...",
  "latitud": 20.0,
  "longitud": -100.0,
  "tipo": "...",
  "materiales": "...",
  "image_url": "https://www.zerowaste-qro.com/media/puntos/archivo.webp",
  "promedio": 0.0,
  "total_reviews": 0
}
```

`latitud` y `longitud` son números finitos; sus rangos son `[-90, 90]` y
`[-180, 180]`. Mapbox recibe siempre `[longitud, latitud]`. Una fila inválida se
omite y se registra de forma segura, sin invalidar los demás marcadores.
`imagen` se conserva temporalmente como metadata legacy.

## Perfil

| Método y ruta | Auth | Request | Response relevante |
|---|---|---|---|
| `GET /usuarios/me` | Bearer | — | Perfil y `avatar_url`; `foto_perfil` temporal |
| `PUT /usuarios/me/foto` | Bearer | Multipart `foto_perfil` | Perfil actualizado con URL HTTPS |

El binario se guarda en `/data/media/perfiles`; la base conserva únicamente el
nombre o ruta relativa.

## Ranking, puntos y tienda

| Método y ruta | Auth | Propósito |
|---|---|---|
| `GET /impacto/ranking` | Pública | Ranking; `avatar_url` es canónico y `avatar` queda como compatibilidad |
| `GET /impacto/me` | Bearer | Saldo disponible, impacto histórico, nivel y posición |
| `GET /impacto/movimientos` | Bearer | Historial de puntos del usuario |
| `GET /impacto/recompensas` | Pública | Catálogo; `image_url` usa `/media/recompensas/` |
| `GET /impacto/recompensas/{id}` | Pública | Detalle de recompensa |
| `POST /impacto/canjes` | Bearer | `{ "recompensa_id": int, "cantidad": 1..10 }` |
| `GET /impacto/canjes` | Bearer | Canjes del usuario |

Los conflictos de stock o saldo responden `409`; autenticación inválida `401`;
recurso ausente `404`; payload inválido `422`.

## Salud

| Servicio | Liveness | Readiness |
|---|---|---|
| FastAPI | `/health`, sin DB | `/ready`, consulta read-only de dependencias |
| Flask | `/health`, sin DB | `/ready`, consulta read-only de dependencias |
| Laravel | `/up`, proceso HTTP | Readiness se observa por probes y dependencias compartidas |
| Nginx interno | `:8081/nginx-health` | Probes de upstream separados |
| Media Static | `/health` interno | Archivo real por Blackbox/HTTPS |

Los endpoints `/metrics`, `/stub_status`, Prometheus, Redis, exporters y
healthchecks internos no son rutas públicas.

## Política de medios

- Canónico: `image_url`, `avatar_url` y `cover_url`.
- Compatibilidad temporal: `imagen`, `imagen_url`, `foto_perfil`, `autor_foto`
  y `avatar`, según el modelo histórico.
- Sólo `https://` absoluto o rutas relativas canónicas.
- Se rechazan `javascript:`, `file:`, `data:`, rutas internas, hosts locales e
  IP privadas.
- Los nombres de archivo son UUID y el tipo/tamaño se valida por contenido.

