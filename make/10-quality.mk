##@ Qualité
cs: stan rector fixer ## PHPStan + Rector + CS Fixer

stan: ## Analyse statique PHPStan (niveau défini dans phpstan.dist.neon)
	@$(PHPSTAN) analyse -c phpstan.dist.neon

rector: ## Rector
	@$(RECTOR) process --clear-cache

fixer: fixer-src fixer-tests ## CS Fixer sur src/ et tests/

fixer-src: ## CS Fixer sur src/
	@$(PHP_CS_FIXER) fix src --allow-risky=yes --using-cache=no $(CS_FIXER_FLAGS)

fixer-tests: ## CS Fixer sur tests/
	@$(PHP_CS_FIXER) fix tests --allow-risky=yes --using-cache=no $(CS_FIXER_FLAGS)

composer-lock-realign: ## Réaligne le content-hash de composer.lock (update --lock + validate + commit)
	composer update --lock
	composer validate --strict --no-check-all
	@if git diff --quiet composer.lock; then \
		echo "🟢 composer.lock déjà aligné, rien à committer."; \
	else \
		git add composer.lock; \
		git commit -m "chore(deps): realign composer.lock content-hash"; \
		echo "✅ composer.lock réaligné et committé."; \
	fi
