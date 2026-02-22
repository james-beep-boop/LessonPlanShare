#!/bin/bash
# ============================================================================
#  ARES Education — DreamHost First-Time Deployment Script
# ============================================================================
#
#  Run this script via SSH on your DreamHost server:
#
#    ssh david_sheql@sheql.com
#    bash DEPLOY_DREAMHOST.sh
#
#  Or copy-paste the commands section by section if you prefer.
#
#  What this script does:
#    1. Installs Composer (if not already present)
#    2. Creates a fresh Laravel 12 project
#    3. Installs Laravel Breeze (authentication)
#    4. Clones your GitHub repo and overlays the custom files
#    5. Converts the project into a git repo linked to your GitHub
#    6. Configures .env for DreamHost
#    7. Runs database migrations
#    8. Sets up the web directory symlink
#    9. Sets permissions and creates storage links
#   10. Caches config for production
#
#  After this, updating is just: git pull && php artisan migrate
# ============================================================================

set -e  # Stop on any error

echo "============================================"
echo "  ARES Education — DreamHost Deployment"
echo "============================================"
echo ""

# ── Step 1: Install Composer ──────────────────────────────────────────────

if ! command -v composer &> /dev/null && [ ! -f ~/composer.phar ]; then
    echo "[1/10] Installing Composer..."
    cd ~
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    php composer-setup.php --quiet
    php -r "unlink('composer-setup.php');"
    echo 'alias composer="php ~/composer.phar"' >> ~/.bashrc
    source ~/.bashrc
    echo "  ✓ Composer installed at ~/composer.phar"
else
    echo "[1/10] Composer already available ✓"
fi

# Make sure composer alias works for the rest of this script
if [ -f ~/composer.phar ]; then
    alias composer="php ~/composer.phar"
    COMPOSER_CMD="php ~/composer.phar"
else
    COMPOSER_CMD="composer"
fi

# ── Step 2: Back up existing site ─────────────────────────────────────────

echo "[2/10] Backing up existing site..."
if [ -d ~/sheql.com ] && [ ! -L ~/sheql.com ]; then
    mv ~/sheql.com ~/sheql.com.old.$(date +%Y%m%d_%H%M%S)
    echo "  ✓ Old site backed up"
elif [ -L ~/sheql.com ]; then
    rm ~/sheql.com
    echo "  ✓ Old symlink removed"
else
    echo "  ✓ No existing site to back up"
fi

# Also move any old LessonPlanShare directory
if [ -d ~/LessonPlanShare ]; then
    mv ~/LessonPlanShare ~/LessonPlanShare.old.$(date +%Y%m%d_%H%M%S)
    echo "  ✓ Old LessonPlanShare backed up"
fi

# ── Step 3: Create fresh Laravel project ──────────────────────────────────

echo "[3/10] Creating fresh Laravel 12 project (this takes a few minutes)..."
cd ~
$COMPOSER_CMD create-project laravel/laravel LessonPlanShare --quiet
echo "  ✓ Laravel project created"

# ── Step 4: Install Breeze ────────────────────────────────────────────────

echo "[4/10] Installing Laravel Breeze..."
cd ~/LessonPlanShare
$COMPOSER_CMD require laravel/breeze --dev --quiet
php artisan breeze:install blade --quiet 2>/dev/null || true
# Breeze may fail on npm — that's OK, we use CDN
echo "  ✓ Breeze installed (npm errors are expected and harmless)"

# ── Step 5: Clone GitHub repo and overlay custom files ────────────────────

echo "[5/10] Cloning custom files from GitHub..."
cd ~
git clone https://github.com/james-beep-boop/LessonPlanShare.git LessonPlanCustom

echo "  Overlaying custom files..."
# Copy all custom files, overwriting Laravel defaults where needed
cp -r ~/LessonPlanCustom/LessonPlanShare/app/* ~/LessonPlanShare/app/ 2>/dev/null || true
cp -r ~/LessonPlanCustom/LessonPlanShare/database/* ~/LessonPlanShare/database/ 2>/dev/null || true
cp -r ~/LessonPlanCustom/LessonPlanShare/resources/* ~/LessonPlanShare/resources/ 2>/dev/null || true
cp -r ~/LessonPlanCustom/LessonPlanShare/routes/* ~/LessonPlanShare/routes/ 2>/dev/null || true
cp -r ~/LessonPlanCustom/LessonPlanShare/public/* ~/LessonPlanShare/public/ 2>/dev/null || true
cp -r ~/LessonPlanCustom/LessonPlanShare/storage/* ~/LessonPlanShare/storage/ 2>/dev/null || true
cp ~/LessonPlanCustom/LessonPlanShare/.env.example ~/LessonPlanShare/.env.example
cp ~/LessonPlanCustom/LessonPlanShare/.gitignore ~/LessonPlanShare/.gitignore
cp ~/LessonPlanCustom/LessonPlanShare/DEPLOYMENT.md ~/LessonPlanShare/ 2>/dev/null || true
cp ~/LessonPlanCustom/LessonPlanShare/TECHNICAL_DESIGN.md ~/LessonPlanShare/ 2>/dev/null || true

echo "  ✓ Custom files overlaid"

# Clean up the temp clone
rm -rf ~/LessonPlanCustom

# ── Step 6: Set up git in the project for future updates ──────────────────

echo "[6/10] Setting up git for future updates..."
cd ~/LessonPlanShare

# Re-initialize git in the full project directory
rm -rf .git
git init
git remote add origin https://github.com/james-beep-boop/LessonPlanShare.git

echo "  ✓ Git initialized (see UPDATING section in the output below)"

# ── Step 7: Configure .env ────────────────────────────────────────────────

echo "[7/10] Configuring .env..."
cd ~/LessonPlanShare
cp .env.example .env

# Generate app key
php artisan key:generate --quiet

echo ""
echo "  ╔═══════════════════════════════════════════════════════╗"
echo "  ║  IMPORTANT: You must edit .env with your passwords!  ║"
echo "  ╚═══════════════════════════════════════════════════════╝"
echo ""
echo "  Run this command after the script finishes:"
echo ""
echo "    nano ~/LessonPlanShare/.env"
echo ""
echo "  Fill in these two values:"
echo "    DB_PASSWORD=your_database_password_here"
echo "    MAIL_PASSWORD=your_email_password_here"
echo ""

# Set production values (everything except passwords)
sed -i 's/APP_NAME=.*/APP_NAME="ARES Education"/' .env
sed -i 's/APP_ENV=.*/APP_ENV=production/' .env
sed -i 's/APP_DEBUG=.*/APP_DEBUG=false/' .env
sed -i 's|APP_URL=.*|APP_URL=https://www.sheql.com|' .env
sed -i 's/DB_HOST=.*/DB_HOST=mysql.sheql.com/' .env
sed -i 's/DB_DATABASE=.*/DB_DATABASE=sheql_lessons/' .env
sed -i 's/DB_USERNAME=.*/DB_USERNAME=sheql_dbuser/' .env
sed -i 's/DB_PASSWORD=.*/DB_PASSWORD=CHANGE_ME/' .env
sed -i 's/SESSION_DRIVER=.*/SESSION_DRIVER=file/' .env
sed -i 's/MAIL_MAILER=.*/MAIL_MAILER=smtp/' .env
sed -i 's/MAIL_HOST=.*/MAIL_HOST=mail.sheql.com/' .env
sed -i 's/MAIL_PORT=.*/MAIL_PORT=587/' .env
sed -i 's/MAIL_USERNAME=.*/MAIL_USERNAME=david@sheql.com/' .env
sed -i 's/MAIL_PASSWORD=.*/MAIL_PASSWORD=CHANGE_ME/' .env
sed -i 's/MAIL_ENCRYPTION=.*/MAIL_ENCRYPTION=tls/' .env
sed -i 's/MAIL_FROM_ADDRESS=.*/MAIL_FROM_ADDRESS="david@sheql.com"/' .env
sed -i 's/MAIL_FROM_NAME=.*/MAIL_FROM_NAME="ARES Education"/' .env

# Add session security settings if not present
grep -q 'SESSION_SECURE_COOKIE' .env || echo 'SESSION_SECURE_COOKIE=true' >> .env
grep -q 'SESSION_SAME_SITE' .env || echo 'SESSION_SAME_SITE=lax' >> .env

echo "  ✓ .env configured (passwords still need manual entry)"

# ── Step 8: Run database migrations ───────────────────────────────────────

echo "[8/10] Running database migrations..."
echo "  (This will fail if DB_PASSWORD is still CHANGE_ME)"
echo "  If it fails, edit .env first, then run: php artisan migrate"
cd ~/LessonPlanShare
php artisan migrate --force 2>&1 || echo "  ⚠ Migration failed — edit .env with real passwords, then run: cd ~/LessonPlanShare && php artisan migrate --force"

# ── Step 9: Point domain to Laravel's public folder ───────────────────────

echo "[9/10] Creating web directory symlink..."
ln -sf ~/LessonPlanShare/public ~/sheql.com
echo "  ✓ sheql.com → LessonPlanShare/public"

# Create storage symlink
cd ~/LessonPlanShare
php artisan storage:link 2>/dev/null || ln -sf ~/LessonPlanShare/storage/app/public ~/sheql.com/storage
echo "  ✓ Storage symlink created"

# Set permissions
chmod -R 775 ~/LessonPlanShare/storage
chmod -R 775 ~/LessonPlanShare/bootstrap/cache

# Create lessons upload directory
mkdir -p ~/LessonPlanShare/storage/app/public/lessons
chmod 775 ~/LessonPlanShare/storage/app/public/lessons
echo "  ✓ Permissions set"

# ── Step 10: Cache config for production ──────────────────────────────────

echo "[10/10] Building production caches..."
cd ~/LessonPlanShare
php artisan optimize:clear --quiet 2>/dev/null || true
php artisan config:cache --quiet 2>/dev/null || true
php artisan route:cache --quiet 2>/dev/null || true
php artisan view:cache --quiet 2>/dev/null || true
echo "  ✓ Caches built"

# ── Done! ─────────────────────────────────────────────────────────────────

echo ""
echo "============================================"
echo "  Deployment complete!"
echo "============================================"
echo ""
echo "  Next steps:"
echo ""
echo "  1. Edit .env with your real passwords:"
echo "       nano ~/LessonPlanShare/.env"
echo "     Change DB_PASSWORD and MAIL_PASSWORD"
echo ""
echo "  2. Rebuild config cache after editing .env:"
echo "       cd ~/LessonPlanShare && php artisan config:cache"
echo ""
echo "  3. Run migrations (if they failed above):"
echo "       cd ~/LessonPlanShare && php artisan migrate --force"
echo ""
echo "  4. Visit https://www.sheql.com to verify!"
echo ""
echo "  ── For future updates ──"
echo "  Since the GitHub repo only has custom files (not a full"
echo "  Laravel project), use this update script:"
echo ""
echo "    cd ~"
echo "    git clone https://github.com/james-beep-boop/LessonPlanShare.git /tmp/LPC"
echo "    cp -r /tmp/LPC/LessonPlanShare/app/* ~/LessonPlanShare/app/"
echo "    cp -r /tmp/LPC/LessonPlanShare/database/* ~/LessonPlanShare/database/"
echo "    cp -r /tmp/LPC/LessonPlanShare/resources/* ~/LessonPlanShare/resources/"
echo "    cp -r /tmp/LPC/LessonPlanShare/routes/* ~/LessonPlanShare/routes/"
echo "    cp -r /tmp/LPC/LessonPlanShare/public/* ~/LessonPlanShare/public/"
echo "    rm -rf /tmp/LPC"
echo "    cd ~/LessonPlanShare"
echo "    php artisan migrate --force"
echo "    php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache"
echo ""
echo "  Or use the simpler UPDATE_SITE.sh script (created alongside this one)."
echo ""
