#!/bin/sh
# Deploy/atualização em produção.
# Uso (no servidor, na pasta do projeto): ./docker/scripts/deploy.sh
set -e

COMPOSE="docker compose -f docker-compose.prod.yml --env-file .env.production"

git pull --ff-only
$COMPOSE build
$COMPOSE up -d
# O container "app" roda no entrypoint: migrate --force, config:cache, route:cache, view:cache.
$COMPOSE ps
