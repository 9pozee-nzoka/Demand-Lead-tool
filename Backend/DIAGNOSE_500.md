# 🚨 Production 500 Error - Diagnosis Guide

## Current Status
❌ https://soarcorp.co.ke/login returns **500 Server Error**

## Step 1: Check Laravel Logs

```bash
cd ~/soarcorp/Demand-Lead-tool/Backend

# Pull latest fixes
git pull origin main

# Check latest errors
tail -50 storage/logs/laravel.log

# If file is too large, check recent errors only
tail -200 storage/logs/laravel.log | grep -A 5 "ERROR\|CRITICAL\|Exception"
```

## Step 2: Check PHP Error Logs

```bash
# LiteSpeed typically logs to:
tail -50 /usr/local/lsws/logs/error.log

# Or check cPanel error logs:
tail -50 ~/logs/error_log
```

## Step 3: Check Permissions

```bash
cd ~/soarcorp/Demand-Lead-tool/Backend

# Check storage permissions
ls -la storage/
ls -la storage/framework/
ls -la storage/framework/sessions/
ls -la storage/logs/

# Fix all permissions
chmod -R 775 storage bootstrap/cache
chown -R $(whoami):$(whoami) storage bootstrap/cache
```

## Step 4: Verify Environment

```bash
# Check if .env exists
ls -la .env

# Verify APP_KEY is set
grep APP_KEY .env

# Check debug mode
grep APP_DEBUG .env
```

## Step 5: Clear All Caches

```bash
# Clear everything
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Remove cached files manually
rm -rf bootstrap/cache/*.php
rm -rf storage/framework/cache/data/*
rm -rf storage/framework/sessions/*
rm -rf storage/framework/views/*

# Rebuild config cache
php artisan config:cache
```

## Step 6: Test Database Connection

```bash
php artisan tinker --execute="try { DB::connection()->getPdo(); echo 'Database: Connected'; } catch (Exception \$e) { echo 'Database Error: ' . \$e->getMessage(); }"
```

## Step 7: Check Web Server User

```bash
# Who owns the files?
ls -la ~/soarcorp/Demand-Lead-tool/Backend/ | head -5

# Check which user PHP runs as
ps aux | grep php

# Make sure web server can write to storage
chmod -R 775 storage bootstrap/cache
```

## Step 8: Restart Services

```bash
# Restart PHP-FPM
sudo systemctl restart php-fpm

# Or if using LiteSpeed PHP
sudo /usr/local/lsws/bin/lswsctrl restart
```

## Common 500 Error Causes

### 1. Missing APP_KEY
**Symptom:** 500 error immediately
**Fix:**
```bash
php artisan key:generate
php artisan config:cache
```

### 2. Storage Permission Denied
**Symptom:** Cannot write sessions/logs
**Fix:**
```bash
chmod -R 775 storage bootstrap/cache
```

### 3. Database Connection Failed
**Symptom:** SQLSTATE errors in log
**Fix:** Update DB credentials in .env

### 4. Redis Not Available
**Symptom:** "Class Redis not found"
**Fix:** Change to file driver:
```bash
# In .env
CACHE_STORE=file
SESSION_DRIVER=file
QUEUE_CONNECTION=database
```

### 5. Cached Config with Wrong Settings
**Symptom:** Changes to .env don't work
**Fix:**
```bash
php artisan config:clear
rm -rf bootstrap/cache/config.php
php artisan config:cache
```

### 6. Missing Dependencies
**Symptom:** Class not found errors
**Fix:**
```bash
composer install --no-dev --optimize-autoloader
```

## Enable Debug Mode (Temporarily)

⚠️ **ONLY for diagnosis - disable after!**

```bash
# Edit .env
nano .env

# Change:
APP_DEBUG=true

# Save and test
php artisan config:cache

# Check site, then DISABLE:
APP_DEBUG=false
php artisan config:cache
```

## Report Back With:

1. **Last 50 lines of laravel.log:**
```bash
tail -50 storage/logs/laravel.log
```

2. **PHP version:**
```bash
php -v
```

3. **Environment check:**
```bash
php artisan about
```

4. **File permissions:**
```bash
ls -la storage/logs/
```

---

## Quick Fix Checklist

Run these in order:

```bash
cd ~/soarcorp/Demand-Lead-tool/Backend

# 1. Pull latest code
git pull origin main

# 2. Fix permissions
chmod -R 775 storage bootstrap/cache
mkdir -p storage/framework/{sessions,views,cache}

# 3. Clear everything
php artisan config:clear
php artisan cache:clear
rm -rf bootstrap/cache/*.php

# 4. Verify APP_KEY exists
grep APP_KEY .env

# 5. If missing:
php artisan key:generate --force

# 6. Cache config
php artisan config:cache

# 7. Restart
sudo systemctl restart php-fpm
# OR
sudo /usr/local/lsws/bin/lswsctrl restart
```

Then check: https://soarcorp.co.ke/login
