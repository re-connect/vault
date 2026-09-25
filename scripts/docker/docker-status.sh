#!/bin/bash

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m'

SHARED_CONTAINERS="vault-db vault-minio vault-clamav vault-nginx"
ENVS="main dev main-test dev-test"
SEPARATOR="──────────────────────────────────────────────────────────────────────────────────────────────────"

# ── Check that Docker is running ─────────────────────────────────────────────
if ! docker info > /dev/null 2>&1; then
    printf "\n${RED}${BOLD} ⚠️   Docker ne répond pas.${NC}\n"
    printf "${YELLOW}      Vérifiez que le daemon Docker est bien démarré.${NC}\n\n"
    exit 1
fi

ALL_CONTAINERS=$(docker ps -a --format '{{.Names}}')

exists() {
    echo "$ALL_CONTAINERS" | grep -q "^$1$"
}

get_url() {
    local CONTAINER=$1
    case "$CONTAINER" in
        vault-minio)         echo "https://minio.vault.local:8443" ;;
        vault-main)          echo "https://main.vault.local:8443" ;;
        vault-dev)           echo "https://dev.vault.local:8443" ;;
        vault-main-mailpit)  echo "http://localhost:8029" ;;
        vault-dev-mailpit)   echo "http://localhost:8028" ;;
        *)                   echo "—" ;;
    esac
}

get_status() {
    docker inspect "$1" --format '{{.State.Status}}' 2>/dev/null || echo "—"
}

# Application containers create /tmp/ready at the end of their entrypoint
get_entry() {
    local CONTAINER=$1
    local SVC=$2
    if [ "$SVC" != "app" ]; then
        echo "—"
    elif docker exec "$CONTAINER" test -f /tmp/ready 2>/dev/null; then
        echo "prêt"
    else
        echo "en attente"
    fi
}

color_status() {
    case "$1" in
        running)            echo "${GREEN}$1${NC}" ;;
        exited|dead|paused) echo "${RED}$1${NC}" ;;
        *)                  echo "${YELLOW}$1${NC}" ;;
    esac
}

color_entry() {
    case "$1" in
        "prêt")       echo "${GREEN}$1${NC}" ;;
        "en attente") echo "${YELLOW}$1${NC}" ;;
        *)            echo "$1" ;;
    esac
}

print_row() {
    local ENV_COL=$1 SVC=$2 STATUS=$3 ENTRY=$4 URL=$5
    local ENTRY_LEN
    ENTRY_LEN=$(printf "%s" "$ENTRY" | wc -m)
    printf "%-15s %-20s " "$ENV_COL" "$SVC"
    printf "$(color_status "$STATUS")"
    printf "%$((13 - ${#STATUS}))s" ""
    printf "$(color_entry "$ENTRY")"
    printf "%$((15 - ENTRY_LEN))s" ""
    printf "%-40s\n" "$URL"
}

# ── Header ─────────────────────────────────────────────────────────────────────
printf "\n"
printf "${CYAN}${BOLD}╔══════════════════════════════════════════╗${NC}\n"
printf "${CYAN}${BOLD}║                 Vault                    ║${NC}\n"
printf "${CYAN}${BOLD}║         Environnements Docker            ║${NC}\n"
printf "${CYAN}${BOLD}╚══════════════════════════════════════════╝${NC}\n"
printf "\n"

if ! echo "$ALL_CONTAINERS" | grep -q '^vault-'; then
    printf "${RED} ⚠️   Aucun environnement actif${NC}\n\n"
    exit 0
fi

printf "${CYAN}${BOLD}%-15s %-20s %-12s %-14s %-40s${NC}\n" "Environnement" "Service" "Statut" "Entrypoint" "URL"
printf "${CYAN}${SEPARATOR}${NC}\n"

# ── Shared services ───────────────────────────────────────────────────────────
for CONTAINER in $SHARED_CONTAINERS; do
    exists "$CONTAINER" || continue
    SVC=${CONTAINER#vault-}
    ENV_LABEL=$(echo "$SVC" | tr '[:lower:]' '[:upper:]')
    print_row "$ENV_LABEL" "$SVC" "$(get_status "$CONTAINER")" "—" "$(get_url "$CONTAINER")"
done
printf "${CYAN}${SEPARATOR}${NC}\n"

# ── Application environments ──────────────────────────────────────────────────
for ENV in $ENVS; do
    ENV_LABEL=$(echo "$ENV" | tr '[:lower:]' '[:upper:]' | tr '-' ' ')
    PRINTED_ANY=0

    for SUFFIX in "" "-db" "-mailpit"; do
        CONTAINER="vault-${ENV}${SUFFIX}"
        exists "$CONTAINER" || continue

        case "$SUFFIX" in
            "")         SVC="app" ;;
            "-db")      SVC="db" ;;
            "-mailpit") SVC="mailpit" ;;
        esac

        if [ "$PRINTED_ANY" -eq 0 ]; then ENV_COL="$ENV_LABEL"; else ENV_COL=""; fi
        PRINTED_ANY=1

        print_row "$ENV_COL" "$SVC" "$(get_status "$CONTAINER")" "$(get_entry "$CONTAINER" "$SVC")" "$(get_url "$CONTAINER")"
    done

    if [ "$PRINTED_ANY" -eq 1 ]; then
        printf "${CYAN}${SEPARATOR}${NC}\n"
    fi
done

printf "\n"
