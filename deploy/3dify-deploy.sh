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
WEB_GROUP="${WEB_GROUP:-www-data}"
DEPLOY_BRANCH="${DEPLOY_BRANCH:-main}"
PHP_FPM_SERVICE="${PHP_FPM_SERVICE:-php8.4-fpm}"
QUEUE_SERVICE="${QUEUE_SERVICE:-3dify-queue.service}"

export APP_DIR DEPLOY_BRANCH PHP_FPM_SERVICE QUEUE_SERVICE

repair_permissions() {
  [[ -d "$APP_DIR" ]] || return 0

  local writable_group="$WEB_GROUP"
  if ! getent group "$writable_group" >/dev/null 2>&1; then
    writable_group="$APP_USER"
  fi

  echo "==> Repairing Laravel writable permissions for ${APP_DIR}"
  mkdir -p \
    "$APP_DIR/storage/app/public" \
    "$APP_DIR/storage/framework/cache" \
    "$APP_DIR/storage/framework/sessions" \
    "$APP_DIR/storage/framework/testing" \
    "$APP_DIR/storage/framework/views" \
    "$APP_DIR/storage/logs" \
    "$APP_DIR/bootstrap/cache"

  chown -R "$APP_USER:$APP_USER" "$APP_DIR" 2>/dev/null || true
  chown -R "$APP_USER:$writable_group" "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" 2>/dev/null || true

  find "$APP_DIR" -type d -exec chmod u+rwx,g+rx {} \; 2>/dev/null || true
  find "$APP_DIR" -type f -exec chmod u+rw,g+r {} \; 2>/dev/null || true

  find "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" -type d -exec chmod 2775 {} \; 2>/dev/null || true
  find "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" -type f -exec chmod 664 {} \; 2>/dev/null || true

  if command -v setfacl >/dev/null 2>&1; then
    setfacl -R -m "u:${APP_USER}:rwX,g:${writable_group}:rwX" "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" 2>/dev/null || true
    setfacl -R -d -m "u:${APP_USER}:rwX,g:${writable_group}:rwX" "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" 2>/dev/null || true
  fi
}

if [[ "$EUID" -eq 0 ]]; then
  repair_permissions

  set +e
  sudo -u "$APP_USER" -H env \
    APP_DIR="$APP_DIR" \
    DEPLOY_BRANCH="$DEPLOY_BRANCH" \
    PHP_FPM_SERVICE="$PHP_FPM_SERVICE" \
    QUEUE_SERVICE="$QUEUE_SERVICE" \
    bash "$APP_DIR/scripts/deploy.sh" "$@"
  status=$?
  set -e

  repair_permissions
  exit "$status"
fi

exec bash "$APP_DIR/scripts/deploy.sh" "$@"
