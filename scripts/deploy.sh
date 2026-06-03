#!/usr/bin/env bash
#
# Safe one-command deploy for 3Dify.
#
# Usage on the server, from the project directory:
#   chmod +x scripts/deploy.sh
#   ./scripts/deploy.sh
#
# Useful environment variables:
#   DEPLOY_BRANCH=main          Git branch to deploy.
#   APP_DIR=/var/www/3dify      Project path, if running from another directory.
#   BACKUP_DIR=storage/backups  Where deployment backups are stored.
#   SKIP_BACKUP=1               Skip .env/storage/database backup.
#   SKIP_COMPOSER=1             Skip composer install.
#   SKIP_NPM=1                  Skip npm install and npm run build.
#   SKIP_MIGRATE=1              Skip php artisan migrate --force.
#   RUN_TESTS=1                 Run php artisan test before bringing the app up.
#   PHP_FPM_SERVICE=php8.4-fpm  Reload this service after deploy, if systemctl exists.
#   QUEUE_SERVICE=3dify-queue   Restart this queue worker service, if systemctl exists.
#   DEPLOY_HEALTH_URL=https://example.com  Curl this URL after deploy.

set -Eeuo pipefail

APP_DIR="${APP_DIR:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
BRANCH="${DEPLOY_BRANCH:-main}"
BACKUP_DIR="${BACKUP_DIR:-$APP_DIR/storage/backups/deploy}"
PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"
NPM_BIN="${NPM_BIN:-npm}"
LOCK_DIR="${APP_DIR}/storage/framework/deploy.lock"

log() {
  printf '\n==> %s\n' "$*"
}

fail() {
  printf '\nERROR: %s\n' "$*" >&2
  exit 1
}

run() {
  printf '+ %s\n' "$*"
  "$@"
}

dotenv_get() {
  local key="$1"
  local value=""

  if [[ -f "$APP_DIR/.env" ]]; then
    value="$(grep -E "^${key}=" "$APP_DIR/.env" | tail -n 1 | cut -d= -f2- || true)"
  fi

  value="${value%\"}"
  value="${value#\"}"
  value="${value%\'}"
  value="${value#\'}"
  printf '%s' "$value"
}

cleanup_lock() {
  rmdir "$LOCK_DIR" >/dev/null 2>&1 || true
}

bring_app_up() {
  if [[ -f "$APP_DIR/artisan" ]]; then
    "$PHP_BIN" artisan up >/dev/null 2>&1 || true
  fi
}

backup_files() {
  [[ "${SKIP_BACKUP:-0}" == "1" ]] && return 0

  local stamp="$1"
  local target="$BACKUP_DIR/$stamp"
  mkdir -p "$target"

  log "Backup .env and public storage"
  [[ -f .env ]] && cp .env "$target/.env.backup"
  if [[ -d storage/app/public ]]; then
    tar -czf "$target/storage-app-public.tar.gz" storage/app/public
  fi
}

backup_database() {
  [[ "${SKIP_BACKUP:-0}" == "1" ]] && return 0

  local stamp="$1"
  local target="$BACKUP_DIR/$stamp"
  local conn db user pass host port

  conn="$(dotenv_get DB_CONNECTION)"
  db="$(dotenv_get DB_DATABASE)"
  user="$(dotenv_get DB_USERNAME)"
  pass="$(dotenv_get DB_PASSWORD)"
  host="$(dotenv_get DB_HOST)"
  port="$(dotenv_get DB_PORT)"
  host="${host:-127.0.0.1}"

  mkdir -p "$target"

  case "$conn" in
    mysql|mariadb)
      if command -v mysqldump >/dev/null 2>&1 && [[ -n "$db" ]]; then
        log "Backup MySQL/MariaDB database"
        MYSQL_PWD="$pass" mysqldump \
          --single-transaction \
          --quick \
          --host="$host" \
          ${port:+--port="$port"} \
          ${user:+--user="$user"} \
          "$db" > "$target/database.sql"
        gzip -f "$target/database.sql"
      else
        echo "mysqldump or DB_DATABASE not available, database backup skipped."
      fi
      ;;
    pgsql)
      if command -v pg_dump >/dev/null 2>&1 && [[ -n "$db" ]]; then
        log "Backup PostgreSQL database"
        PGPASSWORD="$pass" pg_dump \
          --host="$host" \
          ${port:+--port="$port"} \
          ${user:+--username="$user"} \
          --format=custom \
          --file="$target/database.dump" \
          "$db"
      else
        echo "pg_dump or DB_DATABASE not available, database backup skipped."
      fi
      ;;
    sqlite)
      if [[ -n "$db" && -f "$db" ]]; then
        log "Backup SQLite database"
        cp "$db" "$target/database.sqlite"
      elif [[ -n "$db" && -f "$APP_DIR/$db" ]]; then
        log "Backup SQLite database"
        cp "$APP_DIR/$db" "$target/database.sqlite"
      else
        echo "SQLite database file not found, database backup skipped."
      fi
      ;;
    *)
      echo "Unsupported or empty DB_CONNECTION='$conn', database backup skipped."
      ;;
  esac
}

reload_services() {
  if command -v systemctl >/dev/null 2>&1; then
    if [[ -n "${PHP_FPM_SERVICE:-}" ]]; then
      log "Reload PHP-FPM service"
      sudo systemctl reload "$PHP_FPM_SERVICE" || true
    fi

    if [[ -n "${QUEUE_SERVICE:-}" ]]; then
      log "Restart queue service"
      sudo systemctl restart "$QUEUE_SERVICE" || true
    fi
  fi
}

health_check() {
  if [[ -n "${DEPLOY_HEALTH_URL:-}" ]] && command -v curl >/dev/null 2>&1; then
    log "Health check"
    curl --fail --silent --show-error --max-time 20 "$DEPLOY_HEALTH_URL" >/dev/null
  fi
}

ensure_production_assets() {
  log "Verify frontend assets"

  if [[ -f public/hot ]]; then
    fail "public/hot still exists. Production would load Vite dev assets from 127.0.0.1."
  fi

  [[ -f public/build/manifest.json ]] || fail "public/build/manifest.json is missing. Run npm run build."

  local css_file js_file
  css_file="$(php -r '$m=json_decode(file_get_contents("public/build/manifest.json"), true); echo $m["resources/css/app.css"]["file"] ?? "";')"
  js_file="$(php -r '$m=json_decode(file_get_contents("public/build/manifest.json"), true); echo $m["resources/js/app.js"]["file"] ?? "";')"

  [[ -n "$css_file" && -f "public/build/$css_file" ]] || fail "Compiled CSS is missing from public/build."
  [[ -n "$js_file" && -f "public/build/$js_file" ]] || fail "Compiled JS is missing from public/build."

  chmod -R a+rX public/build
}

cd "$APP_DIR" || fail "Project directory not found: $APP_DIR"
[[ -f artisan ]] || fail "artisan not found in $APP_DIR"
[[ -d .git ]] || fail ".git directory not found in $APP_DIR"

mkdir -p storage/framework
if ! mkdir "$LOCK_DIR" >/dev/null 2>&1; then
  fail "Another deploy is already running: $LOCK_DIR"
fi
trap cleanup_lock EXIT

log "Deploy 3Dify"
echo "directory: $APP_DIR"
echo "branch   : $BRANCH"
echo "current  : $(git rev-parse --short HEAD 2>/dev/null || echo unknown)"

if [[ -n "$(git status --porcelain --untracked-files=no)" && "${ALLOW_DIRTY:-0}" != "1" ]]; then
  fail "Tracked files have local changes. Commit/stash them or rerun with ALLOW_DIRTY=1 after reviewing."
fi

STAMP="$(date +%Y%m%d_%H%M%S)"
backup_files "$STAMP"
backup_database "$STAMP"

log "Maintenance mode"
run "$PHP_BIN" artisan down --retry=60 || true
trap 'bring_app_up; cleanup_lock' EXIT

log "Pull latest code"
run git fetch origin "$BRANCH"
run git checkout "$BRANCH"
run git merge --ff-only "origin/$BRANCH"

log "Disable Vite dev server mode"
rm -f public/hot

if [[ "${SKIP_COMPOSER:-0}" != "1" ]]; then
  log "Composer install"
  run "$COMPOSER_BIN" install --no-dev --optimize-autoloader --prefer-dist --no-interaction
fi

if [[ "${SKIP_NPM:-0}" != "1" ]]; then
  if command -v "$NPM_BIN" >/dev/null 2>&1; then
    log "Frontend build"
    # npm install is intentionally used instead of npm ci: production deploys
    # should tolerate lock drift and continue to rebuild Vite assets.
    run "$NPM_BIN" install --no-audit --no-fund
    run "$NPM_BIN" run build
  else
    echo "npm not found, frontend build skipped."
  fi
fi

ensure_production_assets

log "Clear Laravel caches"
run "$PHP_BIN" artisan optimize:clear

if [[ "${SKIP_MIGRATE:-0}" != "1" ]]; then
  log "Run migrations"
  run "$PHP_BIN" artisan migrate --force --no-interaction
fi

log "Ensure storage link"
run "$PHP_BIN" artisan storage:link || true

log "Warm Laravel caches"
run "$PHP_BIN" artisan config:cache
run "$PHP_BIN" artisan route:cache
run "$PHP_BIN" artisan view:cache
run "$PHP_BIN" artisan event:cache || true

if [[ "${RUN_TESTS:-0}" == "1" ]]; then
  log "Run tests"
  run "$PHP_BIN" artisan test
fi

log "Restart queues"
run "$PHP_BIN" artisan queue:restart || true
reload_services

log "Bring app up"
run "$PHP_BIN" artisan up
trap cleanup_lock EXIT

health_check

log "Deploy complete"
echo "deployed : $(git rev-parse --short HEAD)"
echo "backup   : $BACKUP_DIR/$STAMP"
