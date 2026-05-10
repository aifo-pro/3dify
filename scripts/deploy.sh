#!/usr/bin/env bash
# Одна команда деплою на сервері (Ubuntu/Debian, bash).
# Використання з каталогу проєкту:
#   chmod +x scripts/deploy.sh   # один раз
#   ./scripts/deploy.sh
#
# Змінні середовища (необов’язково):
#   DEPLOY_BRANCH=main          гілка для checkout/pull
#   SKIP_NPM=1                  пропустити npm ci && npm run build
#   SKIP_OPTIMIZE_CLEAR=1       не викликати optimize:clear перед міграціями

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

BRANCH="${DEPLOY_BRANCH:-main}"

echo "==> [deploy] root=$ROOT branch=$BRANCH"

git fetch origin

CURRENT="$(git rev-parse --abbrev-ref HEAD)"
if [[ "$CURRENT" != "$BRANCH" ]]; then
  echo "==> [deploy] checkout $BRANCH"
  git checkout "$BRANCH"
fi

git pull origin "$BRANCH"

echo "==> [deploy] composer install"
composer install --no-dev --optimize-autoloader --no-interaction

if [[ "${SKIP_NPM:-0}" != "1" ]] && command -v npm >/dev/null 2>&1; then
  if [[ -f package-lock.json ]]; then
    echo "==> [deploy] npm ci && npm run build"
    npm ci
    npm run build
  else
    echo "==> [deploy] npm: package-lock.json не знайдено, пропуск"
  fi
else
  echo "==> [deploy] npm: пропущено (SKIP_NPM або npm немає в PATH)"
fi

if [[ "${SKIP_OPTIMIZE_CLEAR:-0}" != "1" ]]; then
  echo "==> [deploy] php artisan optimize:clear"
  php artisan optimize:clear
fi

echo "==> [deploy] php artisan migrate"
php artisan migrate --force --no-interaction

echo "==> [deploy] php artisan optimize"
php artisan optimize

echo "==> [deploy] php artisan queue:restart"
php artisan queue:restart || true

echo "==> [deploy] готово."
echo "    Opcache: підбери свій сервіс PHP-FPM і перезавантаж:"
echo "      systemctl list-units --type=service '*php*fpm*'"
echo "      або: php -v   потім   sudo systemctl reload phpX.Y-fpm"
echo "    Якщо сервіс називається інакше: sudo service apache2 reload   (mod_php) або sudo systemctl reload lsws / openlitespeed"
