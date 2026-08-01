#!/usr/bin/env bash

# Funciones compartidas por los scripts operativos. Este archivo se carga con
# source y no debe ejecutarse directamente.

detectar_compose() {
  if docker compose version >/dev/null 2>&1; then
    COMANDO_COMPOSE=(docker compose)
  elif command -v docker-compose >/dev/null 2>&1 \
    && docker-compose version >/dev/null 2>&1; then
    COMANDO_COMPOSE=(docker-compose)
  else
    printf 'ERROR: no se encontró Docker Compose moderno ni docker-compose clásico.\n' >&2
    return 1
  fi
}

ejecutar_compose() {
  "${COMANDO_COMPOSE[@]}" "$@"
}
