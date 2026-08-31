# Production Deployment Guide

## Domain Configuration

**Frontend:** https://soarcorp.co.ke  
**Backend API:** https://api.soarcorp.co.ke

---

## Backend Deployment (Laravel)

### 1. Server Requirements
- PHP 8.3.33 (already configured on das107)
- MySQL/MariaDB
- Redis (for queues and cache)
- Composer

### 2. Upload Files
```bash
# On your local machine
cd ~/Desktop/projects/Demand-Lead-tool
rsync -avz --exclude 'node_modules' --exclude 'vendor' --exclude '.git' \
  Backend/ mweelacr@das107:~/Demand-Lead-tool/Backend/
```

### 3. Server Setup
```bash
# SSH into server
ssh mweelacr@das107

cd ~/Demand-Lead-tool/Backend

# Copy production environment file
cp .env.production .env

# Edit .env and set:
# - APP_KEY (generate with: php artisan key:generate)
# - DB_USERNAME and DB_PASSWORD
# - MAIL credentials
# - HORIZON_BASIC_AUTH_PASSWORD
# - AT_API_KEY and AT_USERNAME (for SMS)
# - OPENAI_API_KEY (for AI features)

# Install dependencies (this will generate composer.lock for PHP 8.3)
composer install --no-dev --optimize-autoloader

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate --force

# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set permissions
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 4. Web Server Configuration (Apache/Nginx)

**For Apache (.htaccess in public directory):**
```apache
<VirtualHost *:443>
    ServerName api.soarcorp.co.ke
    DocumentRoot /home/mweelacr/Demand-Lead-tool/Backend/public

    <Directory /home/mweelacr/Demand-Lead-tool/Backend/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # SSL Configuration
    SSLEngine on
    SSLCertificateFile /path/to/ssl/certificate.crt
    SSLCertificateKeyFile /path/to/ssl/private.key
    SSLCertificateChainFile /path/to/ssl/chain.crt
</VirtualHost>
```

**For Nginx:**
```nginx
server {
    listen 443 ssl http2;
    server_name api.soarcorp.co.ke;
    root /home/mweelacr/Demand-Lead-tool/Backend/public;

    index index.php;

    # SSL Configuration
    ssl_certificate /path/to/ssl/certificate.crt;
    ssl_certificate_key /path/to/ssl/private.key;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### 5. Queue Worker (Horizon)
```bash
# Install supervisor
sudo apt install supervisor

# Create supervisor config
sudo nano /etc/supervisor/conf.d/demandlead-worker.conf
```

**Supervisor Configuration:**
```ini
[program:demandlead-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /home/mweelacr/Demand-Lead-tool/Backend/artisan horizon
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/home/mweelacr/Demand-Lead-tool/Backend/storage/logs/horizon.log
stopwaitsecs=3600
```

```bash
# Update supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start demandlead-worker:*
```

### 6. Scheduler (Cron)
```bash
# Edit crontab
crontab -e

# Add Laravel scheduler
* * * * * cd /home/mweelacr/Demand-Lead-tool/Backend && php artisan schedule:run >> /dev/null 2>&1
```

---

## Frontend Deployment (Angular)

### 1. Build for Production
```bash
# On your local machine
cd ~/Desktop/projects/Demand-Lead-tool/Frontend

# Install dependencies
npm install

# Build production bundle
npm run build:prod

# This creates optimized files in dist/ directory
```

### 2. Upload Build Files
```bash
# Upload dist folder to server
rsync -avz --delete dist/ mweelacr@das107:~/public_html/
```

### 3. Web Server Configuration

**For Apache (.htaccess in public_html):**
```apache
<VirtualHost *:443>
    ServerName soarcorp.co.ke
    DocumentRoot /home/mweelacr/public_html

    <Directory /home/mweelacr/public_html>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted

        # Angular routing - redirect all to index.html
        RewriteEngine On
        RewriteBase /
        RewriteRule ^index\.html$ - [L]
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule . /index.html [L]
    </Directory>

    # SSL Configuration
    SSLEngine on
    SSLCertificateFile /path/to/ssl/certificate.crt
    SSLCertificateKeyFile /path/to/ssl/private.key
    SSLCertificateChainFile /path/to/ssl/chain.crt

    # Compression
    <IfModule mod_deflate.c>
        AddOutputFilterByType DEFLATE text/html text/css text/javascript application/javascript application/json
    </IfModule>

    # Cache static assets
    <IfModule mod_expires.c>
        ExpiresActive On
        ExpiresByType image/jpg "access plus 1 year"
        ExpiresByType image/jpeg "access plus 1 year"
        ExpiresByType image/gif "access plus 1 year"
        ExpiresByType image/png "access plus 1 year"
        ExpiresByType text/css "access plus 1 month"
        ExpiresByType application/javascript "access plus 1 month"
    </IfModule>
</VirtualHost>
```

**For Nginx:**
```nginx
server {
    listen 443 ssl http2;
    server_name soarcorp.co.ke;
    root /home/mweelacr/public_html;

    index index.html;

    # SSL Configuration
    ssl_certificate /path/to/ssl/certificate.crt;
    ssl_certificate_key /path/to/ssl/private.key;

    # Gzip compression
    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml;

    # Angular routing - serve index.html for all routes
    location / {
        try_files $uri $uri/ /index.html;
    }

    # Cache static assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

---

## Environment Variables Summary

### Backend (.env)
```env
APP_URL=https://api.soarcorp.co.ke
CORS_ALLOWED_ORIGINS=https://soarcorp.co.ke
SANCTUM_STATEFUL_DOMAINS=soarcorp.co.ke

DB_DATABASE=demand_lead
DB_USERNAME=demand_lead_user
DB_PASSWORD=<strong-password>

MAIL_FROM_ADDRESS=noreply@soarcorp.co.ke
```

### Frontend (environment.production.ts)
```typescript
export const environment = {
  production: true,
  apiBase:    'https://api.soarcorp.co.ke',
  appUrl:     'https://soarcorp.co.ke',
};
```

---

## SSL Certificate Setup

### Using Let's Encrypt (Free)
```bash
# Install Certbot
sudo apt install certbot python3-certbot-apache

# For Apache
sudo certbot --apache -d soarcorp.co.ke -d api.soarcorp.co.ke

# For Nginx
sudo certbot --nginx -d soarcorp.co.ke -d api.soarcorp.co.ke

# Auto-renewal is configured automatically
```

---

## Post-Deployment Checklist

### Backend
- [ ] Database created and migrations run
- [ ] APP_KEY generated
- [ ] Redis running (`redis-cli ping` should return PONG)
- [ ] Queue worker running (`supervisorctl status`)
- [ ] Cron job added for scheduler
- [ ] Storage permissions set (755)
- [ ] SSL certificate installed
- [ ] CORS configured for frontend domain

### Frontend
- [ ] Production build successful
- [ ] Files uploaded to public_html
- [ ] .htaccess routing configured
- [ ] SSL certificate installed
- [ ] Test navigation (all routes work)
- [ ] API calls working (check browser console)

### Testing
- [ ] Visit https://soarcorp.co.ke
- [ ] Register new user
- [ ] Login works
- [ ] API calls show in Network tab
- [ ] Check Laravel logs: `tail -f storage/logs/laravel.log`
- [ ] Check Horizon dashboard: https://api.soarcorp.co.ke/horizon

---

## Troubleshooting

### 500 Internal Server Error
```bash
# Check Laravel logs
tail -50 storage/logs/laravel.log

# Check web server error logs
sudo tail -50 /var/log/apache2/error.log
# or
sudo tail -50 /var/log/nginx/error.log

# Common fixes
php artisan cache:clear
php artisan config:clear
chmod -R 755 storage bootstrap/cache
```

### CORS Errors
```bash
# Verify .env settings
grep CORS .env
grep SANCTUM .env

# Should be:
# CORS_ALLOWED_ORIGINS=https://soarcorp.co.ke
# SANCTUM_STATEFUL_DOMAINS=soarcorp.co.ke
```

### Queue Jobs Not Processing
```bash
# Check Horizon status
sudo supervisorctl status demandlead-worker

# Restart Horizon
sudo supervisorctl restart demandlead-worker:*

# View logs
tail -f storage/logs/horizon.log
```

---

## Maintenance Commands

### Update Application
```bash
# Pull latest changes
cd ~/Demand-Lead-tool/Backend
git pull

# Install dependencies
composer install --no-dev --optimize-autoloader

# Run migrations
php artisan migrate --force

# Clear and cache config
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart queue workers
sudo supervisorctl restart demandlead-worker:*
```

### Database Backup
```bash
# Create backup
mysqldump -u demand_lead_user -p demand_lead > backup-$(date +%Y%m%d).sql

# Restore backup
mysql -u demand_lead_user -p demand_lead < backup-20260831.sql
```

---

**Deployed:** August 31, 2026  
**Production URLs:**  
- Frontend: https://soarcorp.co.ke  
- Backend API: https://api.soarcorp.co.ke  
- Horizon Dashboard: https://api.soarcorp.co.ke/horizon
