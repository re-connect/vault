#!/bin/bash
set -e

log() { echo "$(date +%H:%M:%S) $*"; }

log "▶ APP_ENV=${APP_ENV}"

# ── Host ownership ───────────────────────────────────────────────────────────
# On native Linux, files written by root in the bind-mount become root:root on
# the host: hand them back to HOST_UID/HOST_GID (no-op on macOS).
fix_perms() {
    [ -n "${HOST_UID:-}" ] || return 0
    chown -R "${HOST_UID}:${HOST_GID:-$HOST_UID}" "$@" 2>/dev/null || true
}

# ── Xdebug ─────────────────────────────────────────────────────────────────────
# Extension always loaded, mode driven by XDEBUG_MODE (off by default)
XDEBUG_MODE="${XDEBUG_MODE:-off}"
{
    echo "xdebug.mode=${XDEBUG_MODE}"
    echo "xdebug.client_host=host.docker.internal"
    echo "xdebug.start_with_request=yes"
} > "$PHP_INI_DIR/conf.d/xdebug.ini"
log "▶ Xdebug mode=${XDEBUG_MODE}"

# ── Composer install ────────────────────────────────────────────────────────────
# Skipped when vendor/ is up to date: compares the composer.lock hash with the
# last known hash stored in vendor/.composer-lock-hash
LOCK_HASH=$(md5sum /vault/composer.lock 2>/dev/null | cut -d' ' -f1 || echo "none")
STORED_HASH=$(cat /vault/vendor/.composer-lock-hash 2>/dev/null || echo "none")

if [ "$LOCK_HASH" != "$STORED_HASH" ]; then
    log "⏳ composer install..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
    echo "$LOCK_HASH" > /vault/vendor/.composer-lock-hash
    fix_perms /vault/vendor
    log "✅ Dépendances installées."
else
    log "✅ vendor/ à jour — composer install ignoré."
fi

# ── OAuth2 keys (league/oauth2-server-bundle) ──────────────────────────────────
# Versioned test keys, same as CI — local use only
if [ ! -f /vault/var/oauth/private.key ]; then
    mkdir -p /vault/var/oauth
    cp /vault/tests/keys/* /vault/var/oauth/
    log "✅ Clés OAuth copiées depuis tests/keys."
fi

# ── Database readiness ─────────────────────────────────────────────────────────
# Waits for a real login on the DATABASE_URL server (without selecting a database,
# which may not exist yet). A plain ping is not enough: on first boot MariaDB runs a
# temporary server before applying its credentials.
wait_for_db() {
    log "⏳ Attente de la base de données..."
    for _ in $(seq 1 60); do
        if php -r '$u = parse_url(getenv("DATABASE_URL")); new PDO(sprintf("mysql:host=%s;port=%d", $u["host"], $u["port"] ?? 3306), urldecode($u["user"]), urldecode($u["pass"] ?? ""));' 2>/dev/null; then
            log "✅ Base de données joignable."
            return 0
        fi
        sleep 2
    done
    log "❌ Base de données injoignable après 2 min (DATABASE_URL)."
    return 1
}

# ── Assets ─────────────────────────────────────────────────────────────────────
# Required by Twig pages (Encore manifest) in both dev and test
build_assets() {
    log "⏳ Build assets..."
    php bin/console fos:js-routing:dump --format=json --target=public/js/fos_js_routes.json -q
    yarn install --frozen-lockfile --cache-folder "${YARN_CACHE_DIR:-/tmp/yarn-cache}"
    yarn dev
    fix_perms /vault/public/build /vault/public/js /vault/node_modules
    log "✅ Assets compilés."
}

# ── DEV environment ─────────────────────────────────────────────────────────────
if [ "$APP_ENV" = "dev" ]; then

    # ── JWT keys (lexik/jwt-authentication-bundle) ─────────────────────────────
    if [ ! -f /vault/config/jwt/private.pem ]; then
        log "⏳ Génération des clés JWT..."
        php bin/console lexik:jwt:generate-keypair -q
        log "✅ Clés JWT générées."
    fi

    # ── Migrations ─────────────────────────────────────────────────────────────
    wait_for_db
    log "⏳ Migrations..."
    php bin/console doctrine:database:create --if-not-exists -q
    php bin/console doctrine:migrations:migrate -n -q
    log "✅ BDD prête."

    build_assets

    # ── Symfony server ─────────────────────────────────────────────────────────
    log "⏳ Démarrage du serveur Symfony..."
    symfony serve --no-tls --port=8010 --daemon --listen-ip=0.0.0.0
    log "✅ Serveur Symfony démarré sur http://localhost:8010"

    # ── nginx certificate ───────────────────────────────────────────────────────
    # Trust the self-signed certificate for outgoing HTTPS calls (MinIO)
    if [ -f /etc/nginx/certs/vault.local.crt ]; then
        cp /etc/nginx/certs/vault.local.crt /usr/local/share/ca-certificates/
        update-ca-certificates --fresh > /dev/null 2>&1
    fi

    # var/ (cache, log) lives on the bind-mount and is rewritten on every boot
    fix_perms /vault/var

    touch /tmp/ready
fi

# ── TEST environment ────────────────────────────────────────────────────────────
if [ "$APP_ENV" = "test" ]; then

    # ── Databases ──────────────────────────────────────────────────────────────
    # Fixtures are not loaded here: v1 and v2 fixtures are incompatible, each test
    # suite loads its own (make test / make test-parallel).
    wait_for_db
    log "⏳ Initialisation BDD de test..."
    php bin/console doctrine:database:create --env=test --if-not-exists -q
    php bin/console doctrine:migrations:migrate --env=test -n -q
    log "✅ BDD de test prête."

    # One database per paratest worker: vault_test1..N (TEST_TOKEN dbname suffix)
    log "⏳ Bases paratest (1 → ${PARATEST_PROCESSES:-4})..."
    make db-test-parallel PHP=php PARATEST_PROCESSES="${PARATEST_PROCESSES:-4}"
    log "✅ Bases paratest prêtes."

    # ── Assets ─────────────────────────────────────────────────────────────────
    # public/ is shared with the dev container through the bind-mount: only build
    # when missing, so both containers never run yarn at the same time
    if [ -f /vault/public/build/manifest.json ] && [ -f /vault/public/js/fos_js_routes.json ]; then
        log "✅ Assets déjà compilés."
    else
        build_assets
    fi

    # ── Cache warmup ───────────────────────────────────────────────────────────
    log "⏳ Cache warmup..."
    php bin/console cache:warmup --env=test -q
    log "✅ Cache prêt."

    touch /tmp/ready
fi

exec "$@"
