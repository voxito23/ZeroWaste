#!/usr/bin/env bash
set -Eeuo pipefail

RAIZ_PROYECTO="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd -P)"

leer_variable_raiz() {
  local nombre="$1"
  [[ -f "$RAIZ_PROYECTO/.env" ]] || return 0
  awk -F= -v clave="$nombre" '
    $1 == clave {
      sub(/^[^=]*=/, "")
      gsub(/^['\''\"]|['\''\"]$/, "")
      gsub(/\r$/, "")
      print
      exit
    }
  ' "$RAIZ_PROYECTO/.env"
}

readonly RAIZ_ESPERADA="/opt/ZeroWaste"
readonly RAIZ_RESPALDOS="${ZEROWASTE_BACKUP_ROOT:-/opt/zerowaste-backups}"
GID_MEDIOS_CONFIGURADO="${MEDIA_GID:-$(leer_variable_raiz MEDIA_GID)}"
RAIZ_MEDIOS_CONFIGURADA="${MEDIA_ROOT:-$(leer_variable_raiz MEDIA_ROOT)}"
if [[ -n "$RAIZ_MEDIOS_CONFIGURADA" && "$RAIZ_MEDIOS_CONFIGURADA" != /* ]]; then
  RAIZ_MEDIOS_CONFIGURADA="$RAIZ_PROYECTO/$RAIZ_MEDIOS_CONFIGURADA"
fi
readonly GID_MEDIOS="${GID_MEDIOS_CONFIGURADO:-2000}"
readonly RAIZ_MEDIOS="$(realpath -m "${RAIZ_MEDIOS_CONFIGURADA:-$RAIZ_PROYECTO/shared/media}")"
readonly MARCA_TIEMPO="$(date -u +%Y%m%dT%H%M%SZ)"
readonly ARCHIVO_CONFLICTOS="${RAIZ_RESPALDOS}/conflictos-medios-${MARCA_TIEMPO}.txt"

COPIADOS=0
EXISTENTES=0
CONFLICTOS=0

fallar() {
  printf 'ERROR: %s\n' "$*" >&2
  exit 1
}

if [[ "$RAIZ_PROYECTO" != "$RAIZ_ESPERADA" && "${PERMITIR_RAIZ_ALTERNATIVA:-0}" != "1" ]]; then
  fallar "ejecuta este script desde ${RAIZ_ESPERADA}"
fi

[[ "$RAIZ_MEDIOS" == /opt/ZeroWaste/shared/media ]] \
  || [[ "${PERMITIR_RAIZ_ALTERNATIVA:-0}" == "1" ]] \
  || fallar "MEDIA_ROOT debe apuntar a /opt/ZeroWaste/shared/media"

cd "$RAIZ_PROYECTO"
command -v sha256sum >/dev/null || fallar "sha256sum no está instalado"
command -v python3 >/dev/null || fallar "python3 no está instalado"

install -d -m 0700 "$RAIZ_RESPALDOS"
for categoria in foro perfiles recompensas campanas eventos puntos; do
  install -d -o root -g "$GID_MEDIOS" -m 2770 "$RAIZ_MEDIOS/$categoria"
done
: > "$ARCHIVO_CONFLICTOS"
chmod 0600 "$ARCHIVO_CONFLICTOS"

copiar_archivo() {
  local origen="$1"
  local categoria="$2"
  local nombre destino extension
  [[ -f "$origen" && ! -L "$origen" ]] || return 0
  nombre="$(basename "$origen")"
  extension="${nombre##*.}"
  extension="${extension,,}"
  case "$extension" in
    jpg|jpeg|png|webp) ;;
    *) return 0 ;;
  esac
  destino="$RAIZ_MEDIOS/$categoria/$nombre"
  if [[ -e "$destino" ]]; then
    if [[ "$(sha256sum "$origen" | awk '{print $1}')" == "$(sha256sum "$destino" | awk '{print $1}')" ]]; then
      EXISTENTES=$((EXISTENTES + 1))
    else
      printf '%s | conservado=%s | alternativo=%s\n' "$categoria/$nombre" "$destino" "$origen" >> "$ARCHIVO_CONFLICTOS"
      CONFLICTOS=$((CONFLICTOS + 1))
    fi
    return 0
  fi
  install -o root -g "$GID_MEDIOS" -m 0660 "$origen" "$destino"
  COPIADOS=$((COPIADOS + 1))
}

copiar_directorio() {
  local origen="$1"
  local categoria="$2"
  [[ -d "$origen" ]] || return 0
  while IFS= read -r -d '' archivo; do
    copiar_archivo "$archivo" "$categoria"
  done < <(find "$origen" -type f -print0)
}

copiar_directorio_directo() {
  local origen="$1"
  local categoria="$2"
  [[ -d "$origen" ]] || return 0
  while IFS= read -r -d '' archivo; do
    copiar_archivo "$archivo" "$categoria"
  done < <(find "$origen" -maxdepth 1 -type f -print0)
}

printf 'Creando inventario previo de medios...\n'
python3 scripts/inventory_media.py --include-legacy \
  > "${RAIZ_RESPALDOS}/inventario-medios-antes-${MARCA_TIEMPO}.json"

# Publicaciones y perfiles históricos.
copiar_directorio "flask_zerowaste/static/img/posts" foro
copiar_directorio "flask_zerowaste/static/img/perfiles" perfiles
copiar_directorio "laravel_zerowaste/public/img/perfiles" perfiles
copiar_directorio "fast_api/static/perfiles" perfiles
copiar_directorio "fast_api/static/img/perfiles" perfiles

# El directorio histórico eventos almacenaba tanto campañas como eventos. Se
# conserva una copia en ambas categorías; el tipo del registro decide la URL.
for origen_eventos in \
  "flask_zerowaste/static/img/eventos" \
  "laravel_zerowaste/public/img/eventos"
do
  copiar_directorio "$origen_eventos" campanas
  copiar_directorio "$origen_eventos" eventos
done

# Los puntos antiguos podían estar directamente bajo static/img o en mapa.
copiar_directorio_directo "flask_zerowaste/static/img" puntos
copiar_directorio "laravel_zerowaste/public/img/mapa" puntos

# Recursos iniciales de recompensas.
copiar_directorio "laravel_zerowaste/public/images/recompensas" recompensas

# Respaldar y migrar volúmenes Docker heredados, si todavía existen. Nunca se
# eliminan ni se escriben esos volúmenes.
if command -v docker >/dev/null 2>&1; then
  while IFS= read -r volumen; do
    [[ -n "$volumen" ]] || continue
    case "$volumen" in
      *_foro_media) categoria=foro ;;
      *_perfiles_compartidos) categoria=perfiles ;;
      *) continue ;;
    esac
    respaldo_volumen="${RAIZ_RESPALDOS}/volumen-${volumen}-${MARCA_TIEMPO}"
    install -d -m 0700 "$respaldo_volumen"
    docker run --rm \
      --mount "source=${volumen},target=/origen,readonly" \
      --mount "type=bind,source=${respaldo_volumen},target=/respaldo" \
      alpine:3.20 sh -ceu 'cp -a /origen/. /respaldo/'
    copiar_directorio "$respaldo_volumen" "$categoria"
  done < <(docker volume ls --format '{{.Name}}')
fi

find "$RAIZ_MEDIOS" -type d -exec chown root:"$GID_MEDIOS" {} +
find "$RAIZ_MEDIOS" -type d -exec chmod 2770 {} +
find "$RAIZ_MEDIOS" -type f -exec chown root:"$GID_MEDIOS" {} +
find "$RAIZ_MEDIOS" -type f -exec chmod 0660 {} +

python3 scripts/inventory_media.py \
  --root "foro=${RAIZ_MEDIOS}/foro" \
  --root "perfiles=${RAIZ_MEDIOS}/perfiles" \
  --root "recompensas=${RAIZ_MEDIOS}/recompensas" \
  --root "campanas=${RAIZ_MEDIOS}/campanas" \
  --root "eventos=${RAIZ_MEDIOS}/eventos" \
  --root "puntos=${RAIZ_MEDIOS}/puntos" \
  > "${RAIZ_RESPALDOS}/inventario-medios-despues-${MARCA_TIEMPO}.json"

printf 'Migración conservadora de medios terminada.\n'
printf '  copiados: %s\n  ya existentes: %s\n  conflictos conservados: %s\n' \
  "$COPIADOS" "$EXISTENTES" "$CONFLICTOS"
if [[ "$CONFLICTOS" -eq 0 ]]; then
  rm -f "$ARCHIVO_CONFLICTOS"
else
  printf 'Revisa los conflictos sin sobrescribir en: %s\n' "$ARCHIVO_CONFLICTOS"
fi
