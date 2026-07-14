#!/usr/bin/env bash
set -e

echo "==> Atualizando código..."
git pull origin main

echo "==> Instalando dependências PHP..."
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Executando migrations..."
php artisan migrate --force

echo "==> Limpando caches..."
php artisan optimize:clear

echo "==> Reconstruindo caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Deploy concluído."
