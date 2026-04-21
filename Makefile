.PHONY: help build run dev lint migrate-up migrate-down docs-generate

help:
	@echo "Available commands:"
	@echo "make sail-up                - Start Laravel Sail"
	@echo "make sail-down              - Stop Laravel Sail"
	@echo "make stan                   - Run PHPStan static analysis"
	@echo "make sail-test              - Run tests with Laravel Sail"
	@echo "make sail-test-coverage     - Run tests with coverage using Laravel Sail"
	@echo "make sail-test-coverage-html - Run tests with HTML coverage report using Laravel Sail"

sail-up:
	@echo "Starting Laravel Sail..."
	@./vendor/bin/sail up

sail-down:
	@echo "Stopping Laravel Sail..."
	@./vendor/bin/sail down

stan:
	@echo "Running PHPStan..."
	@./vendor/bin/phpstan analyse -l max --memory-limit=2G

sail-test:
	@echo "Running tests with Laravel Sail..."
	@./vendor/bin/sail artisan test

sail-test-coverage:
	@echo "Running tests with coverage using Laravel Sail..."
	@./vendor/bin/sail artisan test --coverage

# ===== Goの処理（子Makefileへ委譲！） =====
go-run:
	@$(MAKE) -C ai-recipe-service run

go-lint:
	@$(MAKE) -C ai-recipe-service lint

go-build:
	@$(MAKE) -C ai-recipe-service build

# ===== 全体の一括処理（CI/CDで大活躍！） =====
test-all: sail-test
	@$(MAKE) -C ai-recipe-service test