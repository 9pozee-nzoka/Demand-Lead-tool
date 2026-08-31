# Quick Reference Card

## 🌐 Production URLs

| Service | URL |
|---------|-----|
| Frontend | https://soarcorp.co.ke |
| Backend API | https://api.soarcorp.co.ke |
| Horizon | https://api.soarcorp.co.ke/horizon |

---

## 🚀 Deploy Backend (5 minutes)

```bash
# 1. Upload to server
rsync -avz --exclude 'node_modules' --exclude 'vendor' Backend/ mweelacr@das107:~/Demand-Lead-tool/Backend/

# 2. SSH and configure
ssh mweelacr@das107
cd ~/Demand-Lead-tool/Backend
cp .env.production .env
nano .env  # Set APP_KEY, DB credentials, passwords

# 3. Install and setup
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
php artisan config:cache
php artisan route:cache

# 4. Set permissions
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

---

## 🎨 Deploy Frontend (3 minutes)

```bash
# 1. Build locally
cd Frontend
npm install
npm run build:prod

# 2. Upload to server
rsync -avz --delete dist/ mweelacr@das107:~/public_html/
```

---

## 🔧 Essential Commands

### Backend
```bash
# View logs
tail -f storage/logs/laravel.log

# Clear cache
php artisan config:clear && php artisan cache:clear

# Check queue status
php artisan horizon:status

# Run migrations
php artisan migrate --force
```

### Server
```bash
# Restart queue worker
sudo supervisorctl restart demandlead-worker:*

# Check PHP version
php -v

# Test database connection
mysql -u demand_lead_user -p demand_lead
```

---

## ✅ Post-Deployment Test

1. **Frontend:** Visit https://soarcorp.co.ke
2. **Registration:** Create test account
3. **Login:** Sign in with test account
4. **API:** Check browser Network tab for API calls
5. **Logs:** Monitor `tail -f storage/logs/laravel.log`

---

## 🐛 Quick Fixes

### 500 Error
```bash
php artisan config:clear
chmod -R 755 storage bootstrap/cache
```

### CORS Error
Check `.env`:
```env
CORS_ALLOWED_ORIGINS=https://soarcorp.co.ke
SANCTUM_STATEFUL_DOMAINS=soarcorp.co.ke
```

### Queue Not Processing
```bash
sudo supervisorctl restart demandlead-worker:*
```

---

## 📞 Important Files

- Backend config: `Backend/.env.production`
- Frontend config: `Frontend/src/environments/environment.production.ts`
- Full guide: `PRODUCTION_DEPLOYMENT.md`
- Setup summary: `PRODUCTION_SETUP_SUMMARY.md`

---

**Server:** das107  
**User:** mweelacr  
**PHP:** 8.3.33  
**Database:** demand_lead
