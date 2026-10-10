#!/usr/bin/env bash
# One-command setup for a fresh Ubuntu 24.04 server.
# Usage:  sudo bash deploy.sh yourdomain.com
set -euo pipefail

DOMAIN="${1:?Usage: sudo bash deploy.sh yourdomain.com}"
[ "$(id -u)" -eq 0 ] || { echo "Run with sudo."; exit 1; }
. /etc/os-release
[ "$VERSION_ID" = "24.04" ] || { echo "This script is for Ubuntu 24.04."; exit 1; }

SRC="$(cd "$(dirname "$0")" && pwd)"
APP=/var/www/sfms
DB=sfms; DBUSER=sfms; DBPASS="$(openssl rand -hex 16)"
export DEBIAN_FRONTEND=noninteractive COMPOSER_ALLOW_SUPERUSER=1

echo "== Installing software"
apt-get update -y
apt-get install -y caddy php8.3-fpm php8.3-cli php8.3-mysql php8.3-mbstring php8.3-xml \
  php8.3-curl php8.3-zip mariadb-server composer unzip ufw fail2ban unattended-upgrades
echo 'APT::Periodic::Update-Package-Lists "1"; APT::Periodic::Unattended-Upgrade "1";' > /etc/apt/apt.conf.d/20auto-upgrades

echo "== Database (private to this server)"
mysql -e "CREATE DATABASE IF NOT EXISTS $DB CHARACTER SET utf8mb4;
CREATE USER IF NOT EXISTS '$DBUSER'@'localhost' IDENTIFIED BY '$DBPASS';
ALTER USER '$DBUSER'@'localhost' IDENTIFIED BY '$DBPASS';
GRANT ALL ON $DB.* TO '$DBUSER'@'localhost'; FLUSH PRIVILEGES;"

echo "== Laravel"
[ -d "$APP" ] || composer create-project laravel/laravel "$APP" --no-interaction
cd "$APP"
sed -i '/^#\?[[:space:]]*DB_/d; /^SESSION_LIFETIME=/d; /^SESSION_SECURE_COOKIE=/d' .env
sed -i 's/^APP_ENV=.*/APP_ENV=production/; s/^APP_DEBUG=.*/APP_DEBUG=false/; s#^APP_URL=.*#APP_URL=https://'"$DOMAIN"'#' .env
cat >> .env <<ENV
SESSION_LIFETIME=30
SESSION_SECURE_COOKIE=true
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=$DB
DB_USERNAME=$DBUSER
DB_PASSWORD=$DBPASS
MTN_WEBHOOK_SECRET=$(openssl rand -hex 32)
AIRTEL_WEBHOOK_SECRET=$(openssl rand -hex 32)
SCHOOLPAY_WEBHOOK_SECRET=$(openssl rand -hex 32)
ENV
php artisan install:api --no-interaction || true
cp -r "$SRC/app/." app/
cp -r "$SRC/database/." database/
cp -r "$SRC/config/." config/
cp -r "$SRC/resources/." resources/
cp "$SRC/routes/web.php" routes/web.php
cp "$SRC/routes/api.php" routes/api.php
php artisan migrate --force
php artisan db:seed --class=UserSeeder --force
[ -f storage/app/first-logins.txt ] && { mv storage/app/first-logins.txt /root/sfms-logins.txt; chmod 600 /root/sfms-logins.txt; }
mysql "$DB" -e "INSERT IGNORE INTO terms (year, number, is_current) VALUES (YEAR(CURDATE()), 1, 1);"
php artisan config:cache

chown -R root:www-data .
chmod -R g+rX .
chmod -R g+w storage bootstrap/cache
chmod 640 .env

echo "== Web server with automatic HTTPS"
cat > /etc/caddy/Caddyfile <<CADDY
$DOMAIN {
    root * $APP/public
    encode gzip
    php_fastcgi unix//run/php/php8.3-fpm.sock
    file_server
    header {
        Strict-Transport-Security "max-age=31536000"
        X-Content-Type-Options nosniff
        X-Frame-Options DENY
        -Server
    }
}
CADDY
systemctl enable --now php8.3-fpm caddy
systemctl reload caddy

echo "== Firewall, brute-force protection, nightly backup"
ufw allow OpenSSH; ufw allow 80/tcp; ufw allow 443/tcp; ufw --force enable
systemctl enable --now fail2ban
mkdir -p /var/backups/sfms
cat > /etc/cron.d/sfms-backup <<CRON
0 2 * * * root mysqldump $DB | gzip > /var/backups/sfms/sfms-\$(date +\%F).sql.gz && find /var/backups/sfms -mtime +14 -delete
CRON

umask 077
{ echo "Webhook URL:  https://$DOMAIN/api/webhooks/payments/{mtn|airtel|schoolpay}"; grep WEBHOOK_SECRET .env; } > /root/sfms-secrets.txt
echo
echo "Done. Webhook secrets: /root/sfms-secrets.txt. First logins: /root/sfms-logins.txt (root only). Change each password after signing in, then delete that file."
echo "Site: https://$DOMAIN   (the first visit can take a minute while the certificate is issued)"
