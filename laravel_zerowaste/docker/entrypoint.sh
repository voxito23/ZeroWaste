#!/bin/sh
set -eu

media_root="${MEDIA_ROOT:-/data/media}"
media_gid="${MEDIA_GID:-2000}"

case "$media_gid" in
  ''|*[!0-9]*)
    echo "MEDIA_GID debe ser un identificador numérico." >&2
    exit 1
    ;;
esac

# El único destino permitido es el volumen compartido definido por Compose.
# Los directorios setgid conservan el grupo entre Laravel, FastAPI y Flask.
if [ "$media_root" = "/data/media" ] && [ -d "$media_root" ]; then
  for category in foro perfiles recompensas campanas eventos puntos; do
    install -d -o root -g "$media_gid" -m 2770 "$media_root/$category"
  done
fi

exec "$@"
