.PHONY          :

SYMFONY         = symfony
CONSOLE         = $(SYMFONY) console
COMPOSER        = $(SYMFONY) composer
BIN             = ./vendor/bin
PHPUNIT         = $(BIN)/simple-phpunit
PHPSTAN         = $(BIN)/phpstan
RECTOR          = $(BIN)/rector
PHP_CS_FIXER    = $(BIN)/php-cs-fixer
PHPSTAN_LEVEL   = 1

cs: stan fixer

stan:
	@$(PHPSTAN) analyse -l $(PHPSTAN_LEVEL) --xdebug

rector:
	@$(RECTOR) process --clear-cache

fixer:
	@$(PHP_CS_FIXER) fix src --allow-risky=yes --using-cache=no
	@$(PHP_CS_FIXER) fix tests --allow-risky=yes --using-cache=no

deploy:
	@$(CONSOLE) deploy preprod

dep: deploy
