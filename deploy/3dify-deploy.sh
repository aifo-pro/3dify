#!/usr/bin/env bash
#
# 3Dify production redeploy helper — pulls latest code, rebuilds, migrates.
#
# Install on a freshly-provisioned VPS:
#
#   sudo wget -qO /usr/local/bin/3dify-deploy \
#       https://raw.githubusercontent.com/aifo-pro/3dify/main/deploy/3dify-deploy.sh
#   sudo chmod +x /usr/local/bin/3dify-deploy
#
# Run after every push to main:
#
#   sudo 3dify-deploy
#
# Notes:
#   • When invoked as root, the script re-execs itself under the `deploy`
#     user so git/composer/npm/artisan touch files as the project owner.
#     This avoids git's "dubious ownership in repository" guard
#     (CVE-2022-24765) and keeps file permissions stable across deploys.
#   • systemctl reload/restart still happens via sudo — the deploy user
#     is whitelisted for exactly those commands in /etc/sudoers.d/deploy.

set -euo pipefail

APP_DIR="/var/www/3dify"
APP_SLUG="3dify"
APP_USER="deploy"
PHP_VERSION="8.4"

# ─── Re-exec as deploy if we're root ────────────────────────────────────────
if [[ "$EUID" -eq 0 ]]; then
    exec sudo -u "$APP_USER" -H bash "$0" "$@"
fi

if [[ "$(id -un)" != "$APP_USER" ]]; then
    echo "✗ This script must run as root (preferred) or as ${APP_USER}." >&2
    exit 1
fi

# ─── Deploy ─────────────────────────────────────────────────────────────────
cd "${APP_DIR}" || { echo "✗ ${APP_DIR} not found" >&2; exit 1; }

echo "==> Re-deploying ${APP_SLUG}"
echo "    user      : $(id -un)"
echo "    workdir   : ${APP_DIR}"
echo "    git head  : $(git rev-parse --short HEAD 2>/dev/null || echo '?')"

# Maintenance mode — render a static 503 so visitors see something nice.
php artisan down --render="errors::503" --retry=60 || true

# Ensure we always come back up, even if a step below fails.
trap 'php artisan up >/dev/null 2>&1 || true' EXIT

git pull --ff-only

composer install --no-dev --optimize-autoloader --prefer-dist --no-interaction

# `npm install` is more forgiving than `npm ci` when a deploy is in flight —
# we don't want stale package-lock.json to take the site down.
rm -rf node_modules
npm install --no-audit --no-fund
npm run build
rm -rf node_modules

php artisan migrate --force --no-interaction

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Sudoers grants the deploy user passwordless access to exactly these
# two systemctl commands — see /etc/sudoers.d/deploy.
sudo /bin/systemctl reload "php${PHP_VERSION}-fpm"
sudo /bin/systemctl restart "${APP_SLUG}-queue.service"

# Disable trap and bring the site up explicitly so we exit cleanly.
trap - EXIT
php artisan up

echo
echo "✓ ${APP_SLUG} deployed at $(date -Iseconds)"
echo "  head      : $(git rev-parse --short HEAD)"
