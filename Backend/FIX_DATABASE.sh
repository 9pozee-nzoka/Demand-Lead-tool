#!/bin/bash

# ══════════════════════════════════════════════════════════════════════════════
# Production Database Credentials Fix
# ══════════════════════════════════════════════════════════════════════════════

echo "🔧 Fixing Production Database Configuration..."

cd ~/soarcorp/Demand-Lead-tool/Backend

# Step 1: Update .env with correct credentials
echo "📝 Updating .env file..."

# Backup existing .env
cp .env .env.backup.$(date +%Y%m%d_%H%M%S)

# Update database credentials
sed -i 's/^DB_HOST=.*/DB_HOST=localhost/' .env
sed -i 's/^DB_DATABASE=.*/DB_DATABASE=mweelacr_soarcorp/' .env
sed -i 's/^DB_USERNAME=.*/DB_USERNAME=mweelacr_pauljohns730/' .env
sed -i 's/^DB_PASSWORD=.*/DB_PASSWORD=Pozee@52683542/' .env

echo "✅ Database credentials updated"

# Step 2: Clear cached config (CRITICAL!)
echo "🗑️  Clearing cached config..."
php artisan config:clear
rm -rf bootstrap/cache/config.php

echo "✅ Config cache cleared"

# Step 3: Test database connection
echo "🔍 Testing database connection..."
php artisan tinker --execute="
try {
    \$pdo = DB::connection()->getPdo();
    echo '✅ Database: Connected Successfully!' . PHP_EOL;
    echo 'Database Name: ' . DB::connection()->getDatabaseName() . PHP_EOL;
} catch (Exception \$e) {
    echo '❌ Database Error: ' . \$e->getMessage() . PHP_EOL;
    exit(1);
}
"

# Step 4: Run migrations
echo "📦 Running migrations..."
php artisan migrate --force

# Step 5: Clear all caches
echo "🧹 Clearing all caches..."
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Step 6: Rebuild config cache
echo "🔄 Rebuilding config cache..."
php artisan config:cache

# Step 7: Fix permissions
echo "🔒 Fixing permissions..."
chmod -R 775 storage bootstrap/cache
mkdir -p storage/framework/sessions
chmod -R 775 storage/framework/sessions

echo ""
echo "═══════════════════════════════════════════════════════════════════════════"
echo "✅ Database configuration fixed!"
echo "═══════════════════════════════════════════════════════════════════════════"
echo ""
echo "Next step: Restart PHP-FPM"
echo "  sudo systemctl restart php-fpm"
echo ""
echo "Then test: https://soarcorp.co.ke/login"
echo ""
