# DemandLead Backend - Production Deployment Guide

## Prerequisites

- ✅ PHP 8.3+ installed
- ✅ Composer 2.x installed
- ✅ MySQL 8.0+ or MariaDB 10.3+
- ✅ Redis (optional but recommended)
- ✅ Nginx or Apache web server
- ✅ SSL certificate configured

## Quick Deployment Steps

### 1. Clone Repository

```bash
cd /var/www
git clone https://github.com/9pozee-nzoka/Demand-Lead-tool.git
cd Demand-Lead-tool/Backend
```

### 2. Install Dependencies

```bash
composer install --no-dev --optimize-autoloader
```

### 3. Configure Environment

```bash
# Copy production template
cp .env.production .env

# Generate application key
php artisan key:generate

# Edit .env and fill in your values
nano .env
```

**Required variables to set:**
- `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `MAIL_HOST`, `MAIL_USERNAME`, `MAIL_PASSWORD`
- `OPENAI_API_KEY` (for AI features)
- `AT_API_KEY`, `AT_USERNAME` (for SMS alerts)
- `SUPER_ADMIN_EMAIL`, `SUPER_ADMIN_PASSWORD`

### 4. Set Permissions

```bash
# Make storage and cache writable
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Create symbolic link for public storage
php artisan storage:link
```

### 5. Database Setup

```bash
# Run migrations
php artisan migrate --force

# Seed super admin
php artisan db:seed --class=SuperAdminSeeder

# (Optional) Seed demo data for testing
# php artisan db:seed --class=DemoDataSeeder
```

### 6. Optimize for Production

```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer dump-autoload --optimize
```

### 7. Configure Queue Workers

Create systemd service file: `/etc/systemd/system/demandlead-worker.service`

```ini
[Unit]
Description=DemandLead Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
Restart=always
RestartSec=5
WorkingDirectory=/var/www/Demand-Lead-tool/Backend
ExecStart=/usr/bin/php artisan queue:work --sleep=3 --tries=3 --max-time=3600

[Install]
WantedBy=multi-user.target
```

Enable and start:

```bash
sudo systemctl enable demandlead-worker
sudo systemctl start demandlead-worker
sudo systemctl status demandlead-worker
```

### 8. Configure Scheduler

Add to crontab (`crontab -e -u www-data`):

```bash
* * * * * cd /var/www/Demand-Lead-tool/Backend && php artisan schedule:run >> /dev/null 2>&1
```

### 9. Configure Web Server

#### Nginx Configuration

Create `/etc/nginx/sites-available/demandlead-api`:

```nginx
server {
    listen 80;
    server_name api.demandlead.io;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name api.demandlead.io;

    root /var/www/Demand-Lead-tool/Backend/public;
    index index.php;

    # SSL Configuration
    ssl_certificate /etc/ssl/certs/demandlead.crt;
    ssl_certificate_key /etc/ssl/private/demandlead.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    # Gzip compression
    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss text/javascript;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # Laravel application
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Deny access to hidden files
    location ~ /\. {
        deny all;
    }

    # Logging
    access_log /var/log/nginx/demandlead-api-access.log;
    error_log /var/log/nginx/demandlead-api-error.log;
}
```

Enable site:

```bash
sudo ln -s /etc/nginx/sites-available/demandlead-api /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### 10. Configure Horizon (Queue Dashboard)

Access at: `https://api.demandlead.io/horizon`

Protected by basic auth (credentials in `.env`):
- Username: `HORIZON_BASIC_AUTH_USERNAME`
- Password: `HORIZON_BASIC_AUTH_PASSWORD`

Start Horizon:

```bash
php artisan horizon
```

Or create systemd service: `/etc/systemd/system/demandlead-horizon.service`

```ini
[Unit]
Description=DemandLead Horizon
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
Restart=always
RestartSec=5
WorkingDirectory=/var/www/Demand-Lead-tool/Backend
ExecStart=/usr/bin/php artisan horizon

[Install]
WantedBy=multi-user.target
```

## Post-Deployment Verification

### Health Checks

```bash
# Check application status
php artisan about

# Check database connection
php artisan migrate:status

# Check queue workers
php artisan queue:monitor

# Check routes
php artisan route:list

# View logs
tail -f storage/logs/laravel.log
```

### Test Critical Features

1. **Authentication**
   - Register new user
   - Login
   - Password reset

2. **Projects & Keywords**
   - Create project
   - Add keywords
   - View trends

3. **Integrations**
   - Test OpenAI connection
   - Test Africa's Talking SMS
   - Test email delivery

4. **Queue Processing**
   - Check Horizon dashboard
   - Verify jobs are processing

5. **Scheduled Tasks**
   - Check cron is running: `grep CRON /var/log/syslog`

## Maintenance Commands

```bash
# Update application
cd /var/www/Demand-Lead-tool/Backend
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo systemctl restart demandlead-worker
sudo systemctl restart demandlead-horizon

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# View logs
tail -f storage/logs/laravel.log

# Queue monitoring
php artisan queue:monitor

# Restart queue workers
sudo systemctl restart demandlead-worker
```

## Backup Strategy

### Database Backup

```bash
#!/bin/bash
# /usr/local/bin/backup-demandlead-db.sh

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/var/backups/demandlead"
mkdir -p $BACKUP_DIR

mysqldump -u demand_lead_user -p demand_lead | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Keep last 7 days
find $BACKUP_DIR -name "db_*.sql.gz" -mtime +7 -delete
```

Add to crontab:
```bash
0 2 * * * /usr/local/bin/backup-demandlead-db.sh
```

### Storage Backup

```bash
rsync -avz /var/www/Demand-Lead-tool/Backend/storage /var/backups/demandlead/storage_$(date +%Y%m%d)
```

## Monitoring & Logging

### Log Files

- Laravel: `storage/logs/laravel.log`
- Nginx access: `/var/log/nginx/demandlead-api-access.log`
- Nginx error: `/var/log/nginx/demandlead-api-error.log`
- PHP-FPM: `/var/log/php8.3-fpm.log`

### Performance Monitoring

```bash
# Monitor queue size
watch -n 5 'php artisan queue:monitor'

# Monitor failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

## Troubleshooting

### Permission Issues

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Queue Not Processing

```bash
sudo systemctl restart demandlead-worker
php artisan queue:restart
```

### Config Cached

```bash
php artisan config:clear
php artisan cache:clear
```

### Database Connection Error

- Check `.env` credentials
- Verify MySQL is running: `sudo systemctl status mysql`
- Test connection: `php artisan tinker` → `DB::connection()->getPdo();`

## Security Checklist

- [ ] APP_DEBUG=false
- [ ] Strong APP_KEY generated
- [ ] Database user with minimal privileges
- [ ] Strong passwords for all services
- [ ] SSL/TLS enabled
- [ ] Firewall configured (only 80, 443, 22 open)
- [ ] File permissions correct (775 for storage)
- [ ] .env file not web-accessible
- [ ] Regular security updates
- [ ] Automated backups configured
- [ ] Rate limiting enabled
- [ ] CORS properly configured

## Support

- Documentation: `Backend/INTEGRATION_ARCHITECTURE.md`
- Environment Config: `Backend/.env.production`
- Issues: GitHub Issues

---

**Last Updated**: 2026-09-14
