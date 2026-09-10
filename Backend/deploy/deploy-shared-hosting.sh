#!/bin/bash

##############################################################################
# SoarCorp Demand Intelligence — Shared Hosting Deployment Script
# 
# Purpose: Deploy updates to production shared hosting environment
# Usage: bash deploy/deploy-shared-hosting.sh
##############################################################################

set -e  # Exit on any error

echo "🚀 SoarCorp Deployment Script"
echo "========================================"
echo ""

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Step 1: Enable maintenance mode
echo -e "${YELLOW}Step 1: Enabling maintenance mode...${NC}"
php artisan down --render="errors::503" || true
echo -e "${GREEN}✓ Maintenance mode enabled${NC}"
echo ""

# Step 2: Pull latest code
echo -e "${YELLOW}Step 2: Pulling latest code from Git...${NC}"
git fetch origin
git pull origin main
echo -e "${GREEN}✓ Code updated${NC}"
echo ""

# Step 3: Install/update Composer dependencies
echo -e "${YELLOW}Step 3: Installing Composer dependencies...${NC}"
composer install --no-dev --optimize-autoloader --no-interaction
echo -e "${GREEN}✓ Composer dependencies installed${NC}"
echo ""

# Step 4: Install/update NPM dependencies and build assets
echo -e "${YELLOW}Step 4: Building frontend assets...${NC}"
npm ci --production=false
npm run build
echo -e "${GREEN}✓ Assets built${NC}"
echo ""

# Step 5: Clear old caches
echo -e "${YELLOW}Step 5: Clearing old caches...${NC}"
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
echo -e "${GREEN}✓ Caches cleared${NC}"
echo ""

# Step 6: Run database migrations
echo -e "${YELLOW}Step 6: Running database migrations...${NC}"
php artisan migrate --force --no-interaction
echo -e "${GREEN}✓ Migrations complete${NC}"
echo ""

# Step 7: Optimize for production
echo -e "${YELLOW}Step 7: Optimizing for production...${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
echo -e "${GREEN}✓ Optimization complete${NC}"
echo ""

# Step 8: Set correct permissions
echo -e "${YELLOW}Step 8: Setting file permissions...${NC}"
chmod -R 755 storage bootstrap/cache
echo -e "${GREEN}✓ Permissions set${NC}"
echo ""

# Step 9: Process any pending queue jobs
echo -e "${YELLOW}Step 9: Processing pending queue jobs...${NC}"
php artisan queue:work --stop-when-empty --tries=3 --timeout=60 &
echo -e "${GREEN}✓ Queue worker started${NC}"
echo ""

# Step 10: Disable maintenance mode
echo -e "${YELLOW}Step 10: Disabling maintenance mode...${NC}"
php artisan up
echo -e "${GREEN}✓ Application is now live${NC}"
echo ""

# Step 11: Health check
echo -e "${YELLOW}Step 11: Running health check...${NC}"
php artisan demand:health
echo ""

echo -e "${GREEN}========================================"
echo -e "✅ DEPLOYMENT COMPLETE!"
echo -e "========================================${NC}"
echo ""
echo "Next steps:"
echo "1. Visit https://api.soarcorp.co.ke to verify"
echo "2. Check logs: tail -f storage/logs/laravel.log"
echo "3. Monitor queue: php artisan queue:listen"
echo ""
