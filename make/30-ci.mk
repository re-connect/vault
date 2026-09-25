##@ CI
ci: cs test ## Suite qualité complète + tests (sur l'hôte)

ci-main: ## Lance la CI dans le conteneur vault-main-test
	@bash scripts/ci/ci-docker.sh main

ci-dev: ## Lance la CI dans le conteneur vault-dev-test
	@bash scripts/ci/ci-docker.sh dev
