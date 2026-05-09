#!/usr/bin/env bash
#
# 3Dify VPS provisioning  —  Ubuntu 24.04 LTS / 22.04 LTS / Debian 12
# ============================================================================
#
# Usage on a fresh box (run as root):
#
#   # Interactive — will prompt for missing values:
#   bash provision.sh
#
#   # Non-interactive (recommended for CI / repeatable installs):
#   DOMAIN=3dify.example.com \
#   LE_EMAIL=admin@example.com \
#   GIT_REPO=git@github.com:USER/3dify.git \
#   bash provision.sh
#
# Re-running is SAFE.  Every step checks state first and skips work that's
# already done.  If something fails, fix and re-run — you won't get duplicate
# users, broken nginx vhosts or repeated apt prompts.
#
# What you get:
#   • PHP 8.4 + FPM + every extension this app needs
#   • Composer 2, Node.js 20 (LTS), MySQL 8, Redis 7+, Nginx, Certbot/SSL
#   • A `deploy` user with restricted sudo
#   • Code at /var/www/3dify, owned by deploy:www-data
#   • systemd units for queue worker + scheduler (no supervisor / cron)
#   • One-command redeploy helper:  3dify-deploy
#
# ============================================================================

set -euo pipefail
IFS=$'\n\t'

# ─── Config (overridable via env) ──────────────────────────────────────────
APP_NAME="${APP_NAME:-3Dify}"
APP_SLUG="${APP_SLUG:-3dify}"
APP_DIR="${APP_DIR:-/var/www/${APP_SLUG}}"
APP_USER="${APP_USER:-deploy}"
APP_GROUP="www-data"

PHP_VERSION="${PHP_VERSION:-8.4}"
NODE_MAJOR="${NODE_MAJOR:-20}"
TIMEZONE="${TIMEZONE:-Europe/Kyiv}"

DOMAIN="${DOMAIN:-}"
LE_EMAIL="${LE_EMAIL:-}"
GIT_REPO="${GIT_REPO:-}"
GIT_BRANCH="${GIT_BRANCH:-main}"

DB_NAME="${DB_NAME:-${APP_SLUG}}"
DB_USER="${DB_USER:-${APP_SLUG}}"
DB_PASSWORD="${DB_PASSWORD:-}"

# ─── Helpers ────────────────────────────────────────────────────────────────
C_RST="\033[0m"; C_GRN="\033[1;32m"; C_BLU="\033[1;34m"; C_YEL="\033[1;33m"; C_RED="\033[1;31m"; C_DIM="\033[2m"
step()    { echo -e "\n${C_BLU}==>${C_RST} ${C_GRN}$*${C_RST}"; }
info()    { echo -e "    ${C_DIM}·${C_RST} $*"; }
warn()    { echo -e "${C_YEL}!!  $*${C_RST}"; }
fatal()   { echo -e "${C_RED}xx  $*${C_RST}"; exit 1; }
prompt_if_empty() { local v=$1 q=$2; if [[ -z "${!v}" ]]; then read -rp "$q " "$v"; fi; [[ -n "${!v}" ]] || fatal "$v required"; export "$v"; }
gen_password()    { openssl rand -base64 24 | tr -d '/+=\n' | cut -c1-24; }
have()            { command -v "$1" >/dev/null 2>&1; }

# ─── Pre-flight ─────────────────────────────────────────────────────────────
[[ $EUID -eq 0 ]] || fatal "Run as root or with sudo."

OS_ID=$(. /etc/os-release && echo "$ID")
OS_VERSION=$(. /etc/os-release && echo "$VERSION_ID")
case "$OS_ID" in
    ubuntu|debian) info "Detected ${OS_ID} ${OS_VERSION}";;
    *) fatal "Unsupported OS: $OS_ID. Use Ubuntu 22.04+ or Debian 12+." ;;
esac

prompt_if_empty DOMAIN     "Domain (e.g. 3dify.example.com):"
prompt_if_empty LE_EMAIL   "Email for Let's Encrypt:"
prompt_if_empty GIT_REPO   "Git repository URL (or 'skip' to upload code manually):"
[[ -z "$DB_PASSWORD" ]] && DB_PASSWORD=$(gen_password) && info "Generated DB password: ${DB_PASSWORD}"

# ─── 1. Base packages & timezone ────────────────────────────────────────────
step "Updating system & installing base packages"
export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get -y -qq -o Dpkg::Options::="--force-confnew" upgrade

apt-get install -y -qq \
    software-properties-common ca-certificates curl gnupg lsb-release \
    ufw fail2ban unattended-upgrades \
    git unzip tar zip rsync \
    htop tmux jq

timedatectl set-timezone "$TIMEZONE" || true
info "Timezone: $TIMEZONE"

# ─── 2. Firewall ────────────────────────────────────────────────────────────
step "Configuring firewall"
ufw allow OpenSSH > /dev/null
ufw allow http    > /dev/null
ufw allow https   > /dev/null
ufw --force enable > /dev/null
info "$(ufw status | head -1)"

# ─── 3. Deploy user ─────────────────────────────────────────────────────────
step "Ensuring deploy user '${APP_USER}'"
if ! id "$APP_USER" &> /dev/null; then
    adduser --disabled-password --gecos "" "$APP_USER"
    usermod -aG sudo "$APP_USER"
    if [[ -d /root/.ssh ]]; then
        rsync --archive --chown="${APP_USER}:${APP_USER}" /root/.ssh "/home/${APP_USER}/" || true
    fi
    info "Created user ${APP_USER}"
else
    info "User ${APP_USER} already exists"
fi

# Restricted sudo: only the commands deploy actually needs.
cat > /etc/sudoers.d/${APP_USER} <<EOF
${APP_USER} ALL=(ALL) NOPASSWD: /bin/systemctl reload php${PHP_VERSION}-fpm
${APP_USER} ALL=(ALL) NOPASSWD: /bin/systemctl restart ${APP_SLUG}-queue.service
${APP_USER} ALL=(ALL) NOPASSWD: /bin/systemctl status ${APP_SLUG}-queue.service
${APP_USER} ALL=(ALL) NOPASSWD: /bin/systemctl reload nginx
EOF
chmod 440 /etc/sudoers.d/${APP_USER}

# ─── 4. PHP 8.4 + extensions ────────────────────────────────────────────────
step "Installing PHP ${PHP_VERSION} + extensions"
if [[ "$OS_ID" == "ubuntu" ]]; then
    add-apt-repository -y ppa:ondrej/php
else
    if [[ ! -f /etc/apt/sources.list.d/sury.list ]]; then
        curl -sSL https://packages.sury.org/php/apt.gpg | gpg --dearmor > /etc/apt/trusted.gpg.d/sury.gpg
        echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/sury.list
    fi
fi
apt-get update -qq
apt-get install -y -qq \
    php${PHP_VERSION} php${PHP_VERSION}-fpm php${PHP_VERSION}-cli \
    php${PHP_VERSION}-bcmath php${PHP_VERSION}-gmp \
    php${PHP_VERSION}-mbstring php${PHP_VERSION}-xml php${PHP_VERSION}-zip php${PHP_VERSION}-intl \
    php${PHP_VERSION}-curl php${PHP_VERSION}-gd \
    php${PHP_VERSION}-mysql php${PHP_VERSION}-pgsql php${PHP_VERSION}-sqlite3 \
    php${PHP_VERSION}-redis php${PHP_VERSION}-opcache php${PHP_VERSION}-readline

# Make /usr/bin/php point at the chosen version
update-alternatives --set php /usr/bin/php${PHP_VERSION} || true

# Tune php.ini for 3D files (Intervention/Image, big STL/OBJ uploads)
for ini in /etc/php/${PHP_VERSION}/fpm/php.ini /etc/php/${PHP_VERSION}/cli/php.ini; do
    sed -i \
        -e "s|^post_max_size = .*|post_max_size = 256M|" \
        -e "s|^upload_max_filesize = .*|upload_max_filesize = 256M|" \
        -e "s|^memory_limit = .*|memory_limit = 512M|" \
        -e "s|^max_execution_time = .*|max_execution_time = 120|" \
        -e "s|^;date.timezone =.*|date.timezone = ${TIMEZONE}|" \
        -e "s|^date.timezone =.*|date.timezone = ${TIMEZONE}|" \
        "$ini"
done

# Aggressive OPcache for production. After every deploy we reload php-fpm so
# new code takes effect — without that, validate_timestamps=0 would serve stale code.
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
info "PHP $(php -v | head -n1)"

# ─── 5. Composer 2 ──────────────────────────────────────────────────────────
step "Installing Composer 2"
if ! have composer; then
    EXPECTED_SIG=$(curl -fsSL https://composer.github.io/installer.sig)
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    ACTUAL_SIG=$(php -r "echo hash_file('sha384', 'composer-setup.php');")
    [[ "$EXPECTED_SIG" == "$ACTUAL_SIG" ]] || fatal "Composer installer checksum mismatch"
    php composer-setup.php --quiet --install-dir=/usr/local/bin --filename=composer
    rm -f composer-setup.php
fi
info "$(composer --version)"

# ─── 6. Node.js 20 ──────────────────────────────────────────────────────────
step "Installing Node.js ${NODE_MAJOR}"
NEED_NODE=1
if have node; then
    CUR=$(node -v | sed 's/v//' | cut -d. -f1)
    [[ "$CUR" -ge "$NODE_MAJOR" ]] && NEED_NODE=0
fi
if [[ "$NEED_NODE" -eq 1 ]]; then
    curl -fsSL "https://deb.nodesource.com/setup_${NODE_MAJOR}.x" | bash -
    apt-get install -y -qq nodejs
fi
info "node $(node -v) | npm $(npm -v)"

# ─── 7. MySQL 8 ─────────────────────────────────────────────────────────────
step "Installing MySQL 8"
apt-get install -y -qq mysql-server
systemctl enable --now mysql

mysql --protocol=socket -uroot <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';
ALTER USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL
info "Database '${DB_NAME}' ready (user: ${DB_USER})"

# ─── 8. Redis ───────────────────────────────────────────────────────────────
step "Installing Redis"
apt-get install -y -qq redis-server
systemctl enable --now redis-server
redis-cli ping > /dev/null && info "Redis: PONG"

# ─── 9. Nginx ───────────────────────────────────────────────────────────────
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

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff"  always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    index index.php;
    charset utf-8;

    client_max_body_size 256M;
    client_body_timeout  120s;

    gzip on;
    gzip_vary on;
    gzip_types text/plain text/css application/json application/javascript text/xml
               application/xml application/xml+rss text/javascript image/svg+xml;
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

# Validate before reload — bad config aborts here, doesn't break running site
nginx -t && systemctl reload nginx

# ─── 10. systemd units (BEFORE migrations, so deploy script can restart them) ─
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
# Don't start the queue worker yet — code isn't there. We start it after deploy.
systemctl enable ${APP_SLUG}-queue.service
systemctl enable ${APP_SLUG}-scheduler.timer
info "Units registered (will start after first successful deploy)"

# ─── 11. Project checkout ───────────────────────────────────────────────────
step "Cloning project to ${APP_DIR}"
mkdir -p "$APP_DIR"
chown -R "${APP_USER}:${APP_GROUP}" "$APP_DIR"

if [[ "$GIT_REPO" != "skip" ]]; then
    if [[ -d "$APP_DIR/.git" ]]; then
        info "Repo already cloned, pulling latest"
        sudo -u "$APP_USER" git -C "$APP_DIR" pull --ff-only
    else
        sudo -u "$APP_USER" git clone --branch "$GIT_BRANCH" "$GIT_REPO" "$APP_DIR"
    fi
else
    warn "Skipping git clone — upload code to ${APP_DIR} manually"
    if [[ ! -f "$APP_DIR/artisan" ]]; then
        echo "Press ENTER once code is in place, or Ctrl-C to abort."
        read -r
    fi
fi

# ─── 12. .env, dependencies, build, migrate ────────────────────────────────
step "Configuring .env"
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
    info ".env initialized (review and edit it if you need SMTP/payment keys)"
else
    info ".env already exists, leaving it as-is"
fi

step "Installing PHP dependencies"
sudo -u "$APP_USER" composer install --no-dev --optimize-autoloader --prefer-dist --no-interaction

step "Installing & building frontend"
# Use `npm install` (not `npm ci`): it tolerates lock drift, which spares us
# downtime when someone forgets to commit package-lock.json. Vite still needs
# the full devDependencies set, so we don't pass --omit=dev here.
sudo -u "$APP_USER" -H bash -lc "
    cd '$APP_DIR'
    rm -rf node_modules
    npm install --no-audit --no-fund
    npm run build
    rm -rf node_modules
"
info "Frontend built (node_modules removed — Vite output lives in public/build)"

step "Generating app key, linking storage, running migrations"
sudo -u "$APP_USER" php artisan key:generate --force
sudo -u "$APP_USER" php artisan storage:link || true   # already linked → ok

# Test DB connectivity before attempting migrations — gives a clear error
# instead of cryptic SQLSTATE messages later.
sudo -u "$APP_USER" php -r "
    require 'vendor/autoload.php';
    \$app = require 'bootstrap/app.php';
    \$app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();
    \$pdo = DB::connection()->getPdo();
    echo 'DB connection: '.\$pdo->getAttribute(PDO::ATTR_SERVER_VERSION).PHP_EOL;
" || fatal "Cannot connect to database — check .env credentials"

sudo -u "$APP_USER" php artisan migrate --force --no-interaction

step "Caching production config / routes / views / events"
sudo -u "$APP_USER" php artisan config:cache
sudo -u "$APP_USER" php artisan route:cache
sudo -u "$APP_USER" php artisan view:cache
sudo -u "$APP_USER" php artisan event:cache

# ─── 13. Filesystem permissions ─────────────────────────────────────────────
step "Setting filesystem permissions"
chown -R "${APP_USER}:${APP_GROUP}" "$APP_DIR"
find "$APP_DIR" -type f -exec chmod 664 {} \;
find "$APP_DIR" -type d -exec chmod 775 {} \;
chmod -R 775 "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache"

# ─── 14. Start queue worker NOW (code is in place) ──────────────────────────
step "Starting queue worker"
systemctl restart ${APP_SLUG}-queue.service
systemctl start   ${APP_SLUG}-scheduler.timer
sleep 1
systemctl is-active ${APP_SLUG}-queue.service > /dev/null \
    && info "queue worker: active" \
    || warn "queue worker not active — check: journalctl -u ${APP_SLUG}-queue.service"

# ─── 15. SSL via Let's Encrypt ──────────────────────────────────────────────
step "Installing Certbot & issuing SSL certificate"
apt-get install -y -qq certbot python3-certbot-nginx
if [[ "$DOMAIN" == *"example.com"* ]]; then
    warn "Skipping Certbot — \$DOMAIN is a placeholder (${DOMAIN})."
    warn "Run later:  certbot --nginx -d ${DOMAIN} --email ${LE_EMAIL} --agree-tos --redirect"
elif certbot --nginx -d "$DOMAIN" --email "$LE_EMAIL" --agree-tos --no-eff-email --redirect --non-interactive; then
    info "SSL certificate issued successfully"
else
    warn "Certbot failed (probably DNS for ${DOMAIN} doesn't point at this server yet)."
    warn "Run later:  certbot --nginx -d ${DOMAIN}"
fi

# ─── 16. Deploy helper ──────────────────────────────────────────────────────
step "Installing /usr/local/bin/${APP_SLUG}-deploy"
cat > /usr/local/bin/${APP_SLUG}-deploy <<DEPLOY
#!/usr/bin/env bash
set -euo pipefail
APP_DIR="${APP_DIR}"
APP_SLUG="${APP_SLUG}"
APP_USER="${APP_USER}"
PHP_VERSION="${PHP_VERSION}"

# When invoked as root (the usual \`sudo \${APP_SLUG}-deploy\`), re-exec
# under the deploy user so git/composer/npm/artisan touch files as the
# project owner. This avoids git's "dubious ownership" guard and keeps
# file permissions stable across deploys.
if [[ "\$EUID" -eq 0 ]]; then
    exec sudo -u "\$APP_USER" -H bash "\$0" "\$@"
fi

echo "==> Re-deploying \${APP_SLUG}"
cd "\${APP_DIR}"
php artisan down --render="errors::503" || true

git pull --ff-only
composer install --no-dev --optimize-autoloader --prefer-dist --no-interaction

# npm install is more forgiving than npm ci when a deploy is in flight —
# we don't want a stale lock file to take the site down.
rm -rf node_modules
npm install --no-audit --no-fund
npm run build
rm -rf node_modules

php artisan migrate --force --no-interaction
php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache

# Sudoers grants the deploy user passwordless access to exactly these
# two systemctl commands (see /etc/sudoers.d/\${APP_USER}).
sudo /bin/systemctl reload php\${PHP_VERSION}-fpm
sudo /bin/systemctl restart \${APP_SLUG}-queue.service

php artisan up
echo "✓ \${APP_SLUG} deployed at \$(date)"
DEPLOY
chmod +x /usr/local/bin/${APP_SLUG}-deploy

# ─── 17. Smoke check ────────────────────────────────────────────────────────
step "Smoke checks"
SMOKE_OK=1
for unit in php${PHP_VERSION}-fpm nginx mysql redis-server ${APP_SLUG}-queue.service; do
    if systemctl is-active --quiet "$unit"; then
        info "✓ $unit"
    else
        warn "✗ $unit not active"
        SMOKE_OK=0
    fi
done

# Try fetching homepage. If Certbot succeeded → use https; else http.
if curl -sIo /dev/null --max-time 5 -w "%{http_code}" "http://${DOMAIN}/" 2>/dev/null | grep -qE "^(200|301|302)$"; then
    info "✓ HTTP responds OK"
else
    warn "Homepage didn't return 2xx/3xx — check DNS, nginx logs, or .env"
fi

# ─── DONE ────────────────────────────────────────────────────────────────────
step "All done"
# Use `echo -e` (or `printf`) so the ANSI escape codes are interpreted —
# `cat <<HEREDOC` would print them as literal "\033[1;32m" garbage.
echo -e "
  ${C_GRN}✓${C_RST} ${APP_NAME} provisioned

    URL              :  https://${DOMAIN}
    Code             :  ${APP_DIR}
    Deploy user      :  ${APP_USER}
    PHP              :  ${PHP_VERSION}
    Node             :  $(node -v)
    Database         :  ${DB_NAME} / ${DB_USER}
    DB password      :  ${DB_PASSWORD}     ${C_YEL}<-- save this!${C_RST}
    Cache + Queue    :  Redis
    Worker           :  systemctl status ${APP_SLUG}-queue.service
    Scheduler timer  :  systemctl list-timers ${APP_SLUG}-scheduler.timer
    Re-deploy        :  ssh ${APP_USER}@${DOMAIN} '${APP_SLUG}-deploy'
"

if [[ "$SMOKE_OK" -eq 0 ]]; then
    warn "Some smoke checks failed — investigate before celebrating."
    exit 1
fi
