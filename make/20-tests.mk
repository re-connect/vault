##@ Tests
# v1 and v2 fixtures are incompatible: each suite reloads its own (with purge)
# before running.
test: fixture-v1 test-v1 fixture-v2 test-v2 ## Fixtures + tests v1 puis v2

test-v1: ## Lance PHPUnit sur tests/v1 (fixtures v1 requises)
	@$(PHPUNIT) tests/v1

test-v2: ## Lance PHPUnit sur tests/v2 (fixtures v2 requises)
	@$(PHPUNIT) tests/v2

fixture-v1: ## Charge les fixtures v1 (env test)
	@$(CONSOLE) doctrine:fixtures:load --env=test --group=v1 -n

fixture-v2: ## Charge les fixtures v2 (env test)
	@$(CONSOLE) doctrine:fixtures:load --env=test --group=v2 -n

db-test: ## Recrée la base de test (⚠️ efface les données)
	@$(CONSOLE) doctrine:database:drop --env=test --force --if-exists
	@$(CONSOLE) doctrine:database:create --env=test
	@$(CONSOLE) doctrine:migrations:migrate --env=test -n

##@ Tests parallèles (paratest)
# Only tests/v2 runs in parallel: tests/v1 is 11 tests (~7s), not worth it.
# Each paratest worker gets TEST_TOKEN=1..N and its own database vault_test<N>
# (dbname_suffix in config/packages/doctrine.yaml).
# paratest 6 / PHPUnit 9 on purpose: moving to PHPUnit 11 + paratest 7 is part of the PHP 8.4 upgrade.
PARATEST_PROCESSES ?= 4

# $(call for_each_worker,<command>) : runs <command> once per worker in parallel,
# with TEST_TOKEN set, and prints every worker log if one of them fails
define for_each_worker
	@PIDS=""; \
	for i in $$(seq 1 $(PARATEST_PROCESSES)); do \
		(export TEST_TOKEN=$$i; $(1)) > /tmp/vault-worker-$$i.log 2>&1 & PIDS="$$PIDS $$!"; \
	done; \
	FAIL=0; for PID in $$PIDS; do wait $$PID || FAIL=1; done; \
	if [ $$FAIL -ne 0 ]; then \
		for i in $$(seq 1 $(PARATEST_PROCESSES)); do echo "── worker $$i ──"; cat /tmp/vault-worker-$$i.log; done; \
		rm -f /tmp/vault-worker-*.log; \
		exit 1; \
	fi; \
	rm -f /tmp/vault-worker-*.log
endef

test-parallel: fixture-v1 test-v1 fixture-v2-parallel test-v2-parallel ## v1 en séquentiel puis v2 avec paratest

test-v2-parallel: ## Lance tests/v2 avec paratest (fixtures v2 parallèles requises)
	@$(PARATEST) --processes=$(PARATEST_PROCESSES) --runner=WrapperRunner tests/v2

fixture-v2-parallel: ## Charge les fixtures v2 dans chaque base worker (vault_test1..N)
	@echo "⏳ Fixtures v2 sur $(PARATEST_PROCESSES) bases worker..."
	$(call for_each_worker,$(CONSOLE) doctrine:fixtures:load --env=test --group=v2 -n -q)
	@echo "✅ Fixtures v2 chargées."

db-test-parallel: ## Crée et migre les bases worker vault_test1..N
	@echo "⏳ Bases worker 1 → $(PARATEST_PROCESSES)..."
	$(call for_each_worker,$(CONSOLE) doctrine:database:create --env=test --if-not-exists -q && $(CONSOLE) doctrine:migrations:migrate --env=test -n -q)
	@echo "✅ Bases worker prêtes."
