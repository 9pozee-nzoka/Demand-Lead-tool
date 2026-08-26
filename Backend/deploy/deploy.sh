#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────
# SoarCorp Demand Intelligence — Production Deployment Script
# Run from the server: bash deploy/deploy.sh
#
# Prerequisites on the server:
#   - PHP 8.3 + php8.3-fpm
#   - Composer
#   - Node 20 + npm
#   - MySQL 8
#   - Redis
#   - Nginx
#   - Supervisor
# ─────────────────────────────────────────────────────────────────────────────
set -euo pipefail

BACKEND_DIR="/var/www/demand-lead"
FRONTEND_DIR="/var/www/demand-lead-frontend"
PHP="php8.3"
ARTISAN="${PHP} ${BACKEND_DIR}/artisan"

echo ""
echo "🚀  SoarCorp Deployment — $(date)"
echo "─────────────────────────────────────────────────"

# ── Backend ───────────────────────────────────────────────────────────────────

echo "▶  Pulling latest backend code…"
git -C "${BACKEND_DIR}" pull origin main

echo "▶  Installing PHP dependencies…"
composer install \
  --working-dir="${BACKEND_DIR}" \
  --no-dev --no-interaction --prefer-dist --optimize-autoloader

echo "▶  Putting app in maintenance mode…"
${ARTISAN} down --render="maintenance" --retry=10 --refresh=15

echo "▶  Running migrations…"
${ARTISAN} migrate --force

echo "▶  Seeding super-admin…"
${ARTISAN} db:seed --class=SuperAdminSeeder --force

echo "▶  Clearing & rebuilding caches…"
${ARTISAN} config:cache
${ARTISAN} route:cache
${ARTISAN} view:cache
${ARTISAN} event:cache

echo "▶  Restarting queue workers…"
${ARTISAN} queue:restart

echo "▶  Restarting Horizon…"
${ARTISAN} horizon:terminate

# ── Frontend ──────────────────────────────────────────────────────────────────

echo "▶  Pulling latest frontend code…"
git -C "${FRONTEND_DIR}" pull origin main

echo "▶  Installing frontend dependencies…"
npm ci --prefix "${FRONTEND_DIR}" --silent

echo "▶  Building Angular (production)…"
npm run build --prefix "${FRONTEND_DIR}"
# Output: dist/Frontend/browser/

# ── Bring back online ─────────────────────────────────────────────────────────

echo "▶  Bringing app back online…"
${ARTISAN} up

echo ""
echo "✅  Deployment complete!"
echo "   Backend:  https://api.soarcorp.co.ke"
echo "   Frontend: https://app.soarcorp.co.ke"
echo "   Admin:    https://app.soarcorp.co.ke/admin"
echo ""
