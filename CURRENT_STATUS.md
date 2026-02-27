# CURRENT_STATUS.md — What's Done vs What's Left

**Last updated:** 2026-02-27 (semantic versioning, back buttons on guide/admin/stats pages)

This file tracks the gap between TECHNICAL_DESIGN.md (the spec) and the actual codebase. Check this before every task.

---

## Fully Implemented (matches spec)

- User registration: Teacher Name (unique display name) + Teacher Email + Password in a single merged modal
- Single auth modal — no separate Sign In / Sign Up panels; three-case server-side logic:
  - New email → create account, send verification, redirect to "Check Your Email"
  - Unverified existing email → resend verification email
  - Verified existing email → standard authentication (wrong password = error)
- Teacher Name uniqueness enforced server-side (new accounts only)
- Login/logout with Alpine.js modal + standalone fallback pages
- Email verification (custom session-free `VerifyEmailController`)
- Password reset flow (forgot-password + reset-password views)
- Lesson plan upload with canonical naming (`{Class}_Day{N}_{Author}_{Timestamp}UTC.{ext}`)
- New version creation: same-author links via `original_id` / `parent_id`; different-author creates standalone plan (Section 2.5)
- File storage with SHA-256 hashing
- Voting system (upvote/downvote toggle, self-vote prevention, cached `vote_score`)
- `recalculateVoteScore()` uses raw `DB::table()` update — does NOT touch `updated_at`
- VoteController returns JSON for `Accept: application/json` requests (AJAX support)
- Dashboard with counters (unique classes, total plans, favorite plan), search, filter, sort, pagination (10/page)
- Dashboard shows all versions by default; "Latest version only" checkbox to filter
- Dashboard Author column: shows Teacher Name (LEFT JOIN on users, sortable)
- Dashboard Rating column: "Vote 👍 👎 +N" for guests; locked ▲▼ (greyed) for unviewed plans; AJAX ▲▼ for viewed plans
- Dashboard column alignments: Class left, Day# center, Author left, Version center, Rating center, Updated left
- Dashboard Actions: "View/Edit/Vote" button (greyed out for guests); no Download button on dashboard
- View tracking: visiting `lesson-plans.show` records a view in `lesson_plan_views` table; gates AJAX voting
- Favorites: AJAX star toggle on dashboard; yellow when favorited, grey when not; greyed out for guests; `favorites` table with unique `[user_id, lesson_plan_id]` index; `FavoriteController::toggle()` returns JSON
- Guide page (`/guide`): public, linked in header for all users; covers login, version numbering, view/download, upload, delete, voting, and admin rules
- Plan detail page (two-column layout, voting, version history sidebar)
- Print/Save PDF button on plan detail page (`window.print()`)
- Black `← Back to Dashboard` button (white text, `bg-gray-900 hover:bg-gray-700`) in the top-right header area on: show page, guide page, admin panel, stats page
- Document preview (Google Docs Viewer iframe, `&t=time()` cache-buster prevents blank-on-revisit)
- Preview page buttons: "Home" (→ dashboard) and "← Back to Details" (→ show page)
- My Plans page (auth+verified, 25/page, sorted by `updated_at DESC`)
- Stats page (counters + 4 detail cards: per-class, top-rated, top-contributors, most-revised)
- Stats `groupBy` bug fixed: uses `DB::raw('COALESCE(original_id, id)')` not the alias
- Upload success dialog (Alpine.js modal, canonical filename display)
- Flash messages (success/error/status)
- Duplicate content detection artisan command (`lessons:detect-duplicates [--dry-run]`)
- File type restriction: DOC, DOCX, TXT, RTF, ODT only (client and server both validated)
- SMTP configured for `smtp.dreamhost.com` (port 587, TLS)
- **Semantic versioning:** `major.minor.patch` format per `(class_name, lesson_day)` scope
  - First upload for any class/day → `1.0.0`; first integer always stays `1`
  - Major revision (new standalone version) → increment second integer, reset third (e.g., `1.1.0`)
  - Minor revision (patch/tweak) → increment third integer only (e.g., `1.1.1`)
  - Global sequence: version is determined by the highest existing version for that class/day regardless of author or family
  - DB columns: `version_major`, `version_minor`, `version_patch` (unsigned int, correct numeric sort)
  - Unique DB index on `(class_name, lesson_day, version_major, version_minor, version_patch)` as race guard
  - `semantic_version` Eloquent accessor on `LessonPlan` model → `"1.2.3"` string
  - Canonical filename includes `_v{major}-{minor}-{patch}` suffix (hyphens, not dots)
  - `revision_type` radio (major/minor) shown on edit/new-version form
  - Live AJAX version preview: fetches from `GET /lesson-plans-next-version` as class/day changes
  - Server pre-computes both options in `edit()` to avoid AJAX round-trip on page load
  - Unique constraint violation (race condition) caught and returns user-friendly error
  - `LessonPlanFactory` for tests; `tests/Feature/SemanticVersionTest.php` (11 tests)
  - Backfill migration assigns `1.N.0` to all existing rows grouped by class/day
- `lesson-plans.show`, `lesson-plans.preview`, `lesson-plans.download` require `auth+verified`
- "Favorite Lesson Plan" counter links to `lesson-plans.show`
- Upload button (create + edit forms) greyed out until a valid file is chosen
- After new version upload → redirects to dashboard (not show page)
- Session regeneration on all `Auth::login()` calls (all three auth cases + VerifyEmailController)
- **Admin system:**
  - `is_admin` boolean column on `users` table (migration `2026_02_26_210000_add_is_admin_to_users_table.php`)
  - `AdminMiddleware` enforces the flag; 403 if not admin
  - `AdminController` with per-row delete and bulk-delete for both plans and users
  - `/admin` page: two tables (lesson plans + registered users) with checkboxes, bulk-delete, Verify AJAX button
  - Admin link in header (visible to admins only, left of username)
  - Sub-navigation links (Browse All / My Plans / Upload New Lesson) removed from layout
  - **Admin privilege toggle:** "Make Admin" button (any admin can promote); "Revoke Admin" button (only `priority2@protonmail.ch` super-admin can demote); both blocked for self; `SUPER_ADMIN_EMAIL` constant in `AdminController`

---

## Not Started

No major features remain. All spec items are implemented.

---

## Suggested Next Tasks (in priority order)

---

## Key Files Reference

| File | What it does |
|---|---|
| `routes/web.php` | All app routes — public / auth+verified / admin grouping |
| `app/Http/Controllers/DashboardController.php` | Dashboard (LEFT JOIN users, loads userVotes + viewedIds) + Stats |
| `app/Http/Controllers/LessonPlanController.php` | CRUD + preview + download + view recording |
| `app/Http/Controllers/VoteController.php` | Vote toggle; returns JSON for AJAX requests |
| `app/Http/Controllers/AdminController.php` | Admin delete (plans + users), bulk-delete, toggleAdmin |
| `app/Http/Middleware/AdminMiddleware.php` | Enforces `is_admin` flag on admin routes |
| `app/Models/User.php` | Auth user; Teacher Name (unique); `is_admin` flag |
| `app/Models/LessonPlanView.php` | View tracking pivot (user_id, lesson_plan_id) |
| `resources/views/components/layout.blade.php` | Master layout: header, merged auth modal, admin link |
| `resources/views/components/vote-buttons.blade.php` | 4-mode vote display (readonly/locked/inline/form) |
| `resources/views/dashboard.blade.php` | 7-column table with inline AJAX vote buttons |
| `resources/views/admin/index.blade.php` | Admin panel: plans + users tables with delete/bulk-delete |
| `database/migrations/` | 7 migrations (views, is_admin, favorites, semantic_version) |
| `database/factories/LessonPlanFactory.php` | Factory for feature tests |
| `tests/Feature/SemanticVersionTest.php` | 11 feature tests for semantic versioning |

## Admin Access Setup

To grant admin access to a user on DreamHost (run once after deploy):
```bash
php artisan tinker
>>> User::where('email', 'priority2@protonmail.ch')->update(['is_admin' => true]);
```
