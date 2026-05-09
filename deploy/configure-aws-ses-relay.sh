#!/usr/bin/env bash
#
# Configure Postfix on this iRedMail server to relay all outbound mail
# through Amazon SES (port 587 STARTTLS + SASL auth).
# ============================================================================
#
# Why:  many VPS providers block outbound port 25 to fight spam.  Postfix
# can't talk directly to Gmail / Outlook from such a host.  Amazon SES is
# the cheapest professional way to deliver transactional + marketing mail
# at $0.10 per 1,000 messages with high inbox-placement reputation.
#
# Run THIS SCRIPT on the same VPS where install-iredmail.sh has finished
# successfully (so /etc/postfix already exists and Postfix is running).
# It is idempotent — running twice with the same credentials is fine.
#
# Quick install (interactive):
#
#   sudo wget -qO /tmp/configure-aws-ses-relay.sh \
#       https://raw.githubusercontent.com/aifo-pro/3dify/main/deploy/configure-aws-ses-relay.sh
#   sudo bash /tmp/configure-aws-ses-relay.sh
#
# Or non-interactive (override via env):
#
#   sudo SES_REGION=eu-central-1 \
#        SES_USERNAME='AKIAxxxxxxxxxxxx' \
#        SES_PASSWORD='BJxxxxxxxxxxxxxxxxxxxxxxxxxxxxx' \
#        SES_FROM_DOMAIN='3dify.dev' \
#        bash /tmp/configure-aws-ses-relay.sh
#
# After the script finishes:
#   • Postfix relayhost is set to email-smtp.<region>.amazonaws.com:587
#   • /etc/postfix/sasl_passwd contains your SMTP credentials (mode 600)
#   • Postfix is reloaded and validated with `postfix check`
#   • A test message is sent to root@<your-domain> so you can verify in
#     /var/log/mail.log that SES accepted it
# ============================================================================

set -euo pipefail
IFS=$'\n\t'

trap 'rc=$?; echo -e "\n\033[1;31mxx  configure-aws-ses-relay.sh aborted at line $LINENO (exit $rc)\033[0m" >&2' ERR

SCRIPT_REVISION="2026-05-09.r1"

SES_REGION="${SES_REGION:-}"
SES_USERNAME="${SES_USERNAME:-}"
SES_PASSWORD="${SES_PASSWORD:-}"
SES_FROM_DOMAIN="${SES_FROM_DOMAIN:-}"
SES_TEST_RECIPIENT="${SES_TEST_RECIPIENT:-}"

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

# ─── 0. Sanity ──────────────────────────────────────────────────────────────
echo -e "${C_DIM}configure-aws-ses-relay.sh revision ${SCRIPT_REVISION}${C_RST}"
[[ $EUID -eq 0 ]] || fatal "Run as root (sudo bash $0)"
[[ -d /etc/postfix ]] || fatal "/etc/postfix not found — install Postfix first."
command -v postconf >/dev/null || fatal "postconf not found — Postfix must be installed."
command -v postmap  >/dev/null || fatal "postmap not found — Postfix must be installed."

# ─── 1. Inputs ──────────────────────────────────────────────────────────────
step "Collecting Amazon SES details"

cat <<EXPLAIN
    Find these values in AWS Console:

      • Region                — top-right of the SES dashboard.
                                 Recommended: eu-central-1 (Frankfurt) for EU,
                                              us-east-1     (N. Virginia)  for US.
      • SMTP user / password — SES → "SMTP settings" → "Create SMTP credentials".
                                 (NOT the IAM access keys — SES gives a separate pair!)
      • From-domain          — the verified identity, e.g. 3dify.dev.

EXPLAIN

prompt SES_REGION         "AWS region (e.g. eu-central-1):"          "eu-central-1"
prompt SES_USERNAME       "SES SMTP username (starts with AKIA...):"
prompt SES_PASSWORD       "SES SMTP password:"
prompt SES_FROM_DOMAIN    "Verified mail domain (e.g. 3dify.dev):"
prompt SES_TEST_RECIPIENT "Send a test message to (must be a verified address while in sandbox):" "postmaster@${SES_FROM_DOMAIN}"

SES_HOST="email-smtp.${SES_REGION}.amazonaws.com"
SES_PORT=587

# ─── 2. Connectivity probe ──────────────────────────────────────────────────
step "Probing connectivity to ${SES_HOST}:${SES_PORT}"
if ! command -v nc >/dev/null; then
    apt-get update -qq && apt-get install -y -qq netcat-openbsd >/dev/null
fi
if nc -zv -w 5 "$SES_HOST" "$SES_PORT" 2>&1 | grep -q -E 'succeeded|open'; then
    info "Outbound 587 reachable — good."
else
    warn "Could not connect to ${SES_HOST}:${SES_PORT}"
    warn "Either VPS provider blocks 587 too, or AWS region is wrong."
    read -rp "    Continue anyway? [y/N]: " ans
    [[ "$ans" =~ ^[Yy]$ ]] || exit 1
fi

# ─── 3. SASL credentials file ───────────────────────────────────────────────
step "Writing /etc/postfix/sasl_passwd"
SASL_FILE="/etc/postfix/sasl_passwd"
echo "[${SES_HOST}]:${SES_PORT} ${SES_USERNAME}:${SES_PASSWORD}" > "$SASL_FILE"
chown root:root "$SASL_FILE"
chmod 0600 "$SASL_FILE"
postmap "$SASL_FILE"
chmod 0600 "${SASL_FILE}.db"
info "SASL credentials hashed → ${SASL_FILE}.db"

# ─── 4. Postfix main.cf ─────────────────────────────────────────────────────
step "Updating /etc/postfix/main.cf"

postconf -e "relayhost = [${SES_HOST}]:${SES_PORT}"
postconf -e "smtp_sasl_auth_enable = yes"
postconf -e "smtp_sasl_security_options = noanonymous"
postconf -e "smtp_sasl_password_maps = hash:${SASL_FILE}"
postconf -e "smtp_use_tls = yes"
postconf -e "smtp_tls_security_level = encrypt"
postconf -e "smtp_tls_note_starttls_offer = yes"
postconf -e "smtp_tls_loglevel = 1"

# Use the system CA bundle — Debian/Ubuntu ships it here.
if [[ -f /etc/ssl/certs/ca-certificates.crt ]]; then
    postconf -e "smtp_tls_CAfile = /etc/ssl/certs/ca-certificates.crt"
fi

# Defensive: SES rejects messages > 10MB and bare CRLF lines.
postconf -e "smtputf8_enable = yes"
postconf -e "smtp_bind_address = "
postconf -e "smtp_helo_name = ${SES_FROM_DOMAIN}"

# Sender masquerading — make sure outgoing envelope sender always uses our
# verified domain so SES doesn't reject "MAIL FROM" mismatches.
SENDER_REWRITE="/etc/postfix/sender_canonical"
cat > "$SENDER_REWRITE" <<MAP
# Force every outgoing envelope-from to a verified SES identity.
/.+/    no-reply@${SES_FROM_DOMAIN}
MAP
postconf -e "sender_canonical_classes = envelope_sender"
postconf -e "sender_canonical_maps = regexp:${SENDER_REWRITE}"

info "Postfix main.cf updated"

# ─── 5. Validate config ─────────────────────────────────────────────────────
step "Validating Postfix configuration"
if postfix check 2>&1 | tee /tmp/postfix-check.log | grep -E "warning|error" >/dev/null; then
    warn "postfix check produced warnings:"
    cat /tmp/postfix-check.log
else
    info "postfix check OK"
fi

systemctl reload postfix
info "Postfix reloaded"

# ─── 6. Test send ───────────────────────────────────────────────────────────
step "Sending a test message → ${SES_TEST_RECIPIENT}"
TEST_BODY=$(mktemp)
cat > "$TEST_BODY" <<EOF
From: no-reply@${SES_FROM_DOMAIN}
To: ${SES_TEST_RECIPIENT}
Subject: SES relay test from $(hostname -f)
Date: $(date -R)
Content-Type: text/plain; charset=UTF-8

Hello!

This is an automated SMTP test message sent from $(hostname -f)
through Amazon SES (${SES_HOST}).  If you can read this, the relay
is working and Postfix is delivering through SES correctly.

Server time: $(date)
Public IP  : $(curl -s https://api.ipify.org || echo unknown)

— configure-aws-ses-relay.sh
EOF

if ! command -v sendmail >/dev/null; then
    info "sendmail wrapper missing — using direct SMTP via swaks/openssl alternative"
    apt-get install -y -qq mailutils >/dev/null
fi

sendmail -t < "$TEST_BODY" || warn "sendmail returned non-zero — check /var/log/mail.log"
rm -f "$TEST_BODY"

sleep 2
info "Tail of /var/log/mail.log (last 15 lines):"
tail -15 /var/log/mail.log 2>/dev/null || journalctl -u postfix --no-pager -n 15

# ─── 7. Summary ─────────────────────────────────────────────────────────────
DASH="────────────────────────────────────────────────────────────────────"
step "SES relay configured"
cat <<SUMMARY

  ${C_GRN}✓${C_RST} Postfix now relays through  ${SES_HOST}:${SES_PORT}
    Auth user        : ${SES_USERNAME}
    Verified domain  : ${SES_FROM_DOMAIN}
    Test recipient   : ${SES_TEST_RECIPIENT}

  ${DASH}
  ${C_BLU}Watch the test land${C_RST}
  ${DASH}

    1) Open the inbox of ${SES_TEST_RECIPIENT}.  In SES sandbox mode
       the recipient must already be a "verified identity" — otherwise
       SES will silently drop the message.

    2) Tail Postfix log live:
       sudo tail -f /var/log/mail.log
       Look for  status=sent (250 Ok)   ← success.
       Look for  status=bounced         ← see the SES error description.

  ${DASH}
  ${C_BLU}Production access (one-time)${C_RST}
  ${DASH}

    SES starts each account in a *sandbox*: max 200 msg/day, only to
    pre-verified recipients.  To send to anyone:

      AWS Console → SES → "Account dashboard" → "Request production access".

    Fill in the form (mention 3D-model marketplace transactional + marketing
    use case, link to https://${SES_FROM_DOMAIN}).  Approval typically
    takes 24 hours – 3 business days.

  ${DASH}
  ${C_BLU}Laravel .env block (paste on the 3Dify VPS)${C_RST}
  ${DASH}

    MAIL_MAILER=smtp
    MAIL_HOST=${SES_HOST}
    MAIL_PORT=${SES_PORT}
    MAIL_USERNAME=${SES_USERNAME}
    MAIL_PASSWORD=<your-SES-SMTP-password>
    MAIL_ENCRYPTION=tls
    MAIL_FROM_ADDRESS="no-reply@${SES_FROM_DOMAIN}"
    MAIL_FROM_NAME="3Dify"

    Then on the 3Dify VPS:
       sudo -u deploy php artisan config:cache
       sudo -u deploy php artisan tinker --execute="\\Mail::raw('SES test', fn(\\\$m) => \\\$m->to('your@gmail.com')->subject('3Dify · SES'));"

  ${DASH}
  ${C_BLU}Reverting back (if needed)${C_RST}
  ${DASH}

    postconf -e 'relayhost ='
    postconf -e 'smtp_sasl_auth_enable = no'
    rm -f ${SASL_FILE}{,.db}
    systemctl reload postfix

SUMMARY
