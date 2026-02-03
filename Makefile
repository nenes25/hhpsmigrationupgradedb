.PHONY: help up down restart logs shell install test clean ps-install switch

# Chargement du fichier .env
include .env
export

help: ## Affiche l'aide
	@grep -E '^[a-zA-Z0-9._-]+:.*?## .*$$' Makefile | sort | awk 'BEGIN {FS = ":.*## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

up: ## Démarre l'environnement PrestaShop
	@echo "🚀 Démarrage de PrestaShop ${PS_VERSION}..."
	docker compose up -d
	@echo "✅ PrestaShop ${PS_VERSION} disponible sur http://localhost:${PS_PORT}"
	@echo "✅ phpMyAdmin disponible sur http://localhost:${PMA_PORT}"

down: ## Arrête l'environnement
	@echo "🛑 Arrêt de PrestaShop ${PS_VERSION}..."
	docker compose down

restart: down up ## Redémarre l'environnement

logs: ## Affiche les logs
	docker compose logs -f prestashop

shell: ## Accède au shell du container PrestaShop
	docker compose exec prestashop bash

install: ## Installe les dépendances
	docker compose exec prestashop composer install

test: ## Lance les tests
	docker compose exec prestashop php bin/console hhennes:psmigration:upgrade-db --get-version

clean: ## Supprime complètement l'environnement (⚠️ supprime les données)
	@echo "⚠️  Suppression de PrestaShop ${PS_VERSION} et de ses données..."
	docker compose down -v
	docker volume rm $$(docker volume ls -q | grep ${PS_VERSION}) 2>/dev/null || true

ps-install: up ## Lance l'installation et ouvre le navigateur
	@echo "📦 Installation de PrestaShop ${PS_VERSION}"
	@echo "Accédez à http://localhost:${PS_PORT}"
	@sleep 5
	@command -v open >/dev/null && open http://localhost:${PS_PORT} || echo "Ouvrez http://localhost:${PS_PORT} dans votre navigateur"

# Commandes de changement de version
switch-8.1: ## Bascule vers PrestaShop 8.1
	@$(MAKE) switch PS_VERSION=8.1 PS_PORT=8080 MYSQL_PORT=3306 PMA_PORT=8081

switch-8.0: ## Bascule vers PrestaShop 8.0
	@$(MAKE) switch PS_VERSION=8.0.5 PS_PORT=8082 MYSQL_PORT=3307 PMA_PORT=8083

switch-1.7.8: ## Bascule vers PrestaShop 1.7.8
	@$(MAKE) switch PS_VERSION=1.7.8.11 PS_PORT=8084 MYSQL_PORT=3308 PMA_PORT=8085

switch: ## Change la version de PrestaShop (usage interne)
	@echo "PS_VERSION=${PS_VERSION}" > .env
	@echo "MYSQL_ROOT_PASSWORD=root" >> .env
	@echo "MYSQL_DATABASE=prestashop" >> .env
	@echo "MYSQL_USER=prestashop" >> .env
	@echo "MYSQL_PASSWORD=prestashop" >> .env
	@echo "PS_PORT=${PS_PORT}" >> .env
	@echo "MYSQL_PORT=${MYSQL_PORT}" >> .env
	@echo "PMA_PORT=${PMA_PORT}" >> .env
	@echo "✅ Basculé vers PrestaShop ${PS_VERSION}"
	@echo "📌 Ports: PrestaShop=${PS_PORT}, MySQL=${MYSQL_PORT}, phpMyAdmin=${PMA_PORT}"
	@echo "💡 Lancez 'make up' pour démarrer"

# Lancer plusieurs versions simultanément
multi: ## Lance toutes les versions en parallèle
	@echo "🚀 Lancement de plusieurs versions de PrestaShop..."
	PS_VERSION=8.1 PS_PORT=8080 MYSQL_PORT=3306 PMA_PORT=8081 docker compose -p ps81 up -d
	PS_VERSION=8.0.5 PS_PORT=8082 MYSQL_PORT=3307 PMA_PORT=8083 docker compose -p ps80 up -d
	PS_VERSION=1.7.8.11 PS_PORT=8084 MYSQL_PORT=3308 PMA_PORT=8085 docker compose -p ps17 up -d
	@echo "✅ Versions disponibles:"
	@echo "   - PrestaShop 8.1:     http://localhost:8080"
	@echo "   - PrestaShop 8.0.5:   http://localhost:8082"
	@echo "   - PrestaShop 1.7.8:   http://localhost:8084"

multi-down: ## Arrête toutes les versions
	docker compose -p ps81 down
	docker compose -p ps80 down
	docker compose -p ps17 down
