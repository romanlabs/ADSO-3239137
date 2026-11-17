#!/bin/sh
# Seeds demo data through the API in two phases (ADR-005): the API runs 20 days in the past for the loans that must end
# overdue, then it is restarted with today's clock. Run it from this folder after `docker compose up` in ../infra.
set -e

cd "$(dirname "$0")"
INFRA=../infra
API_URL="${API_URL:-http://localhost:8000}"

if [ -z "$ADMIN_USERNAME" ] || [ -z "$ADMIN_PASSWORD" ]; then
  set -a
  . "$INFRA/.env"
  set +a
fi
export API_URL ADMIN_USERNAME ADMIN_PASSWORD

wait_for_api() {
  i=0
  until curl -fs "$API_URL/api/v1/health" > /dev/null; do
    i=$((i + 1))
    if [ "$i" -gt 60 ]; then
      echo "La API no responde en $API_URL" >&2
      exit 1
    fi
    sleep 2
  done
}

restart_api() {
  CLOCK_OFFSET_DAYS="$1" docker compose --project-directory "$INFRA" -f "$INFRA/compose.yaml" up -d --force-recreate --wait api
  wait_for_api
}

restart_api -20
node seed.mjs past

restart_api 0
node seed.mjs present
