# 🚀 DEPLOY NOW - Production Commands

## Current Status
❌ https://soarcorp.co.ke/login → **500 Server Error**

This is because production hasn't pulled the latest code with schema fixes yet.

---

## Run These Commands on Production Server

SSH into production and run:

```bash
# Navigate to project
cd ~/soarcorp/Demand-Lead-tool/Backend

# Pull latest code (includes schema fixes)
git pull origin main

# Run migrations (adds missing columns)
php artisan migrate --force

# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Rebuild config cache
php artisan config:cache

# Fix permissions
chmod -R 775 storage bootstrap/cache
mkdir -p storage/framework/sessions
chmod -R 775 storage/framework/sessions

# Restart PHP-FPM
sudo systemctl restart php-fpm

# OR if using LiteSpeed:
# sudo /usr/local/lsws/bin/lswsctrl restart
```

---

## Verify After Deployment

### 1. Check Schema
```bash
php artisan tinker --execute="
echo 'keyword: ' . (Schema::hasColumn('keywords', 'keyword') ? 'YES' : 'NO') . PHP_EOL;
echo 'term: ' . (Schema::hasColumn('keywords', 'term') ? 'YES' : 'NO') . PHP_EOL;
echo 'match_type: ' . (Schema::hasColumn('keywords', 'match_type') ? 'YES' : 'NO') . PHP_EOL;
echo 'notes: ' . (Schema::hasColumn('keywords', 'notes') ? 'YES' : 'NO') . PHP_EOL;
"
```

Expected output:
```
keyword: YES
term: YES
match_type: YES
notes: YES
```

### 2. Check Configuration
```bash
php artisan tinker --execute="
echo 'SESSION_DRIVER: ' . config('session.driver') . PHP_EOL;
echo 'SESSION_ENCRYPT: ' . (config('session.encrypt') ? 'true' : 'false') . PHP_EOL;
echo 'APP_DEBUG: ' . (config('app.debug') ? 'true' : 'false') . PHP_EOL;
"
```

Expected output:
```
SESSION_DRIVER: file
SESSION_ENCRYPT: false
APP_DEBUG: false
```

### 3. Test Login Page
```bash
curl -I https://soarcorp.co.ke/login
```

Expected: `HTTP/2 200` (not 500)

### 4. Check Logs
```bash
tail -20 storage/logs/laravel.log
```

Should see no new errors.

---

## What Was Fixed

1. **Added missing columns to keywords table:**
   - `term` (string, nullable)
   - `match_type` (enum: exact/phrase/broad)
   - `notes` (text, nullable)

2. **Fixed KeywordController:**
   - Now populates both `keyword` and `term` fields
   - Uses `country` instead of `location` in keyword_locations

3. **Cleaned up:**
   - Removed temporary debugging docs
   - Removed debug routes

---

## If Still Getting 500 Error

### Check APP_KEY
```bash
grep APP_KEY .env
```

If empty or missing:
```bash
php artisan key:generate --force
php artisan config:cache
```

### Check Database Credentials
```bash
php artisan tinker --execute="
try {
    DB::connection()->getPdo();
    echo 'Database: Connected';
} catch (Exception \$e) {
    echo 'Database Error: ' . \$e->getMessage();
}
"
```

If connection fails, update `.env`:
```bash
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=demand_lead
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### Enable Debug (Temporarily)
```bash
# Edit .env
nano .env

# Change:
APP_DEBUG=true

# Save and cache
php artisan config:cache

# Check error at: https://soarcorp.co.ke/login

# Then DISABLE debug:
APP_DEBUG=false
php artisan config:cache
```

---

## Expected Result

After deployment:
- ✅ Login page loads (HTTP 200)
- ✅ Can create keywords without errors
- ✅ Sessions persist correctly
- ✅ No 500 errors

---

**Delete this file after successful deployment.**
