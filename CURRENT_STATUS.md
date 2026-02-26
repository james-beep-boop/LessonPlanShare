# CURRENT_STATUS.md — What's Done vs What's Left

**Last updated:** 2026-02-26 (view-gated voting, inline AJAX votes, upload UX, versioning)

This file tracks the gap between TECHNICAL_DESIGN.md (the spec) and the actual codebase. Check this before every task.

---

## Fully Implemented (matches spec)

- User registration (single email field = name + email)
- Login/logout with Alpine.js modal + standalone fallback pages
- Email verification (custom session-free `VerifyEmailController`)
- Password reset flow (forgot-password + reset-password views)
- Lesson plan upload with canonical naming (`{Class}_Day{N}_{Author}_{Timestamp}UTC.{ext}`)
- New version creation: same-author links via `original_id` / `parent_id`; different-author creates standalone plan (Section 2.5)
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
- `favoritePlan` counter correctly shows plan with highest `vote_score`
- `lesson-plans.show`, `lesson-plans.preview`, `lesson-plans.download` require `auth+verified`
- Dashboard Actions column: "View/Edit" button (greyed out for guests, no Download button)
- "Favorite Lesson Plan" counter links to `lesson-plans.show`
- Dashboard Author column: LEFT JOIN on users, sortable, email `@` and `.` stripped
- Dashboard Rating column: "Vote 👍 👎 +N" for guests; locked ▲▼ for unviewed plans; AJAX ▲▼ for viewed plans
- Dashboard column alignments: Class left, Day# center, Author left, Version center, Rating center, Updated left
- Dashboard shows all versions by default; "Latest version only" checkbox to filter
- Back button on show page is a prominent button ("← Back to Dashboard")
- Upload button (create + edit forms) greyed out until a valid file is chosen
- After new version upload → redirects to dashboard (not show page)
- View tracking: visiting `lesson-plans.show` records a view in `lesson_plan_views` table
- AJAX voting from dashboard: VoteController returns JSON for `Accept: application/json` requests

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

---

## Suggested Next Tasks (in priority order)

### Priority 1 — Favorites system (larger feature) ← START HERE
Full implementation: migration → model → controller → route → dashboard AJAX column.
See "Not Started" section above for full checklist.

---

## Key Files Reference

| File | What it does |
|---|---|
| `routes/web.php` | All app routes — public vs auth+verified grouping |
| `app/Http/Controllers/DashboardController.php` | Dashboard (LEFT JOIN users, loads userVotes + viewedIds) |
| `app/Http/Controllers/LessonPlanController.php` | CRUD + versioning + view recording |
| `app/Http/Controllers/VoteController.php` | Vote toggle; returns JSON for AJAX requests |
| `app/Models/LessonPlanView.php` | View tracking pivot (user_id, lesson_plan_id) |
| `resources/views/dashboard.blade.php` | 7-column table with inline AJAX vote buttons |
| `resources/views/components/vote-buttons.blade.php` | 4-mode vote display (readonly/locked/inline/form) |
| `database/migrations/` | 4 migrations present including lesson_plan_views |
