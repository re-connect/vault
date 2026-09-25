##@ Docker - Switch / Reload
docker-switch-main: ## Bascule sur l'environnement main (arrête dev et démarre main)
	$(call boot_env,main,dev)

docker-switch-dev: ## Bascule sur l'environnement dev (arrête main et démarre dev)
	$(call boot_env,dev,main)

docker-main-reload: ## Recharge main (down + up → rejoue l'entrypoint)
	$(call boot_env,main,main)

docker-dev-reload: ## Recharge dev (down + up → rejoue l'entrypoint)
	$(call boot_env,dev,dev)

##@ Docker - Main
docker-main-build: ## Rebuild l'image vault-main (no cache)
	$(call compose,main) build --no-cache

docker-main-up: docker-db-setup docker-minio-setup docker-clamav-ensure ## Démarre vault-main puis vault-main-test (une fois main prêt)
	$(call compose,main) up -d --build
	$(call wait_app_ready,vault-main)
	$(MAKE) docker-main-test-up
	$(status_end)

docker-main-down: docker-main-test-down ## Coupe vault-main (vault-main-test d'abord)
	$(call compose,main) down 2>/dev/null || true

docker-main-bash: ## Ouvre un shell dans vault-main
	docker exec -it vault-main bash

docker-main-logs: ## Affiche les logs Symfony vault-main en temps réel
	docker exec vault-main tail -f /vault/var/log/dev.log

docker-main-entrypoint-logs: ## Affiche les logs de l'entrypoint vault-main
	docker logs -f vault-main

##@ Docker - Main Test
docker-main-test-up: ## Démarre vault-main-test (et sa BDD en mémoire)
	$(call compose_test,main) up -d

docker-main-test-down: ## Coupe vault-main-test
	$(call compose_test,main) down 2>/dev/null || true

docker-main-test-bash: ## Ouvre un shell dans vault-main-test
	docker exec -it vault-main-test bash

docker-main-test-logs: ## Affiche les logs Symfony vault-main-test en temps réel
	docker exec vault-main-test tail -f /vault/var/log/test.log

docker-main-test-entrypoint-logs: ## Affiche les logs de l'entrypoint vault-main-test
	docker logs -f vault-main-test

##@ Docker - Dev
docker-dev-build: ## Rebuild l'image vault-dev (no cache)
	$(call compose,dev) build --no-cache

docker-dev-up: docker-db-setup docker-minio-setup docker-clamav-ensure ## Démarre vault-dev puis vault-dev-test (une fois dev prêt)
	$(call compose,dev) up -d --build
	$(call wait_app_ready,vault-dev)
	$(MAKE) docker-dev-test-up
	$(status_end)

docker-dev-down: docker-dev-test-down ## Coupe vault-dev (vault-dev-test d'abord)
	$(call compose,dev) down 2>/dev/null || true

docker-dev-bash: ## Ouvre un shell dans vault-dev
	docker exec -it vault-dev bash

docker-dev-logs: ## Affiche les logs Symfony vault-dev en temps réel
	docker exec vault-dev tail -f /vault/var/log/dev.log

docker-dev-entrypoint-logs: ## Affiche les logs de l'entrypoint vault-dev
	docker logs -f vault-dev

##@ Docker - Dev Test
docker-dev-test-up: ## Démarre vault-dev-test (et sa BDD en mémoire)
	$(call compose_test,dev) up -d

docker-dev-test-down: ## Coupe vault-dev-test
	$(call compose_test,dev) down 2>/dev/null || true

docker-dev-test-bash: ## Ouvre un shell dans vault-dev-test
	docker exec -it vault-dev-test bash

docker-dev-test-logs: ## Affiche les logs Symfony vault-dev-test en temps réel
	docker exec vault-dev-test tail -f /vault/var/log/test.log

docker-dev-test-entrypoint-logs: ## Affiche les logs de l'entrypoint vault-dev-test
	docker logs -f vault-dev-test
