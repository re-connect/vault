.DEFAULT_GOAL := help

# On the host, `symfony php` picks the version declared in .php-version (8.3);
# otherwise (container without Symfony CLI, CI) fall back to the `php` in PATH.
PHP          ?= $(shell command -v symfony >/dev/null 2>&1 && echo 'symfony php' || echo php)
CONSOLE       = $(PHP) bin/console
BIN           = ./vendor/bin
PHPUNIT       = $(PHP) $(BIN)/phpunit
PARATEST      = $(PHP) $(BIN)/paratest
PHPSTAN       = $(PHP) $(BIN)/phpstan
RECTOR        = $(PHP) $(BIN)/rector
PHP_CS_FIXER  = $(PHP) $(BIN)/php-cs-fixer
DEPLOYER      = $(PHP) $(BIN)/dep

##@ Aide
help: ## Affiche cette aide (groupée par catégorie)
	@awk 'BEGIN {FS = ":.*##"} \
		/^##@/ { printf "\n\033[1m%s\033[0m\n\033[90m────────────────────────────────────────────────────────\033[0m\n", substr($$0, 5); next } \
		/^[a-zA-Z0-9_-]+:.*?##/ { printf "  \033[36m%-30s\033[0m %s\n", $$1, $$2 }' \
		$(MAKEFILE_LIST)

# ── Split by domain (make/*.mk) ──────────────────────────────────────────────
# Help display order follows the numeric prefix of the files.
include $(sort $(wildcard make/*.mk))

# Automatically mark every documented target (`target: ## …`) as phony
.PHONY: $(shell grep -hE '^[a-zA-Z0-9_-]+:.*?##' $(MAKEFILE_LIST) | sed 's/:.*//')
