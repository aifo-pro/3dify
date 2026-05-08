# 3Dify VPS provisioning

End-to-end shell installer for a fresh **Ubuntu 24.04 LTS** (or 22.04 / Debian 12) box.
Sets up everything this app needs and is **idempotent** — safe to re-run.

## What it installs

| Component        | Version             | Purpose                                       |
| ---------------- | ------------------- | --------------------------------------------- |
| PHP              | 8.3 + FPM           | runtime (with `bcmath`, `gmp`, `gd`, `intl`, `mbstring`, `mysql`, `redis`, `zip`, `opcache`, …) |
| Composer         | 2.x                 | PHP package manager                           |
| Node.js          | 20 LTS              | for `vite build` once at deploy time          |
| MySQL            | 8                   | primary database                              |
| Redis            | 7+                  | cache / session / queue driver                |
| Nginx            | latest              | web server with HTTP/2 and gzip               |
| Certbot          | latest              | Let's Encrypt SSL                             |
| systemd units    | `*-queue.service`, `*-scheduler.timer` | replace `supervisor`/`cron` |

## Usage

### 1. Prepare DNS

Point your domain (`A` record) at the VPS IP **before** running, otherwise SSL issuance will fail (script will skip Certbot in that case and tell you to run it later).

### 2. Upload the script to the server

```bash
scp deploy/provision.sh root@YOUR_VPS_IP:/root/
```

### 3. Run it

Easiest — interactive (it will prompt for missing values):

```bash
ssh root@YOUR_VPS_IP "bash /root/provision.sh"
```

Or fully non-interactive — pass everything via env:

```bash
ssh root@YOUR_VPS_IP \
    "DOMAIN=3dify.example.com \
     LE_EMAIL=admin@example.com \
     GIT_REPO=git@github.com:USER/3dify.git \
     GIT_BRANCH=main \
     DB_PASSWORD=$(openssl rand -base64 24) \
     bash /root/provision.sh"
```

If the repo is private, set up SSH-deploy keys for `root` first (or use `https://USER:TOKEN@github.com/...`).

### 4. After it finishes

The script prints a summary with the generated DB password, paths, and helpful commands. **Save the DB password** — it's also written into `${APP_DIR}/.env`.

## Re-deploying after a code push

```bash
ssh deploy@YOUR_VPS_IP "3dify-deploy"
```

This helper does:

1. `php artisan down` (graceful 503)
2. `git pull --ff-only`
3. `composer install --no-dev --optimize-autoloader`
4. `npm ci && npm run build`
5. `php artisan migrate --force`
6. cache config/routes/views/events
7. `systemctl reload php8.3-fpm`
8. `systemctl restart 3dify-queue.service`
9. `php artisan up`

## Tweaks

All defaults can be overridden via environment variables:

| Variable       | Default          | Notes                              |
| -------------- | ---------------- | ---------------------------------- |
| `APP_NAME`     | `3Dify`          | shown in the summary banner        |
| `APP_SLUG`     | `3dify`          | used in paths and unit names       |
| `APP_DIR`      | `/var/www/3dify` | where code lives                   |
| `APP_USER`     | `deploy`         | system user that owns the code     |
| `PHP_VERSION`  | `8.3`            | matches `composer.json` constraint |
| `NODE_MAJOR`   | `20`             | Vite 8 needs ≥ 20.19               |
| `DOMAIN`       | _required_       | server name + Certbot              |
| `LE_EMAIL`     | _required_       | Let's Encrypt account              |
| `GIT_REPO`     | _required_       | use `skip` to upload code manually |
| `GIT_BRANCH`   | `main`           |                                    |
| `DB_NAME`      | `3dify`          |                                    |
| `DB_USER`      | `3dify`          |                                    |
| `DB_PASSWORD`  | _generated_      | shown once in the summary          |
| `TIMEZONE`     | `Europe/Kyiv`    |                                    |

## Operational reference

```bash
# logs
sudo journalctl -u 3dify-queue.service -f
sudo journalctl -u 3dify-scheduler.timer -f
tail -f /var/www/3dify/storage/logs/laravel.log

# status
sudo systemctl status 3dify-queue.service
sudo systemctl list-timers 3dify-scheduler.timer
sudo systemctl status php8.3-fpm nginx mysql redis-server

# restart parts
sudo systemctl reload nginx
sudo systemctl reload php8.3-fpm
sudo systemctl restart 3dify-queue.service

# certificate renewal (Certbot installs its own timer; manual force-renew):
sudo certbot renew --dry-run
sudo certbot renew --force-renewal
```

## Backups (recommended)

Quick MySQL + storage backup via cron (run as `deploy`):

```cron
30 3 * * * mysqldump -u 3dify -p"$DB_PASSWORD" 3dify | gzip > /var/backups/3dify-$(date +\%F).sql.gz
0  4 * * * tar czf /var/backups/3dify-storage-$(date +\%F).tgz -C /var/www/3dify/storage app
```

For off-site copies, use `restic` or `rclone` to push `/var/backups/` to S3 / Backblaze B2 / Hetzner Storage Box.
