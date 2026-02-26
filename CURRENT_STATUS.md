# CURRENT_STATUS.md — What's Done vs What's Left

**Last updated:** 2026-02-25 (dashboard Author column, Rating format, column alignments done)

This file tracks the gap between TECHNICAL_DESIGN.md (the spec) and the actual codebase. Check this before every task.

---

## Fully Implemented (matches spec)

- User registration (single email field = name + email)
- Login/logout with Alpine.js modal + standalone fallback pages
- Email verification (custom session-free `VerifyEmailController`)
- Password reset flow (forgot-password + reset-password views)
- Lesson plan upload with canonical naming (`{Class}_Day{N}_{Author}_{Timestamp}UTC.{ext}`)
- New version creation (same-author increments version; links via `original_id` / `parent_id`)
- File storage with SHA-256 hashing
- Voting system (upvote/downvote toggle, self-vote prevention, cached `vote_score`)
- Dashboard with counters (unique classes, total plans, favorite plan), search, filter, sort, pagination (10/page)
- Plan detail page (two-column layout, voting, version history sidebar)
- Document preview (Google Docs Viewer iframe)
- My Plans page (auth+verified, 25/page, sorted by `updated_at DESC`)
- Stats page (counters + 4 detail cards: per-class, top-rated, top-contributors, most-revised)
- Upload success dialog (Alpine.js modal, canonical filename display)
- Flash messages (success/error/status)
- Duplicate content detection artisan command (`lessons:detect-duplicates [--dry-run]`)
- File type restriction: DOC, DOCX, TXT, RTF, ODT only
- SMTP configured for `smtp.dreamhost.com` (port 587, TLS)
- Version numbers displayed as plain integers (no "v" prefix)
- "Lesson Plan Details" heading on show page
- `favoritePlan` counter on dashboard correctly shows plan with highest `vote_score`
- `lesson-plans.show`, `lesson-plans.preview`, `lesson-plans.download` require `auth+verified` (moved from public)
- Dashboard Actions column: "View/Edit" button (greyed out for guests, no Download button)
- "Favorite Lesson Plan" counter links to `lesson-plans.show` (not preview)
- Dashboard Author column: LEFT JOIN on users, `author_name` sortable, email `@` and `.` stripped for display
- Dashboard Rating column: "Vote 👍 👎 +N" format (`whitespace-nowrap`, score in green/red/gray)
- Dashboard column alignments: Class left, Day# center, Author left, Version center, Rating center, Updated left

---

## Partially Implemented / Needs Changes

### 1. Different-User Versioning (Section 2.5 / Section 7 of spec)

**Current state:** `LessonPlanController::update()` always calls `$lessonPlan->createNewVersion([...])`, which always links the new version to the parent's family (sets `original_id` and `parent_id`), regardless of who is uploading.

**Spec requires:** If the uploading user is **NOT** the same as `$lessonPlan->author_id`, create a **brand new plan** (version 1, no `original_id`, no `parent_id`) — completely independent from the parent family.

**File to change:** `app/Http/Controllers/LessonPlanController.php` — add author check in `update()` before calling `createNewVersion()`

---

## Not Started

### Favorites System (Section 15 of spec)

Zero code exists for this feature. Full implementation required:

- [ ] **Migration:** `database/migrations/..._create_favorites_table.php`
  - Columns: `id`, `user_id` (FK → users.id CASCADE), `lesson_plan_id` (FK → lesson_plans.id CASCADE), `created_at`
  - Unique index on `[user_id, lesson_plan_id]`
- [ ] **Model:** `app/Models/Favorite.php` (pivot-style, relationships to User and LessonPlan)
- [ ] **Controller:** `app/Http/Controllers/FavoriteController.php` — single `toggle()` method (POST, returns JSON `{ favorited: true/false }`)
- [ ] **Route:** `POST /lesson-plans/{lessonPlan}/favorite` in auth+verified group, named `favorites.toggle`
- [ ] **Dashboard column:** Checkbox in rightmost column; AJAX Alpine.js toggle; greyed out for guests
- [ ] **DashboardController:** Eager-load user's favorites to pre-populate checkboxes
- [ ] **Update DEPLOYMENT.md:** Add `FavoriteController.php`, `Favorite.php`, and the new migration to the file list and Step 4 copy list

---

## Suggested Next Tasks (in priority order)

### Priority 1 — Different-user versioning (moderate) ← START HERE
Add author check in `LessonPlanController::update()` before `createNewVersion()`. If uploader ≠ `$lessonPlan->author_id`, create a brand-new plan (no version family linkage).

### Priority 2 — Favorites system (larger feature)
Full implementation: migration → model → controller → route → dashboard AJAX column. Update DEPLOYMENT.md + UPDATE_SITE.sh file lists.

---

## Key Files Reference

| File | What it does |
|---|---|
| `routes/web.php` | All app routes — public vs auth+verified grouping |
| `app/Http/Controllers/DashboardController.php` | Dashboard index + stats (LEFT JOIN on users, author sort) |
| `app/Http/Controllers/LessonPlanController.php` | CRUD + versioning logic (different-user check needed in `update()`) |
| `app/Http/Controllers/VoteController.php` | Vote toggle logic |
| `resources/views/dashboard.blade.php` | Dashboard table (7 columns; Favorites column not yet added) |
| `resources/views/components/vote-buttons.blade.php` | Vote display component (readonly shows "Vote 👍 👎 +N") |
| `database/migrations/` | 3 migrations present; favorites migration not yet created |
