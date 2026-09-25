# ── Docker helpers (functions & canned recipes) ──────────────────────────────
# No ##@ header: this file defines no target, only functions reused by the
# other make/*.mk files.

# ── Ownership of container-written files (native Linux) ──────────────────────
# The container runs as root: on native Linux, files it writes in the /vault
# bind-mount become root:root on the host. We pass the host UID/GID so the
# entrypoint can hand them back (fix_perms). On macOS, Docker Desktop already
# remaps ownership → vars left empty, fix_perms is a no-op.
ifeq ($(shell uname -s),Linux)
export HOST_UID := $(shell id -u)
export HOST_GID := $(shell id -g)
endif

# $(call compose,<env>)        → application container (main|dev)
compose = docker compose --env-file docker/.docker-versions.$(1) -f docker/$(1)/docker-compose.yml

# $(call compose_test,<env>)   → + test override
compose_test = $(call compose,$(1)) -f docker/$(1)/docker-compose.test.yml

# $(call compose_shared,<svc>) → shared service (db|nginx|minio|clamav)
compose_shared = docker compose --env-file docker/.docker-versions.main -f docker/shared/docker-compose.$(1).yml

# $(status_end) : status view printed at the end of a *-up target. The refreshing loop
# never returns, so chained targets (docker-all-up) set NO_STATUS_LOOP=1 to get a
# one-shot status instead.
status_end = $(MAKE) $(if $(NO_STATUS_LOOP),docker-status,docker-status-loop)

# $(call boot_env,<up>,<down>) : stops <down>, (re)starts <up>, replays the entrypoint
define boot_env
	$(MAKE) docker-$(2)-down
	$(MAKE) docker-$(1)-up
endef

# $(call wait_app_ready,<container>) : waits until the entrypoint of <container> has
# fully finished (/tmp/ready). Unlike RP, waiting for vendor/ only is not enough: the
# dev entrypoint then builds assets in the shared public/, which the test container
# would otherwise rebuild concurrently on first boot.
define wait_app_ready
	@echo "⏳ $(1) : attente de la fin de l'entrypoint (composer, migrations, assets)..."
	@for i in $$(seq 1 300); do \
		if docker exec $(1) test -f /tmp/ready 2>/dev/null; then \
			echo "✅ $(1) : prêt."; \
			exit 0; \
		fi; \
		sleep 2; \
	done; \
	echo "⚠️  $(1) : timeout (10 min) — on démarre le test quand même. Logs : docker logs $(1)"
endef

# $(call ensure_running,<container>,<target>) : starts <target> if <container> is down
define ensure_running
	@docker ps --filter name=^$(1)$$ --filter status=running --format '{{.Names}}' | grep -q '^$(1)$$' \
		|| (echo "⚠️  $(1) non démarré, démarrage..." && $(MAKE) $(2))
endef
