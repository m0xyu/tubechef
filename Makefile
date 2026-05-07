.PHONY: help sail-up sail-down stan test test-coverage go-run go-lint go-build go-tidy go-test test-all prod-build prod-up prod-down prod-migrate prod-optimize prod-deploy prod-bash

help:
	@echo "Available commands:"
	@echo "make sail-up                - Start Laravel Sail"
	@echo "make sail-down              - Stop Laravel Sail"
	@echo "make stan                   - Run PHPStan static analysis"
	@echo "make test              - Run tests with Laravel Sail"
	@echo "make test-coverage     - Run tests with coverage using Laravel Sail"
	@echo "make go-run                 - Run the Go API server"
	@echo "make go-lint                - Run Go linters"
	@echo "make go-build               - Build the Go API server"
	@echo "make go-tidy                - Tidy Go modules"
	@echo "make go-test                - Run Go tests"
	@echo "make test-all               - Run all tests (PHP and Go)"
	@echo "make prod-build             - Build production containers"
	@echo "make prod-up                - Start production environment"
	@echo "make prod-down              - Stop production environment"
	@echo "make prod-migrate           - Run database migrations for production"
	@echo "make prod-optimize          - Optimize Laravel for production"
	@echo "make prod-deploy            - Build and deploy to production"
	@echo "make prod-bash              - Open a bash shell in the production container"

sail-up:
	@echo "Starting Laravel Sail..."
	@./vendor/bin/sail up

sail-down:
	@echo "Stopping Laravel Sail..."
	@./vendor/bin/sail down

stan:
	@echo "Running PHPStan..."
	@./vendor/bin/phpstan analyse -l max --memory-limit=2G

test:
	@echo "Running tests with Laravel Sail..."
	@php artisan test

test-coverage:
	@echo "Running tests with coverage using Laravel Sail..."
	@php artisan test --coverage

# ===== Goの処理（子Makefileへ委譲！） =====
go-run:
	@$(MAKE) -C ai-recipe-service run

go-lint:
	@$(MAKE) -C ai-recipe-service lint

go-build:
	@$(MAKE) -C ai-recipe-service build

go-tidy:
	@$(MAKE) -C ai-recipe-service tidy

go-test: 
	@$(MAKE) -C ai-recipe-service test

# ===== 全体の一括処理 =====
test-all: test
	@$(MAKE) -C ai-recipe-service test

# ===== Production (本番環境用) =====
prod-build:
	@echo "Building production containers..."
	@docker compose -f docker-compose.prod.yml build

prod-up:
	@echo "Starting production environment..."
	@docker compose -f docker-compose.prod.yml up -d

prod-down:
	@echo "Stopping production environment..."
	@docker compose -f docker-compose.prod.yml down

prod-migrate:
	@echo "Running database migrations for production..."
	@docker compose -f docker-compose.prod.yml exec tubechef-app php artisan migrate --force

prod-optimize:
	@echo "Optimizing Laravel for production (caching config, routes, views)..."
	@docker compose -f docker-compose.prod.yml exec tubechef-app php artisan optimize

prod-deploy: prod-build prod-up prod-migrate prod-optimize
	@echo "🚀 Deployment completed successfully!"

prod-bash:
	@docker compose -f docker-compose.prod.yml exec tubechef-app bash