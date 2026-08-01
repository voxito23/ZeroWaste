#!/usr/bin/env bash
set -Eeuo pipefail

RAIZ_SCRIPT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd -P)"
exec bash "${RAIZ_SCRIPT}/deploy_impact_schema.sh" "$@"
