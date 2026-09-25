##@ Git
git-clear-local-branch: ## Nettoyer les remotes obsolètes + supprimer les branches locales dont le remote a été supprimé
	# Drop stale remote-tracking refs, then delete local branches marked ': gone]'
	@git fetch --prune
	@git branch -vv | grep ': gone]' | awk '{print $$1}' | xargs -r git branch -d
	@echo ""
	@echo "💡 Branches avec commits non mergés non supprimées ci-dessus ?"
	@echo "   Pour forcer leur suppression, exec la commande :"
	@echo "   git branch -vv | grep ': gone]' | awk '{print \$$1}' | xargs git branch -D"

git-commit-auto-fixes: ## Stager tout + commit auto des corrections rector/cs-fixer/phpstan
	@git add .
	@git commit -m "chore: apply rector, cs-fixer and phpstan auto-fixes"
