# CLAUDE.md — Project Guide for Claude Code

**Last updated:** 2026-02-28
**Auto-loaded in every session.** Keep this file short and high-signal.

## Project Overview
A Laravel 12 web app for Kenyan high school teachers (ARES Education) to upload, share, version, rate, and download lesson plan documents. Deployed on DreamHost shared hosting at www.sheql.com.

## Core Reference Documents (read these first)
- **TECHNICAL_DESIGN.md** → Authoritative full specification (v3.0). All features, architecture, data models, and page-by-page specs. Each section is self-contained — read only the section relevant to your task.
- **CURRENT_STATUS.md** → Living "where we actually are" summary vs the spec. Check before every task.
- **DEPLOYMENT.md** → DreamHost-specific process, quirks, past errors, post-deploy checklist, and gotchas. See especially the "DreamHost Quirks & Lessons Learned" section.

## Tech Stack
- Frontend: Tailwind CSS via CDN + Alpine.js via CDN (NO build step, NO Vite, NO Webpack)
- Backend: Laravel 12 + Laravel Breeze (Blade stack), PHP 8.4
- Database: MySQL 8.0 on remote host (mysql.sheql.com)
- Session/Cache: File driver (no Redis on shared hosting)
- Email: SMTP via smtp.dreamhost.com (port 587, TLS)
- Key constraint: This is an **overlay repo** — the git repo only contains custom files, NOT a full Laravel installation. Breeze and Laravel core live on the server but are NOT in git.

## Local Development Commands
- Start dev server: `php artisan serve` (or `php -S localhost:8000 -t public`)
- Run migrations: `php artisan migrate`
- Clear cache / rebuild: `php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache`
- Test duplicate detection: `php artisan lessons:detect-duplicates --dry-run`
- Generate app key: `php artisan key:generate`

**Rule:** Always test locally first. Never push broken code to DreamHost.

## Coding Style & Conventions
- Indentation: 4 spaces (PSR-12 for PHP, standard for Blade)
- Naming: snake_case for PHP variables/DB columns, camelCase for JS
- Views use `<x-layout>` component wrapper (NOT Breeze's `<x-app-layout>`)
- All custom CSS uses Tailwind utility classes — no custom stylesheets
- Alpine.js for client-side interactivity (modals, toggles, combos)
- Comments: Explain *why*, not *what*. Use `{{-- Blade comment --}}` in views
- Error handling: Always user-friendly flash messages + logging to laravel.log
- Security: Author identity always from `Auth::id()`, never user input. All inputs validated server-side via Form Request classes (`StoreLessonPlanRequest` for new plans, `StoreVersionRequest` for new versions). Authorization via `LessonPlanPolicy` (delete: author only; `before()` gives admins blanket pass).

## Architecture Principles (from TECHNICAL_DESIGN.md)
- Follow existing patterns exactly. Read the relevant TECHNICAL_DESIGN.md section before changing anything.
- Prefer small, focused changes over big refactors.
- Keep code modular — each page/component is described independently in the spec.
- No new NPM packages or build tools. CDN only.
- File uploads are renamed to canonical format. Original filenames are discarded.
- Version families use `original_id` / `parent_id` tree structure.
- **Engineering philosophy (§1.5):** Code quality and best practices are an ongoing priority, not an afterthought. Controllers stay thin (no inline validation, authorization, or business logic). Follow Laravel conventions: Form Requests for validation, Policies for authorization, private helpers for DRY code. Design for 100+ concurrent users.
- **New version upload route:** `POST /lesson-plans/{id}/versions` → `LessonPlanController@storeVersion` (name: `lesson-plans.store-version`). Not `PUT /lesson-plans/{id}` — that old route no longer exists.
- **Rate limiting on routes:** upload/store → `throttle:10,1`; download → `throttle:60,1`; sendVerification → `throttle:6,1`.

## Deployment Workflow (DreamHost)
See DEPLOYMENT.md for full details, quirks, and the exact update script.

**Standard flow:**
1. Test everything locally first
2. Commit changes: `git add [files] && git commit -m "..."`
3. Push to GitHub: `git push`
4. SSH to DreamHost: `ssh david_sheql@sheql.com`
5. Run update: `bash ~/LessonPlanShare/UPDATE_SITE.sh`
6. Verify the live site

**Critical DreamHost gotchas (see DEPLOYMENT.md for full list):**
- SMTP host MUST be `smtp.dreamhost.com` (not `mail.sheql.com` — TLS cert mismatch)
- Email verification route must NOT use `auth` middleware (opens in new tab with no session)
- Use `--depth 1` for git clone (memory limits on shared hosting)
- NEVER auto-detect stale files — overlay repo would delete Laravel core
- After `.env` changes, MUST clear and rebuild config cache

**Rules Claude must follow:**
- Never assume auto-deploy — always remind me to run UPDATE_SITE.sh on DreamHost after push.
- After any code change, output the exact git commands I should run.
- Flag anything that might behave differently on DreamHost (permissions, .htaccess, PHP version quirks, etc.).

## How Claude Code Must Behave in This Project
- Always reference the relevant section of TECHNICAL_DESIGN.md or CURRENT_STATUS.md before making changes.
- Show a clear diff before editing any file.
- Suggest one focused task at a time (never "build the whole next module").
- After completing work, update CURRENT_STATUS.md and suggest a git commit message.
- Flag any DreamHost compatibility risk immediately.
- Be concise. No fluff. Use bullet points and code blocks.
- When adding new files, update THREE places: (1) DEPLOYMENT.md COMPLETE FILE LIST, (2) DEPLOYMENT.md Step 4 copy list, (3) `UPDATE_SITE.sh` copy section — new files won't deploy without this.
- When adding new routes, update routes/web.php or routes/auth.php AND the route table in TECHNICAL_DESIGN.md.
