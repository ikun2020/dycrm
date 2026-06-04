#!/usr/bin/env bash
set -euo pipefail

if ! command -v docker >/dev/null 2>&1; then
  sudo apt update
  sudo apt install -y ca-certificates curl git
  curl -fsSL https://get.docker.com | sudo sh
  sudo usermod -aG docker "$USER"
  echo "Docker installed. Log out and log back in, then run this script again."
  exit 0
fi

if [ ! -f .env ]; then
  cp .env.production.example .env
  echo ".env created. Edit APP_URL, DB_PASSWORD, and DB_ROOT_PASSWORD before continuing."
  exit 0
fi

if [ ! -f artisan ]; then
  docker run --rm -v "$PWD":/app -w /app composer:2 sh -lc '
    rm -rf /app/.laravel-tmp
    composer create-project laravel/laravel /app/.laravel-tmp
    find /app/.laravel-tmp -mindepth 1 -maxdepth 1 ! -name .env ! -name .git -exec cp -a {} /app/ \;
    rm -rf /app/.laravel-tmp
  '
fi

docker run --rm -v "$PWD":/app -w /app composer:2 require filament/filament

docker compose -f docker-compose.prod.yml build
docker compose -f docker-compose.prod.yml up -d
docker compose -f docker-compose.prod.yml exec app php artisan key:generate --force
docker compose -f docker-compose.prod.yml exec app php artisan filament:install --panels
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force

echo "DYCRM is running at http://127.0.0.1:${APP_PORT:-3100}/admin"
