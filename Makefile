.PHONY: setup up down build install migrate migrate-test test test-unit test-integration \
        phpstan cs-fix cs-check logs logs-app shell ci hooks create-admin

setup: build up install migrate hooks

up:
	docker compose up -d

down:
	docker compose down

build:
	docker compose build --no-cache

install:
	docker compose exec php composer install

migrate:
	docker compose exec php php config/migrate.php

migrate-test:
	docker compose exec php-test php config/migrate-test.php

test:
	docker compose exec php vendor/bin/phpunit --configuration phpunit.xml --colors=always

test-unit:
	docker compose exec php vendor/bin/phpunit --configuration phpunit.xml --testsuite Unit

test-integration:
	docker compose exec php-test vendor/bin/phpunit --configuration phpunit.integration.xml --testsuite Integration

phpstan:
	docker compose exec php vendor/bin/phpstan analyse --memory-limit=256M

cs-fix:
	docker compose exec php vendor/bin/php-cs-fixer fix  --allow-risky=yes

cs-check:
	docker compose exec php vendor/bin/php-cs-fixer fix --dry-run --diff --allow-risky=yes

logs:
	docker compose logs -f

logs-app:
	tail -f logs/app.log | python3 -m json.tool

shell:
	docker compose exec php bash

# Install the git pre-push hook locally
hooks:
	@if [ -d .git ]; then \
		cp .git-hooks/pre-push .git/hooks/pre-push && chmod +x .git/hooks/pre-push; \
	else \
		echo "Skipping git hooks: not a git repository."; \
	fi

# Seed the first admin user — interactive password prompt (hidden + confirmation)
# Usage: make create-admin EMAIL=admin@example.com FIRST_NAME=Admin LAST_NAME=User
create-admin:
	docker compose exec -it php php bin/create-admin.php $(EMAIL) $(FIRST_NAME) $(LAST_NAME)

# Full CI pipeline: style + analysis + unit tests
ci: cs-check phpstan test
