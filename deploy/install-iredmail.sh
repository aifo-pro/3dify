#!/usr/bin/env bash
#
# Install iRedMail on a fresh Ubuntu 24.04 / 22.04 / Debian 12 server.
# ============================================================================
#
# Run THIS SCRIPT on a freshly-provisioned VPS that will become your mail
# server.  Do NOT run it on a server that already has Postfix / Dovecot /
# Apache installed — iRedMail is opinionated and will refuse.
#
# What you get:
#   • Postfix 3.x (incoming + outgoing SMTP, port 25/465/587)
#   • Dovecot 2.x (IMAP 143/993, POP3 110/995, Sieve 4190)
#   • Rspamd (anti-spam + DKIM signing — replaces Amavis on modern stacks)
#   • Roundcube (webmail) at https://<hostname>/mail/
#   • iRedAdmin (admin panel) at https://<hostname>/iredadmin/
#   • SOGo (CalDAV/CardDAV)  at https://<hostname>/SOGo/
#   • Let's Encrypt SSL for the hostname, auto-renewing
#   • UFW firewall rules opened for mail ports
#   • One mail domain pre-configured + postmaster mailbox
#
# Quick install:
#
#   sudo wget -qO /tmp/install-iredmail.sh \
#       https://raw.githubusercontent.com/aifo-pro/3dify/main/deploy/install-iredmail.sh
#   sudo DOMAIN=3dify.dev LE_EMAIL=you@gmail.com bash /tmp/install-iredmail.sh
#
# Or interactive (will prompt for missing values):
#
#   sudo bash /tmp/install-iredmail.sh
#
# After the installer finishes you'll get:
#   • DNS records to paste into Namecheap (A, MX, SPF, DKIM, DMARC)
#   • Web URLs and credentials for Roundcube + iRedAdmin
#   • Laravel .env block ready to paste on the 3Dify VPS
#
# ============================================================================

set -euo pipefail
IFS=$'\n\t'

# ─── Defaults (override via env) ────────────────────────────────────────────
IREDMAIL_VERSION="${IREDMAIL_VERSION:-1.7.2}"
DOMAIN="${DOMAIN:-}"
MAIL_HOSTNAME="${MAIL_HOSTNAME:-}"
LE_EMAIL="${LE_EMAIL:-}"
USE_CLAMAV="${USE_CLAMAV:-AUTO}"      # AUTO|YES|NO  (AUTO disables if RAM<4G)
USE_FAIL2BAN="${USE_FAIL2BAN:-YES}"
USE_NETDATA="${USE_NETDATA:-NO}"      # heavy, off by default
WEB_SERVER="${WEB_SERVER:-NGINX}"
TIMEZONE="${TIMEZONE:-Europe/Kyiv}"
POSTMASTER_USER="${POSTMASTER_USER:-postmaster}"

# ─── Helpers ────────────────────────────────────────────────────────────────
C_RST="\033[0m"; C_GRN="\033[1;32m"; C_BLU="\033[1;34m"; C_YEL="\033[1;33m"; C_RED="\033[1;31m"; C_DIM="\033[2m"
step()  { echo -e "\n${C_BLU}==>${C_RST} ${C_GRN}$*${C_RST}"; }
info()  { echo -e "    ${C_DIM}·${C_RST} $*"; }
warn()  { echo -e "${C_YEL}!!  $*${C_RST}"; }
fatal() { echo -e "${C_RED}xx  $*${C_RST}"; exit 1; }

prompt() {
    local var="$1" question="$2" default="${3:-}"
    if [[ -z "${!var:-}" ]]; then
        if [[ -n "$default" ]]; then
            read -rp "$question [$default]: " "$var" || true
            eval "$var=\${$var:-$default}"
        else
            read -rp "$question " "$var" || true
        fi
    fi
    [[ -n "${!var:-}" ]] || fatal "$var is required"
    export "$var"
}

gen_password() {
    tr -dc 'A-HJ-NP-Za-km-z2-9' </dev/urandom | head -c 24
}

have() { command -v "$1" >/dev/null 2>&1; }

# ─── 1. Pre-flight ──────────────────────────────────────────────────────────
[[ $EUID -eq 0 ]] || fatal "Run as root (sudo bash $0)"

OS_ID=$(. /etc/os-release && echo "$ID")
OS_VERSION=$(. /etc/os-release && echo "$VERSION_ID")
case "$OS_ID-$OS_VERSION" in
    ubuntu-24.04|ubuntu-22.04|debian-12|debian-11) info "OS: $OS_ID $OS_VERSION (supported)";;
    *) fatal "Unsupported OS: $OS_ID $OS_VERSION. iRedMail supports Ubuntu 22+/Debian 11+ only.";;
esac

RAM_MB=$(awk '/MemTotal/ {print int($2/1024)}' /proc/meminfo)
DISK_FREE_GB=$(df -BG --output=avail / | tail -1 | tr -d 'G ')
info "RAM       : ${RAM_MB} MB"
info "Disk free : ${DISK_FREE_GB} GB"

[[ "$RAM_MB" -ge 1500 ]] || warn "RAM is below 1.5 GB — install may swap heavily."
[[ "$DISK_FREE_GB" -ge 8 ]] || fatal "Need at least 8 GB free on /"

# Auto-decide ClamAV based on RAM
if [[ "$USE_CLAMAV" == "AUTO" ]]; then
    if [[ "$RAM_MB" -ge 3500 ]]; then
        USE_CLAMAV="YES"
        info "ClamAV    : YES (RAM >= 4G)"
    else
        USE_CLAMAV="NO"
        info "ClamAV    : NO (RAM < 4G — would OOM)"
    fi
fi

# Check existing mail services
for unit in postfix dovecot apache2 exim4 sendmail; do
    if systemctl list-unit-files 2>/dev/null | grep -q "^${unit}\."; then
        warn "$unit is already installed — iRedMail will fail to install."
        warn "Stop and uninstall it first:  systemctl stop $unit && apt purge -y $unit"
    fi
done

# Check ports are free
PORTS_BUSY=()
for p in 25 80 110 143 443 465 587 993 995 4190 8080; do
    if ss -tln "( sport = :$p )" 2>/dev/null | grep -q "LISTEN"; then
        PORTS_BUSY+=("$p")
    fi
done
if [[ "${#PORTS_BUSY[@]}" -gt 0 ]]; then
    warn "Ports busy: ${PORTS_BUSY[*]} — iRedMail may fail. Stop the conflicting service."
fi

# Public IP & PTR
PUBLIC_IP=$(curl -fsSL4 https://api.ipify.org 2>/dev/null || ip -4 -o addr show scope global | awk '{print $4}' | cut -d/ -f1 | head -1)
PTR=$(dig +short -x "$PUBLIC_IP" 2>/dev/null | sed 's/\.$//' | head -1)
info "Public IP : ${PUBLIC_IP}"
info "PTR rDNS  : ${PTR:-<not set>}"

# ─── 2. Inputs ──────────────────────────────────────────────────────────────
prompt DOMAIN          "Mail domain you'll host (e.g. 3dify.dev):"
[[ -z "$MAIL_HOSTNAME" ]] && MAIL_HOSTNAME="mail.${DOMAIN}"
prompt MAIL_HOSTNAME   "Mail server hostname (will be set as system FQDN):" "$MAIL_HOSTNAME"
prompt LE_EMAIL        "Email for Let's Encrypt + DMARC reports:"

# Validate that we WILL be reachable: hostname A-record should already point at PUBLIC_IP
RESOLVED_IP=$(dig +short A "$MAIL_HOSTNAME" 2>/dev/null | head -1)
if [[ "$RESOLVED_IP" != "$PUBLIC_IP" ]]; then
    warn "DNS for $MAIL_HOSTNAME currently resolves to '${RESOLVED_IP:-nothing}', not ${PUBLIC_IP}."
    warn "Without correct A-record, Let's Encrypt will FAIL to issue certificate."
    warn "Add this DNS record FIRST:    A    ${MAIL_HOSTNAME}    ${PUBLIC_IP}"
    read -rp "    Continue anyway (Let's Encrypt will be retried later)? [y/N]: " ans
    [[ "$ans" =~ ^[Yy]$ ]] || exit 0
fi

if [[ -z "$PTR" || "$PTR" != "$MAIL_HOSTNAME" ]]; then
    warn "PTR for ${PUBLIC_IP} is '${PTR:-<empty>}', not ${MAIL_HOSTNAME}."
    warn "Without proper rDNS, Gmail / Outlook will REJECT your outgoing mail."
    warn "Open a ticket with your VPS provider:"
    warn "    'Please set rDNS / PTR for ${PUBLIC_IP} to ${MAIL_HOSTNAME}'"
    read -rp "    Continue and fix PTR after install? [y/N]: " ans
    [[ "$ans" =~ ^[Yy]$ ]] || exit 0
fi

# ─── 3. System prep ─────────────────────────────────────────────────────────
step "Setting hostname → ${MAIL_HOSTNAME}"
hostnamectl set-hostname "$MAIL_HOSTNAME"
echo "$MAIL_HOSTNAME" > /etc/hostname

# Make /etc/hosts authoritative
sed -i "/^127\.0\.1\.1[[:space:]]\+/d" /etc/hosts
echo "127.0.1.1   ${MAIL_HOSTNAME} ${MAIL_HOSTNAME%%.*}" >> /etc/hosts
info "$(hostname -f)"

step "Setting timezone → ${TIMEZONE}"
timedatectl set-timezone "$TIMEZONE" || true

step "Updating apt & installing prerequisites"
export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get install -y -qq \
    wget curl tar bzip2 ca-certificates dnsutils \
    ufw \
    >/dev/null
info "Done"

# ─── 4. Firewall ────────────────────────────────────────────────────────────
step "Opening firewall ports for SSH / web / mail"
for rule in 22/tcp 80/tcp 443/tcp 25/tcp 465/tcp 587/tcp 143/tcp 993/tcp 110/tcp 995/tcp 4190/tcp; do
    ufw allow "$rule" >/dev/null
done
yes y | ufw enable >/dev/null 2>&1 || true
info "$(ufw status | head -1)"

# ─── 5. Download iRedMail ───────────────────────────────────────────────────
step "Downloading iRedMail ${IREDMAIL_VERSION}"
cd /opt
if [[ ! -d "/opt/iRedMail-${IREDMAIL_VERSION}" ]]; then
    wget -q "https://github.com/iredmail/iRedMail/archive/refs/tags/${IREDMAIL_VERSION}.tar.gz" -O "/opt/iRedMail-${IREDMAIL_VERSION}.tar.gz"
    tar xf "/opt/iRedMail-${IREDMAIL_VERSION}.tar.gz" -C /opt
    info "Extracted to /opt/iRedMail-${IREDMAIL_VERSION}"
else
    info "Already extracted at /opt/iRedMail-${IREDMAIL_VERSION}"
fi
cd "/opt/iRedMail-${IREDMAIL_VERSION}"

# ─── 6. Generate passwords ──────────────────────────────────────────────────
step "Generating strong passwords"
CREDS_FILE="/root/iredmail-${DOMAIN}.creds"
if [[ -f "$CREDS_FILE" ]]; then
    info "Re-using existing credentials from $CREDS_FILE"
    # shellcheck disable=SC1090
    source "$CREDS_FILE"
else
    MYSQL_ROOT_PASSWD=$(gen_password)
    VMAIL_DB_BIND_PASSWD=$(gen_password)
    VMAIL_DB_ADMIN_PASSWD=$(gen_password)
    AMAVISD_DB_PASSWD=$(gen_password)
    IREDADMIN_DB_PASSWD=$(gen_password)
    IREDAPD_DB_PASSWD=$(gen_password)
    RCM_DB_PASSWD=$(gen_password)
    SOGO_DB_PASSWD=$(gen_password)
    SOGO_SIEVE_MASTER_PASSWD=$(gen_password)
    NETDATA_DB_PASSWD=$(gen_password)
    DOMAIN_ADMIN_PASSWD=$(gen_password)
    POSTMASTER_PASS=$(gen_password)
    NOREPLY_PASS=$(gen_password)
    INFO_PASS=$(gen_password)
    SUPPORT_PASS=$(gen_password)
    MLMMJADMIN_API_AUTH_TOKEN=$(gen_password)
    cat > "$CREDS_FILE" <<CREDS
MYSQL_ROOT_PASSWD='${MYSQL_ROOT_PASSWD}'
VMAIL_DB_BIND_PASSWD='${VMAIL_DB_BIND_PASSWD}'
VMAIL_DB_ADMIN_PASSWD='${VMAIL_DB_ADMIN_PASSWD}'
AMAVISD_DB_PASSWD='${AMAVISD_DB_PASSWD}'
IREDADMIN_DB_PASSWD='${IREDADMIN_DB_PASSWD}'
IREDAPD_DB_PASSWD='${IREDAPD_DB_PASSWD}'
RCM_DB_PASSWD='${RCM_DB_PASSWD}'
SOGO_DB_PASSWD='${SOGO_DB_PASSWD}'
SOGO_SIEVE_MASTER_PASSWD='${SOGO_SIEVE_MASTER_PASSWD}'
NETDATA_DB_PASSWD='${NETDATA_DB_PASSWD}'
DOMAIN_ADMIN_PASSWD='${DOMAIN_ADMIN_PASSWD}'
POSTMASTER_PASS='${POSTMASTER_PASS}'
NOREPLY_PASS='${NOREPLY_PASS}'
INFO_PASS='${INFO_PASS}'
SUPPORT_PASS='${SUPPORT_PASS}'
MLMMJADMIN_API_AUTH_TOKEN='${MLMMJADMIN_API_AUTH_TOKEN}'
CREDS
    chmod 600 "$CREDS_FILE"
    info "Saved to $CREDS_FILE (mode 600)"
fi

# ─── 7. Pre-fill iRedMail config ────────────────────────────────────────────
step "Writing iRedMail configuration"
cat > "/opt/iRedMail-${IREDMAIL_VERSION}/config" <<IREDCFG
export STORAGE_BASE_DIR='/var/vmail'
export WEB_SERVER='${WEB_SERVER}'
export BACKEND_ORIG='MARIADB'
export BACKEND='MYSQL'
export VMAIL_DB_BIND_PASSWD='${VMAIL_DB_BIND_PASSWD}'
export VMAIL_DB_ADMIN_PASSWD='${VMAIL_DB_ADMIN_PASSWD}'
export MLMMJADMIN_API_AUTH_TOKEN='${MLMMJADMIN_API_AUTH_TOKEN}'
export IREDADMIN_DB_PASSWD='${IREDADMIN_DB_PASSWD}'
export IREDAPD_DB_PASSWD='${IREDAPD_DB_PASSWD}'
export AMAVISD_DB_PASSWD='${AMAVISD_DB_PASSWD}'
export RCM_DB_PASSWD='${RCM_DB_PASSWD}'
export SOGO_DB_PASSWD='${SOGO_DB_PASSWD}'
export SOGO_SIEVE_MASTER_PASSWD='${SOGO_SIEVE_MASTER_PASSWD}'
export NETDATA_DB_PASSWD='${NETDATA_DB_PASSWD}'
export FIRST_DOMAIN='${DOMAIN}'
export DOMAIN_ADMIN_NAME='${POSTMASTER_USER}'
export DOMAIN_ADMIN_PASSWD='${POSTMASTER_PASS}'
export DOMAIN_ADMIN_PASSWD_PLAIN='${POSTMASTER_PASS}'
export USE_FAIL2BAN='${USE_FAIL2BAN}'
export USE_NETDATA='${USE_NETDATA}'
export USE_CLAMAV='${USE_CLAMAV}'
export AUTO_USE_EXISTING_MYSQL_PASSWD=YES
export AUTO_INSTALL_WITHOUT_CONFIRM=Y
export AUTO_CLEANUP_REMOVE_SENDMAIL=Y
export AUTO_CLEANUP_REPLACE_FIREWALL_RULES=Y
export AUTO_CLEANUP_RESTART_FIREWALL=Y
export AUTO_CLEANUP_REPLACE_MYSQL_CONFIG=Y
export AUTO_CLEANUP_RESTART_IPTABLES=Y
export AUTO_CLEANUP_RESTART_SYSLOG=Y
IREDCFG
chmod 600 "/opt/iRedMail-${IREDMAIL_VERSION}/config"
info "Config written"

# ─── 8. Run iRedMail installer ──────────────────────────────────────────────
step "Running iRedMail installer (this takes ~5-10 minutes)"
cd "/opt/iRedMail-${IREDMAIL_VERSION}"
if [[ -f /etc/iredmail-release ]]; then
    info "iRedMail is already installed — skipping installer; will only refresh post-install."
else
    bash iRedMail.sh </dev/null
fi

# ─── 9. Let's Encrypt for $MAIL_HOSTNAME ────────────────────────────────────
step "Issuing Let's Encrypt certificate for ${MAIL_HOSTNAME}"
apt-get install -y -qq certbot python3-certbot-nginx >/dev/null

if certbot --nginx -d "$MAIL_HOSTNAME" --email "$LE_EMAIL" --agree-tos --no-eff-email --redirect --non-interactive 2>&1 | tail -5; then
    LE_DIR="/etc/letsencrypt/live/${MAIL_HOSTNAME}"

    if [[ -d "$LE_DIR" ]]; then
        info "Cert issued at $LE_DIR"

        # Repoint Postfix and Dovecot at the LE cert (iRedMail uses self-signed by default)
        if [[ -f /etc/postfix/main.cf ]]; then
            postconf -e "smtpd_tls_cert_file=${LE_DIR}/fullchain.pem"
            postconf -e "smtpd_tls_key_file=${LE_DIR}/privkey.pem"
        fi
        if [[ -f /etc/dovecot/dovecot.conf ]]; then
            sed -i "s|^ssl_cert *=.*|ssl_cert = <${LE_DIR}/fullchain.pem|" /etc/dovecot/dovecot.conf
            sed -i "s|^ssl_key *=.*|ssl_key = <${LE_DIR}/privkey.pem|"  /etc/dovecot/dovecot.conf
        fi

        # Reload deploy hook so renewals take effect everywhere
        mkdir -p /etc/letsencrypt/renewal-hooks/deploy
        cat > /etc/letsencrypt/renewal-hooks/deploy/reload-mail.sh <<'HOOK'
#!/bin/bash
systemctl reload nginx postfix dovecot 2>/dev/null || true
HOOK
        chmod +x /etc/letsencrypt/renewal-hooks/deploy/reload-mail.sh

        systemctl reload nginx postfix dovecot 2>/dev/null || true
    fi
else
    warn "Let's Encrypt failed — DNS A-record might not be propagated yet."
    warn "Run later:  certbot --nginx -d ${MAIL_HOSTNAME} --email ${LE_EMAIL} --agree-tos --redirect"
fi

# ─── 10. Add extra mailboxes (no-reply, info, support) ──────────────────────
step "Creating extra mailboxes (no-reply, info, support)"
SQL_CMD=(mysql --defaults-file=/root/.my.cnf vmail -N -B)

hash_password() { doveadm pw -s SSHA512 -p "$1" 2>/dev/null; }

create_mailbox() {
    local addr="$1" pwd="$2" name="$3"
    local user="${addr%@*}" domain="${addr#*@}"
    local maildir="${domain}/${user}/"
    local hash; hash=$(hash_password "$pwd")
    local exists; exists=$("${SQL_CMD[@]}" -e "SELECT COUNT(*) FROM mailbox WHERE username='${addr}';")
    if [[ "$exists" != "0" ]]; then
        info "  · ${addr} already exists"
        return
    fi
    "${SQL_CMD[@]}" -e "INSERT INTO mailbox
        (username, password, name, storagebasedirectory, storagenode, maildir, quota, domain, active, passwordlastchange, created, modified)
        VALUES
        ('${addr}', '${hash}', '${name}', '/var/vmail', 'vmail1', '${maildir}', 5368709120, '${domain}', 1, NOW(), NOW(), NOW());" 2>/dev/null || \
    "${SQL_CMD[@]}" -e "INSERT INTO mailbox (username, password, name, maildir, quota, domain, active) VALUES ('${addr}', '${hash}', '${name}', '${maildir}', 5368709120, '${domain}', 1);"

    "${SQL_CMD[@]}" -e "INSERT IGNORE INTO alias (address, goto, name, domain, active) VALUES ('${addr}', '${addr}', '${name}', '${domain}', 1);" 2>/dev/null || true
    "${SQL_CMD[@]}" -e "UPDATE domain SET mailboxes = mailboxes + 1 WHERE domain='${domain}';" 2>/dev/null || true
    install -d -o vmail -g vmail -m 0700 "/var/vmail/vmail1/${maildir}" 2>/dev/null || true
    info "  ✓ ${addr}"
}

create_mailbox "no-reply@${DOMAIN}" "$NOREPLY_PASS" "${DOMAIN%%.*} No-Reply"
create_mailbox "info@${DOMAIN}"     "$INFO_PASS"    "${DOMAIN%%.*} Info"
create_mailbox "support@${DOMAIN}"  "$SUPPORT_PASS" "${DOMAIN%%.*} Support"

# ─── 11. Read DKIM public key ──────────────────────────────────────────────
step "Extracting DKIM public key for ${DOMAIN}"
DKIM_PUB=""
if [[ -f "/var/lib/dkim/${DOMAIN}.pem" ]]; then
    DKIM_PUB=$(amavisd-new showkeys "${DOMAIN}" 2>/dev/null | tr -d '\n"' | sed -e 's/.*p=//' -e 's/[ ).].*//' || true)
elif systemctl is-active --quiet rspamd; then
    PUB="/var/lib/rspamd/dkim/${DOMAIN}.dkim.pub"
    if [[ -f "$PUB" ]]; then
        DKIM_PUB=$(grep -oP '"p=\K[^"]+' "$PUB" | tr -d '\n ' || true)
    fi
fi

[[ -n "$DKIM_PUB" ]] || DKIM_PUB="<run 'amavisd-new showkeys ${DOMAIN}' or check /var/lib/rspamd/dkim/>"

# ─── 12. Print summary ──────────────────────────────────────────────────────
DASH="────────────────────────────────────────────────────────────────────"
step "Installation complete — copy DNS records into Namecheap"
cat <<SUMMARY

  ${C_GRN}✓${C_RST} iRedMail running on  ${MAIL_HOSTNAME}  (${PUBLIC_IP})
    First domain      : ${DOMAIN}
    ClamAV            : ${USE_CLAMAV}
    Storage backend   : MariaDB

  ${DASH}
  ${C_BLU}Web logins${C_RST}
  ${DASH}

    Webmail (Roundcube) : https://${MAIL_HOSTNAME}/mail/
    Admin panel          : https://${MAIL_HOSTNAME}/iredadmin/
    Calendar (SOGo)      : https://${MAIL_HOSTNAME}/SOGo/

    Postmaster username  : ${POSTMASTER_USER}@${DOMAIN}
    Postmaster password  : ${POSTMASTER_PASS}

  ${DASH}
  ${C_BLU}Mailbox passwords (also saved in ${CREDS_FILE})${C_RST}
  ${DASH}

    no-reply@${DOMAIN}    : ${NOREPLY_PASS}    ${C_YEL}← put this in Laravel .env${C_RST}
    info@${DOMAIN}        : ${INFO_PASS}
    support@${DOMAIN}     : ${SUPPORT_PASS}

  ${DASH}
  ${C_BLU}DNS records — Namecheap → Domain List → ${DOMAIN} → Advanced DNS${C_RST}
  ${DASH}

    Type    Host                  Value                                                       TTL
    ────    ───────────────────   ──────────────────────────────────────────────────────────  ────
    A       mail                  ${PUBLIC_IP}                                                Auto
    MX      @                     ${MAIL_HOSTNAME}                                            Auto    Priority 10
    TXT     @                     v=spf1 mx -all                                              Auto
    TXT     dkim._domainkey       v=DKIM1; k=rsa; p=${DKIM_PUB}                               Auto
    TXT     _dmarc                v=DMARC1; p=quarantine; rua=mailto:postmaster@${DOMAIN}     Auto
    CNAME   autoconfig            ${MAIL_HOSTNAME}                                            Auto
    CNAME   autodiscover          ${MAIL_HOSTNAME}                                            Auto

  ${C_DIM}TTL: leave on Automatic. Wait 5-30 min for propagation.${C_RST}

  ${DASH}
  ${C_BLU}Laravel .env block (paste on the 3Dify VPS in /var/www/3dify/.env)${C_RST}
  ${DASH}

    MAIL_MAILER=smtp
    MAIL_HOST=${MAIL_HOSTNAME}
    MAIL_PORT=587
    MAIL_USERNAME=no-reply@${DOMAIN}
    MAIL_PASSWORD=${NOREPLY_PASS}
    MAIL_ENCRYPTION=tls
    MAIL_FROM_ADDRESS="no-reply@${DOMAIN}"
    MAIL_FROM_NAME="3Dify"

  ${DASH}
  ${C_BLU}Verification${C_RST}
  ${DASH}

    1) DNS propagation (run from anywhere):
       dig +short MX ${DOMAIN}
       dig +short TXT dkim._domainkey.${DOMAIN}
       dig +short TXT _dmarc.${DOMAIN}

    2) Login to Roundcube as info@${DOMAIN}, send a test to https://www.mail-tester.com
       — aim for 9-10/10 score.

    3) On the 3Dify VPS:
       sudo -u deploy nano /var/www/3dify/.env       # paste the MAIL_* block above
       cd /var/www/3dify && sudo -u deploy php artisan config:cache
       sudo -u deploy php artisan tinker --execute=\\
           "\\Mail::raw('SMTP test', fn(\\\$m) => \\\$m->to('your@gmail.com')->subject('3Dify · test'));"

  ${DASH}
  ${C_YEL}IP-warmup reminder${C_RST}
  ${DASH}

    Your VPS IP has no sender reputation yet. For the first 14-30 days:
      • Send 50-200 emails per day, max — don't blast newsletters yet.
      • Move legit mail from Spam → Inbox in your test Gmail accounts.
      • Watch /var/log/mail.log for bounces.
      • After 30 days reputation is built and you can send larger volumes.

SUMMARY
