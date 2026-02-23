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
#    3. Removes stale custom files that no longer exist in the repo
#    4. Runs any new database migrations
#    5. Rebuilds production caches
# ============================================================================

set -e

echo "Updating ARES Education site..."

# Clone latest from GitHub into a temp directory
echo "  Fetching latest from GitHub..."
rm -rf /tmp/LPC
git clone --depth 1 --quiet https://github.com/james-beep-boop/LessonPlanShare.git /tmp/LPC

# Overlay custom files (repo has files at the root, not in a subfolder)
echo "  Copying updated files..."
cp -r /tmp/LPC/app/* ~/LessonPlanShare/app/ 2>/dev/null || true
cp -r /tmp/LPC/database/* ~/LessonPlanShare/database/ 2>/dev/null || true
cp -r /tmp/LPC/resources/* ~/LessonPlanShare/resources/ 2>/dev/null || true
cp -r /tmp/LPC/routes/* ~/LessonPlanShare/routes/ 2>/dev/null || true
cp -r /tmp/LPC/public/* ~/LessonPlanShare/public/ 2>/dev/null || true
cp -r /tmp/LPC/storage/* ~/LessonPlanShare/storage/ 2>/dev/null || true
cp /tmp/LPC/.env.example ~/LessonPlanShare/.env.example 2>/dev/null || true
cp /tmp/LPC/DEPLOYMENT.md ~/LessonPlanShare/ 2>/dev/null || true
cp /tmp/LPC/TECHNICAL_DESIGN.md ~/LessonPlanShare/ 2>/dev/null || true
cp /tmp/LPC/UPDATE_SITE.sh ~/LessonPlanShare/ 2>/dev/null || true

# ── Stale file cleanup ──
# Remove custom files from the server that no longer exist in the repo.
# This prevents deleted files from lingering on the production server.
# Only checks directories we manage (app/, resources/, routes/, public/, database/).
# NEVER touches .env, vendor/, node_modules/, storage/app/, or bootstrap/.
echo "  Checking for stale files..."
STALE_COUNT=0

for dir in app resources routes database; do
    if [ -d "/tmp/LPC/$dir" ] && [ -d ~/LessonPlanShare/$dir ]; then
        # Find files in the server's directory
        cd ~/LessonPlanShare
        find "$dir" -type f 2>/dev/null | while read -r file; do
            if [ ! -f "/tmp/LPC/$file" ]; then
                echo "    Removing stale: $file"
                rm -f ~/LessonPlanShare/"$file"
                STALE_COUNT=$((STALE_COUNT + 1))
            fi
        done
    fi
done

# Clean up stale files in public/ (but protect storage symlink and .htaccess)
if [ -d "/tmp/LPC/public" ] && [ -d ~/LessonPlanShare/public ]; then
    cd ~/LessonPlanShare
    find public -type f ! -name '.htaccess' 2>/dev/null | while read -r file; do
        if [ ! -f "/tmp/LPC/$file" ]; then
            # Don't remove the storage symlink target or index.php (from Laravel core)
            case "$file" in
                public/storage|public/storage/*|public/index.php|public/robots.txt|public/favicon.ico)
                    ;;
                *)
                    echo "    Removing stale: $file"
                    rm -f ~/LessonPlanShare/"$file"
                    ;;
            esac
        fi
    done
fi

# Remove empty directories left behind (but not the directories themselves at top level)
find ~/LessonPlanShare/app ~/LessonPlanShare/resources ~/LessonPlanShare/public \
     -type d -empty -delete 2>/dev/null || true

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
