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
#    1. Clones the latest custom files from GitHub (shallow clone)
#    2. Copies them over the existing Laravel project
#    3. Removes explicitly listed stale files (safe — never touches Laravel core)
#    4. Runs any new database migrations
#    5. Rebuilds production caches
#
#  IMPORTANT: This repo is an OVERLAY — it only contains custom files, not all
#  of Laravel. The server has a full Laravel + Breeze install. The stale file
#  cleanup uses an explicit list (not automatic detection) to avoid accidentally
#  deleting Laravel core files like AppServiceProvider, Breeze controllers, etc.
# ============================================================================

set -e

echo "Updating ARES Education site..."

# Clone latest from GitHub into a temp directory
echo "  Fetching latest from GitHub..."
rm -rf /tmp/LPC
git clone --depth 1 --quiet -c pack.threads=1 https://github.com/james-beep-boop/LessonPlanShare.git /tmp/LPC

# Overlay custom files (repo has files at the root, not in a subfolder).
# Required directories: errors are printed but the script continues (|| true keeps
# set -e from aborting on DreamHost filesystem quirks — but we no longer suppress
# error output so failures are visible in the terminal).
echo "  Copying updated files..."
cp -a /tmp/LPC/app/.       ~/LessonPlanShare/app/       || true
cp -a /tmp/LPC/database/.  ~/LessonPlanShare/database/  || true
cp -a /tmp/LPC/resources/. ~/LessonPlanShare/resources/ || true
cp -a /tmp/LPC/routes/.    ~/LessonPlanShare/routes/    || true
cp -a /tmp/LPC/public/.    ~/LessonPlanShare/public/    || true
cp -a /tmp/LPC/storage/.   ~/LessonPlanShare/storage/   || true
# Optional files — suppress missing-file noise
cp -a /tmp/LPC/tests/. ~/LessonPlanShare/tests/ 2>/dev/null || true
cp /tmp/LPC/.env.example ~/LessonPlanShare/.env.example  2>/dev/null || true
cp /tmp/LPC/DEPLOYMENT.md ~/LessonPlanShare/             2>/dev/null || true
cp /tmp/LPC/TECHNICAL_DESIGN.md ~/LessonPlanShare/       2>/dev/null || true
cp /tmp/LPC/UPDATE_SITE.sh ~/LessonPlanShare/            2>/dev/null || true
cp /tmp/LPC/VERSION ~/LessonPlanShare/                   2>/dev/null || true

# ── Stale file cleanup (explicit list) ──
# When a file is removed from the repo, add it here so it gets cleaned up
# on the server too. This is safer than automatic detection because our repo
# is an overlay that doesn't contain Laravel's core files.
#
# To add a file: append a line like:
#   remove_if_exists "path/relative/to/LessonPlanShare"
echo "  Cleaning up removed files..."
remove_if_exists() {
    local f=~/LessonPlanShare/"$1"
    if [ -f "$f" ]; then
        echo "    Removed: $1"
        rm -f "$f"
    fi
}

# Files removed 2026-02-23:
remove_if_exists "DEPLOY_DREAMHOST.sh"
remove_if_exists "Claude_Lesson_Deployment_Findings_2_22.docx"
remove_if_exists "public/images/ARES_Logo_300.jpg"

# Write version string to storage so the page footer can display it.
# Source of truth: the VERSION file in the repo (e.g. "0.61").
# Falls back to git tag, then git hash, then "dev" if nothing else is available.
echo "  Writing version info..."
GIT_VERSION=$(cat /tmp/LPC/VERSION 2>/dev/null | tr -d '[:space:]')
if [ -z "$GIT_VERSION" ]; then
    GIT_VERSION=$(git -C /tmp/LPC describe --tags --abbrev=0 2>/dev/null \
        || git -C /tmp/LPC rev-parse --short HEAD 2>/dev/null \
        || echo "dev")
fi
echo "$GIT_VERSION" > ~/LessonPlanShare/storage/app/version.txt

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
