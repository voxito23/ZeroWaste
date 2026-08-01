#!/usr/bin/env bash
set -Eeuo pipefail

readonly RAIZ_ESPERADA="/opt/ZeroWaste"
RAIZ_PROYECTO="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd -P)"
RAIZ_SCRIPT="$RAIZ_PROYECTO/scripts"

if [[ "$RAIZ_PROYECTO" != "$RAIZ_ESPERADA" && "${PERMITIR_RAIZ_ALTERNATIVA:-0}" != "1" ]]; then
  printf 'ERROR: ejecuta este script desde %s.\n' "$RAIZ_ESPERADA" >&2
  exit 1
fi

# shellcheck source=funciones_docker.sh
source "$RAIZ_SCRIPT/funciones_docker.sh"
detectar_compose

cd "$RAIZ_PROYECTO"
printf 'Usando Compose: %s\n' "${COMANDO_COMPOSE[*]}"
ejecutar_compose config >/dev/null

bash "$RAIZ_SCRIPT/migrar_medios_produccion.sh"

printf 'Reconstruyendo y levantando servicios...\n'
ejecutar_compose up --build -d

bash "$RAIZ_SCRIPT/desplegar_esquema_impacto.sh"
python3 "$RAIZ_SCRIPT/verificar_medios_produccion.py"

ejecutar_compose ps
printf 'Despliegue de producción terminado correctamente.\n'
