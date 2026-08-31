#!/bin/bash

# Configuration Verification Script
# Run this before deployment to ensure everything is set up correctly

echo "=================================="
echo "Production Configuration Verification"
echo "=================================="
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check Backend
echo "📦 Backend Configuration"
echo "------------------------"

if [ -f "Backend/.env.production" ]; then
    echo -e "${GREEN}✓${NC} .env.production exists"
    
    # Check key variables
    if grep -q "APP_URL=https://api.soarcorp.co.ke" Backend/.env.production; then
        echo -e "${GREEN}✓${NC} APP_URL configured correctly"
    else
        echo -e "${RED}✗${NC} APP_URL not configured correctly"
    fi
    
    if grep -q "CORS_ALLOWED_ORIGINS=https://soarcorp.co.ke" Backend/.env.production; then
        echo -e "${GREEN}✓${NC} CORS configured correctly"
    else
        echo -e "${RED}✗${NC} CORS not configured correctly"
    fi
    
    if grep -q "SANCTUM_STATEFUL_DOMAINS=soarcorp.co.ke" Backend/.env.production; then
        echo -e "${GREEN}✓${NC} Sanctum domains configured correctly"
    else
        echo -e "${RED}✗${NC} Sanctum domains not configured correctly"
    fi
else
    echo -e "${RED}✗${NC} .env.production not found"
fi

if grep -q '"platform"' Backend/composer.json; then
    echo -e "${GREEN}✓${NC} Platform constraint set in composer.json"
else
    echo -e "${RED}✗${NC} Platform constraint missing in composer.json"
fi

echo ""

# Check Frontend
echo "🎨 Frontend Configuration"
echo "------------------------"

if [ -f "Frontend/src/environments/environment.production.ts" ]; then
    echo -e "${GREEN}✓${NC} environment.production.ts exists"
    
    if grep -q "apiBase:.*'https://api.soarcorp.co.ke'" Frontend/src/environments/environment.production.ts; then
        echo -e "${GREEN}✓${NC} API base URL configured correctly"
    else
        echo -e "${RED}✗${NC} API base URL not configured correctly"
    fi
    
    if grep -q "appUrl:.*'https://soarcorp.co.ke'" Frontend/src/environments/environment.production.ts; then
        echo -e "${GREEN}✓${NC} App URL configured correctly"
    else
        echo -e "${RED}✗${NC} App URL not configured correctly"
    fi
else
    echo -e "${RED}✗${NC} environment.production.ts not found"
fi

if [ -f "Frontend/src/app/core/interceptors/api-base.interceptor.ts" ]; then
    echo -e "${GREEN}✓${NC} API base interceptor exists"
else
    echo -e "${RED}✗${NC} API base interceptor missing"
fi

if grep -q "apiBaseInterceptor" Frontend/src/app/app.config.ts; then
    echo -e "${GREEN}✓${NC} API base interceptor registered in app.config.ts"
else
    echo -e "${RED}✗${NC} API base interceptor not registered"
fi

echo ""

# Check for common issues
echo "⚠️  Pre-Deployment Warnings"
echo "------------------------"

if [ -f "Backend/composer.lock" ]; then
    echo -e "${YELLOW}⚠${NC}  composer.lock exists - should be deleted and regenerated on server with PHP 8.3"
else
    echo -e "${GREEN}✓${NC} composer.lock deleted (will regenerate on server)"
fi

if [ -f "Backend/.env" ] && grep -q "APP_ENV=local" Backend/.env; then
    echo -e "${YELLOW}⚠${NC}  Local .env exists - remember to use .env.production on server"
fi

if [ ! -d "Frontend/node_modules" ]; then
    echo -e "${YELLOW}⚠${NC}  node_modules not found - run 'npm install' before building"
fi

echo ""
echo "=================================="
echo "Verification Complete!"
echo "=================================="
echo ""
echo "Next steps:"
echo "1. Review PRODUCTION_DEPLOYMENT.md"
echo "2. Deploy Backend to das107"
echo "3. Build and deploy Frontend"
echo ""
