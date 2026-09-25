##@ Déploiement
deploy-preprod: ## Déploie en préprod (Deployer, hôte vault-pp)
	@$(DEPLOYER) deploy vault-pp

deploy-prod: ## Déploie en prod (Deployer, hôte vault-prod)
	@$(DEPLOYER) deploy vault-prod
