# Lesson Plan Exchange — Deployment Guide (Revised)

This is a corrected and security-hardened deployment guide for DreamHost shared hosting.

## Scope and Important Clarification

This package directory contains **custom Laravel app files** (controllers, models, views, routes, migrations), but by itself it is **not a complete deployable Laravel project**.

A deployable Laravel project must include at least:
- `composer.json`
- `artisan`
- `bootstrap/` framework files
- full `config/` files

Use this guide to:
1. Create/maintain a full Laravel app repo.
2. Apply these custom files into that app.
3. Deploy the full app to DreamHost.

---

## Part 1: Build a Deployable Project Locally

### 1) Create a full Laravel app

```bash
composer create-project laravel/laravel LessonPlanShare
cd LessonPlanShare
```

### 2) Install Breeze auth scaffolding

```bash
composer require laravel/breeze --dev
php artisan breeze:install blade
```

If `npm install` / `npm run build` fails because Node is not installed, continue. This project uses CDN assets in custom layouts.

### 3) Copy the custom package files into the full Laravel app

Copy from this package into your Laravel app (replace existing where noted in your original guide), including:
- `app/Models/*`
- `app/Http/Controllers/*`
- `app/Http/Requests/*`
- `app/Mail/*`
- `app/Console/Commands/*`
- `database/migrations/*`
- `resources/views/*`
- `routes/web.php`
- `.env.example`
- `.gitignore`

### 4) Remove Vite references if you are using CDN-only views

Remove `@vite(...)` from Breeze layout templates you still use.

### 5) Validate before committing

```bash
php artisan key:generate
php artisan migrate
php artisan storage:link
find app database routes -name "*.php" -print0 | xargs -0 -n1 php -l
php artisan route:list
```

### 6) Commit the **full** Laravel app repo

Deploy from a repo that contains all Laravel framework files, not just the overlay package.

---

## Part 2: DreamHost Server Preparation

### 1) Set PHP version

In DreamHost panel for your site:
- Set PHP to **8.4**

### 2) Ensure shell access

In Users panel:
- Use a **Shell user**

### 3) Create MySQL DB and user

Collect:
- `DB_HOST` (usually `mysql.yourdomain.com`)
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`

### 4) Create SMTP mailbox

Create `noreply@yourdomain.com` and keep credentials.

Recommended SMTP values for DreamHost mail:
- `MAIL_MAILER=smtp`
- `MAIL_HOST=mail.yourdomain.com`
- `MAIL_PORT=587`
- `MAIL_ENCRYPTION=tls`
- `MAIL_USERNAME` = full email address

---

## Part 3: Install Composer Safely on DreamHost (if needed)

If `composer` is missing on the server, install with signature verification:

```bash
cd ~
php -r "copy('https://composer.github.io/installer.sig', 'installer.sig');"
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php -r "if (trim(file_get_contents('installer.sig')) !== hash_file('sha384', 'composer-setup.php')) { echo 'ERROR: Invalid installer signature' . PHP_EOL; unlink('composer-setup.php'); unlink('installer.sig'); exit(1); }"
php composer-setup.php --quiet
php -r "unlink('composer-setup.php'); unlink('installer.sig');"
```

Optional shell alias:

```bash
echo 'alias composer="php ~/composer.phar"' >> ~/.bashrc
source ~/.bashrc
composer --version
```

---

## Part 4: Deploy the Full App

### 1) SSH in and back up current web root

```bash
ssh YOUR_SHELL_USER@yourdomain.com
mv ~/yourdomain.com ~/yourdomain.com.old_$(date +%Y%m%d_%H%M%S)
```

### 2) Clone full Laravel app repo

```bash
cd ~
git clone https://github.com/YOUR_USERNAME/YOUR_FULL_LARAVEL_REPO.git LessonPlanShare
cd ~/LessonPlanShare
```

### 3) Install PHP dependencies

```bash
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
```

### 4) Configure environment

```bash
cp .env.example .env
nano .env
```

Use production-safe defaults:

```env
APP_NAME="Lesson Plan Exchange"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://www.yourdomain.com

LOG_CHANNEL=single
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=mysql.yourdomain.com
DB_PORT=3306
DB_DATABASE=your_db
DB_USERNAME=your_user
DB_PASSWORD=your_password

FILESYSTEM_DISK=public

SESSION_DRIVER=file
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

CACHE_STORE=file
QUEUE_CONNECTION=sync

MAIL_MAILER=smtp
MAIL_HOST=mail.yourdomain.com
MAIL_PORT=587
MAIL_USERNAME=noreply@yourdomain.com
MAIL_PASSWORD=your_mail_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@yourdomain.com"
MAIL_FROM_NAME="Lesson Plan Exchange"
```

Generate key:

```bash
php artisan key:generate
```

### 5) Run migrations and create links

```bash
php artisan migrate --force
php artisan storage:link
mkdir -p storage/app/public/lessons
```

### 6) Set web root symlink

```bash
ln -sfn ~/LessonPlanShare/public ~/yourdomain.com
```

### 7) Permissions

```bash
chmod -R 775 ~/LessonPlanShare/storage ~/LessonPlanShare/bootstrap/cache
chmod 775 ~/LessonPlanShare/storage/app/public/lessons
```

### 8) Clear and rebuild caches

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Part 5: Cron for Duplicate Detection

Edit cron:

```bash
crontab -e
```

Add (adjust user path and PHP path from `which php`):

```cron
0 2 * * * cd /home/YOUR_SHELL_USER/LessonPlanShare && /usr/local/php84/bin/php artisan lessons:detect-duplicates --no-interaction >> /home/YOUR_SHELL_USER/LessonPlanShare/storage/logs/dedup.log 2>&1
```

Validate:

```bash
crontab -l
cd ~/LessonPlanShare
php artisan lessons:detect-duplicates --dry-run
```

---

## Part 6: Post-Deploy Checks

1. Visit `https://www.yourdomain.com` and confirm dashboard loads.
2. Register/login works.
3. Upload a file and download it.
4. Create a new version and confirm version history.
5. Vote from a second account.
6. Check logs:

```bash
tail -50 ~/LessonPlanShare/storage/logs/laravel.log
```

7. Verify debug is off:

```bash
grep -E '^APP_DEBUG=' ~/LessonPlanShare/.env
```

---

## Part 7: Safe Update Procedure

For later releases:

```bash
ssh YOUR_SHELL_USER@yourdomain.com
cd ~/LessonPlanShare
git pull --ff-only
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Troubleshooting Notes

### Site returns 500

```bash
tail -100 ~/LessonPlanShare/storage/logs/laravel.log
php artisan optimize:clear
```

### `.env` changes do not apply

You likely have cached config:

```bash
php artisan optimize:clear
php artisan config:cache
```

### Uploads fail

- Confirm `storage/app/public/lessons` exists.
- Confirm `public/storage` symlink exists (`php artisan storage:link`).
- Check PHP upload limits in DreamHost panel or `.user.ini`.

### DB connection fails

Use `mysql.yourdomain.com`, not `localhost`.

### Mail fails

Check SMTP credentials and:

```bash
tail -50 ~/LessonPlanShare/storage/logs/laravel.log
```

---

## Security Checklist (Quick)

- `APP_ENV=production`
- `APP_DEBUG=false`
- `SESSION_SECURE_COOKIE=true`
- `.env` never committed
- `storage` and `bootstrap/cache` writable
- cron uses absolute paths
- config cache rebuilt after env updates
