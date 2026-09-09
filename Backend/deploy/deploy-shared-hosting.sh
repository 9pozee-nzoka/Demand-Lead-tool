#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────
# SoarCorp — Shared Hosting Deployment (cPanel / Namecheap)
#
# Run via SSH:
#   ssh your-user@soarcorp.co.ke
#   bash /home/mweelacr/demand-lead/deploy/deploy-shared-hosting.sh
#
# First-time setup:
#   git clone <repo> /home/mweelacr/demand-lead
#   cd /home/mweelacr/demand-lead
#   cp .env.example .env
#   nano .env   # fill in DB credentials
#   php artisan key:generate
# ─────────────────────────────────────────────────────────────────────────────
set -euo pipefail

APP_DIR="/home/mweelacr/demand-lead"
PHP=$(which php8.3 2>/dev/null || which php 2>/dev/null)
ARTISAN="${PHP} ${APP_DIR}/artisan"

echo "🚀  SoarCorp deployment — $(date)"

# ── Pull code ─────────────────────────────────────────────────────────────────
echo "▶  Pulling latest code…"
git -C "${APP_DIR}" pull origin main

# ── PHP dependencies ──────────────────────────────────────────────────────────
echo "▶  Installing PHP dependencies (no dev)…"
composer install \
  --working-dir="${APP_DIR}" \
  --no-dev \
  --no-interaction \
  --prefer-dist \
  --optimize-autoloader

# ── Frontend assets (Tailwind CSS) ────────────────────────────────────────────
echo "▶  Installing npm dependencies…"
npm ci --prefix "${APP_DIR}" --silent

echo "▶  Building Vite assets (Tailwind CSS)…"
npm run build --prefix "${APP_DIR}"

# ── Maintenance mode ──────────────────────────────────────────────────────────
echo "▶  Enabling maintenance mode…"
${ARTISAN} down --retry=10

# ── Database ──────────────────────────────────────────────────────────────────
echo "▶  Running migrations…"
${ARTISAN} migrate --force

echo "▶  Seeding super-admin…"
${ARTISAN} db:seed --class=SuperAdminSeeder --force

# ── Caches ────────────────────────────────────────────────────────────────────
echo "▶  Rebuilding caches…"
${ARTISAN} config:cache
${ARTISAN} route:cache
${ARTISAN} view:cache
${ARTISAN} event:cache

# ── Storage ───────────────────────────────────────────────────────────────────
echo "▶  Fixing storage permissions…"
chmod -R 755 "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache"

# ── Queue ─────────────────────────────────────────────────────────────────────
# Signal any running queue workers to restart after current job
${ARTISAN} queue:restart

# ── Back online ───────────────────────────────────────────────────────────────
echo "▶  Taking app out of maintenance mode…"
${ARTISAN} up

echo ""
echo "✅  Deployment complete!"
echo "   API:      https://api.soarcorp.co.ke"
echo "   App:      https://app.soarcorp.co.ke"
echo "   Admin:    https://app.soarcorp.co.ke/admin"
