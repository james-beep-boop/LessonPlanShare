#!/bin/bash
# ============================================================================
#  ARES Education — Quick Update Script
# ============================================================================
#
#  Run this on DreamHost after you push changes to GitHub:
#
#    ssh david_sheql@sheql.com
#    bash ~/LessonPlanShare/UPDATE_SITE.sh
#
#  What it does:
#    1. Clones the latest custom files from GitHub
#    2. Copies them over the existing Laravel project
#    3. Runs any new database migrations
#    4. Rebuilds production caches
# ============================================================================

set -e

echo "Updating ARES Education site..."

# Clone latest from GitHub into a temp directory
echo "  Fetching latest from GitHub..."
rm -rf /tmp/LPC
git clone --quiet https://github.com/james-beep-boop/LessonPlanShare.git /tmp/LPC

# Overlay custom files
echo "  Copying updated files..."
cp -r /tmp/LPC/LessonPlanShare/app/* ~/LessonPlanShare/app/ 2>/dev/null || true
cp -r /tmp/LPC/LessonPlanShare/database/* ~/LessonPlanShare/database/ 2>/dev/null || true
cp -r /tmp/LPC/LessonPlanShare/resources/* ~/LessonPlanShare/resources/ 2>/dev/null || true
cp -r /tmp/LPC/LessonPlanShare/routes/* ~/LessonPlanShare/routes/ 2>/dev/null || true
cp -r /tmp/LPC/LessonPlanShare/public/* ~/LessonPlanShare/public/ 2>/dev/null || true
cp -r /tmp/LPC/LessonPlanShare/storage/* ~/LessonPlanShare/storage/ 2>/dev/null || true
cp /tmp/LPC/LessonPlanShare/.env.example ~/LessonPlanShare/.env.example 2>/dev/null || true
cp /tmp/LPC/LessonPlanShare/DEPLOYMENT.md ~/LessonPlanShare/ 2>/dev/null || true
cp /tmp/LPC/LessonPlanShare/TECHNICAL_DESIGN.md ~/LessonPlanShare/ 2>/dev/null || true
cp /tmp/LPC/LessonPlanShare/UPDATE_SITE.sh ~/LessonPlanShare/ 2>/dev/null || true

# Clean up temp
rm -rf /tmp/LPC

# Run any new migrations
echo "  Running migrations..."
cd ~/LessonPlanShare
php artisan migrate --force 2>&1

# Rebuild caches
echo "  Rebuilding caches..."
php artisan optimize:clear --quiet
php artisan config:cache --quiet
php artisan route:cache --quiet
php artisan view:cache --quiet

echo ""
echo "✓ Site updated! Visit https://www.sheql.com to verify."
echo ""
