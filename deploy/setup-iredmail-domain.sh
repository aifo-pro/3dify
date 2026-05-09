#!/usr/bin/env bash
#
# Provision a new mail domain on an existing iRedMail server.
# ============================================================================
#
# Run THIS SCRIPT on your iRedMail server (not on the 3Dify VPS) as root,
# AFTER iRedMail itself is already installed and serving its primary domain.
#
# Quick install + run:
#
#   sudo wget -qO /tmp/setup-iredmail-domain.sh \
#       https://raw.githubusercontent.com/aifo-pro/3dify/main/deploy/setup-iredmail-domain.sh
#   sudo bash /tmp/setup-iredmail-domain.sh
#
# What it does (idempotent — safe to re-run):
#   1. Detects your iRedMail backend (MySQL/MariaDB, PostgreSQL, OpenLDAP)
#      and DKIM signer (Amavis or Rspamd).
#   2. Adds the new domain to the vmail database / LDAP tree.
#   3. Creates postmaster@, no-reply@, info@, support@ mailboxes
#      with strong random passwords.
#   4. Generates a 2048-bit DKIM RSA key and registers it.
#   5. Prints ready-to-paste DNS records for Namecheap and Laravel .env
#      block for the 3Dify side.
#
# Re-running with the same DOMAIN does not duplicate mailboxes — it skips
# steps that are already done and re-prints the DNS / passwords summary.
#
# ============================================================================

set -euo pipefail
IFS=$'\n\t'

# ─── Colour helpers ─────────────────────────────────────────────────────────
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
    [[ -n "${!var:-}" ]] || fatal "$var is required."
    export "$var"
}

gen_password() {
    # 20 random url-safe chars; avoid confusing characters
    tr -dc 'A-HJ-NP-Za-km-z2-9' </dev/urandom | head -c 20
}

# ─── Pre-flight ─────────────────────────────────────────────────────────────
[[ $EUID -eq 0 ]] || fatal "Run as root (sudo bash $0)"

if [[ ! -f /etc/iredmail-release ]]; then
    warn "/etc/iredmail-release not found — this might not be an iRedMail box."
    read -rp "    Continue anyway? [y/N]: " ans
    [[ "$ans" =~ ^[Yy]$ ]] || exit 0
fi

command -v dig       >/dev/null || apt-get install -y -qq dnsutils >/dev/null 2>&1 || true
command -v openssl   >/dev/null || fatal "openssl is required"
command -v doveadm   >/dev/null || fatal "doveadm not found — Dovecot must be installed"

# ─── Detect SQL backend ─────────────────────────────────────────────────────
BACKEND=""
SQL=""
if [[ -f /root/.my.cnf ]] && grep -q "vmail" /root/.my.cnf 2>/dev/null; then
    BACKEND="mysql"
    SQL=(mysql --defaults-file=/root/.my.cnf vmail -N -B)
elif command -v mysql >/dev/null && systemctl is-active --quiet mariadb 2>/dev/null; then
    BACKEND="mysql"
    SQL=(mysql vmail -N -B)
elif command -v psql >/dev/null && systemctl is-active --quiet postgresql 2>/dev/null; then
    BACKEND="psql"
    SQL=(sudo -u postgres psql -d vmail -tAq)
elif systemctl is-active --quiet slapd 2>/dev/null; then
    BACKEND="ldap"
    fatal "OpenLDAP backend detected — this script only supports SQL backends. Add the domain via iRedAdmin or LDIF manually, then re-run with SKIP_DOMAIN=1 SKIP_MAILBOX=1 to just generate DKIM."
else
    fatal "Cannot detect iRedMail backend (no MySQL / PostgreSQL / OpenLDAP service is active)."
fi

info "Storage backend : ${C_GRN}${BACKEND}${C_RST}"

# Helper to run a SQL query safely
run_sql() {
    "${SQL[@]}" -e "$1" 2>&1
}

# ─── Detect DKIM signer ─────────────────────────────────────────────────────
DKIM_SIGNER=""
if systemctl is-active --quiet rspamd 2>/dev/null; then
    DKIM_SIGNER="rspamd"
elif systemctl is-active --quiet amavis 2>/dev/null || systemctl is-active --quiet amavisd 2>/dev/null; then
    DKIM_SIGNER="amavis"
else
    warn "Neither rspamd nor amavis is active — DKIM signing might be off."
    read -rp "    Continue without DKIM (not recommended)? [y/N]: " ans
    [[ "$ans" =~ ^[Yy]$ ]] || exit 0
    DKIM_SIGNER="none"
fi

info "DKIM signer     : ${C_GRN}${DKIM_SIGNER}${C_RST}"

# ─── Mail server hostname & primary IP ──────────────────────────────────────
MAIL_HOSTNAME=$(postconf -h myhostname 2>/dev/null || hostname -f)
MAIL_IPV4=$(curl -fsSL4 https://api.ipify.org 2>/dev/null || ip -4 -o addr show scope global | awk '{print $4}' | cut -d/ -f1 | head -1)

info "Mail hostname   : ${C_GRN}${MAIL_HOSTNAME}${C_RST}"
info "Mail IPv4       : ${C_GRN}${MAIL_IPV4}${C_RST}"

# ─── Inputs ─────────────────────────────────────────────────────────────────
prompt DOMAIN          "New mail domain (e.g. 3dify.dev):"
prompt MAILBOX_QUOTA   "Per-mailbox quota in MB:" "5120"
prompt CREATE_INFO     "Create info@${DOMAIN}? [Y/n]:"     "y"
prompt CREATE_SUPPORT  "Create support@${DOMAIN}? [Y/n]:"  "y"
prompt CREATE_NOREPLY  "Create no-reply@${DOMAIN}? [Y/n]:" "y"
prompt CREATE_POSTMASTER "Create postmaster@${DOMAIN}? [Y/n]:" "y"

QUOTA_BYTES=$((MAILBOX_QUOTA * 1024 * 1024))

# ─── 1. Register domain ─────────────────────────────────────────────────────
step "Registering domain '${DOMAIN}'"

if [[ "$BACKEND" == "mysql" ]]; then
    EXISTS=$(run_sql "SELECT COUNT(*) FROM domain WHERE domain='${DOMAIN}';" || echo 0)
    if [[ "$EXISTS" == "0" ]]; then
        run_sql "INSERT INTO domain (domain, description, aliases, mailboxes, maxquota, quota, transport, backupmx, settings, defaultuser, defaultpasswordscheme, mailboxquota, max_mailbox_count, max_mailbox_quota, max_alias_count, max_list_count, max_quota_in_use, max_mailbox_in_use, max_alias_in_use, max_list_in_use, account_setting, expired, created, modified, active)
                 VALUES ('${DOMAIN}', '${DOMAIN}', 0, 100, 0, 0, '', 0, '', '', '', ${QUOTA_BYTES}, 100, ${QUOTA_BYTES}, 100, 10, 0, 0, 0, 0, '', NULL, NOW(), NOW(), 1);" \
            2>/dev/null || \
        run_sql "INSERT INTO domain (domain, description, mailboxes, maxquota, quota, transport, backupmx, active)
                 VALUES ('${DOMAIN}', '${DOMAIN}', 100, 0, 0, '', 0, 1);"
        info "Domain row created"
    else
        info "Domain already exists in DB — skipping insert"
    fi

    # Allow ALL admins access
    run_sql "INSERT IGNORE INTO domain_admins (username, domain, created, active)
             VALUES ('ALL', '${DOMAIN}', NOW(), 1);" 2>/dev/null || true

elif [[ "$BACKEND" == "psql" ]]; then
    EXISTS=$(run_sql "SELECT COUNT(*) FROM domain WHERE domain='${DOMAIN}';" || echo 0)
    if [[ "$EXISTS" == "0" ]]; then
        run_sql "INSERT INTO domain (domain, description, mailboxes, maxquota, quota, active) VALUES ('${DOMAIN}', '${DOMAIN}', 100, 0, 0, 1);"
        info "Domain row created"
    else
        info "Domain already exists in DB — skipping insert"
    fi
fi

# ─── 2. Create mailboxes ────────────────────────────────────────────────────
step "Creating mailboxes"

# Hash factory used by iRedMail by default — SSHA512.
hash_password() {
    local pwd="$1"
    doveadm pw -s SSHA512 -p "$pwd" 2>/dev/null
}

create_mailbox() {
    local addr="$1" pwd="$2" full_name="$3"
    local user="${addr%@*}"
    local domain="${addr#*@}"
    local maildir="${domain}/${user}/"
    local pw_hash
    pw_hash=$(hash_password "$pwd")

    if [[ "$BACKEND" == "mysql" ]]; then
        local exists
        exists=$(run_sql "SELECT COUNT(*) FROM mailbox WHERE username='${addr}';")
        if [[ "$exists" != "0" ]]; then
            info "  · ${addr} already exists — skipping"
            return
        fi

        run_sql "INSERT INTO mailbox
                 (username, password, name, storagebasedirectory, storagenode, maildir, quota, domain, active, passwordlastchange, created, modified)
                 VALUES
                 ('${addr}', '${pw_hash}', '${full_name}', '/var/vmail', 'vmail1', '${maildir}', ${QUOTA_BYTES}, '${domain}', 1, NOW(), NOW(), NOW());" \
            2>/dev/null || \
        run_sql "INSERT INTO mailbox (username, password, name, maildir, quota, domain, active)
                 VALUES ('${addr}', '${pw_hash}', '${full_name}', '${maildir}', ${QUOTA_BYTES}, '${domain}', 1);"

        # Reverse alias so the address resolves to itself (iRedMail wants this).
        run_sql "INSERT IGNORE INTO alias (address, goto, name, domain, active)
                 VALUES ('${addr}', '${addr}', '${full_name}', '${domain}', 1);" 2>/dev/null || true

        # forwardings table (newer iRedMail versions)
        run_sql "INSERT IGNORE INTO forwardings (address, forwarding, domain, dest_domain, is_maillist, is_list, is_forwarding, active)
                 VALUES ('${addr}', '${addr}', '${domain}', '${domain}', 0, 0, 0, 1);" 2>/dev/null || true

        # Bump stats counter
        run_sql "UPDATE domain SET mailboxes = mailboxes + 1 WHERE domain='${domain}';" 2>/dev/null || true

    elif [[ "$BACKEND" == "psql" ]]; then
        local exists
        exists=$(run_sql "SELECT COUNT(*) FROM mailbox WHERE username='${addr}';")
        if [[ "${exists// /}" != "0" ]]; then
            info "  · ${addr} already exists — skipping"
            return
        fi

        run_sql "INSERT INTO mailbox (username, password, name, maildir, quota, domain, active) VALUES ('${addr}', '${pw_hash}', '${full_name}', '${maildir}', ${QUOTA_BYTES}, '${domain}', 1);"
        run_sql "INSERT INTO alias (address, goto, name, domain, active) VALUES ('${addr}', '${addr}', '${full_name}', '${domain}', 1);" 2>/dev/null || true
    fi

    # Pre-create maildir so first delivery doesn't have to
    install -d -o vmail -g vmail -m 0700 "/var/vmail/vmail1/${maildir}" 2>/dev/null || true

    info "  ✓ ${addr}"
}

# Generate passwords (or read from existing summary on re-run)
SUMMARY_FILE="/var/lib/iredmail-domain-${DOMAIN}.creds"
if [[ -f "$SUMMARY_FILE" ]]; then
    info "Re-using saved passwords from ${SUMMARY_FILE}"
    # shellcheck disable=SC1090
    source "$SUMMARY_FILE"
else
    POSTMASTER_PASS=$(gen_password)
    NOREPLY_PASS=$(gen_password)
    INFO_PASS=$(gen_password)
    SUPPORT_PASS=$(gen_password)
    cat > "$SUMMARY_FILE" <<CREDS
POSTMASTER_PASS='${POSTMASTER_PASS}'
NOREPLY_PASS='${NOREPLY_PASS}'
INFO_PASS='${INFO_PASS}'
SUPPORT_PASS='${SUPPORT_PASS}'
CREDS
    chmod 600 "$SUMMARY_FILE"
fi

[[ "$CREATE_POSTMASTER" =~ ^[Yy]$ ]] && create_mailbox "postmaster@${DOMAIN}" "$POSTMASTER_PASS" "Postmaster"
[[ "$CREATE_NOREPLY"    =~ ^[Yy]$ ]] && create_mailbox "no-reply@${DOMAIN}"   "$NOREPLY_PASS"   "${DOMAIN%%.*} No-Reply"
[[ "$CREATE_INFO"       =~ ^[Yy]$ ]] && create_mailbox "info@${DOMAIN}"       "$INFO_PASS"      "${DOMAIN%%.*} Info"
[[ "$CREATE_SUPPORT"    =~ ^[Yy]$ ]] && create_mailbox "support@${DOMAIN}"    "$SUPPORT_PASS"   "${DOMAIN%%.*} Support"

# ─── 3. DKIM ────────────────────────────────────────────────────────────────
step "Generating DKIM key for ${DOMAIN}"

DKIM_PUB=""
SELECTOR="dkim"

if [[ "$DKIM_SIGNER" == "amavis" ]]; then
    DKIM_KEY="/var/lib/dkim/${DOMAIN}.pem"
    if [[ ! -f "$DKIM_KEY" ]]; then
        mkdir -p /var/lib/dkim
        amavisd-new genrsa "$DKIM_KEY" 2048 2>/dev/null \
            || amavisd genrsa "$DKIM_KEY" 2048 2>/dev/null \
            || openssl genrsa -out "$DKIM_KEY" 2048 2>/dev/null
        chown amavis:amavis "$DKIM_KEY" 2>/dev/null || chown amavisd:amavisd "$DKIM_KEY" 2>/dev/null || true
        chmod 0640 "$DKIM_KEY"
        info "Created ${DKIM_KEY}"
    else
        info "Key already exists at ${DKIM_KEY}"
    fi

    # Locate amavis user config
    AMAVIS_CONF=""
    for c in /etc/amavis/conf.d/50-user /etc/amavisd/amavisd.conf /etc/amavisd.conf; do
        [[ -f "$c" ]] && AMAVIS_CONF="$c" && break
    done

    if [[ -n "$AMAVIS_CONF" ]] && ! grep -q "dkim_key('${DOMAIN}'" "$AMAVIS_CONF"; then
        # Insert before #------------ end-of-config marker, or append
        local_line="dkim_key('${DOMAIN}', 'dkim', '${DKIM_KEY}');"
        if grep -q '#------------ Do not modify' "$AMAVIS_CONF"; then
            sed -i "/#------------ Do not modify/i ${local_line}" "$AMAVIS_CONF"
        else
            echo "$local_line" >> "$AMAVIS_CONF"
        fi
        info "Patched ${AMAVIS_CONF}"
    fi

    systemctl restart amavis 2>/dev/null || systemctl restart amavisd 2>/dev/null || true
    sleep 2

    # showkeys reads the configured key and emits the DNS record
    DKIM_PUB=$(amavisd-new showkeys "${DOMAIN}" 2>/dev/null | tr -d '\n"' | sed -e 's/.*p=//' -e 's/[ ).].*//' || true)

elif [[ "$DKIM_SIGNER" == "rspamd" ]]; then
    DKIM_DIR="/var/lib/rspamd/dkim"
    KEY_FILE="${DKIM_DIR}/${DOMAIN}.${SELECTOR}.key"
    PUB_FILE="${DKIM_DIR}/${DOMAIN}.${SELECTOR}.pub"

    mkdir -p "$DKIM_DIR"
    if [[ ! -f "$KEY_FILE" ]]; then
        rspamadm dkim_keygen -d "${DOMAIN}" -s "${SELECTOR}" -k "${KEY_FILE}" > "${PUB_FILE}"
        info "Created ${KEY_FILE}"
    else
        info "Key already exists at ${KEY_FILE}"
    fi
    chown -R _rspamd:_rspamd "$DKIM_DIR" 2>/dev/null || \
        chown -R rspamd:rspamd "$DKIM_DIR" 2>/dev/null || true
    chmod 0440 "$KEY_FILE"

    # Ensure dkim_signing.conf has a glob pattern that works for any domain
    SIGN_CONF="/etc/rspamd/local.d/dkim_signing.conf"
    if [[ -f "$SIGN_CONF" ]] && ! grep -q '\$domain' "$SIGN_CONF"; then
        cat > "$SIGN_CONF" <<RSPAMD
enabled = true;
sign_local = true;
use_domain = "header";
allow_username_mismatch = true;
domain {
  ".+" {
    selectors [
      { path = "${DKIM_DIR}/\$domain.${SELECTOR}.key"; selector = "${SELECTOR}"; }
    ]
  }
}
RSPAMD
        info "Wrote multi-domain config to ${SIGN_CONF}"
    fi

    systemctl reload rspamd

    # Extract public key — pub file is a fully-formed BIND record, grab p=...
    DKIM_PUB=$(grep -oP '"p=\K[^"]+' "${PUB_FILE}" | tr -d '\n ' || true)
    if [[ -z "$DKIM_PUB" ]]; then
        # alt format on some versions
        DKIM_PUB=$(awk -F'"' '/p=/ { for (i=1;i<=NF;i++) if ($i ~ /^p=/) { sub(/^p=/, "", $i); printf "%s", $i }}' "${PUB_FILE}" | tr -d '\n ' || true)
    fi
fi

if [[ -z "${DKIM_PUB:-}" ]]; then
    warn "Could not auto-extract the DKIM public key. Open the .pub / showkeys output manually."
    DKIM_PUB="<paste-from-showkeys-output>"
fi

# ─── 4. Reload mail services so the new domain is recognised ───────────────
step "Reloading mail services"
systemctl reload postfix 2>/dev/null || true
systemctl reload dovecot 2>/dev/null || true
systemctl reload nginx   2>/dev/null || true   # for Roundcube domain detection
info "Reloaded postfix / dovecot / nginx (if installed)"

# ─── 5. Print summary ───────────────────────────────────────────────────────
DASH="────────────────────────────────────────────────────────────────────"

step "All done — copy the records below into Namecheap"
cat <<SUMMARY

  ${C_GRN}✓${C_RST} ${DOMAIN} provisioned on ${MAIL_HOSTNAME}

  ${DASH}
  ${C_BLU}DNS records — Namecheap → Domain List → Manage → Advanced DNS${C_RST}
  ${DASH}

  ${C_DIM}Type    Host                  Value${C_RST}
  MX      @                     ${MAIL_HOSTNAME}   ${C_DIM}(Priority: 10)${C_RST}
  TXT     @                     v=spf1 a:${MAIL_HOSTNAME} -all
  TXT     ${SELECTOR}._domainkey       v=DKIM1; k=rsa; p=${DKIM_PUB}
  TXT     _dmarc                v=DMARC1; p=quarantine; rua=mailto:postmaster@${DOMAIN}; pct=100; adkim=s; aspf=s
  CNAME   autoconfig            ${MAIL_HOSTNAME}
  CNAME   autodiscover          ${MAIL_HOSTNAME}

  ${C_DIM}TTL: leave on Automatic. Wait 5-30 min for propagation.${C_RST}

  ${DASH}
  ${C_BLU}Mailbox passwords (saved to ${SUMMARY_FILE}, mode 600)${C_RST}
  ${DASH}

SUMMARY

[[ "$CREATE_POSTMASTER" =~ ^[Yy]$ ]] && echo -e "  postmaster@${DOMAIN}    ${C_GRN}${POSTMASTER_PASS}${C_RST}"
[[ "$CREATE_NOREPLY"    =~ ^[Yy]$ ]] && echo -e "  no-reply@${DOMAIN}      ${C_GRN}${NOREPLY_PASS}${C_RST}    ${C_YEL}← put this in Laravel .env${C_RST}"
[[ "$CREATE_INFO"       =~ ^[Yy]$ ]] && echo -e "  info@${DOMAIN}          ${C_GRN}${INFO_PASS}${C_RST}"
[[ "$CREATE_SUPPORT"    =~ ^[Yy]$ ]] && echo -e "  support@${DOMAIN}       ${C_GRN}${SUPPORT_PASS}${C_RST}"

cat <<ENV

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
  ${C_BLU}Verification once DNS propagates${C_RST}
  ${DASH}

  dig +short MX     ${DOMAIN}
  dig +short TXT    ${DOMAIN}
  dig +short TXT    ${SELECTOR}._domainkey.${DOMAIN}
  dig +short TXT    _dmarc.${DOMAIN}

  Then on the 3Dify VPS:
    sudo -u deploy php /var/www/3dify/artisan config:cache
    sudo -u deploy php /var/www/3dify/artisan tinker --execute=\\
        "\\Mail::raw('SMTP test', fn(\\\$m) => \\\$m->to('your@gmail.com')->subject('3Dify · test'));"

ENV

step "Tip"
info "Send a test from Roundcube (login as info@${DOMAIN}) to https://www.mail-tester.com — aim for 9-10/10."
info "If anything fails, re-running this script is safe — it will skip what's already done."
