#!/bin/bash

# Production Session Fix Script for DemandLead
# Run this from the Backend directory: bash fix-production-sessions.sh

set -e

echo "========================================="
echo "DemandLead Production Session Fix"
echo "========================================="
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo -e "${RED}ERROR: artisan file not found!${NC}"
    echo "Please run this script from the Backend directory:"
    echo "  cd ~/soarcorp/Demand-Lead-tool/Backend"
    echo "  bash fix-production-sessions.sh"
    exit 1
fi

echo -e "${GREEN}✓ Running from Backend directory${NC}"
echo ""

echo "Step 1: Checking current configuration..."
echo ""

# Check .env file
if [ ! -f .env ]; then
    echo -e "${RED}ERROR: .env file not found!${NC}"
    exit 1
fi

# Backup .env
echo "Creating .env backup..."
cp .env .env.backup.$(date +%Y%m%d_%H%M%S)
echo -e "${GREEN}✓ Backup created${NC}"
echo ""

echo "Step 2: Updating session configuration..."

# Update SESSION_DRIVER
if grep -q "^SESSION_DRIVER=" .env; then
    sed -i 's/^SESSION_DRIVER=.*/SESSION_DRIVER=database/' .env
    echo -e "${GREEN}✓ SESSION_DRIVER set to database${NC}"
else
    echo "SESSION_DRIVER=database" >> .env
    echo -e "${GREEN}✓ SESSION_DRIVER added${NC}"
fi

# Update SESSION_SECURE_COOKIE (critical for HTTPS)
if grep -q "^SESSION_SECURE_COOKIE=" .env; then
    sed -i 's/^SESSION_SECURE_COOKIE=.*/SESSION_SECURE_COOKIE=true/' .env
    echo -e "${GREEN}✓ SESSION_SECURE_COOKIE set to true${NC}"
else
    echo "SESSION_SECURE_COOKIE=true" >> .env
    echo -e "${GREEN}✓ SESSION_SECURE_COOKIE added${NC}"
fi

# Update SESSION_ENCRYPT
if grep -q "^SESSION_ENCRYPT=" .env; then
    sed -i 's/^SESSION_ENCRYPT=.*/SESSION_ENCRYPT=false/' .env
    echo -e "${GREEN}✓ SESSION_ENCRYPT set to false${NC}"
else
    echo "SESSION_ENCRYPT=false" >> .env
    echo -e "${GREEN}✓ SESSION_ENCRYPT added${NC}"
fi

# Set SESSION_SAME_SITE
if grep -q "^SESSION_SAME_SITE=" .env; then
    sed -i 's/^SESSION_SAME_SITE=.*/SESSION_SAME_SITE=lax/' .env
    echo -e "${GREEN}✓ SESSION_SAME_SITE set to lax${NC}"
else
    echo "SESSION_SAME_SITE=lax" >> .env
    echo -e "${GREEN}✓ SESSION_SAME_SITE added${NC}"
fi

# Clear SESSION_DOMAIN if set
if grep -q "^SESSION_DOMAIN=" .env; then
    sed -i 's/^SESSION_DOMAIN=.*/SESSION_DOMAIN=/' .env
    echo -e "${GREEN}✓ SESSION_DOMAIN cleared${NC}"
fi

echo ""
echo "Step 3: Running migrations..."
php artisan migrate --force
echo -e "${GREEN}✓ Migrations complete${NC}"
echo ""

echo "Step 4: Clearing all caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
echo -e "${GREEN}✓ Caches cleared${NC}"
echo ""

echo "Step 5: Regenerating config cache..."
php artisan config:cache
echo -e "${GREEN}✓ Config cached${NC}"
echo ""

echo "Step 6: Fixing permissions..."
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
echo -e "${GREEN}✓ Permissions fixed${NC}"
echo ""

echo "Step 7: Clearing old sessions..."
if php artisan tinker --execute="DB::table('sessions')->truncate(); echo 'Sessions cleared';" 2>/dev/null; then
    echo -e "${GREEN}✓ Old sessions cleared${NC}"
else
    echo -e "${YELLOW}⚠ Could not clear sessions table (may not exist yet)${NC}"
fi
echo ""

echo "Step 8: Restarting services..."
if systemctl restart php8.3-fpm 2>/dev/null; then
    echo -e "${GREEN}✓ PHP-FPM restarted${NC}"
else
    echo -e "${YELLOW}⚠ Could not restart PHP-FPM (check service name)${NC}"
fi

if systemctl restart nginx 2>/dev/null; then
    echo -e "${GREEN}✓ Nginx restarted${NC}"
else
    echo -e "${YELLOW}⚠ Could not restart Nginx${NC}"
fi
echo ""

echo "========================================="
echo -e "${GREEN}Fix Complete!${NC}"
echo "========================================="
echo ""
echo "Next steps:"
echo "1. Test login at: https://soarcorp.co.ke/login"
echo "2. Clear browser cookies completely"
echo "3. Try logging in again"
echo ""
echo "Debug endpoints (accessible only in local/staging):"
echo "  - /debug/session-config"
echo "  - /debug/db-test"
echo "  - /debug/session-write"
echo ""
echo "If still not working, check:"
echo "  - tail -f storage/logs/laravel.log"
echo "  - tail -f /var/log/nginx/error.log"
echo ""
