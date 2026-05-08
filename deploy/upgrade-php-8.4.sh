#!/usr/bin/env bash
#
# Upgrade an existing 3Dify VPS from PHP 8.3 to 8.4 in-place.
# Run on the server as root:  sudo bash deploy/upgrade-php-8.4.sh
#
# Reason: composer.lock pinned by Laravel 13 (Symfony 8 components) requires
# PHP >= 8.4; the original provision.sh defaulted to 8.3.
# ---------------------------------------------------------------------------

set -euo pipefail

APP_SLUG="${APP_SLUG:-3dify}"
APP_DIR="${APP_DIR:-/var/www/${APP_SLUG}}"
APP_USER="${APP_USER:-deploy}"
OLD_VER="${OLD_VER:-8.3}"
NEW_VER="${NEW_VER:-8.4}"

C_RST="\033[0m"; C_GRN="\033[1;32m"; C_BLU="\033[1;34m"; C_YEL="\033[1;33m"
step() { echo -e "\n${C_BLU}==>${C_RST} ${C_GRN}$*${C_RST}"; }
warn() { echo -e "${C_YEL}!! $*${C_RST}"; }

[[ $EUID -eq 0 ]] || { echo "Run as root."; exit 1; }

step "Installing PHP ${NEW_VER} alongside ${OLD_VER}"
export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get install -y -qq \
    php${NEW_VER} php${NEW_VER}-fpm php${NEW_VER}-cli \
    php${NEW_VER}-bcmath php${NEW_VER}-gmp \
    php${NEW_VER}-mbstring php${NEW_VER}-xml php${NEW_VER}-zip php${NEW_VER}-intl \
    php${NEW_VER}-curl php${NEW_VER}-gd \
    php${NEW_VER}-mysql php${NEW_VER}-pgsql php${NEW_VER}-sqlite3 \
    php${NEW_VER}-redis php${NEW_VER}-opcache php${NEW_VER}-readline

step "Copying php.ini tuning to ${NEW_VER}"
for ini in /etc/php/${NEW_VER}/fpm/php.ini /etc/php/${NEW_VER}/cli/php.ini; do
    sed -i \
        -e "s|^post_max_size = .*|post_max_size = 256M|" \
        -e "s|^upload_max_filesize = .*|upload_max_filesize = 256M|" \
        -e "s|^memory_limit = .*|memory_limit = 512M|" \
        -e "s|^max_execution_time = .*|max_execution_time = 120|" \
        -e "s|^;date.timezone =.*|date.timezone = Europe/Kyiv|" \
        -e "s|^date.timezone =.*|date.timezone = Europe/Kyiv|" \
        "$ini"
done

if [[ -f /etc/php/${OLD_VER}/fpm/conf.d/99-opcache.ini ]]; then
    cp /etc/php/${OLD_VER}/fpm/conf.d/99-opcache.ini /etc/php/${NEW_VER}/fpm/conf.d/
fi

step "Switching system 'php' alternative to ${NEW_VER}"
update-alternatives --set php /usr/bin/php${NEW_VER} || true

step "Switching FPM service: stop ${OLD_VER}, start ${NEW_VER}"
systemctl disable --now php${OLD_VER}-fpm || true
systemctl enable  --now php${NEW_VER}-fpm
systemctl restart php${NEW_VER}-fpm

step "Updating Nginx site to use the ${NEW_VER} FPM socket"
SITE="/etc/nginx/sites-available/${APP_SLUG}"
if [[ -f "$SITE" ]]; then
    sed -i "s|php${OLD_VER}-fpm.sock|php${NEW_VER}-fpm.sock|g" "$SITE"
    nginx -t && systemctl reload nginx
else
    warn "Nginx site $SITE not found; edit your vhost manually if you have a different name."
fi

step "Updating sudoers / deploy helper to reference ${NEW_VER}"
if [[ -f /etc/sudoers.d/${APP_USER} ]]; then
    sed -i "s|php${OLD_VER}-fpm|php${NEW_VER}-fpm|g" /etc/sudoers.d/${APP_USER}
fi
if [[ -f /usr/local/bin/${APP_SLUG}-deploy ]]; then
    sed -i "s|php${OLD_VER}|php${NEW_VER}|g" /usr/local/bin/${APP_SLUG}-deploy
fi

step "Reinstalling Composer dependencies on the new runtime"
cd "$APP_DIR"
sudo -u "$APP_USER" rm -rf vendor
sudo -u "$APP_USER" composer install --no-dev --optimize-autoloader --prefer-dist --no-interaction

step "Re-caching Laravel + reloading services"
sudo -u "$APP_USER" php artisan config:cache
sudo -u "$APP_USER" php artisan route:cache
sudo -u "$APP_USER" php artisan view:cache
sudo -u "$APP_USER" php artisan event:cache
systemctl reload php${NEW_VER}-fpm
systemctl restart ${APP_SLUG}-queue.service || true

step "Done — system PHP is now $(php -v | head -n1)"

cat <<EOF

  ${C_GRN}✓${C_RST} PHP upgraded ${OLD_VER} → ${NEW_VER}.

  Verify:
    sudo systemctl status php${NEW_VER}-fpm nginx ${APP_SLUG}-queue.service
    php -v
    curl -I https://\$DOMAIN

  When you're confident the app is healthy, you can remove the old runtime:
    sudo apt purge -y 'php${OLD_VER}-*'
    sudo apt autoremove -y

EOF
