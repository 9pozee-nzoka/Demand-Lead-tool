# 🔍 Production 500 Error - Root Cause Explained

## What's Happening

**URL:** https://soarcorp.co.ke/  
**Error:** 500 Internal Server Error

## Why It's Failing

The root route (`/`) in `routes/web.php` does this:

```php
Route::get('/', function () {
    if (auth()->check()) {  // ← THIS CHECKS DATABASE
        // ... redirect logic
    }
    return view('welcome');
});
```

`auth()->check()` needs to:
1. Read session from database or file
2. Query the `users` table to verify the user

**Both require a working database connection!**

## The Actual Problem

Production has **wrong database credentials cached**:

### ❌ What's Cached (WRONG):
```
DB_HOST=127.0.0.1
DB_DATABASE=demand_lead
DB_USERNAME=demand_lead_user
DB_PASSWORD=(some old password)
```

### ✅ What Should Be (CORRECT):
```
DB_HOST=localhost
DB_DATABASE=mweelacr_soarcorp
DB_USERNAME=mweelacr_pauljohns730
DB_PASSWORD=Pozee@52683542
```

## Why Just Editing .env Doesn't Work

Laravel caches configuration in:
- `bootstrap/cache/config.php`

Until you run `php artisan config:clear`, Laravel keeps using the **old cached credentials** even if you update `.env`!

## The Fix (30 seconds)

SSH into production and run:

```bash
cd ~/soarcorp/Demand-Lead-tool/Backend

# Option 1: Automated (recommended)
git pull origin main
bash FIX_DATABASE.sh
sudo systemctl restart php-fpm

# Option 2: Manual (if script fails)
php artisan config:clear
rm -rf bootstrap/cache/config.php
nano .env  # Update DB_* values
php artisan config:cache
sudo systemctl restart php-fpm
```

## Why This Happened

1. Initial setup used test credentials: `demand_lead_user`
2. Production was deployed without updating `.env`
3. Config was cached: `php artisan config:cache`
4. Later, `.env` was manually updated with correct credentials
5. **But** the cache was never cleared!
6. Laravel keeps using the old cached wrong credentials

## Verification After Fix

```bash
# Should connect successfully:
php artisan tinker --execute="DB::connection()->getPdo(); echo 'Connected!';"

# Should return: "Connected!"
```

Then visit: https://soarcorp.co.ke/login  
Expected: **HTTP 200** (login page loads)

---

## Summary

**Problem:** Cached wrong database credentials  
**Solution:** Clear cache, update .env, rebuild cache  
**Time:** 30 seconds  
**Files Involved:** `.env`, `bootstrap/cache/config.php`

**This is NOT a code issue - it's purely a deployment configuration issue!**
