# Production Setup Summary

## ✅ Changes Made

### Backend Configuration

**1. Created `.env.production` file**
- Location: `Backend/.env.production`
- Configuration:
  - `APP_URL=https://api.soarcorp.co.ke`
  - `CORS_ALLOWED_ORIGINS=https://soarcorp.co.ke`
  - `SANCTUM_STATEFUL_DOMAINS=soarcorp.co.ke`
  - MySQL database settings
  - Redis for queues and cache
  - Production logging and security settings

**2. Updated `composer.json`**
- Added platform constraint: `"platform": {"php": "8.3.33"}`
- This ensures Composer resolves PHP 8.3-compatible dependencies
- Removed incompatible `composer.lock` (will be regenerated on server)

### Frontend Configuration

**1. Updated `environment.production.ts`**
- Changed frontend URL from `app.soarcorp.co.ke` to `soarcorp.co.ke`
- API base URL: `https://api.soarcorp.co.ke`
- Configuration:
  ```typescript
  export const environment = {
    production: true,
    apiBase:    'https://api.soarcorp.co.ke',
    appUrl:     'https://soarcorp.co.ke',
  };
  ```

**2. Created API Base URL Interceptor**
- File: `Frontend/src/app/core/interceptors/api-base.interceptor.ts`
- Purpose: Automatically prepends `environment.apiBase` to all API requests
- Development: Uses empty string (proxy handles routing)
- Production: Uses `https://api.soarcorp.co.ke`

**3. Updated `app.config.ts`**
- Added `apiBaseInterceptor` to HTTP interceptor chain
- Order: `apiBaseInterceptor` → `authInterceptor` → `errorInterceptor`

---

## 📋 Deployment URLs

| Service | URL | Purpose |
|---------|-----|---------|
| **Frontend** | https://soarcorp.co.ke | User-facing Angular application |
| **Backend API** | https://api.soarcorp.co.ke | Laravel REST API |
| **Horizon Dashboard** | https://api.soarcorp.co.ke/horizon | Queue monitoring (requires auth) |

---

## 🚀 Deployment Steps

### Backend Deployment

1. **SSH into server:**
   ```bash
   ssh mweelacr@das107
   cd ~/Demand-Lead-tool/Backend
   ```

2. **Copy and configure environment:**
   ```bash
   cp .env.production .env
   nano .env  # Edit: APP_KEY, DB credentials, mail settings
   ```

3. **Install dependencies:**
   ```bash
   composer install --no-dev --optimize-autoloader
   ```
   This will generate a new `composer.lock` compatible with PHP 8.3.33

4. **Setup application:**
   ```bash
   php artisan key:generate
   php artisan migrate --force
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

5. **Set permissions:**
   ```bash
   chmod -R 755 storage bootstrap/cache
   chown -R www-data:www-data storage bootstrap/cache
   ```

6. **Configure web server** (Apache or Nginx) to point to:
   - Domain: `api.soarcorp.co.ke`
   - Document root: `/home/mweelacr/Demand-Lead-tool/Backend/public`

7. **Setup queue worker** with Supervisor

8. **Add cron job** for Laravel scheduler

### Frontend Deployment

1. **Build production bundle:**
   ```bash
   cd ~/Desktop/projects/Demand-Lead-tool/Frontend
   npm install
   npm run build:prod
   ```

2. **Upload to server:**
   ```bash
   rsync -avz --delete dist/ mweelacr@das107:~/public_html/
   ```

3. **Configure web server** to point to:
   - Domain: `soarcorp.co.ke`
   - Document root: `/home/mweelacr/public_html`
   - Enable SPA routing (all routes → index.html)

4. **Install SSL certificates** for both domains

---

## 🔒 Security Checklist

- [ ] SSL certificates installed (https://)
- [ ] APP_KEY generated and secure
- [ ] Database credentials strong
- [ ] CORS properly configured
- [ ] Sanctum stateful domains set
- [ ] Horizon dashboard password protected
- [ ] `.env` file permissions set to 600
- [ ] Debug mode disabled (`APP_DEBUG=false`)
- [ ] Storage directory not publicly accessible

---

## 🧪 Testing Checklist

After deployment, verify:

### Frontend
- [ ] https://soarcorp.co.ke loads
- [ ] No console errors
- [ ] Routing works (refresh on any page)
- [ ] Assets load properly

### Backend
- [ ] https://api.soarcorp.co.ke/api/v1/... returns JSON
- [ ] Registration works: POST `/api/v1/auth/register`
- [ ] Login works: POST `/api/v1/auth/login`
- [ ] CORS headers present in API responses

### Integration
- [ ] Frontend can call backend API
- [ ] Authentication flow works end-to-end
- [ ] No CORS errors in browser console
- [ ] Queue jobs processing (check Horizon)

---

## 📁 Files Changed

### Backend
- ✅ `Backend/.env.production` (created)
- ✅ `Backend/composer.json` (added platform constraint)
- ✅ `Backend/composer.lock` (deleted - will regenerate on server)
- ✅ `.kiro/steering/architecture.md` (updated to Laravel 13.x)

### Frontend
- ✅ `Frontend/src/environments/environment.production.ts` (updated URLs)
- ✅ `Frontend/src/app/core/interceptors/api-base.interceptor.ts` (created)
- ✅ `Frontend/src/app/app.config.ts` (added interceptor)

### Documentation
- ✅ `PRODUCTION_DEPLOYMENT.md` (detailed deployment guide)
- ✅ `PRODUCTION_SETUP_SUMMARY.md` (this file)

---

## 🔧 How It Works

### Development Environment
```
Browser → http://localhost:4200/api/v1/... 
       → Angular Dev Server (proxy.conf.json)
       → http://localhost:8000/api/v1/...
       → Laravel Backend
```

### Production Environment
```
Browser → https://soarcorp.co.ke 
       → Angular App (static files)
       → API call: https://api.soarcorp.co.ke/api/v1/...
       → Laravel Backend
```

**API Base Interceptor Logic:**
```typescript
Development: apiBase = ''
  /api/v1/auth/login → /api/v1/auth/login (proxied to localhost:8000)

Production: apiBase = 'https://api.soarcorp.co.ke'
  /api/v1/auth/login → https://api.soarcorp.co.ke/api/v1/auth/login
```

---

## 🐛 Common Issues & Solutions

### Issue: CORS Error
**Symptom:** Browser console shows "CORS policy blocked"

**Solution:**
```bash
# Check backend .env
CORS_ALLOWED_ORIGINS=https://soarcorp.co.ke
SANCTUM_STATEFUL_DOMAINS=soarcorp.co.ke

# Clear cache
php artisan config:clear
php artisan config:cache
```

### Issue: 404 on Frontend Routes
**Symptom:** Refreshing on `/dashboard` shows 404

**Solution:** Configure web server to route all requests to `index.html`

Apache `.htaccess`:
```apache
RewriteEngine On
RewriteBase /
RewriteRule ^index\.html$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.html [L]
```

### Issue: Composer Symfony Version Error
**Symptom:** "symfony/... requires php >=8.4.1"

**Solution:** The platform constraint in `composer.json` fixes this:
```json
"config": {
  "platform": {
    "php": "8.3.33"
  }
}
```

Delete `composer.lock` and run `composer install` on the server.

---

## 📞 Support Resources

- Laravel Documentation: https://laravel.com/docs/13.x
- Angular Documentation: https://angular.dev
- Deployment Guide: `PRODUCTION_DEPLOYMENT.md`

---

**Setup Date:** August 31, 2026  
**Status:** Ready for deployment  
**Next Step:** Follow Backend Deployment steps in `PRODUCTION_DEPLOYMENT.md`
