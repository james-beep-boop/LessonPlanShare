# CURRENT_STATUS.md — What's Done vs What's Left

**Last updated:** 2026-02-26 (admin system, Teacher Name, merged auth modal, view-gated AJAX voting, sub-nav removed, Stats 500 fix)

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
- Plan detail page (two-column layout, voting, version history sidebar)
- Print/Save PDF button on plan detail page (`window.print()`)
- Back button on show page is a prominent styled button ("← Back to Dashboard")
- Document preview (Google Docs Viewer iframe)
- Preview page buttons: "Home" (→ dashboard) and "← Back to Details" (→ show page)
- My Plans page (auth+verified, 25/page, sorted by `updated_at DESC`)
- Stats page (counters + 4 detail cards: per-class, top-rated, top-contributors, most-revised)
- Stats `groupBy` bug fixed: uses `DB::raw('COALESCE(original_id, id)')` not the alias
- Upload success dialog (Alpine.js modal, canonical filename display)
- Flash messages (success/error/status)
- Duplicate content detection artisan command (`lessons:detect-duplicates [--dry-run]`)
- File type restriction: DOC, DOCX, TXT, RTF, ODT only (client and server both validated)
- SMTP configured for `smtp.dreamhost.com` (port 587, TLS)
- Version numbers displayed as plain integers (no "v" prefix)
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

---

## Not Started

### Favorites System (Section 15 of spec)

Zero code exists for this feature. Full implementation required:

- [ ] **Migration:** `database/migrations/..._create_favorites_table.php`
  - Columns: `id`, `user_id` (FK → users.id CASCADE), `lesson_plan_id` (FK → lesson_plans.id CASCADE), `created_at`
  - Unique index on `[user_id, lesson_plan_id]`
- [ ] **Model:** `app/Models/Favorite.php`
- [ ] **Controller:** `app/Http/Controllers/FavoriteController.php` — `toggle()` returns JSON `{ favorited: bool }`
- [ ] **Route:** `POST /lesson-plans/{lessonPlan}/favorite` in auth+verified group, named `favorites.toggle`
- [ ] **Dashboard column:** Checkbox in rightmost column; AJAX Alpine.js toggle; greyed out for guests
- [ ] **DashboardController:** Eager-load user's favorites to pre-populate checkboxes
- [ ] **Update DEPLOYMENT.md + UPDATE_SITE.sh:** Add `FavoriteController.php`, `Favorite.php`, migration

### Minor Pending

- [ ] **Google Docs Viewer cache-buster:** Add `'&t=' . time()` to `$viewerUrl` in `preview.blade.php` to force a fresh viewer load on every visit (the viewer silently fails on second load without it)

---

## Suggested Next Tasks (in priority order)

### Priority 1 — Google Docs Viewer cache-buster (5-minute fix)
Add `'&t=' . time()` to the viewer URL in `resources/views/lesson-plans/preview.blade.php`.

### Priority 2 — Favorites system (larger feature)
Full implementation: migration → model → controller → route → dashboard AJAX column.
See "Not Started" section above for full checklist.

---

## Key Files Reference

| File | What it does |
|---|---|
| `routes/web.php` | All app routes — public / auth+verified / admin grouping |
| `app/Http/Controllers/DashboardController.php` | Dashboard (LEFT JOIN users, loads userVotes + viewedIds) + Stats |
| `app/Http/Controllers/LessonPlanController.php` | CRUD + preview + download + view recording |
| `app/Http/Controllers/VoteController.php` | Vote toggle; returns JSON for AJAX requests |
| `app/Http/Controllers/AdminController.php` | Admin delete (plans + users), bulk-delete |
| `app/Http/Middleware/AdminMiddleware.php` | Enforces `is_admin` flag on admin routes |
| `app/Models/User.php` | Auth user; Teacher Name (unique); `is_admin` flag |
| `app/Models/LessonPlanView.php` | View tracking pivot (user_id, lesson_plan_id) |
| `resources/views/components/layout.blade.php` | Master layout: header, merged auth modal, admin link |
| `resources/views/components/vote-buttons.blade.php` | 4-mode vote display (readonly/locked/inline/form) |
| `resources/views/dashboard.blade.php` | 7-column table with inline AJAX vote buttons |
| `resources/views/admin/index.blade.php` | Admin panel: plans + users tables with delete/bulk-delete |
| `database/migrations/` | 5 migrations (including lesson_plan_views + is_admin) |

## Admin Access Setup

To grant admin access to a user on DreamHost (run once after deploy):
```bash
php artisan tinker
>>> User::where('email', 'priority2@protonmail.ch')->update(['is_admin' => true]);
```
