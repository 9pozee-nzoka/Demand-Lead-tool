# Login Redirect Issue - Debugging Guide

## Problem
After successful login POST, user is redirected back to `/login` instead of `/dashboard` in production.

## Common Causes & Solutions

### 1. Session Configuration Issues

**Check `.env` file on production:**

```bash
# Session driver - must be database or redis in production
SESSION_DRIVER=database  # or redis

# Session lifetime (minutes)
SESSION_LIFETIME=120

# Cookie settings for production
SESSION_DOMAIN=.yourdomain.com  # Note the leading dot for subdomains
SESSION_SECURE_COOKIE=true      # Must be true for HTTPS
SESSION_ENCRYPT=true            # Recommended for security

# For same-site cookie
SESSION_SAME_SITE=lax          # Options: lax, strict, none
```

**Critical: If using HTTPS (which you should be), ensure:**
```bash
SESSION_SECURE_COOKIE=true
```

### 2. Database Session Table

**Check if sessions table exists:**

```bash
php artisan migrate:status | grep sessions
```

**If not migrated, run:**

```bash
php artisan session:table
php artisan migrate
```

### 3. Cache Configuration

**Clear all caches on production:**

```bash
cd /var/www/Demand-Lead-tool/Backend

# Clear all Laravel caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Regenerate optimized files
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 4. File Permissions

**Ensure storage directory is writable:**

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Specifically for sessions
chmod -R 775 storage/framework/sessions
```

### 5. APP_KEY Issue

**Verify APP_KEY is set and consistent:**

```bash
# Check if APP_KEY exists in .env
grep APP_KEY .env

# If empty, generate one:
php artisan key:generate

# IMPORTANT: After generating, clear config cache:
php artisan config:clear
php artisan config:cache
```

### 6. CORS / Cross-Domain Issues

**If frontend and backend are on different domains:**

Check `config/cors.php`:

```php
'supports_credentials' => true,  // Must be true for cookies
```

And in `.env`:
```bash
SANCTUM_STATEFUL_DOMAINS=app.yourdomain.com
SESSION_DOMAIN=.yourdomain.com  # Note the leading dot
```

### 7. Middleware Configuration

**Check `bootstrap/app.php` or middleware groups** are not blocking:

```bash
php artisan route:list | grep "POST.*login"
```

Should show:
```
POST | login | auth.login | guest
```

### 8. Redis Configuration (if using Redis)

**If SESSION_DRIVER=redis, verify Redis is running:**

```bash
# Test Redis connection
redis-cli ping
# Should return: PONG

# Test Laravel can connect to Redis
php artisan tinker
>>> Redis::connection()->ping()
# Should return: "+PONG"
```

## Debugging Steps

### Step 1: Enable Debug Mode Temporarily

**ONLY ON STAGING/DEV - NEVER ON PRODUCTION:**

```bash
APP_DEBUG=true
APP_ENV=local
LOG_LEVEL=debug
```

Then check `storage/logs/laravel.log` after attempting login.

### Step 2: Add Debug Logging

Add to `AuthenticatedSessionController@store`:

```php
public function store(LoginRequest $request): RedirectResponse
{
    \Log::info('Login attempt', [
        'email' => $request->email,
        'ip' => $request->ip()
    ]);

    $request->authenticate();

    \Log::info('Authentication successful', [
        'user_id' => $request->user()->id,
        'is_super_admin' => $request->user()->is_super_admin
    ]);

    $request->session()->regenerate();

    $redirectUrl = $request->user()->is_super_admin 
        ? route('admin.dashboard', absolute: false)
        : route('dashboard', absolute: false);

    \Log::info('Redirecting to', ['url' => $redirectUrl]);

    return redirect()->intended($redirectUrl);
}
```

### Step 3: Check Session Data

Create test route:

```php
// In routes/web.php (temporarily)
Route::get('/debug-session', function() {
    return [
        'authenticated' => auth()->check(),
        'user' => auth()->user(),
        'session_id' => session()->getId(),
        'session_data' => session()->all(),
    ];
})->middleware('auth');
```

### Step 4: Test Authentication Manually

```bash
php artisan tinker

>>> $user = User::where('email', 'test@example.com')->first();
>>> Auth::login($user);
>>> auth()->check()
# Should return: true
>>> auth()->user()->name
# Should return user name
```

## Production Quick Fix

**Most common issue is session configuration. Try this:**

```bash
cd /var/www/Demand-Lead-tool/Backend

# 1. Backup current .env
cp .env .env.backup

# 2. Update session settings
sed -i 's/^SESSION_DRIVER=.*/SESSION_DRIVER=database/' .env
sed -i 's/^SESSION_SECURE_COOKIE=.*/SESSION_SECURE_COOKIE=true/' .env

# 3. Ensure sessions table exists
php artisan migrate --force

# 4. Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 5. Regenerate caches
php artisan config:cache
php artisan route:cache

# 6. Fix permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# 7. Restart services
sudo systemctl restart php8.3-fpm
sudo systemctl restart nginx
```

## Verification

After fixes, test:

1. **Clear browser cookies completely**
2. Visit `/login`
3. Enter credentials
4. Submit form
5. Should redirect to `/dashboard`

## Check Logs

```bash
# Laravel logs
tail -f storage/logs/laravel.log

# Nginx access log
tail -f /var/log/nginx/access.log

# Nginx error log
tail -f /var/log/nginx/error.log

# PHP-FPM log
tail -f /var/log/php8.3-fpm.log
```

## Common Log Messages

**If session not persisting:**
```
Session store not set on request.
```
**Solution:** Check SESSION_DRIVER and database connection

**If redirecting to intended URL:**
```
Redirecting to intended url
```
**Solution:** Session is working, check middleware blocking dashboard access

**If CSRF token mismatch:**
```
CSRF token mismatch
```
**Solution:** Check SESSION_DOMAIN and SESSION_SECURE_COOKIE settings

## Still Not Working?

1. Check if dashboard route exists:
   ```bash
   php artisan route:list | grep dashboard
   ```

2. Check if user has organization_id:
   ```bash
   php artisan tinker
   >>> User::find(1)->organization_id
   ```

3. Check middleware on dashboard route - might be blocking

4. Enable query logging:
   ```php
   \DB::enableQueryLog();
   // ... attempt login ...
   dd(\DB::getQueryLog());
   ```

## Quick Test Script

Create `test-auth.php` in public directory:

```php
<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::create('/test', 'GET')
);

echo "Session Driver: " . config('session.driver') . "\n";
echo "Session Lifetime: " . config('session.lifetime') . "\n";
echo "Session Secure Cookie: " . config('session.secure') . "\n";
echo "Session Domain: " . config('session.domain') . "\n";
echo "APP_KEY set: " . (config('app.key') ? 'Yes' : 'No') . "\n";
echo "Database connected: ";
try {
    DB::connection()->getPdo();
    echo "Yes\n";
} catch (Exception $e) {
    echo "No - " . $e->getMessage() . "\n";
}
```

Run: `php public/test-auth.php`

---

**Most Likely Cause:** SESSION_SECURE_COOKIE is false but site uses HTTPS, or SESSION_DOMAIN is misconfigured.
