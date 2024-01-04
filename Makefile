.PHONY          :

SYMFONY         = symfony
CONSOLE         = $(SYMFONY) console
COMPOSER        = $(SYMFONY) composer
BIN             = ./vendor/bin
PHPUNIT         = $(BIN)/simple-phpunit
PHPSTAN         = $(BIN)/phpstan
RECTOR          = $(BIN)/rector
PHP_CS_FIXER    = $(BIN)/php-cs-fixer
PHPSTAN_LEVEL   = 7

cs: stan rector fixer

stan:
	@$(PHPSTAN) analyse -l $(PHPSTAN_LEVEL) --xdebug

rector:
	@$(RECTOR) process --clear-cache

fixer:
	@$(PHP_CS_FIXER) fix src --allow-risky=yes --using-cache=no
	@$(PHP_CS_FIXER) fix tests --allow-risky=yes --using-cache=no

deploy:
	@$(CONSOLE) deploy preprod

fixture-v1:
	@$(CONSOLE) doctrine:fixtures:load --env=test --group=v1 -n

fixture-v2:
	@$(CONSOLE) doctrine:fixtures:load --env=test --group=v2 -n

test-v1:
	@$(PHPUNIT) tests/v1

test-v2:
	@$(PHPUNIT) tests/v2

test: fixture-v1 test-v1 fixture-v2 test-v2

dep: deploy
