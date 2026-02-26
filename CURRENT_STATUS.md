# CURRENT_STATUS.md — What's Done vs What's Left

**Last updated:** 2026-02-25 (re-verified by full codebase read)

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

---

## Partially Implemented / Needs Changes

### 1. Dashboard Table — Column Structure (Section 4.4 of spec)

**Current state:** 6 columns total — 5 sortable (Class, Day#, Version, Rating, Updated) + 1 Actions column

**Spec requires:** 8 columns:

| # | Column | Status |
|---|---|---|
| 1 | Class | ✅ done |
| 2 | Day # | ✅ done |
| 3 | **Author** | ❌ MISSING — email with `@` and `.` stripped; requires JOIN to users table |
| 4 | Version | ✅ done |
| 5 | Rating | ⚠️ format wrong — see below |
| 6 | Updated | ✅ done |
| 7 | **Actions** | ⚠️ wrong — see below |
| 8 | **Favorite** | ❌ MISSING — requires full favorites system |

**Files to change:** `resources/views/dashboard.blade.php`, `app/Http/Controllers/DashboardController.php`

### 2. Dashboard Table — Rating Column Format (Section 4.4 #5 / Section 14.3)

**Current state:** Readonly `<x-vote-buttons>` shows a colored arrow icon + numeric score (e.g., `▲ +2`)

**Spec requires:** Label "Vote 👍 👎" with the score in the cell

**File to change:** `resources/views/components/vote-buttons.blade.php` (readonly mode) OR `resources/views/dashboard.blade.php`

### 3. Dashboard Table — Actions Column (Section 4.4 #7)

**Current state:** Two buttons: `View` (gray-100) + `Download` (gray-900 filled)

**Spec requires:**
- [ ] Single button labeled **"View/Edit"** (gray-100)
- [ ] Button links to `lesson-plans.show` (unchanged)
- [ ] **Greyed out and non-clickable for guests** (not logged in)
- [ ] Download button **removed** from dashboard entirely

**File to change:** `resources/views/dashboard.blade.php`

### 4. Dashboard Sort Whitelist (Section 19.4 of spec)

**Current state:** Whitelist in `DashboardController::index()` is:
```php
['class_name', 'lesson_day', 'version_number', 'vote_score', 'updated_at']
```

**Spec requires:** Add `author_name` to the whitelist (requires a JOIN to users table, not a raw column sort)

**File to change:** `app/Http/Controllers/DashboardController.php`

### 5. Authorization: View / Preview / Download Routes (Section 3.5 of spec)

**Current state:** `lesson-plans.show`, `lesson-plans.preview`, and `lesson-plans.download` are **public routes** (no middleware) in `routes/web.php`.

```php
// Currently public:
Route::get('/lesson-plans/{lessonPlan}', ...)->name('lesson-plans.show');
Route::get('/lesson-plans/{lessonPlan}/preview', ...)->name('lesson-plans.preview');
Route::get('/lesson-plans/{lessonPlan}/download', ...)->name('lesson-plans.download');
```

**Spec requires:** Move all three into the `['auth', 'verified']` middleware group.

**Side effect to fix:** The "Favorite Lesson Plan" counter in `dashboard.blade.php` links to `lesson-plans.preview`. After this change, guests clicking that link will be redirected to login. This is acceptable per spec, but optionally the link could be changed to `lesson-plans.show` to soften the redirect experience (no code change strictly required, but worth considering).

**Files to change:** `routes/web.php`, `app/Http/Controllers/LessonPlanController.php` (remove the "Public route" comment in `show()` and `preview()`)

### 6. Different-User Versioning (Section 2.5 / Section 7 of spec)

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

### Priority 1 — Authorization change (moderate; enables correct UX for all other changes)
Move `lesson-plans.show`, `lesson-plans.preview`, `lesson-plans.download` to `['auth', 'verified']` middleware group. This is a small routes change but affects UX significantly.
- **Risk:** "Favorite Lesson Plan" counter link in `dashboard.blade.php` currently goes to `lesson-plans.preview`; will now redirect guests to login. Consider changing it to `lesson-plans.show` for a softer landing.

### Priority 2 — Dashboard: Author column + sort by author (moderate)
Add JOIN in `DashboardController`, add `author_name` to sort whitelist, add Author column to `dashboard.blade.php`.

### Priority 3 — Dashboard: Actions column fix (easy)
Rename "View" → "View/Edit", remove Download button, grey out for guests.

### Priority 4 — Dashboard: Rating format "Vote 👍 👎" (easy)
Update readonly mode of `vote-buttons.blade.php` to include the "Vote 👍 👎" label alongside the score.

### Priority 5 — Dashboard: Column alignments (easy)
Update `text-left` / `text-center` classes in `dashboard.blade.php` per spec (Class left, Day# center, Author left, Version center, Rating center, Updated left).

### Priority 6 — Different-user versioning (moderate)
Add author check in `LessonPlanController::update()` before `createNewVersion()`.

### Priority 7 — Favorites system (larger feature)
Full implementation: migration → model → controller → route → dashboard AJAX column. Update DEPLOYMENT.md file list.

---

## Key Files Reference

| File | What it does |
|---|---|
| `routes/web.php` | All app routes — public vs auth+verified grouping |
| `app/Http/Controllers/DashboardController.php` | Dashboard index + stats (sort whitelist, Author JOIN needed) |
| `app/Http/Controllers/LessonPlanController.php` | CRUD + versioning logic (different-user check needed in `update()`) |
| `app/Http/Controllers/VoteController.php` | Vote toggle logic |
| `resources/views/dashboard.blade.php` | Dashboard table (6 columns → 8 columns needed) |
| `resources/views/components/vote-buttons.blade.php` | Vote display component (readonly format update needed) |
| `database/migrations/` | 3 migrations present; favorites migration not yet created |
