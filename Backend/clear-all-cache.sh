#!/bin/bash

echo "================================================"
echo "Clearing ALL Laravel Caches - Production"
echo "================================================"
echo ""

cd ~/soarcorp/Demand-Lead-tool/Backend

# 1. Clear all artisan caches
echo "1. Clearing Artisan caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear
php artisan optimize:clear

echo "✅ Artisan caches cleared"
echo ""

# 2. Delete cached files manually
echo "2. Deleting cached files manually..."
rm -rf bootstrap/cache/*.php
rm -rf storage/framework/cache/data/*
rm -rf storage/framework/views/*
rm -rf storage/framework/sessions/*

echo "✅ Cached files deleted"
echo ""

# 3. Clear OPcache (PHP bytecode cache)
echo "3. Clearing OPcache..."
php -r "if(function_exists('opcache_reset')) { opcache_reset(); echo 'OPcache cleared'; } else { echo 'OPcache not available'; }"
echo ""
echo ""

# 4. Restart PHP-FPM
echo "4. Restarting PHP-FPM..."
sudo systemctl restart php-fpm 2>/dev/null && echo "✅ PHP-FPM restarted" || echo "⚠️  Could not restart PHP-FPM (may need sudo)"

echo ""
echo "================================================"
echo "All caches cleared!"
echo "================================================"
echo ""
echo "Next steps:"
echo "1. Update .env with correct values"
echo "2. Run: php artisan config:cache"
echo "3. Test site: https://soarcorp.co.ke/login"
