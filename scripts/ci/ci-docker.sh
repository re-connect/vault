#!/bin/bash
set -e

# ── Colors ─────────────────────────────────────────────────────────────────────
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m'

START=$SECONDS
CI_EXIT=0

# ── Guards ─────────────────────────────────────────────────────────────────────
guard() {
    if ! eval "$1" > /dev/null 2>&1; then
        printf "${RED}${BOLD}❌  $2${NC}\n"
        exit 1
    fi
}

guard "command -v docker" "Docker n'est pas installé"
guard "command -v make"   "Make n'est pas installé"
guard "docker info"       "Docker n'est pas démarré"

# ── Environment ────────────────────────────────────────────────────────────────
ARG_ENV=$1

case "$ARG_ENV" in
    main) ENV_NAME="MAIN" ;;
    dev)  ENV_NAME="DEV" ;;
    *)
        printf "${RED}Environnement invalide -- utilise make ci-main ou make ci-dev${NC}\n"
        exit 1
        ;;
esac
TEST_CONTAINER="vault-${ARG_ENV}-test"

guard "docker ps --format '{{.Names}}' | grep -q '^${TEST_CONTAINER}$'" \
    "Conteneur ${TEST_CONTAINER} non actif -- lance make docker-${ARG_ENV}-up (ou docker-${ARG_ENV}-test-up) d'abord"

guard "docker exec ${TEST_CONTAINER} test -f /tmp/ready" \
    "Entrypoint de ${TEST_CONTAINER} non terminé -- attends que le conteneur soit prêt (make ds)"

# ── Container freshness ────────────────────────────────────────────────────────
# A container started on a previous day may run with a stale DB schema / cache
STARTED_DATE=$(docker inspect "$TEST_CONTAINER" --format '{{.State.StartedAt}}' 2>/dev/null | cut -c1-10)
TODAY=$(date -u +%Y-%m-%d)

if [ "$STARTED_DATE" != "$TODAY" ]; then
    printf "${YELLOW}⚠️  ${TEST_CONTAINER} tourne depuis le ${STARTED_DATE} — redémarrage...${NC}\n"
    make "docker-${ARG_ENV}-test-down" 2>/dev/null || true
    make "docker-${ARG_ENV}-test-up"

    printf "${YELLOW}⏳ Attente que l'entrypoint soit prêt...${NC}\n"
    until docker exec "$TEST_CONTAINER" test -f /tmp/ready 2>/dev/null; do
        sleep 2
    done
    printf "${GREEN}✅ Conteneur prêt.${NC}\n\n"
fi

# ── Header ─────────────────────────────────────────────────────────────────────
printf "\n"
printf "${CYAN}${BOLD}╔══════════════════════════════════════════╗${NC}\n"
printf "${CYAN}${BOLD}║                 Vault                    ║${NC}\n"
printf "${CYAN}${BOLD}║%*s%-*s║${NC}\n" 9 "" 33 "CI Docker / ${ENV_NAME}"
printf "${CYAN}${BOLD}║        Rector · Fixer · Stan · Test      ║${NC}\n"
printf "${CYAN}${BOLD}╚══════════════════════════════════════════╝${NC}\n"
printf "\n"

# ── Final result ───────────────────────────────────────────────────────────────
print_result() {
    ELAPSED=$((SECONDS - START))
    if [ "$CI_EXIT" -ne 0 ]; then
        COLOR=$RED; LABEL="❌  CI ÉCHOUÉE"
    else
        COLOR=$GREEN; LABEL="✅  CI RÉUSSIE"
    fi
    printf "\n"
    printf "${COLOR}${BOLD}╔══════════════════════════════════════════╗${NC}\n"
    printf "${COLOR}${BOLD}             %s${NC}\n" "$LABEL"
    printf "${COLOR}${BOLD}          Durée totale : %02d:%02d${NC}\n" $((ELAPSED/60)) $((ELAPSED%60))
    printf "${COLOR}${BOLD}╚══════════════════════════════════════════╝${NC}\n"
}

# ── Run — quality then tests, sequentially ─────────────────────────────────────
docker exec "$TEST_CONTAINER" bash scripts/ci/ci-quality.sh || CI_EXIT=$?

if [ "$CI_EXIT" -eq 0 ]; then
    docker exec "$TEST_CONTAINER" bash scripts/ci/ci-test.sh || CI_EXIT=$?
fi

print_result

exit $CI_EXIT
