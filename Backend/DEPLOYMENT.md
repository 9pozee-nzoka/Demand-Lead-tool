# SoarCorp Demand Intelligence — Production Deployment Guide

**Domain:** soarcorp.co.ke  
**Server:** Shared hosting (cPanel/Namecheap)  
**Environment:** PHP 8.4, MySQL 8, file-based cache/sessions, database queues

---

## Pre-Deployment Checklist

### 1. Server Requirements

✅ **Verified on your server:**
- ✅ PHP 8.4.24 (confirmed via `php -v`)
- ✅ MySQL 8.x
- ✅ Composer 2.x
- ✅ Node.js 20+ & npm (for Vite build)
- ✅ Git access via SSH

❌ **NOT available (shared hosting):**
- ❌ Redis (using file cache instead)
- ❌ Supervisor (using cron instead)

---

## Step 1: DNS Configuration

Add these DNS records at your domain registrar:

```
A    soarcorp.co.ke          → YOUR_SERVER_IP
A    app.soarcorp.co.ke      → YOUR_SERVER_IP
A    api.soarcorp.co.ke      → YOUR_SERVER_IP
```

**Wait 1-24 hours for propagation.**

---

## Step 2: Clone Repository

```bash
ssh your-user@soarcorp.co.ke

cd /home/mweelacr
git clone YOUR_REPO_URL demand-lead
cd demand-lead
```

---

## Step 3: Backend Setup

### 3.1 Environment Configuration

```bash
cd /home/mweelacr/demand-lead
cp .env.example .env
nano .env
```

**Required `.env` changes:**

```env
APP_NAME="SoarCorp Demand Intelligence"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.soarcorp.co.ke

# Database (your actual credentials)
DB_DATABASE=mweelacr_soarcorp
DB_USERNAME=mweelacr_pauljohns730
DB_PASSWORD=hashed

# Session & Cache (file-based for shared hosting)
SESSION_DRIVER=file
SESSION_DOMAIN=.soarcorp.co.ke
SESSION_SECURE_COOKIE=true
CACHE_STORE=file

# Queue (database, not Redis)
QUEUE_CONNECTION=database

# Mail (configure your SMTP)
MAIL_MAILER=smtp
MAIL_HOST=smtp.your-provider.com
MAIL_PORT=587
MAIL_USERNAME=your-email@soarcorp.co.ke
MAIL_PASSWORD=your-mail-password
MAIL_FROM_ADDRESS="noreply@soarcorp.co.ke"

# Super Admin
SUPER_ADMIN_NAME="Paul Johns"
SUPER_ADMIN_EMAIL=pauljohns730@gmail.com
SUPER_ADMIN_PASSWORD=Pozee@5268

# Optional: SerpApi for real Google Trends data
SERPAPI_KEY=your-serpapi-key-here
```

### 3.2 Install Dependencies

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

### 3.3 Generate App Key

```bash
php artisan key:generate
```

### 3.4 Run Migrations

```bash
php artisan migrate --force
```

### 3.5 Seed Database

```bash
# Super admin account
php artisan db:seed --class=SuperAdminSeeder

# Demo data (optional, for testing)
php artisan db:seed --class=DemoDataSeeder
```

### 3.6 Optimize for Production

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

---

## Step 4: cPanel Configuration

### 4.1 Document Root Setup

**For `api.soarcorp.co.ke`:**
- Go to cPanel → Domains → Manage
- Point document root to: `/home/mweelacr/demand-lead/public`

**Important:** The document root MUST be the `/public` folder, not the root. This prevents `.env` exposure.

### 4.2 PHP Version

- Go to cPanel → MultiPHP Manager
- Select `api.soarcorp.co.ke`
- Set PHP version to **8.4** (or 8.3 minimum)

### 4.3 File Permissions

```bash
chmod -R 755 storage bootstrap/cache
```

---

## Step 5: Cron Jobs

Go to cPanel → Cron Jobs and add these two jobs:

### Job 1: Laravel Scheduler (required)

```
*  *  *  *  *  /usr/local/bin/php /home/mweelacr/demand-lead/artisan schedule:run >> /dev/null 2>&1
```

**Frequency:** Every minute  
**Purpose:** Runs all scheduled tasks (keyword collection, signal processing, cleanup)

### Job 2: Queue Worker (required)

```
*/5  *  *  *  *  /usr/local/bin/php /home/mweelacr/demand-lead/artisan queue:work --stop-when-empty --tries=3 --timeout=120 >> /dev/null 2>&1
```

**Frequency:** Every 5 minutes  
**Purpose:** Processes queued jobs (data collection, opportunity scoring, alerts)

---

## Step 6: SSL Certificates

### Option A: cPanel AutoSSL (recommended)

1. Go to cPanel → SSL/TLS Status
2. Select all domains (soarcorp.co.ke, api.soarcorp.co.ke, app.soarcorp.co.ke)
3. Click "Run AutoSSL"

Certificates auto-renew every 90 days.

### Option B: Let's Encrypt via SSH

```bash
certbot --apache -d soarcorp.co.ke -d api.soarcorp.co.ke -d app.soarcorp.co.ke
```

---

## Step 7: Frontend Deployment (Laravel Blade)

The frontend is already included in the Laravel app. No separate deployment needed.

**Access URLs:**
- Marketing page: `https://api.soarcorp.co.ke` (root)
- Dashboard: `https://api.soarcorp.co.ke/dashboard`
- Super admin: `https://api.soarcorp.co.ke/super-admin/dashboard`

---

## Step 8: Post-Deployment Verification

### 8.1 Health Check

```bash
cd /home/mweelacr/demand-lead
php artisan demand:health
```

**Expected output:**
```
Active keywords: X
Measurements (24h): X
Trends updated (6h): X
Pending jobs: 0
Failed jobs: 0
✓ System health looks good!
```

### 8.2 Test Scheduler

```bash
php artisan schedule:test
```

### 8.3 Test Queue

```bash
php artisan queue:work --stop-when-empty
```

Should process any pending jobs.

### 8.4 Test Web Access

Visit these URLs in your browser:

✅ `https://api.soarcorp.co.ke` → Marketing landing page  
✅ `https://api.soarcorp.co.ke/login` → Login form  
✅ `https://api.soarcorp.co.ke/dashboard` → Dashboard (after login)  
✅ `https://api.soarcorp.co.ke/super-admin/dashboard` → Super admin panel

---

## Step 9: Monitoring & Maintenance

### Daily Health Check

Add this to your daily routine:

```bash
php artisan demand:health
```

### Check Failed Jobs

```bash
php artisan queue:failed
```

If any failed jobs exist, retry them:

```bash
php artisan demand:retry-failed
```

### View Logs

```bash
tail -f storage/logs/laravel.log
```

### Clear Old Logs (monthly)

```bash
rm storage/logs/laravel-*.log
```

---

## Step 10: Updating the Application

Create `deploy/deploy-shared-hosting.sh` and run it for updates:

```bash
cd /home/mweelacr/demand-lead
bash deploy/deploy-shared-hosting.sh
```

This script:
1. Pulls latest code
2. Installs dependencies
3. Runs migrations
4. Rebuilds caches
5. Restarts queue workers

---

## Troubleshooting

### Issue: "500 Internal Server Error"

**Check:**
```bash
tail -20 storage/logs/laravel.log
```

**Common fixes:**
```bash
php artisan config:clear
php artisan cache:clear
chmod -R 755 storage bootstrap/cache
```

### Issue: "Queue jobs not processing"

**Check:**
```bash
# Verify cron is running
crontab -l

# Check pending jobs
php artisan queue:listen --once
```

### Issue: "No keyword data collected"

**Check:**
```bash
# Verify scheduler is running
php artisan schedule:list

# Manually trigger collection
php artisan demand:collect --keyword_id=1
```

### Issue: "Database connection failed"

**Check `.env` credentials:**
```bash
php artisan tinker --execute="DB::connection()->getPdo();"
```

---

## Security Checklist

✅ `.env` is outside public folder  
✅ `APP_DEBUG=false` in production  
✅ `SESSION_SECURE_COOKIE=true` (HTTPS only)  
✅ Super admin password is strong  
✅ Database user has minimal privileges  
✅ File permissions correct (755 for storage)  
✅ SSL certificate active  

---

## Performance Optimization

### Enable OPcache (cPanel → PHP Settings)

```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
```

### Database Indexing

Run this once after first migration:

```bash
php artisan db:optimize
```

---

## Backup Strategy

### Database Backup (daily cron)

Add to cPanel cron:

```bash
0 3 * * * mysqldump -u mweelacr_pauljohns730 -phashed mweelacr_soarcorp > /home/mweelacr/backups/db-$(date +\%Y\%m\%d).sql
```

### Code Backup

Your git repository is your backup. Push regularly:

```bash
git push origin main
```

---

## Support Contacts

- **Server Issues:** Namecheap support
- **Application Issues:** pauljohns730@gmail.com
- **DNS Issues:** Domain registrar

---

## Quick Reference Commands

```bash
# Health check
php artisan demand:health

# Manual data collection
php artisan demand:collect

# View logs
tail -f storage/logs/laravel.log

# Clear caches
php artisan cache:clear && php artisan config:clear

# Retry failed jobs
php artisan demand:retry-failed

# Optimize for production
php artisan optimize

# Test scheduler
php artisan schedule:test
```

---

**Deployment Date:** _______________  
**Deployed By:** Paul Johns  
**Server IP:** _______________  
**Database Name:** mweelacr_soarcorp

✅ **DEPLOYMENT COMPLETE**
