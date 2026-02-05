.PHONY: help up down restart logs shell install test clean ps-install switch

# Load .env file
include .env
export

help: ## Display help
	@grep -E '^[a-zA-Z0-9._-]+:.*?## .*$$' Makefile | sort | awk 'BEGIN {FS = ":.*## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

up: ## Start the PrestaShop environment
	@echo "🚀 Starting PrestaShop ${PS_VERSION}..."
	docker compose up -d
	@echo "✅ PrestaShop ${PS_VERSION} available at http://localhost:${PS_PORT}"
	@echo "✅ phpMyAdmin available at http://localhost:${PMA_PORT}"

down: ## Stop the environment
	@echo "🛑 Stopping PrestaShop ${PS_VERSION}..."
	docker compose down

restart: down up ## Restart the environment

logs: ## Show logs
	docker compose logs -f prestashop

shell: ## Access the PrestaShop container shell
	docker compose exec prestashop bash

install: ## Install dependencies
	docker compose exec prestashop composer install

test: ## Run tests
	docker compose exec prestashop php bin/console hhennes:psmigration:upgrade-db --get-version

clean: ## Completely remove the environment (⚠️ deletes data)
	@echo "⚠️  Removing PrestaShop ${PS_VERSION} and its data..."
	docker compose down -v
	docker volume rm $$(docker volume ls -q | grep ${PS_VERSION}) 2>/dev/null || true

ps-install: up ## Start installation and open browser
	@echo "📦 Installing PrestaShop ${PS_VERSION}"
	@echo "Access http://localhost:${PS_PORT}"
	@sleep 5
	@command -v open >/dev/null && open http://localhost:${PS_PORT} || echo "Open http://localhost:${PS_PORT} in your browser"

# Version switching commands
switch-8.1: ## Switch to PrestaShop 8.1
	@$(MAKE) switch PS_VERSION=8.1 PS_PORT=8080 MYSQL_PORT=3306 PMA_PORT=8081

switch-8.0: ## Switch to PrestaShop 8.0
	@$(MAKE) switch PS_VERSION=8.0.5 PS_PORT=8082 MYSQL_PORT=3307 PMA_PORT=8083

switch-1.7.8: ## Switch to PrestaShop 1.7.8
	@$(MAKE) switch PS_VERSION=1.7.8.11 PS_PORT=8084 MYSQL_PORT=3308 PMA_PORT=8085

switch: ## Change PrestaShop version (internal use)
	@echo "PS_VERSION=${PS_VERSION}" > .env
	@echo "MYSQL_ROOT_PASSWORD=root" >> .env
	@echo "MYSQL_DATABASE=prestashop" >> .env
	@echo "MYSQL_USER=prestashop" >> .env
	@echo "MYSQL_PASSWORD=prestashop" >> .env
	@echo "PS_PORT=${PS_PORT}" >> .env
	@echo "MYSQL_PORT=${MYSQL_PORT}" >> .env
	@echo "PMA_PORT=${PMA_PORT}" >> .env
	@echo "✅ Switched to PrestaShop ${PS_VERSION}"
	@echo "📌 Ports: PrestaShop=${PS_PORT}, MySQL=${MYSQL_PORT}, phpMyAdmin=${PMA_PORT}"
	@echo "💡 Run 'make up' to start"

# Run multiple versions simultaneously
multi: ## Start all versions in parallel
	@echo "🚀 Starting multiple PrestaShop versions..."
	PS_VERSION=8.1 PS_PORT=8080 MYSQL_PORT=3306 PMA_PORT=8081 docker compose -p ps81 up -d
	PS_VERSION=8.0.5 PS_PORT=8082 MYSQL_PORT=3307 PMA_PORT=8083 docker compose -p ps80 up -d
	PS_VERSION=1.7.8.11 PS_PORT=8084 MYSQL_PORT=3308 PMA_PORT=8085 docker compose -p ps17 up -d
	@echo "✅ Available versions:"
	@echo "   - PrestaShop 8.1:     http://localhost:8080"
	@echo "   - PrestaShop 8.0.5:   http://localhost:8082"
	@echo "   - PrestaShop 1.7.8:   http://localhost:8084"

multi-down: ## Stop all versions
	docker compose -p ps81 down
	docker compose -p ps80 down
	docker compose -p ps17 down
