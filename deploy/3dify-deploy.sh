#!/usr/bin/env bash
#
# Production wrapper for the safe 3Dify deploy script.
#
# Install:
#   sudo wget -qO /usr/local/bin/3dify-deploy \
#     https://raw.githubusercontent.com/aifo-pro/3dify/main/deploy/3dify-deploy.sh
#   sudo chmod +x /usr/local/bin/3dify-deploy
#
# Run:
#   sudo 3dify-deploy
#
# Override if your server differs:
#   APP_DIR=/var/www/3dify DEPLOY_BRANCH=main PHP_FPM_SERVICE=php8.4-fpm QUEUE_SERVICE=3dify-queue sudo 3dify-deploy

set -Eeuo pipefail

APP_DIR="${APP_DIR:-/var/www/3dify}"
APP_USER="${APP_USER:-deploy}"
DEPLOY_BRANCH="${DEPLOY_BRANCH:-main}"
PHP_FPM_SERVICE="${PHP_FPM_SERVICE:-php8.4-fpm}"
QUEUE_SERVICE="${QUEUE_SERVICE:-3dify-queue.service}"

export APP_DIR DEPLOY_BRANCH PHP_FPM_SERVICE QUEUE_SERVICE

if [[ "$EUID" -eq 0 ]]; then
  if [[ -d "$APP_DIR" ]]; then
    echo "==> Fixing deploy ownership for ${APP_DIR}"
    chown -R "$APP_USER:$APP_USER" "$APP_DIR/.git" "$APP_DIR/bootstrap/cache" "$APP_DIR/storage" 2>/dev/null || true
  fi

  exec sudo -u "$APP_USER" -H env \
    APP_DIR="$APP_DIR" \
    DEPLOY_BRANCH="$DEPLOY_BRANCH" \
    PHP_FPM_SERVICE="$PHP_FPM_SERVICE" \
    QUEUE_SERVICE="$QUEUE_SERVICE" \
    bash "$APP_DIR/scripts/deploy.sh" "$@"
fi

exec bash "$APP_DIR/scripts/deploy.sh" "$@"
