#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

echo "==> Installing Composer dependencies..."
composer install --no-interaction --prefer-dist --optimize-autoloader

if [ ! -f .env ]; then
  echo "==> Creating .env from .env.example..."
  cp .env.example .env
  php artisan key:generate
fi

echo "==> Running migrations..."
php artisan migrate --force

echo "==> Seeding SaaS foundation..."
php artisan db:seed --class=SaasFoundationSeeder --force
php artisan db:seed --class=IntegrationsSeeder --force

echo "==> Backfilling legacy data for multi-tenant..."
php artisan saas:backfill-tenant --force 2>/dev/null || php artisan saas:backfill-tenant || true
php artisan saas:assign-roles-from-utype --force 2>/dev/null || php artisan saas:assign-roles-from-utype || true
php artisan sales:seed-pipeline-stages --force 2>/dev/null || php artisan sales:seed-pipeline-stages || true
php artisan sales:backfill-pipeline-stages --force 2>/dev/null || php artisan sales:backfill-pipeline-stages || true
php artisan operations:sync-deposits --force 2>/dev/null || php artisan operations:sync-deposits || true

echo ""
echo "SaaS API is ready."
echo "  php artisan serve   # http://127.0.0.1:8000"
echo "  Health: GET /up"
echo "  Register tenant: POST /api/v1/tenants/register"
echo "  Bootstrap UI: GET /api/v1/app/bootstrap?tenant=default"
echo ""
echo "Point adroitsolscrmfront at APP_URL (see SANCTUM_STATEFUL_DOMAINS in .env)."
