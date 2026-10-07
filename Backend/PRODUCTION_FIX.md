# Production Login Fix - Quick Guide

## Current Status
✅ Login page loads: https://soarcorp.co.ke/login  
❌ Dashboard redirect issue after login

## The Problem
After login POST, user is redirected back to login instead of dashboard. This is a **session persistence issue**.

## Quick Fix Commands

Run these on production server:

```bash
cd ~/soarcorp/Demand-Lead-tool/Backend

# 1. Pull latest code
git pull origin main

# 2. Edit .env file
nano .env
```

**Ensure these exact settings in .env:**

```bash
# Critical Session Settings
SESSION_DRIVER=file               # OR database (both work)
SESSION_SECURE_COOKIE=true
SESSION_ENCRYPT=false
SESSION_DOMAIN=
SESSION_SAME_SITE=lax

# Cache (NOT Redis)
CACHE_STORE=file
QUEUE_CONNECTION=database

# Remove or comment out Redis settings
# REDIS_HOST=127.0.0.1
# REDIS_PASSWORD=null
# REDIS_PORT=6379
```

Save: `Ctrl+X`, `Y`, `Enter`

```bash
# 3. Clear everything
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# 4. Fix session directory permissions (CRITICAL for file driver)
chmod -R 775 storage/framework/sessions
mkdir -p storage/framework/sessions
chown -R $(whoami):$(whoami) storage/framework/sessions

# 5. Clear old sessions
rm -rf storage/framework/sessions/*

# 6. Cache config
php artisan config:cache

# 7. Fix all storage permissions
chmod -R 775 storage bootstrap/cache

# 8. Restart PHP-FPM
sudo systemctl restart php-fpm
```

## Test Login

1. **Clear browser cookies completely** (Important!)
2. Visit: https://soarcorp.co.ke/login
3. Enter credentials
4. Should redirect to dashboard

## Verify Configuration

```bash
php artisan tinker
>>> config('session.driver')        # Should be: "file"
>>> config('session.secure')        # Should be: true
>>> config('session.encrypt')       # Should be: false
>>> config('session.domain')        # Should be: null
>>> config('session.path')          # Should be: "/"
>>> exit
```

**Check session directory:**
```bash
ls -la storage/framework/sessions/
# Should be writable (drwxrwxr-x)
```

## If Still Not Working

Check logs:
```bash
tail -50 storage/logs/laravel.log
```

Verify database credentials work:
```bash
php artisan tinker --execute="DB::connection()->getPdo(); echo 'Connected';"
```

Force clear everything:
```bash
rm -rf bootstrap/cache/*.php
rm -rf storage/framework/cache/data/*
rm -rf storage/framework/sessions/*
rm -rf storage/framework/views/*
php artisan cache:clear
php artisan config:clear
sudo systemctl restart php-fpm
```

## Common Issues

**Issue:** Redis error  
**Fix:** Set `CACHE_STORE=file` and `QUEUE_CONNECTION=database` in .env

**Issue:** Database connection error  
**Fix:** Update `DB_*` credentials in .env

**Issue:** CSRF token mismatch  
**Fix:** Clear browser cookies completely and retry

**Issue:** 419 Page Expired  
**Fix:** Clear sessions table and browser cookies

---

The key is **SESSION_ENCRYPT=false** for HTTPS sites. When true, it can cause silent session failures.
