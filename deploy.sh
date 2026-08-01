#!/usr/bin/env bash
set -e

echo "==> Atualizando código..."
# pack.threads=1: a hospedagem limita processos e o index-pack falha com
# "unable to create thread" ao resolver deltas em paralelo.
git -c pack.threads=1 fetch origin main
git merge --ff-only origin/main

echo "==> Instalando dependências PHP..."
# O composer não está no PATH de shells não-interativos desta hospedagem;
# usamos o composer.phar do cPanel. Sobrescreva com COMPOSER_PHAR=... se mudar.
COMPOSER_PHAR="${COMPOSER_PHAR:-/opt/cpanel/ea-wappspector/composer.phar}"
php "$COMPOSER_PHAR" install --no-dev --optimize-autoloader --no-interaction

echo "==> Executando migrations..."
php artisan migrate --force

echo "==> Limpando caches..."
php artisan optimize:clear

echo "==> Reconstruindo caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Deploy concluído."
