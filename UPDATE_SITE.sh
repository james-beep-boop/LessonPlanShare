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
# REQUIRED directories: no || true — a failure aborts the deploy (set -e is active).
# If a required copy fails on DreamHost, it's better to stop than to deploy
# partial code silently.
echo "  Copying updated files..."
cp -a /tmp/LPC/app/.       ~/LessonPlanShare/app/
cp -a /tmp/LPC/database/.  ~/LessonPlanShare/database/
cp -a /tmp/LPC/resources/. ~/LessonPlanShare/resources/
cp -a /tmp/LPC/routes/.    ~/LessonPlanShare/routes/
cp -a /tmp/LPC/public/.    ~/LessonPlanShare/public/ 2>/dev/null || true
cp -a /tmp/LPC/storage/.   ~/LessonPlanShare/storage/
# Optional files — not in every repo state; suppress missing-file noise
cp -a /tmp/LPC/tests/. ~/LessonPlanShare/tests/ 2>/dev/null || true
cp /tmp/LPC/.env.example ~/LessonPlanShare/.env.example  2>/dev/null || true
cp /tmp/LPC/DEPLOYMENT.md ~/LessonPlanShare/             2>/dev/null || true
cp /tmp/LPC/TECHNICAL_DESIGN.md ~/LessonPlanShare/       2>/dev/null || true
cp /tmp/LPC/UPDATE_SITE.sh ~/LessonPlanShare/            2>/dev/null || true
cp -a /tmp/LPC/scripts/. ~/LessonPlanShare/scripts/     2>/dev/null || true

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

# Files removed 2026-02-27:
remove_if_exists "resources/views/stats.blade.php"

# Files removed 2026-03-01:
remove_if_exists "resources/views/components/vote-buttons.blade.php"
remove_if_exists "resources/views/lesson-plans/my-plans.blade.php"

# Write short commit hash to version.txt (displayed in the page footer).
# Cache is cleared below so the footer picks it up on the next page load.
echo "  Writing version info..."
git -C /tmp/LPC rev-parse --short HEAD > ~/LessonPlanShare/storage/app/version.txt

# Install post-merge hook so future git pulls auto-update version.txt.
# Guarded: DreamHost server has no .git directory (overlay repo, not a git clone).
if [ -d ~/LessonPlanShare/.git/hooks ]; then
    echo "  Installing post-merge hook..."
    install -m 755 ~/LessonPlanShare/scripts/post-merge-hook.sh \
        ~/LessonPlanShare/.git/hooks/post-merge
fi

# Clean up temp
rm -rf /tmp/LPC

# ── Composer packages ──
# This overlay repo does NOT track composer.json/composer.lock, so Composer
# is NOT run automatically. If you have added new PHP packages to composer.json
# (e.g. a new Laravel package), run the following MANUALLY before this script:
#
#   cd ~/LessonPlanShare && composer install --no-dev --optimize-autoloader
#
# The current project has no non-framework dependencies, so this is only needed
# when you deliberately add a package. Framework packages (Laravel, Breeze) are
# already installed in the full Laravel install on the server.

# Run any new migrations
echo "  Running migrations..."
cd ~/LessonPlanShare
php artisan migrate --force 2>&1

# Rebuild caches
echo "  Rebuilding caches..."
php artisan optimize:clear --quiet
php artisan cache:forget app_version --quiet 2>/dev/null || true
php artisan config:cache --quiet
php artisan route:cache --quiet
php artisan view:cache --quiet

echo ""
echo "✓ Site updated! Visit https://www.sheql.com to verify."
echo ""
