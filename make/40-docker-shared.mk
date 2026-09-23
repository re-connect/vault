##@ Docker - Statut
docker-status: ## Affiche l'état de tous les conteneurs vault
	@bash scripts/docker/docker-status.sh

docker-status-loop: ## Affiche le statut Docker en boucle (3s, Ctrl-C pour quitter)
	@while true; do clear; bash scripts/docker/docker-status.sh; sleep 3; done

##@ Docker - Tous les environnements
docker-all-up: ## Démarre les services partagés puis main et dev (démarrage séquentiel)
	@# Sequential on purpose: both containers build assets in the same shared public/
	$(MAKE) docker-main-up NO_STATUS_LOOP=1
	$(MAKE) docker-dev-up NO_STATUS_LOOP=1
	$(MAKE) docker-status-loop

docker-all-down: ## Coupe main, dev (et leurs tests) et tous les services partagés
	$(MAKE) -j2 docker-main-down docker-dev-down
	$(MAKE) -j2 docker-minio-down docker-db-down
	$(MAKE) docker-clamav-down
	$(MAKE) docker-nginx-down

##@ Docker - DB partagée
docker-db-ensure: ## Vérifie que vault-db tourne, sinon le démarre
	@if ! docker ps --format '{{.Names}}' | grep -q '^vault-db$$'; then \
		echo "⚠️  vault-db non démarré, démarrage en cours..."; \
		$(MAKE) docker-db-up; \
	fi
	@# Wait for a real root login, not just a ping: on first boot the image runs a
	@# temporary server (which answers ping) before setting the root password.
	@for i in $$(seq 1 60); do \
		if docker exec vault-db mysql -u root -pvault -e "SELECT 1" >/dev/null 2>&1; then \
			echo "✅ vault-db prêt."; \
			exit 0; \
		fi; \
		[ "$$i" -eq 1 ] && echo "⏳ Attente que vault-db soit prêt..."; \
		sleep 2; \
	done; \
	echo "❌ vault-db : connexion root impossible après 2 min."; \
	echo "   Logs : make docker-db-logs"; \
	echo "   Si docker/shared/data/mysql a été initialisé avec un autre mot de passe et que les données sont jetables :"; \
	echo "   make docker-db-down && rm -rf docker/shared/data/mysql"; \
	exit 1

docker-db-setup: docker-db-ensure ## Crée les bases vault_main / vault_dev et les grants
	docker exec vault-db mysql -u root -pvault -e \
		"CREATE DATABASE IF NOT EXISTS vault_main CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; \
		 CREATE DATABASE IF NOT EXISTS vault_dev  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; \
		 GRANT ALL PRIVILEGES ON vault_main.* TO 'vault'@'%'; \
		 GRANT ALL PRIVILEGES ON vault_dev.*  TO 'vault'@'%'; \
		 FLUSH PRIVILEGES;"

docker-db-reset: docker-db-ensure ## Recrée les bases vault_main et vault_dev (⚠️ efface les données)
	docker exec vault-db mysql -u root -pvault -e \
		"DROP DATABASE IF EXISTS vault_main; \
		 DROP DATABASE IF EXISTS vault_dev; \
		 CREATE DATABASE vault_main CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; \
		 CREATE DATABASE vault_dev  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; \
		 GRANT ALL PRIVILEGES ON vault_main.* TO 'vault'@'%'; \
		 GRANT ALL PRIVILEGES ON vault_dev.*  TO 'vault'@'%'; \
		 FLUSH PRIVILEGES;"

docker-db-up: ## Démarre le conteneur MariaDB partagé (vault-db)
	$(call compose_shared,db) up -d

docker-db-down: ## Coupe le conteneur MariaDB partagé
	$(call compose_shared,db) down 2>/dev/null || true

docker-db-logs: ## Affiche les logs MariaDB en temps réel
	docker logs -f vault-db

docker-db-bash: ## Ouvre un shell dans le conteneur MariaDB
	docker exec -it vault-db bash

docker-db-mysql: ## Ouvre un client MySQL en root dans vault-db
	docker exec -it vault-db mysql -u root -pvault

docker-db-mysql-vault: ## Ouvre un client MySQL en tant qu'user vault dans vault-db
	docker exec -it vault-db mysql -u vault -pvault

##@ Docker - Nginx partagé
docker-nginx-up: ## Démarre le conteneur nginx
	$(call compose_shared,nginx) up -d

docker-nginx-down: ## Arrête le conteneur nginx
	$(call compose_shared,nginx) down 2>/dev/null || true

docker-nginx-logs: ## Logs nginx en temps réel
	docker logs -f vault-nginx

docker-nginx-ensure: ## Vérifie que nginx est démarré, le démarre si nécessaire
	$(call ensure_running,vault-nginx,docker-nginx-up)

docker-nginx-trust-cert: ## Importe le certificat self-signed sur l'hôte (nécessite sudo)
	docker cp vault-nginx:/etc/nginx/certs/vault.local.crt /tmp/vault.local.crt
	@if [ "$$(uname)" = "Darwin" ]; then \
		sudo security add-trusted-cert -d -r trustRoot -k /Library/Keychains/System.keychain /tmp/vault.local.crt; \
	else \
		sudo cp /tmp/vault.local.crt /usr/local/share/ca-certificates/vault.local.crt; \
		sudo update-ca-certificates; \
	fi
	@echo "✅ Certificat importé — relancez votre navigateur"

docker-nginx-hosts-install: ## Ajoute les entrées /etc/hosts manquantes (nécessite sudo)
	@# Match the exact host name: a plain substring match would find minio.vault.local
	@# inside console.minio.vault.local and never add it
	@for HOST in main.vault.local dev.vault.local minio.vault.local console.minio.vault.local; do \
		if grep -qE "^[^#]*[[:space:]]$$HOST([[:space:]]|$$)" /etc/hosts; then \
			echo "✅ $$HOST déjà présent"; \
		else \
			sudo sh -c "echo '127.0.0.1 $$HOST' >> /etc/hosts"; \
			echo "➕ $$HOST ajouté"; \
		fi; \
	done

##@ Docker - MinIO partagé
docker-minio-ensure: docker-nginx-ensure ## Vérifie que MinIO est démarré, le démarre si nécessaire
	$(call ensure_running,vault-minio,docker-minio-up)

docker-minio-setup: docker-minio-ensure ## Crée les buckets MinIO vault-main et vault-dev s'ils n'existent pas
	@# The server needs a few seconds after start before accepting connections
	@for i in $$(seq 1 30); do \
		if docker exec vault-minio mc alias set vault http://localhost:9000 minioadmin minioadmin >/dev/null 2>&1; then \
			break; \
		fi; \
		[ "$$i" -eq 1 ] && echo "⏳ Attente que vault-minio soit prêt..."; \
		if [ "$$i" -eq 30 ]; then \
			echo "❌ vault-minio : injoignable après 1 min. Logs : make docker-minio-logs"; \
			exit 1; \
		fi; \
		sleep 2; \
	done
	@# One bucket per environment, like the databases. S3 bucket names allow no underscore.
	@docker exec vault-minio mc mb --ignore-existing vault/vault-main vault/vault-dev

docker-minio-up: ## Démarre le conteneur MinIO partagé
	$(call compose_shared,minio) up -d

docker-minio-down: ## Arrête le conteneur MinIO partagé
	$(call compose_shared,minio) down 2>/dev/null || true

docker-minio-logs: ## Affiche les logs MinIO en temps réel
	docker logs -f vault-minio

##@ Docker - ClamAV partagé
docker-clamav-ensure: ## Vérifie que ClamAV est démarré, le démarre si nécessaire
	$(call ensure_running,vault-clamav,docker-clamav-up)

docker-clamav-up: ## Démarre le conteneur ClamAV partagé
	$(call compose_shared,clamav) up -d

docker-clamav-down: ## Arrête le conteneur ClamAV partagé
	$(call compose_shared,clamav) down 2>/dev/null || true

docker-clamav-logs: ## Affiche les logs ClamAV en temps réel
	docker logs -f vault-clamav
