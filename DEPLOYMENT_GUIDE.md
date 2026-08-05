# Jeera - Linode 4GB Deployment Guide

## IMPORTANT COMMANDS

| Category                | Command                                                                                                                   | Notes                              |
| ----------------------- | ------------------------------------------------------------------------------------------------------------------------- | ---------------------------------- |
| **Basic**               | `sudo su`                                                                                                                 | Switch to root                     |
|                         | `apt update && apt upgrade && sudo apt autoremove`                                                                        | Update system                      |
| **For PHP Artisan**     | `sudo -u www-data php artisan <command>`                                                                                  | Always use www-data user           |
| **Storage Permissions** | `sudo chown -R $USER:www-data /var/www/jeera`                                                                             | Set ownership                      |
|                         | `sudo find /var/www/jeera -type d -exec chmod 755 {} \;`                                                                  | Directory permissions              |
|                         | `sudo find /var/www/jeera -type f -exec chmod 644 {} \;`                                                                  | File permissions                   |
|                         | `sudo chown -R www-data:www-data storage bootstrap/cache`                                                                 | Laravel cache dirs                 |
|                         | `sudo chmod -R 775 storage bootstrap/cache`                                                                               | Cache permissions                  |
|                         | `sudo chmod 640 .env`                                                                                                     | Secure .env                        |
|                         | `sudo chown $USER:www-data .env`                                                                                          | .env ownership                     |
|                         | `sudo chmod -R g+s /var/www/jeera`                                                                                        | Inherit group permissions          |
|                         | `sudo usermod -aG www-data $USER`                                                                                         | Add current user to www-data group |
|                         | `newgrp www-data`                                                                                                         | Apply group changes immediately    |
| **Database Backup**     | `pg_dump -U jeera_user -h localhost -p 5432 jeera_prod > backup_$(date +%Y%m%d_%H%M).sql`                                 | Backup PostgreSQL                  |
|                         | `psql -U postgres -d jeera_prod -f backup.sql`                                                                            | Restore backup                     |
| **Security Check**      | `grep -Ei "wp-\|wordpress\|xmlrpc\.php\|union\|select\|insert\|drop\|--\|%27\|eval\(" /var/log/nginx/access.log \| wc -l` | Check for attacks                  |

---

## SYSTEM SETUP

### Base Packages

```bash
timedatectl set-timezone "Asia/Kolkata"

apt upgrade
apt update

apt -y install git unzip ufw htop fail2ban curl ca-certificates \
  software-properties-common lsb-release apt-transport-https
```

### PHP 8.3

```bash
add-apt-repository ppa:ondrej/php -y
apt update

# Install PHP 8.3 with all required extensions
apt -y install php8.3-fpm php8.3-cli php8.3-common

# Install PHP extensions (CRITICAL: includes php8.3-imap for email polling)
apt install -y php8.3-pgsql php8.3-xml php8.3-mbstring php8.3-curl \
  php8.3-zip php8.3-gd php8.3-bcmath php8.3-intl php8.3-redis \
  php8.3-sqlite3 php8.3-imap
```

**IMPORTANT:** `php8.3-imap` is required for Jeera's email polling feature!

### Composer

```bash
php -r "copy('https://getcomposer.org/installer','composer-setup.php');"
php composer-setup.php --install-dir=/usr/local/bin --filename=composer
rm -f composer-setup.php
```

### Node.js 22 LTS

```bash
curl -fsSL https://deb.nodesource.com/setup_22.x | bash -
apt -y install nodejs
```

### Redis

```bash
apt -y install redis

# Configure Redis
nano /etc/redis/redis.conf
# Add these lines:
supervised systemd
maxmemory 1gb
maxmemory-policy allkeys-lru

systemctl enable --now redis-server
```

### PostgreSQL 16 + pgvector

```bash
# Install PostgreSQL repository
apt install wget ca-certificates -y
wget -qO- https://www.postgresql.org/media/keys/ACCC4CF8.asc \
  | sudo gpg --dearmor -o /usr/share/keyrings/postgresql.gpg

echo "deb [signed-by=/usr/share/keyrings/postgresql.gpg] \
http://apt.postgresql.org/pub/repos/apt noble-pgdg main" \
  | sudo tee /etc/apt/sources.list.d/pgdg.list

apt update

# Install PostgreSQL 16 server and pgvector
apt install -y postgresql-16 postgresql-contrib-16 postgresql-16-pgvector

# Enable and start
systemctl enable postgresql
systemctl start postgresql

# Create database and user
sudo -u postgres psql <<EOF
CREATE DATABASE jeera_prod;
CREATE USER jeera_user WITH PASSWORD 'CHANGE_THIS_PASSWORD';
GRANT ALL PRIVILEGES ON DATABASE jeera_prod TO jeera_user;
ALTER DATABASE jeera_prod OWNER TO jeera_user;
\c jeera_prod
CREATE EXTENSION vector;
GRANT ALL ON SCHEMA public TO jeera_user;
\q
EOF
```

---

## NGINX SETUP

### Install Nginx

```bash
apt -y install nginx
mkdir -p /var/www/jeera
```

### Configure Nginx Site

```bash
nano /etc/nginx/sites-available/jeera.netlr.com
```

Paste this configuration:

```nginx
server {
    server_name jeera.netlr.com;
    root /var/www/jeera/public;
    index index.php index.html;

    client_max_body_size 36M;

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header X-XSS-Protection "1; mode=block" always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        fastcgi_param PATH_INFO "";
        fastcgi_read_timeout 120s;
        fastcgi_buffering on;
        fastcgi_buffers 16 256k;
        fastcgi_buffer_size 256k;
        fastcgi_intercept_errors on;
    }

    location ~* \.(css|js|jpg|jpeg|png|gif|ico|svg|woff2?)$ {
        access_log off;
        expires 30d;
        add_header Cache-Control "public, immutable";
        try_files $uri =404;
    }

    location ~ /\.(?!well-known) {
        deny all;
    }
}
```

Enable site:

```bash
ln -sf /etc/nginx/sites-available/jeera.netlr.com /etc/nginx/sites-enabled/jeera.netlr.com
nginx -t
systemctl reload nginx
```

### SSL Certificates (Let's Encrypt)

```bash
apt install certbot python3-certbot-nginx
certbot --nginx -d jeera.netlr.com
```

---

## PHP OPTIMIZATION

Edit both `/etc/php/8.3/fpm/php.ini` AND `/etc/php/8.3/cli/php.ini`:

```bash
nano /etc/php/8.3/fpm/php.ini
```

Update these values:

```ini
upload_max_filesize=32M
post_max_size=40M
max_execution_time=120
memory_limit=512M
```

**ONLY in `/etc/php/8.3/fpm/php.ini` (NOT cli):**

```ini
; Uncomment (remove semicolon) and set:
zend_extension=opcache

; Add at bottom:
opcache.enable=1
opcache.enable_cli=0
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=100000
opcache.validate_timestamps=0
opcache.revalidate_freq=0
opcache.save_comments=1
opcache.jit=0
realpath_cache_size=4096K
realpath_cache_ttl=600
```

Edit FPM pool config:

```bash
nano /etc/php/8.3/fpm/pool.d/www.conf
```

Update:

```ini
pm = dynamic
pm.max_children=40
pm.start_servers=8
pm.min_spare_servers=8
pm.max_spare_servers=16
pm.max_requests=500
```

Restart PHP-FPM:

```bash
systemctl restart php8.3-fpm
```

---

## GIT SETUP & CLONE

### Generate Deploy Key

```bash
ssh-keygen -t ed25519 -C "deploy-key-$(hostname)-$(date +%F)" -f ~/.ssh/github_deploy_key
chmod 600 ~/.ssh/github_deploy_key
```

### Add to GitHub

```bash
cat ~/.ssh/github_deploy_key.pub
```

Copy output and add to: https://github.com/vinay898/jeera/settings/keys (as Deploy Key)

### Configure SSH

```bash
nano ~/.ssh/config
```

Add:

```
Host github.com
    HostName github.com
    User git
    IdentityFile ~/.ssh/github_deploy_key
    IdentitiesOnly yes
```

### Clone Repository

```bash
cd /var/www
git clone git@github.com:vinay898/jeera.git
git config --global --add safe.directory /var/www/jeera
```

---

## APPLICATION SETUP

### Install Dependencies

```bash
cd /var/www/jeera

# Set permissions first
sudo chown -R $USER:www-data /var/www/jeera
sudo usermod -aG www-data $USER
newgrp www-data

# Composer install
# Add blueprint license command from pw
composer install --no-dev --prefer-dist --optimize-autoloader

# NPM install and build
npm install
npm run build

# Fix node_modules permissions (if needed)
sudo chown -R $USER:www-data node_modules public/build
find node_modules/.bin -type f -exec chmod 755 {} \;
chmod +x node_modules/.bin/vite
```

### Environment Configuration

```bash
cp .env.example .env
nano .env
```

Update these critical values:

```ini
APP_NAME="Jeera"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://jeera.netlr.com

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=jeera_prod
DB_USERNAME=jeera_user
DB_PASSWORD=CHANGE_THIS_PASSWORD

SESSION_DRIVER=database
QUEUE_CONNECTION=database

CACHE_STORE=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_FROM_ADDRESS=noreply@netlr.com
MAIL_FROM_NAME="Jeera Support"
```

### Run Migrations

```bash
sudo -u www-data php artisan key:generate
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan migrate --force
```

### Set Final Permissions

```bash
sudo chown -R $USER:www-data /var/www/jeera
sudo find /var/www/jeera -type d -exec chmod 755 {} \;
sudo find /var/www/jeera -type f -exec chmod 644 {} \;
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
sudo chmod 640 .env
sudo chown $USER:www-data .env
sudo chmod -R g+s /var/www/jeera
```

---

## SUPERVISOR SETUP

### Install Supervisor

```bash
apt install -y supervisor
systemctl enable supervisor
systemctl start supervisor
```

### Queue Worker Configuration

```bash
nano /etc/supervisor/conf.d/laravel-worker.conf
```

Paste:

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=/usr/bin/php /var/www/jeera/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/jeera/storage/logs/worker.log
```

### Email Poller Configuration (CRITICAL for Jeera)

```bash
nano /etc/supervisor/conf.d/laravel-email-poller.conf
```

Paste:

```ini
[program:laravel-email-poller]
command=/usr/bin/php /var/www/jeera/artisan emails:process-inbound
process_name=%(program_name)s
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/jeera/storage/logs/email-poller.log
stopwaitsecs=10
```

**Note:** The scheduler (cron) will trigger email polling every 10 seconds via the schedule defined in `routes/console.php`.

### Reload Supervisor

```bash
supervisorctl reread
supervisorctl update
supervisorctl status
```

**Common Supervisor Commands:**

```bash
# Restart all workers
sudo supervisorctl restart all

# Restart specific worker
sudo supervisorctl restart laravel-worker:*

# View logs
sudo supervisorctl tail -f laravel-worker
sudo supervisorctl tail -f laravel-email-poller
```

---

## SCHEDULER SETUP (Cron)

```bash
sudo crontab -u www-data -e
```

Add this line:

```
* * * * * /usr/bin/php /var/www/jeera/artisan schedule:run >> /dev/null 2>&1
```

Or create cron file:

```bash
nano /etc/cron.d/laravel-scheduler
```

Add:

```
* * * * * www-data /usr/bin/php /var/www/jeera/artisan schedule:run >> /dev/null 2>&1
```

Set permissions:

```bash
sudo chmod 644 /etc/cron.d/laravel-scheduler
sudo chown root:root /etc/cron.d/laravel-scheduler
sudo systemctl restart cron
```

---

## DEPLOYMENT SCRIPT

Create deployment script for future updates:

```bash
nano /var/www/jeera/deployscript.sh
```

Paste:

```bash
#!/bin/bash
echo "Starting deployment optimization..."
cd /var/www/jeera || exit

echo "Git pull latest changes..."
git pull origin main

echo "Installing composer dependencies..."
composer install --no-dev --prefer-dist --optimize-autoloader

echo "NPM install and build..."
npm install
npm run build

echo "Clearing all Laravel caches..."
php artisan optimize:clear
php artisan filament:optimize-clear

echo "Running Laravel optimize..."
php artisan optimize

echo "Running migrations..."
php artisan migrate --force

echo "Clearing compiled views..."
php artisan view:clear

echo "Clearing and re-caching Filament components..."
php artisan filament:clear
php artisan filament:optimize

echo "Restarting PHP-FPM service..."
systemctl restart php8.3-fpm

echo "Restarting supervisor processes..."
supervisorctl restart all

echo "Deployment complete!"
```

Make executable:

```bash
sudo chmod +x /var/www/jeera/deployscript.sh
```

**To deploy updates:**

```bash
cd /var/www/jeera
sudo ./deployscript.sh
```

---

## VERIFICATION CHECKLIST

After deployment, verify:

1. **Web Access:** Visit https://jeera.netlr.com
2. **Create User:** Register first user account
3. **Create Team:** Set up your first team
4. **Create Project:** Add a project
5. **Create Ticket:** Test ticket creation
6. **Test Email Settings:**
    - Go to Email Settings page in admin
    - Configure IMAP settings
    - Test connection
7. **Upload Attachment:** Create ticket with attachment
8. **Check Logs:**
    ```bash
    tail -f /var/www/jeera/storage/logs/laravel.log
    tail -f /var/log/nginx/error.log
    sudo supervisorctl tail -f laravel-worker
    sudo supervisorctl tail -f laravel-email-poller
    ```

---

## TROUBLESHOOTING

### Permission Issues

```bash
# Reset all permissions
sudo chown -R $USER:www-data /var/www/jeera
sudo find /var/www/jeera -type d -exec chmod 755 {} \;
sudo find /var/www/jeera -type f -exec chmod 644 {} \;
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
sudo chmod -R g+s /var/www/jeera
```

### Node Build Errors

```bash
rm -rf node_modules/@esbuild
npm rebuild esbuild
npm run build
```

### Queue Not Processing

```bash
sudo supervisorctl restart laravel-worker:*
sudo supervisorctl status
```

### Email Polling Not Working

```bash
# Check if IMAP extension is installed
php -m | grep imap

# If not installed:
apt install -y php8.3-imap
systemctl restart php8.3-fpm

# Check email poller logs
sudo supervisorctl tail -f laravel-email-poller
```

### Clear All Caches

```bash
php artisan optimize:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
redis-cli FLUSHALL
```

### Check Service Status

```bash
systemctl status nginx
systemctl status php8.3-fpm
systemctl status postgresql
systemctl status redis-server
systemctl status supervisor
```

---

## BACKUP & RESTORE

### Database Backup

```bash
# Backup
pg_dump -U jeera_user -h localhost jeera_prod > ~/backup/jeera_backup_$(date +%Y%m%d_%H%M).sql

# Restore
psql -U postgres -d jeera_prod -f ~/backup/jeera_backup_20260125.sql
```

### Full Application Backup

```bash
# Create backup directory
mkdir -p ~/backup

# Backup database
pg_dump -U jeera_user -h localhost jeera_prod > ~/backup/db_backup.sql

# Backup .env
cp /var/www/jeera/.env ~/backup/env_backup

# Backup storage (attachments)
tar -czf ~/backup/storage_backup.tar.gz -C /var/www/jeera storage/app/public

# Download to local machine (from local terminal):
# scp -r user@your-server-ip:~/backup/ .
```

---

## MAINTENANCE COMMANDS

### View Logs

```bash
# Application logs
tail -f /var/www/jeera/storage/logs/laravel.log

# Nginx logs
tail -f /var/log/nginx/access.log
tail -f /var/log/nginx/error.log

# Supervisor logs
sudo supervisorctl tail -f laravel-worker
sudo supervisorctl tail -f laravel-email-poller

# PostgreSQL logs
sudo tail -f /var/log/postgresql/postgresql-16-main.log
```

### Database Console

```bash
# Connect to database
sudo -u postgres psql jeera_prod

# Or with jeera_user
psql -U jeera_user -d jeera_prod -h localhost
```

### Laravel Tinker

```bash
sudo -u www-data php artisan tinker
```

### Monitor Resources

```bash
htop
df -h
free -h
```

---

## OPTIONAL: Laravel Pulse (Monitoring)

```bash
composer require laravel/pulse
php artisan vendor:publish --provider="Laravel\Pulse\PulseServiceProvider"
php artisan migrate
```

Access at: https://jeera.netlr.com/pulse

---

## SECURITY NOTES

1. **Firewall:** Configure UFW to only allow necessary ports

    ```bash
    ufw allow 22/tcp
    ufw allow 80/tcp
    ufw allow 443/tcp
    ufw enable
    ```

2. **Fail2ban:** Already installed, monitors SSH attempts

3. **Database:** PostgreSQL only accepts local connections by default

4. **Environment File:** `.env` has restrictive permissions (640)

5. **Regular Updates:**
    ```bash
    apt update && apt upgrade -y
    apt autoremove -y
    ```

---

## ALL DONE!

Your Jeera installation is complete.

**Monthly Cost:** ~$24-30/month (Linode 4GB + optional backups)

**Key Features Enabled:**

- ✅ PostgreSQL 16 with pgvector (ready for AI features)
- ✅ Email polling via IMAP (every 10 seconds)
- ✅ Queue workers (2 processes)
- ✅ Scheduler (cron-based)
- ✅ Redis caching
- ✅ File uploads (local storage)
- ✅ SSL certificates (Let's Encrypt)
- ✅ Optimized PHP/Nginx

**Next Steps:**

1. Configure email settings in admin panel
2. Create your first team and project
3. Test ticket creation with email
4. Monitor logs for any issues
5. Set up regular backups
