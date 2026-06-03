#!/usr/bin/env bash
#
# Configure 3Dify to send transactional email through Mailjet SMTP.
#
# Usage on the server:
#   sudo APP_DIR=/var/www/3dify MAILJET_API_KEY=... MAILJET_SECRET_KEY=... \
#     MAIL_FROM_ADDRESS=no-reply@3dify.dev bash deploy/configure-mailjet-smtp.sh

set -Eeuo pipefail

APP_DIR="${APP_DIR:-/var/www/3dify}"
APP_USER="${APP_USER:-deploy}"
PHP_BIN="${PHP_BIN:-php}"
PHP_FPM_SERVICE="${PHP_FPM_SERVICE:-php8.4-fpm}"
QUEUE_SERVICE="${QUEUE_SERVICE:-3dify-queue.service}"

MAILJET_API_KEY="${MAILJET_API_KEY:-}"
MAILJET_SECRET_KEY="${MAILJET_SECRET_KEY:-}"
MAILJET_SMTP_HOST="${MAILJET_SMTP_HOST:-in-v3.mailjet.com}"
MAILJET_SMTP_PORT="${MAILJET_SMTP_PORT:-587}"
MAILJET_SMTP_ENCRYPTION="${MAILJET_SMTP_ENCRYPTION:-tls}"
MAIL_FROM_ADDRESS="${MAIL_FROM_ADDRESS:-}"
MAIL_FROM_NAME="${MAIL_FROM_NAME:-3Dify}"
MAIL_EHLO_DOMAIN="${MAIL_EHLO_DOMAIN:-}"

info() { printf '\n==> %s\n' "$*"; }
fail() { printf '\nERROR: %s\n' "$*" >&2; exit 1; }

prompt_secret() {
  local var_name="$1"
  local label="$2"
  local value="${!var_name:-}"

  if [[ -z "$value" ]]; then
    read -r -s -p "$label: " value
    printf '\n'
    export "$var_name=$value"
  fi
}

prompt_value() {
  local var_name="$1"
  local label="$2"
  local default="${3:-}"
  local value="${!var_name:-}"

  if [[ -z "$value" ]]; then
    read -r -p "$label${default:+ [$default]}: " value
    value="${value:-$default}"
    export "$var_name=$value"
  fi
}

set_env() {
  local key="$1"
  local value="$2"

  "$PHP_BIN" -r '
    $path = $argv[1];
    $key = $argv[2];
    $value = $argv[3];
    $quote = preg_match("/[\s#\"'\''\\\\]/", $value) ? "\"" : "";
    $escaped = $quote ? str_replace(["\\", "\""], ["\\\\", "\\\""], $value) : $value;
    $line = $key."=".$quote.$escaped.$quote;
    $lines = file_exists($path) ? file($path, FILE_IGNORE_NEW_LINES) : [];
    $found = false;
    foreach ($lines as &$existing) {
      if (str_starts_with($existing, $key."=")) {
        $existing = $line;
        $found = true;
      }
    }
    unset($existing);
    if (! $found) {
      $lines[] = $line;
    }
    file_put_contents($path, implode(PHP_EOL, $lines).PHP_EOL);
  ' "$APP_DIR/.env" "$key" "$value"
}

cd "$APP_DIR" || fail "APP_DIR not found: $APP_DIR"
[[ -f artisan ]] || fail "artisan not found in $APP_DIR"
[[ -f .env ]] || fail ".env not found in $APP_DIR"

prompt_secret MAILJET_API_KEY "Mailjet API Key"
prompt_secret MAILJET_SECRET_KEY "Mailjet Secret Key"
prompt_value MAIL_FROM_ADDRESS "Verified From address" "no-reply@3dify.dev"

if [[ -z "$MAIL_EHLO_DOMAIN" ]]; then
  MAIL_EHLO_DOMAIN="$(grep -E '^APP_URL=' .env | tail -n 1 | cut -d= -f2- | sed -E 's#^https?://##; s#/.*$##; s/^\"//; s/\"$//')"
  MAIL_EHLO_DOMAIN="${MAIL_EHLO_DOMAIN:-3dify.dev}"
fi

info "Writing Mailjet SMTP settings to .env"
set_env MAIL_MAILER smtp
set_env MAIL_HOST "$MAILJET_SMTP_HOST"
set_env MAIL_PORT "$MAILJET_SMTP_PORT"
set_env MAIL_USERNAME "$MAILJET_API_KEY"
set_env MAIL_PASSWORD "$MAILJET_SECRET_KEY"
set_env MAIL_SCHEME "$MAILJET_SMTP_ENCRYPTION"
set_env MAIL_ENCRYPTION "$MAILJET_SMTP_ENCRYPTION"
set_env MAIL_FROM_ADDRESS "$MAIL_FROM_ADDRESS"
set_env MAIL_FROM_NAME "$MAIL_FROM_NAME"
set_env MAIL_EHLO_DOMAIN "$MAIL_EHLO_DOMAIN"
set_env MAILJET_API_KEY "$MAILJET_API_KEY"
set_env MAILJET_SECRET_KEY "$MAILJET_SECRET_KEY"
set_env MAILJET_SMTP_HOST "$MAILJET_SMTP_HOST"
set_env MAILJET_SMTP_PORT "$MAILJET_SMTP_PORT"
set_env MAILJET_SMTP_ENCRYPTION "$MAILJET_SMTP_ENCRYPTION"

info "Refreshing Laravel config cache"
sudo -u "$APP_USER" "$PHP_BIN" artisan optimize:clear
sudo -u "$APP_USER" "$PHP_BIN" artisan config:cache
sudo -u "$APP_USER" "$PHP_BIN" artisan queue:restart || true

if command -v systemctl >/dev/null 2>&1; then
  sudo systemctl reload "$PHP_FPM_SERVICE" || true
  sudo systemctl restart "$QUEUE_SERVICE" || true
fi

info "Mailjet SMTP configured"
echo "Host      : $MAILJET_SMTP_HOST:$MAILJET_SMTP_PORT ($MAILJET_SMTP_ENCRYPTION)"
echo "From      : $MAIL_FROM_NAME <$MAIL_FROM_ADDRESS>"
echo "Test from : /admin/content?tab=mail"
