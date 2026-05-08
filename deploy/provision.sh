#!/usr/bin/env bash
#
# 3Dify VPS provisioning script
# ---------------------------------------------------------------------------
# Run on a fresh Ubuntu 24.04 LTS (or 22.04) VPS as root:
#
#   curl -fsSL https://your-host/provision.sh | sudo bash
#       — or —
#   scp deploy/provision.sh root@HOST:/root/ && ssh root@HOST 'bash provision.sh'
#
# All inputs are taken from environment variables; missing ones are prompted.
# Re-running is safe — every step is idempotent.
# ---------------------------------------------------------------------------

set -euo pipefail
IFS=$'\n\t'

# -----------------------------  CONFIG  ------------------------------------
APP_NAME="${APP_NAME:-3Dify}"
APP_SLUG="${APP_SLUG:-3dify}"
APP_DIR="${APP_DIR:-/var/www/${APP_SLUG}}"
APP_USER="${APP_USER:-deploy}"
APP_GROUP="www-data"

PHP_VERSION="${PHP_VERSION:-8.3}"
NODE_MAJOR="${NODE_MAJOR:-20}"

DOMAIN="${DOMAIN:-}"                 # 3dify.example.com
LE_EMAIL="${LE_EMAIL:-}"             # admin@example.com (Let's Encrypt)
GIT_REPO="${GIT_REPO:-}"             # git@github.com:USER/3dify.git
GIT_BRANCH="${GIT_BRANCH:-main}"

DB_NAME="${DB_NAME:-${APP_SLUG}}"
DB_USER="${DB_USER:-${APP_SLUG}}"
DB_PASSWORD="${DB_PASSWORD:-}"       # auto-generated if empty
TIMEZONE="${TIMEZONE:-Europe/Kyiv}"

# -----------------------------  HELPERS  -----------------------------------
C_RST="\033[0m"; C_GRN="\033[1;32m"; C_BLU="\033[1;34m"; C_YEL="\033[1;33m"; C_RED="\033[1;31m"

step()    { echo -e "\n${C_BLU}==>${C_RST} ${C_GRN}$*${C_RST}"; }
info()    { echo -e "    ${C_BLU}·${C_RST} $*"; }
warn()    { echo -e "${C_YEL}!!  $*${C_RST}"; }
fatal()   { echo -e "${C_RED}xx  $*${C_RST}"; exit 1; }
require_root() { [[ $EUID -eq 0 ]] || fatal "Run as root or with sudo."; }
prompt_if_empty() {
    local var=$1 question=$2
    if [[ -z "${!var}" ]]; then read -rp "$question " "$var"; fi
    [[ -n "${!var}" ]] || fatal "$var is required"
    export "$var"
}
gen_password() { openssl rand -base64 24 | tr -d '/+=\n' | cut -c1-24; }

# -----------------------------  CHECKS  ------------------------------------
require_root

OS_ID=$(. /etc/os-release && echo "$ID")
OS_VERSION=$(. /etc/os-release && echo "$VERSION_ID")
[[ "$OS_ID" == "ubuntu" || "$OS_ID" == "debian" ]] || fatal "Unsupported OS: $OS_ID. Use Ubuntu 22.04+ or Debian 12+."
info "Detected ${OS_ID} ${OS_VERSION}"

prompt_if_empty DOMAIN     "Domain (e.g. 3dify.example.com):"
prompt_if_empty LE_EMAIL   "Email for Let's Encrypt:"
prompt_if_empty GIT_REPO   "Git repository URL (or 'skip' to clone manually later):"

[[ -z "$DB_PASSWORD" ]] && DB_PASSWORD=$(gen_password) && info "Generated DB password: ${DB_PASSWORD}"

# -----------------------------  1) BASE PACKAGES  --------------------------
step "Updating system & installing base packages"
export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get upgrade -y -qq

apt-get install -y -qq \
    software-properties-common ca-certificates curl gnupg lsb-release \
    ufw fail2ban unattended-upgrades \
    git unzip tar zip \
    htop tmux jq

timedatectl set-timezone "$TIMEZONE" || true
info "Timezone: $TIMEZONE"

# -----------------------------  2) FIREWALL  -------------------------------
step "Configuring firewall"
ufw allow OpenSSH > /dev/null
ufw allow http    > /dev/null
ufw allow https   > /dev/null
ufw --force enable > /dev/null
ufw status | head -3

# -----------------------------  3) DEPLOY USER  ----------------------------
step "Ensuring deploy user '${APP_USER}'"
if ! id "$APP_USER" &> /dev/null; then
    adduser --disabled-password --gecos "" "$APP_USER"
    usermod -aG sudo "$APP_USER"
    if [[ -d /root/.ssh ]]; then
        rsync --archive --chown="${APP_USER}:${APP_USER}" /root/.ssh "/home/${APP_USER}/"
    fi
fi
# Allow deploy to reload php-fpm & restart queue worker without password
cat > /etc/sudoers.d/${APP_USER} <<EOF
${APP_USER} ALL=(ALL) NOPASSWD: /bin/systemctl reload php${PHP_VERSION}-fpm
${APP_USER} ALL=(ALL) NOPASSWD: /bin/systemctl restart ${APP_SLUG}-queue.service
${APP_USER} ALL=(ALL) NOPASSWD: /bin/systemctl status ${APP_SLUG}-queue.service
EOF
chmod 440 /etc/sudoers.d/${APP_USER}

# -----------------------------  4) PHP  ------------------------------------
step "Installing PHP ${PHP_VERSION} + extensions"
if [[ "$OS_ID" == "ubuntu" ]]; then
    add-apt-repository -y ppa:ondrej/php
else
    curl -sSL https://packages.sury.org/php/apt.gpg | gpg --dearmor > /etc/apt/trusted.gpg.d/sury.gpg
    echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/sury.list
fi
apt-get update -qq
apt-get install -y -qq \
    php${PHP_VERSION} php${PHP_VERSION}-fpm php${PHP_VERSION}-cli \
    php${PHP_VERSION}-bcmath php${PHP_VERSION}-gmp \
    php${PHP_VERSION}-mbstring php${PHP_VERSION}-xml php${PHP_VERSION}-zip php${PHP_VERSION}-intl \
    php${PHP_VERSION}-curl php${PHP_VERSION}-gd \
    php${PHP_VERSION}-mysql php${PHP_VERSION}-pgsql php${PHP_VERSION}-sqlite3 \
    php${PHP_VERSION}-redis php${PHP_VERSION}-opcache php${PHP_VERSION}-readline

# php.ini tuning matching this project's needs (3D files, intervention/image)
PHP_INI="/etc/php/${PHP_VERSION}/fpm/php.ini"
PHP_CLI_INI="/etc/php/${PHP_VERSION}/cli/php.ini"
for ini in "$PHP_INI" "$PHP_CLI_INI"; do
    sed -i \
        -e "s|^post_max_size = .*|post_max_size = 256M|" \
        -e "s|^upload_max_filesize = .*|upload_max_filesize = 256M|" \
        -e "s|^memory_limit = .*|memory_limit = 512M|" \
        -e "s|^max_execution_time = .*|max_execution_time = 120|" \
        -e "s|^;date.timezone =.*|date.timezone = ${TIMEZONE}|" \
        -e "s|^date.timezone =.*|date.timezone = ${TIMEZONE}|" \
        "$ini"
done

# OPcache (production): no timestamp checks for max speed (reload php-fpm after deploy)
cat > /etc/php/${PHP_VERSION}/fpm/conf.d/99-opcache.ini <<EOF
opcache.enable=1
opcache.enable_cli=0
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
opcache.revalidate_freq=0
opcache.save_comments=1
EOF

systemctl enable --now php${PHP_VERSION}-fpm
systemctl restart php${PHP_VERSION}-fpm

# -----------------------------  5) COMPOSER  -------------------------------
step "Installing Composer 2"
if ! command -v composer &> /dev/null; then
    EXPECTED=$(curl -fsSL https://composer.github.io/installer.sig)
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    ACTUAL=$(php -r "echo hash_file('sha384', 'composer-setup.php');")
    [[ "$EXPECTED" == "$ACTUAL" ]] || fatal "Composer installer checksum mismatch"
    php composer-setup.php --install-dir=/usr/local/bin --filename=composer
    rm -f composer-setup.php
fi
composer --version

# -----------------------------  6) NODE.JS  --------------------------------
step "Installing Node.js ${NODE_MAJOR}"
if ! command -v node &> /dev/null || [[ $(node -v | grep -oP '\d+' | head -1) -lt $NODE_MAJOR ]]; then
    curl -fsSL "https://deb.nodesource.com/setup_${NODE_MAJOR}.x" | bash -
    apt-get install -y -qq nodejs
fi
info "node $(node -v) | npm $(npm -v)"

# -----------------------------  7) MYSQL  ----------------------------------
step "Installing MySQL 8"
apt-get install -y -qq mysql-server
systemctl enable --now mysql

# Idempotent DB + user creation
mysql --protocol=socket -uroot <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';
ALTER USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL
info "Database '${DB_NAME}' ready"

# -----------------------------  8) REDIS  ----------------------------------
step "Installing Redis"
apt-get install -y -qq redis-server
systemctl enable --now redis-server
redis-cli ping

# -----------------------------  9) NGINX  ----------------------------------
step "Installing & configuring Nginx"
apt-get install -y -qq nginx
systemctl enable --now nginx

NGX="/etc/nginx/sites-available/${APP_SLUG}"
cat > "$NGX" <<NGINX
server {
    listen 80;
    listen [::]:80;
    server_name ${DOMAIN};
    root ${APP_DIR}/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header Referrer-Policy "strict-origin-when-cross-origin";

    index index.php;
    charset utf-8;

    client_max_body_size 256M;
    client_body_timeout 120s;

    gzip on;
    gzip_vary on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss text/javascript image/svg+xml;
    gzip_comp_level 6;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ ^/(storage|build|favicon\.ico|robots\.txt) {
        expires 7d;
        access_log off;
        try_files \$uri =404;
    }

    error_page 404 /index.php;

    location ~ \.php\$ {
        fastcgi_pass unix:/var/run/php/php${PHP_VERSION}-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 120s;
    }

    location ~ /\.(?!well-known).* { deny all; }
}
NGINX

ln -sf "$NGX" /etc/nginx/sites-enabled/${APP_SLUG}
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx

# -----------------------------  10) PROJECT CHECKOUT  ----------------------
step "Cloning project"
mkdir -p "$APP_DIR"
chown -R "${APP_USER}:${APP_GROUP}" "$APP_DIR"

if [[ "$GIT_REPO" != "skip" ]]; then
    if [[ -d "$APP_DIR/.git" ]]; then
        sudo -u "$APP_USER" git -C "$APP_DIR" pull --ff-only
    else
        sudo -u "$APP_USER" git clone --branch "$GIT_BRANCH" "$GIT_REPO" "$APP_DIR"
    fi
else
    warn "Skipping clone — upload code to ${APP_DIR} manually before continuing"
    if [[ ! -f "$APP_DIR/artisan" ]]; then
        echo "Press ENTER once code is in place at ${APP_DIR}, or Ctrl-C to abort."
        read -r
    fi
fi

# -----------------------------  11) .env + DEPENDENCIES  -------------------
step "Setting up .env and installing application dependencies"
cd "$APP_DIR"
if [[ ! -f .env ]]; then
    cp .env.example .env
    sed -i \
        -e "s|^APP_NAME=.*|APP_NAME=\"${APP_NAME}\"|" \
        -e "s|^APP_ENV=.*|APP_ENV=production|" \
        -e "s|^APP_DEBUG=.*|APP_DEBUG=false|" \
        -e "s|^APP_URL=.*|APP_URL=https://${DOMAIN}|" \
        -e "s|^DB_CONNECTION=.*|DB_CONNECTION=mysql|" \
        -e "s|^# DB_HOST=.*|DB_HOST=127.0.0.1|" \
        -e "s|^# DB_PORT=.*|DB_PORT=3306|" \
        -e "s|^# DB_DATABASE=.*|DB_DATABASE=${DB_NAME}|" \
        -e "s|^# DB_USERNAME=.*|DB_USERNAME=${DB_USER}|" \
        -e "s|^# DB_PASSWORD=.*|DB_PASSWORD=${DB_PASSWORD}|" \
        -e "s|^DB_DATABASE=.*|DB_DATABASE=${DB_NAME}|" \
        -e "s|^DB_USERNAME=.*|DB_USERNAME=${DB_USER}|" \
        -e "s|^DB_PASSWORD=.*|DB_PASSWORD=${DB_PASSWORD}|" \
        -e "s|^CACHE_STORE=.*|CACHE_STORE=redis|" \
        -e "s|^SESSION_DRIVER=.*|SESSION_DRIVER=redis|" \
        -e "s|^QUEUE_CONNECTION=.*|QUEUE_CONNECTION=redis|" \
        -e "s|^FILESYSTEM_DISK=.*|FILESYSTEM_DISK=public|" \
        .env
    chown "${APP_USER}:${APP_GROUP}" .env
    chmod 640 .env
fi

sudo -u "$APP_USER" composer install --no-dev --optimize-autoloader --prefer-dist --no-interaction
sudo -u "$APP_USER" npm ci
sudo -u "$APP_USER" npm run build

sudo -u "$APP_USER" php artisan key:generate --force
sudo -u "$APP_USER" php artisan storage:link
sudo -u "$APP_USER" php artisan migrate --force

# Production caches
sudo -u "$APP_USER" php artisan config:cache
sudo -u "$APP_USER" php artisan route:cache
sudo -u "$APP_USER" php artisan view:cache
sudo -u "$APP_USER" php artisan event:cache

# -----------------------------  12) PERMISSIONS  ---------------------------
step "Setting filesystem permissions"
chown -R "${APP_USER}:${APP_GROUP}" "$APP_DIR"
find "$APP_DIR" -type f -exec chmod 664 {} \;
find "$APP_DIR" -type d -exec chmod 775 {} \;
chmod -R 775 "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache"

# -----------------------------  13) QUEUE + SCHEDULER (systemd)  ----------
step "Installing systemd units for queue worker & scheduler"

cat > /etc/systemd/system/${APP_SLUG}-queue.service <<EOF
[Unit]
Description=${APP_NAME} Laravel queue worker
After=network.target redis-server.service mysql.service

[Service]
User=${APP_USER}
Group=${APP_GROUP}
Restart=always
RestartSec=3
WorkingDirectory=${APP_DIR}
ExecStart=/usr/bin/php artisan queue:work --sleep=3 --tries=3 --timeout=120 --max-time=3600

[Install]
WantedBy=multi-user.target
EOF

cat > /etc/systemd/system/${APP_SLUG}-scheduler.service <<EOF
[Unit]
Description=${APP_NAME} Laravel scheduler tick
After=network.target

[Service]
Type=oneshot
User=${APP_USER}
WorkingDirectory=${APP_DIR}
ExecStart=/usr/bin/php artisan schedule:run
EOF

cat > /etc/systemd/system/${APP_SLUG}-scheduler.timer <<EOF
[Unit]
Description=Run ${APP_NAME} scheduler every minute

[Timer]
OnBootSec=1min
OnUnitActiveSec=1min
AccuracySec=1s
Unit=${APP_SLUG}-scheduler.service

[Install]
WantedBy=timers.target
EOF

systemctl daemon-reload
systemctl enable --now ${APP_SLUG}-queue.service
systemctl enable --now ${APP_SLUG}-scheduler.timer

# -----------------------------  14) HTTPS (Let's Encrypt)  ----------------
step "Installing Certbot & issuing SSL certificate"
apt-get install -y -qq certbot python3-certbot-nginx
if [[ "$DOMAIN" != *"example.com"* ]]; then
    certbot --nginx -d "$DOMAIN" --email "$LE_EMAIL" --agree-tos --no-eff-email --redirect --non-interactive || \
        warn "Certbot failed — DNS for ${DOMAIN} probably not pointed at this server yet. Run later: certbot --nginx -d ${DOMAIN}"
else
    warn "Skipping Certbot (placeholder domain). Run later: certbot --nginx -d ${DOMAIN}"
fi

# -----------------------------  15) DEPLOY HELPER SCRIPT  -----------------
step "Installing /usr/local/bin/${APP_SLUG}-deploy helper"
cat > /usr/local/bin/${APP_SLUG}-deploy <<'DEPLOY'
#!/usr/bin/env bash
set -euo pipefail
APP_DIR="__APP_DIR__"
APP_SLUG="__APP_SLUG__"
PHP_VERSION="__PHP_VERSION__"

cd "$APP_DIR"
php artisan down --render="errors::503" || true
git pull --ff-only
composer install --no-dev --optimize-autoloader --prefer-dist --no-interaction
npm ci && npm run build
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache
sudo /bin/systemctl reload php${PHP_VERSION}-fpm
sudo /bin/systemctl restart ${APP_SLUG}-queue.service
php artisan up
echo "✓ Deployed"
DEPLOY
sed -i \
    -e "s|__APP_DIR__|${APP_DIR}|g" \
    -e "s|__APP_SLUG__|${APP_SLUG}|g" \
    -e "s|__PHP_VERSION__|${PHP_VERSION}|g" \
    /usr/local/bin/${APP_SLUG}-deploy
chmod +x /usr/local/bin/${APP_SLUG}-deploy

# -----------------------------  DONE  -------------------------------------
step "All done"
cat <<SUMMARY

  ${C_GRN}✓${C_RST} ${APP_NAME} provisioned successfully

  URL              :  https://${DOMAIN}
  Code             :  ${APP_DIR}
  Deploy user      :  ${APP_USER}
  PHP              :  ${PHP_VERSION} (FPM)
  Node             :  $(node -v)
  Database         :  ${DB_NAME} / ${DB_USER}
  DB password      :  ${DB_PASSWORD}
  Cache + Queue    :  Redis
  Worker           :  systemctl status ${APP_SLUG}-queue.service
  Scheduler timer  :  systemctl list-timers ${APP_SLUG}-scheduler.timer
  Re-deploy with   :  ${APP_SLUG}-deploy   (run as ${APP_USER})

  ${C_YEL}!${C_RST} Save the DB password in a secure place.
  ${C_YEL}!${C_RST} If Certbot was skipped, point DNS at this VPS and run:
        certbot --nginx -d ${DOMAIN}

SUMMARY
