# CURRENT_STATUS.md — What's Done vs What's Left

**Last updated:** 2026-02-28 (Multiple UX improvements: guest dashboard limited to 6 rows + Author column hidden; ARES Education branding styled as border button; Teacher Name in header is a link to "my plans" filtered dashboard; upload form shows duplicate class+day warning dialog with 4 options (change class, next available day, archive existing, cancel); Guide page updated for new two-dialog auth, Google Docs + Microsoft Office viewers, duplicate warning, username click, View/Edit button rename, Section 9 "For Administrators" removed; `latestVersions()` scope fixed to show highest semantic version per class/day globally via NOT EXISTS anti-join.)

This file tracks the gap between TECHNICAL_DESIGN.md (the spec) and the actual codebase. Check this before every task.

---

## Fully Implemented (matches spec)

- **Two separate auth modals** (Sign In + Sign Up) in `layout.blade.php`, sharing one Alpine `x-data` scope (`signIn` / `signUp` booleans):
  - **Sign In dialog**: Teacher Email + Password only; errors in `login` named bag; "Forgot your password?" link; "New User? Sign Up" button switches to Sign Up dialog
  - **Sign Up dialog**: Teacher Name (unique) + Teacher Email + Password; errors in `register` named bag; "Already have an account? Sign In" link switches back
  - `open-auth-modal` window event → opens Sign In; `open-signup-modal` → opens Sign Up
  - Backdrop click (`@click` on wrapper) closes; `@click.stop` on inner dialog box prevents propagation (avoids `@click.away` cross-modal bug)
  - Auto-reopen on validation error: `$errors->login->any()` → `signIn: true`; `$errors->register->any()` → `signUp: true`
- **Sign In server-side (AuthenticatedSessionController)**: three-case logic, all errors in `login` named bag:
  - Email not found → error directing user to Sign Up
  - Unverified existing email → `Hash::check` first, then resend verification, login, redirect to `verification.notice`
  - Verified existing email → `Hash::check` + `Auth::login()` (wrong password = error in `login` bag)
- **Sign Up server-side (RegisteredUserController)**: `validateWithBag('register', [...])`, checks email uniqueness and Teacher Name uniqueness, creates user, fires `Registered` event (→ verification email), logs in, redirects to `verification.notice`
- `POST /register` route → `RegisteredUserController::store()` (name: `register.store`); `GET /register` still redirects to dashboard
- Teacher Name uniqueness enforced server-side for new accounts only
- Login/logout with Alpine.js modal + standalone fallback pages
- Email verification (custom session-free `VerifyEmailController`)
- Password reset flow (forgot-password + reset-password views)
- Lesson plan upload with canonical naming (`{Class}_Day{N}_{Author}_{Timestamp}UTC_v{major}-{minor}-{patch}.{ext}`)
- New version creation: same-author links via `original_id` / `parent_id`; different-author creates standalone plan (Section 2.5)
- File storage with SHA-256 hashing
- Voting system (upvote/downvote toggle, self-vote prevention, cached `vote_score`)
- `recalculateVoteScore()` uses raw `DB::table()` update — does NOT touch `updated_at`
- VoteController returns JSON for `Accept: application/json` requests (AJAX support)
- VoteController `Vote::create()` wrapped in `try/catch (QueryException)` for SQLSTATE 23000 — handles concurrent duplicate-insert race condition gracefully (idempotent no-op)
- Dashboard with counters (unique classes, total plans, favorite plan), search, filter, sort, pagination (20/page)
- Dashboard shows all versions by default; filter bar below search has "Show only latest" and "Show only my favorites" (verified users only) checkboxes that auto-submit
- Dashboard filter bar also shows hint "Sort by clicking a blue column header below"
- Sort column headers styled as distinct blue button pills (active = blue filled, inactive = blue text with hover)
- Dashboard counters + table + favorite plan all re-fetched on page load (live DB queries); ↻ Refresh link forces fresh load
- Dashboard/Stats responses include `Cache-Control: no-store` headers to prevent proxy/browser caching of stale counts
- Favorite Lesson Plan title truncated to 20 chars with ellipsis; full filename in tooltip
- Dashboard Author column: shows Teacher Name (LEFT JOIN on users, sortable)
- Dashboard Rating column: "Vote 👍 👎 +N" for guests; locked 👍👎 (greyed) for unviewed plans; AJAX 👍👎 for viewed plans
- Dashboard column alignments: Class left, Day# center, Author left, Version center, Rating center, Updated left
- Dashboard Actions: "View/Edit/Vote" button (greyed out for guests **and unverified users**); no Download button on dashboard
- View tracking: visiting `lesson-plans.show` records a view in `lesson_plan_views` table; gates AJAX voting
- Favorites: AJAX star toggle on dashboard; yellow when favorited, grey when not; greyed out for guests **and unverified users**; `favorites` table with unique `[user_id, lesson_plan_id]` index; `FavoriteController::toggle()` returns JSON
- Guide page (`/guide`): public, linked in header for all users; covers login, version numbering, view/download, upload, delete, voting, and admin rules
- Footer: "Kenya Lesson Plan Repository version {VERSION} © YEAR ARES Education — Lesson Plans are licensed under CC BY-SA 4.0" + inline SVG CC/BY/SA badge icons (no external CDN)
- Footer version from `storage/app/version.txt` (written by `UPDATE_SITE.sh` using `git describe --tags --abbrev=0` to prefer clean tag, falls back to full describe/hash/dev)
- Vote buttons: thumbs-up/down (👍👎) everywhere — locked mode, inline AJAX mode, form mode (SVG). Arrow icons removed.
- Vote AJAX (inline dashboard): error-safe — `if (!r.ok) return null; .catch(() => {})` prevents unhandled promise rejections on expired sessions or server errors
- `favorites_only` filter on dashboard: `Favorite::where('user_id',...)->pluck('lesson_plan_id')` + `whereIn` on `lesson_plans.id`; only active for verified users
- `my_plans_only` filter on dashboard: `where('lesson_plans.author_id', Auth::id())`; only active for verified users; replaces the old My Plans page
- Plan detail page (two-column layout, voting, version history sidebar)
- Black `← Back to Dashboard` button in the top-right header area on: show page, guide page, admin panel
- Show page action buttons: 3-row layout — Row 1: "View in Google Docs ↗" + "View in Microsoft Office ↗" (new tab, external); Row 2: "Download" + "Upload New Version" (auth); Row 3: full-width "Delete — Cannot Be Undone!" (author only, Alpine confirm modal)
- Dashboard: clicking anywhere on a table row navigates to `lesson-plans.show`; Rating, Favorite, and Actions cells use `event.stopPropagation()`; Actions button labelled "View/Edit" (not "View/Edit/Vote")
- Dashboard Author column: shows Teacher Name (`users.name`); falls back to "No Teacher Name" (not "Anonymous") when user account is deleted or name is blank
- **Preview page removed** — route, controller method (`preview()`), and blade file deleted; replaced by external viewer buttons on show page
- **My Plans page removed** — route, controller method (`myPlans()`), and blade file deleted; replaced by "Show only my plans" checkbox in dashboard filter bar
- Stats page **removed** — route, view, and `DashboardController::stats()` deleted. Key counters (Lesson Plans, Contributors, Top Rated, Top Contributor) now shown in the dashboard 4-box counter row.
- Upload success dialog (Alpine.js modal, canonical filename display)
- Flash messages (success/error/status)
- Duplicate content detection artisan command (`lessons:detect-duplicates [--dry-run]`)
- File type restriction: DOC, DOCX, TXT, RTF, ODT only (client and server validated); server uses `$file->extension()` (MIME-derived, not client filename) as a second defence layer in `persistUploadedFile()` to prevent extension spoofing
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
- Class name dropdown (upload + edit forms) built by `buildClassNames()`: merges the hard-coded `CLASS_NAMES` seed array with all distinct class names from the DB, de-duplicated and sorted — ensures existing archive classes always appear even if not in the seed list
- Dashboard "Version" column sorts by three-column numeric `ORDER BY version_major, version_minor, version_patch` (not by string or a single column) so `1.10.0` sorts after `1.9.0`
- `lesson-plans.show`, `lesson-plans.download` require `auth+verified`
- "Favorite Lesson Plan" counter links to `lesson-plans.show`
- Upload button (create + edit forms) greyed out until a valid file is chosen
- After new version upload → redirects to dashboard (not show page)
- Session regeneration on all `Auth::login()` calls (all three auth cases + VerifyEmailController fresh-verify branch)
- VerifyEmailController already-verified replay branch does NOT call `Auth::login()` — prevents a signed-URL replay from granting a new session
- **Admin system:**
  - `is_admin` boolean column on `users` table (migration `2026_02_26_210000_add_is_admin_to_users_table.php`)
  - `AdminMiddleware` enforces the flag; 403 if not admin
  - `AdminController` with per-row delete and bulk-delete for both plans and users
  - `/admin` page: 3 counter boxes (Unique Classes, Total Plans, Contributors) + two searchable/sortable tables (lesson plans + registered users) paginated at 20, with checkboxes, bulk-delete, Verify AJAX button; default sort: Lesson Plans by Class name asc, Users by Teacher Name asc; fallback sort aligned with defaults
  - Admin link in header (visible to admins only, left of username)
  - Sub-navigation links removed from layout; Upload button moved to below the dashboard table
  - Stats page fully removed (route, view, controller method all deleted)
  - **Admin privilege toggle:** "Make Admin" button (any admin can promote); "Revoke Admin" button (only `priority2@protonmail.ch` super-admin can demote); both blocked for self; `SUPER_ADMIN_EMAIL` constant in `AdminController`
- **Dashboard:** 4-box counters (Lesson Plans, Contributors, Top Rated, Top Contributor); Upload New Lesson button below table (verified users only)
- **Show page (lesson-plans.show):** 3-row action button layout; author name displays fall back to "No Teacher Name" if user deleted; `AdminController` uses `deletePlanFile()` private method (DRY) for all 4 delete paths
- **Route `lesson-plans.store-version`:** `POST /lesson-plans/{id}/versions` → `LessonPlanController::storeVersion()`. Replaces old `PUT /lesson-plans/{id}` (`lesson-plans.update`). Validated by `StoreVersionRequest` (always requires file + revision_type; no `isMethod()` branching).
- **`LessonPlanPolicy`** (`app/Policies/LessonPlanPolicy.php`): auto-discovered. `before()` gives admins blanket pass; `delete()` checks author. Used by `LessonPlanController::destroy()` via `$this->authorize()`.
- **`AdminController::sendVerification()`**: moved from `DashboardController`; route unchanged (`admin/users/{user}/send-verification`, throttle 6/min).
- **Rate limiting:** upload routes (`store`, `storeVersion`) → `throttle:10,1`; download → `throttle:60,1`.
- **`votes` table** has `ON DELETE CASCADE` FK — `$lessonPlan->votes()->delete()` in `destroy()` was redundant and removed.
- **`CLASS_NAMES`** → `private const` (was `public const`); `buildPlanAttributes()` private method eliminates duplicate attribute array in `store()` and `storeVersion()`.

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
| `app/Http/Controllers/DashboardController.php` | Dashboard (LEFT JOIN users, loads userVotes + viewedIds) |
| `app/Http/Controllers/LessonPlanController.php` | CRUD + preview + download + view recording |
| `app/Http/Controllers/VoteController.php` | Vote toggle; returns JSON for AJAX requests |
| `app/Http/Controllers/AdminController.php` | Admin delete (plans + users), bulk-delete, toggleAdmin, sendVerification |
| `app/Http/Requests/StoreVersionRequest.php` | Validation for Upload New Version form (always requires file + revision_type) |
| `app/Policies/LessonPlanPolicy.php` | `delete`: author only; `before`: admin bypass |
| `app/Http/Middleware/AdminMiddleware.php` | Enforces `is_admin` flag on admin routes |
| `app/Models/User.php` | Auth user; Teacher Name (unique); `is_admin` flag |
| `app/Models/LessonPlanView.php` | View tracking pivot (user_id, lesson_plan_id) |
| `resources/views/components/layout.blade.php` | Master layout: header, Sign In + Sign Up modals (two separate Alpine dialogs), admin link |
| `resources/views/components/vote-buttons.blade.php` | 4-mode vote display (readonly/locked/inline/form); all use 👍👎 thumbs icons |
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
