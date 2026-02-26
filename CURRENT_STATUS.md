# CURRENT_STATUS.md — What's Done vs What's Left

**Last updated:** 2026-02-26

This file tracks the gap between TECHNICAL_DESIGN.md (the spec) and the actual codebase. Check this before every task.

---

## Fully Implemented (matches spec)

- User registration (single email field = name + email)
- Login/logout with Alpine.js modal + standalone fallback pages
- Email verification (custom session-free VerifyEmailController)
- Password reset flow (forgot-password + reset-password views)
- Lesson plan upload with canonical naming
- New version creation (same-author increments version)
- File storage with SHA-256 hashing
- Voting system (upvote/downvote toggle, self-vote prevention, cached score)
- Dashboard with counters, search, filter, sort, pagination
- Plan detail page (two-column layout, voting, version history)
- Document preview (Google Docs Viewer iframe)
- My Plans page
- Stats page (counters + 4 detail cards)
- Upload success dialog
- Flash messages (success/error/status)
- Duplicate content detection artisan command
- File type restriction: DOC, DOCX, TXT, RTF, ODT only
- SMTP configured for smtp.dreamhost.com
- Version numbers displayed as plain integers (no "v" prefix) ✅
- "Lesson Plan Details" heading on show page ✅

## Partially Implemented / Needs Changes

### Dashboard Table (Section 4.4 of spec)
**Current state:** 6 columns (Class, Day#, Version, Rating, Updated, Actions with View + Download buttons)
**Spec requires:** 8 columns with these changes:
- [ ] Add **Author** column (position 3, left-aligned) — requires JOIN to users table
- [ ] Add **Favorite** column (position 8, center, checkbox) — requires favorites table/controller/model
- [ ] Change **Actions** column: rename "View" → "View/Edit", grey out for guests, remove Download button
- [ ] Change **Rating** display to "Vote 👍 👎" format
- [ ] Fix column alignments per spec (Class left, Day# center, Author left, Version center, Rating center, Updated left)

### Authorization Changes (Section 3.5 of spec)
**Current state:** View, Preview, Download are public routes
**Spec requires:**
- [ ] Move View (`lesson-plans.show`), Preview (`lesson-plans.preview`), and Download (`lesson-plans.download`) to authenticated+verified route group
- [ ] Dashboard "View/Edit" button greyed out and non-clickable for guests

### Favorites System (Section 15 of spec)
**Current state:** Not implemented at all
**Spec requires:**
- [ ] Create `favorites` migration (user_id + lesson_plan_id, unique index)
- [ ] Create `Favorite` model
- [ ] Create `FavoriteController@toggle` (AJAX POST, returns JSON)
- [ ] Add `POST /lesson-plans/{lessonPlan}/favorite` route
- [ ] Add Favorite checkbox column to dashboard table
- [ ] Grey out checkbox for guests

### Different-User Versioning (Section 2.5 / 7 of spec)
**Current state:** New version always links to parent's family regardless of author
**Spec requires:**
- [ ] If uploading user ≠ parent author → create brand new plan (version 1, no original_id/parent_id)

### Sort Whitelist (Section 19.4 of spec)
**Current state:** Whitelist is `class_name, lesson_day, version_number, vote_score, updated_at`
**Spec requires:**
- [ ] Add `author_name` to sort whitelist (requires JOIN to users table in DashboardController)

## Not Started

- Nothing beyond the items listed above.

## Suggested Next Tasks (in order)

1. **Dashboard table: Add Author column + sort by author** — Moderate complexity. Requires adding a JOIN in DashboardController, adding `author_name` to sort whitelist, and adding the column to dashboard.blade.php.
2. **Dashboard table: Rename "View" → "View/Edit", grey for guests, remove Download button** — Easy. View template changes only.
3. **Dashboard table: Fix column alignments** — Easy. Tailwind class changes in dashboard.blade.php.
4. **Dashboard table: Vote display format "Vote 👍 👎"** — Easy. Update vote-buttons component readonly mode.
5. **Move View/Preview/Download to auth routes** — Moderate. Route changes + controller guard + dashboard button greying.
6. **Favorites system** — Larger feature. Migration, model, controller, route, AJAX, dashboard column.
7. **Different-user versioning** — Moderate. Logic change in LessonPlanController@update.
