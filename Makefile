.PHONY          :

SYMFONY         = symfony
CONSOLE         = $(SYMFONY) console
COMPOSER        = $(SYMFONY) composer
BIN             = ./vendor/bin
DEPLOYER      	= $(BIN)/dep
PHPUNIT         = $(BIN)/simple-phpunit
PHPSTAN         = $(BIN)/phpstan
RECTOR          = $(BIN)/rector
PHP_CS_FIXER    = $(BIN)/php-cs-fixer
PHPSTAN_LEVEL   = 7

cs: stan rector fixer
ci: cs test

stan:
	@$(PHPSTAN) analyse -l $(PHPSTAN_LEVEL) --xdebug

rector:
	@$(RECTOR) process --clear-cache

fixer:
	PHP_CS_FIXER_IGNORE_ENV=true @$(PHP_CS_FIXER) fix src --allow-risky=yes --using-cache=no
	PHP_CS_FIXER_IGNORE_ENV=true @$(PHP_CS_FIXER) fix tests --allow-risky=yes --using-cache=no

deploy-preprod:
	@$(DEPLOYER) deploy stage=preprod

deploy-prod:
	@$(CONSOLE) deploy prod

fixture-v1:
	@$(CONSOLE) doctrine:fixtures:load --env=test --group=v1 -n

fixture-v2:
	@$(CONSOLE) doctrine:fixtures:load --env=test --group=v2 -n

test: fixture-v1 test-v1 fixture-v2 test-v2

test-v1:
	@$(PHPUNIT) tests/v1

test-v2:
	@$(PHPUNIT) tests/v2
