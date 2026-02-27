# ARES Education — Kenya Lesson Plan Repository: Technical Design Document

**Version:** 3.1
**Date:** February 2026 (updated 2026-02-26)
**Status:** Deployed at www.sheql.com

---

## 1. Project Overview

### 1.1 Purpose

The ARES Education Kenya Lesson Plan Repository is a web application that allows high school teachers to upload, share, version, rate, and download lesson plan documents. It serves a small group of educators affiliated with the ARES Education program in Kenya, providing a centralized repository for collaborative lesson planning.

### 1.2 Target Users

A closed community of 5–30 high school teachers who share lesson plan documents with each other. All users know each other as colleagues; the system is not designed for the general public, though the browsing interface is publicly accessible without authentication.

### 1.3 Hosting Environment

The application is deployed on DreamHost shared hosting (www.sheql.com) with the following constraints that shape all technical decisions:

- No root/sudo access; shared PHP environment (PHP 8.4)
- MySQL 8.0 on a remote host (mysql.sheql.com)
- No Node.js runtime; no build tools (Vite, Webpack)
- No Redis or Memcached; file-based sessions and cache
- No background workers (queues); all operations are synchronous
- SMTP email via DreamHost mail server (smtp.dreamhost.com, port 587, TLS)
- HTTPS via DreamHost's free Let's Encrypt integration
- Cron jobs available for scheduled commands
- Limited memory on shared hosting (requires `--depth 1` for git clone operations)

### 1.4 Technology Stack

| Component | Technology | Rationale |
|---|---|---|
| Backend Framework | Laravel 12 | PHP framework with built-in auth, ORM, migrations |
| Authentication | Laravel Breeze (Blade) | Lightweight auth scaffolding; no SPA complexity |
| CSS Framework | Tailwind CSS via CDN | No build step required; instant styling |
| JavaScript | Alpine.js via CDN | Lightweight reactivity for modals and toggles; no build step |
| Database | MySQL 8.0 | DreamHost-provided; standard Laravel support |
| File Storage | Local disk (`storage/app/public`) | Simplest option for shared hosting |
| Email | SMTP (DreamHost) | Direct SMTP; no third-party mail service needed |
| Session/Cache | File driver | No Redis available on shared hosting |

---

## 2. Data Model

> **Modularity note:** Each table is described independently. Changes to one table should NOT require reading any other table's description, except where foreign keys are explicitly noted.

### 2.1 `users` Table (Laravel Default + Customizations)

| Column | Type | Notes |
|---|---|---|
| id | bigint unsigned PK | Auto-increment |
| name | varchar(255) unique | Teacher Name chosen by the user at registration |
| email | varchar(255) unique | Login identifier |
| email_verified_at | timestamp nullable | Set when user clicks verification link |
| password | varchar(255) | Bcrypt-hashed |
| is_admin | boolean default false | Admin flag; grants access to `/admin` panel |
| remember_token | varchar(100) nullable | Laravel session persistence |
| created_at / updated_at | timestamps | Standard Laravel |

**Design decision — Teacher Name:** The registration form has three fields: Teacher Name (any unique string), Teacher Email, and Password. The Teacher Name is stored in the `name` column and displayed throughout the UI (dashboard Author column, plan detail pages, stats). It is distinct from the email address. Teacher Name uniqueness is enforced server-side.

**`is_admin` flag:** Defaults to `false`. Set manually via `php artisan tinker`:
```
User::where('email', 'user@example.com')->update(['is_admin' => true]);
```
Enforced by `AdminMiddleware` on all `/admin` routes.

**Author display name:** The Teacher Name (from `users.name`) is displayed directly — no email stripping required. The canonical filename still uses the email-derived slug for historical consistency.

### 2.2 `lesson_plans` Table

| Column | Type | Notes |
|---|---|---|
| id | bigint unsigned PK | Auto-increment |
| class_name | varchar(255) | Subject name (free-text; dropdown offers existing names + "Other") |
| lesson_day | unsigned int | Lesson number (1–20) |
| description | text nullable | Free-text description of the plan |
| name | varchar(255) | Auto-generated canonical name |
| original_id | bigint unsigned nullable FK | Points to root plan in version family |
| parent_id | bigint unsigned nullable FK | Points to immediate predecessor version |
| version_number | unsigned int default 1 | Auto-incremented within version family |
| author_id | bigint unsigned FK | The user who uploaded this version |
| file_path | varchar(255) nullable | Relative path on the public disk |
| file_name | varchar(255) nullable | Canonical filename with extension |
| file_size | unsigned int nullable | File size in bytes |
| file_hash | varchar(64) nullable | SHA-256 hash for duplicate detection |
| vote_score | int default 0 | Cached sum of all vote values |
| created_at / updated_at | timestamps | Standard Laravel |

**Indexes:** `class_name`, `original_id`, `parent_id`, `vote_score`, `file_hash`

**Foreign keys:**
- `author_id` → `users.id` (CASCADE on delete)
- `original_id` → `lesson_plans.id` (SET NULL on delete)
- `parent_id` → `lesson_plans.id` (SET NULL on delete)

### 2.3 `votes` Table

| Column | Type | Notes |
|---|---|---|
| id | bigint unsigned PK | Auto-increment |
| lesson_plan_id | bigint unsigned FK | The plan version being voted on |
| user_id | bigint unsigned FK | The user who cast the vote |
| value | tinyint | +1 (upvote) or -1 (downvote) |
| created_at / updated_at | timestamps | Standard Laravel |

**Constraints:** UNIQUE index on `[lesson_plan_id, user_id]` (one vote per user per version)

**Foreign keys:**
- `lesson_plan_id` → `lesson_plans.id` (CASCADE on delete)
- `user_id` → `users.id` (CASCADE on delete)

### 2.4 `lesson_plan_views` Table

| Column | Type | Notes |
|---|---|---|
| user_id | bigint unsigned FK | The user who viewed the plan |
| lesson_plan_id | bigint unsigned FK | The plan that was viewed |
| created_at | timestamp | When the view was first recorded |

**Constraints:** UNIQUE index on `[user_id, lesson_plan_id]` (one record per user per plan — only the first view is recorded).

**Foreign keys:**
- `user_id` → `users.id` (CASCADE on delete)
- `lesson_plan_id` → `lesson_plans.id` (CASCADE on delete)

**Behavior:** A view is recorded when an authenticated user visits the `lesson-plans.show` route. Views gate AJAX voting in the dashboard — a user must have viewed a plan before they can vote on it inline. See Section 14.4.

### 2.5 `favorites` Table

| Column | Type | Notes |
|---|---|---|
| id | bigint unsigned PK | Auto-increment |
| user_id | bigint unsigned FK | The user who favorited |
| lesson_plan_id | bigint unsigned FK | The plan that was favorited |
| created_at | timestamp | When the favorite was added |

**Constraints:** UNIQUE index on `[user_id, lesson_plan_id]` (one favorite per user per plan)

**Foreign keys:**
- `user_id` → `users.id` (CASCADE on delete)
- `lesson_plan_id` → `lesson_plans.id` (CASCADE on delete)

**Behavior:** Toggled via AJAX POST to `/lesson-plans/{id}/favorite`. Authenticated users only. Returns JSON `{ favorited: true/false }` for frontend toggle without page reload.

### 2.6 Version Family Model

Lesson plans use a tree-based versioning system:

```
Version 1 (root):  original_id = NULL, parent_id = NULL
Version 2:         original_id = 1,    parent_id = 1
Version 3:         original_id = 1,    parent_id = 2
```

- The root plan (version 1) always has `original_id = NULL` and `parent_id = NULL`.
- All descendant versions share the same `original_id`, pointing to the root.
- `parent_id` tracks the direct lineage (which version was this derived from).
- `version_number` auto-increments within a family by finding `MAX(version_number)` across all records sharing the same `original_id`.

**Dashboard default:** All versions are shown. A "Latest version only" checkbox restricts to one row per family (subquery: `MAX(id)` grouped by `COALESCE(original_id, id)`).

**Deletion guard:** The root plan cannot be deleted if child versions exist, because `original_id` uses `onDelete('set null')`, which would orphan the family linkage. Users must delete children first.

**Different-user versioning:** When a user who is NOT the original author creates a new version, the system creates a completely new plan (version 1) with no `original_id` or `parent_id` link. This "breaks the link" — the new plan starts its own independent version family.

### 2.7 Canonical Naming

Every uploaded document is renamed to a canonical format, regardless of the original upload filename:

```
{ClassName}_Day{N}_{AuthorName}_{YYYYMMDD_HHMMSS}UTC.{ext}
```

Example: `Mathematics_Day5_davidsheqlcom_20260221_143022UTC.docx`

**Sanitization rules:**
- Spaces → hyphens
- All characters except A-Z, a-z, 0-9, and hyphen are stripped
- The `@` and `.` in email addresses are removed (e.g., `david@sheql.com` → `davidsheqlcom`)
- Timestamp is always UTC

**Uniqueness:** The combination of class + day + author + second-resolution timestamp ensures unique names. A server-side guard rejects uploads if an identical canonical name already exists (which can only happen if the same user uploads the same class/day within the same second).

### 2.8 File Storage

- Files are stored at `storage/app/public/lessons/{canonical_name}.{ext}`
- The `storage:link` artisan command creates a symlink from `public/storage` → `storage/app/public`
- Files are served via the public disk for direct download
- Maximum file size: 1 MB (enforced by validation rule `max:1024` and client-side JavaScript)
- Accepted formats: DOC, DOCX, TXT, RTF, ODT

### 2.9 Duplicate Content Detection

A SHA-256 hash of each uploaded file's contents is computed and stored in `file_hash`. An artisan command (`lessons:detect-duplicates`) runs on a cron schedule to:

1. Back-fill hashes for any records missing them
2. Group records by hash and identify groups with more than one record
3. Keep the earliest upload (lowest id) and delete all later duplicates
4. Delete the stored file and database record for each duplicate
5. Email the author of each deleted duplicate explaining what happened

The command supports a `--dry-run` flag for preview-only execution.

---

## 3. Authentication and Authorization

> **Modularity note:** This section covers how users register, log in, reset passwords, and verify email. Changes here should NOT require reading any other section.

### 3.1 Registration and Login Flow (Single Merged Form)

The auth modal is a single form — there are no separate "Sign In" and "Sign Up" panels. The same form handles registration, re-verification, and login based on what exists in the database.

**Form fields:** Teacher Name (unique; required for new accounts only), Teacher Email (required), Password (required; Show/Hide toggle).

**Server-side three-case logic** (`AuthenticatedSessionController@store`):

1. **New email (registration):** Teacher Name is required and validated for uniqueness. A new `User` record is created. The `Registered` event fires (sends verification email). The user is logged in (so the verification notice page can display their email and offer "Resend"). They are redirected to `verification.notice` — they cannot access authenticated routes until clicking the link.

2. **Unverified existing email:** The password is ignored. The verification email is resent. The user is logged in and redirected to `verification.notice`.

3. **Verified existing email:** Standard authentication. Wrong password returns a validation error. On success, redirects to dashboard.

**Teacher Name for existing users (Cases 2 & 3):** The Name field is present in the form but ignored by the server — it is only validated in Case 1.

**Modal trigger:** "Sign In" button dispatches Alpine.js `open-auth-modal` event.

**Standalone fallback:** If validation fails and redirects to `/login`, a standalone login page renders an identical form in a full-page layout.

### 3.2 Password Reset Flow

### 3.3 Password Reset Flow

1. User clicks "Forgot your password?" link (available in both the auth modal and the standalone login page).
2. Navigates to `/forgot-password` — standalone form requesting the user's email address.
3. On submit, Laravel sends a password reset email via the standard Breeze `PasswordResetLinkController`.
4. The user clicks the reset link in their email → opens `/reset-password/{token}` with their email pre-filled.
5. User enters a new password + confirmation → submits → Laravel's `NewPasswordController` handles the reset.
6. On success, user is redirected to login with a status message.

### 3.4 Email Verification (Custom — Session-Free)

The email verification link does NOT require the user to be logged in. This is critical because the link often opens in a new browser tab or a different browser where no session exists.

**Custom `VerifyEmailController`** (replaces Breeze's default):
1. Validates the signed URL (Laravel's `signed` middleware)
2. Finds the user by ID from the URL parameter
3. Verifies the hash matches the user's email
4. Marks the email as verified
5. Logs the user in automatically (with `$request->session()->regenerate()`)
6. Redirects to dashboard with a success message

The route uses `signed` and `throttle:6,1` middleware but NOT `auth` middleware.

### 3.5 Authorization Rules

| Action | Who Can Do It |
|---|---|
| Browse all plans (dashboard) | Anyone (public) |
| View archive statistics | Anyone (public) |
| View a single plan's detail page | Authenticated + verified email |
| Preview a document | Authenticated + verified email |
| Download a file | Authenticated + verified email |
| Upload a new plan | Authenticated + verified email |
| Create a new version | Authenticated + verified email |
| Vote on a plan | Authenticated + verified email (not the author; must have viewed the plan) |
| Inline AJAX vote from dashboard | Authenticated + verified email (not author; must have viewed plan) |
| Favorite a plan | Authenticated + verified email |
| Delete a plan | Authenticated + verified email (only the plan's author) |
| View "My Plans" | Authenticated + verified email |
| Access admin panel (`/admin`) | Authenticated + verified email + `is_admin = true` |
| Delete any plan or user (admin) | Authenticated + verified email + `is_admin = true` |

Authorization is enforced via Laravel middleware (`['auth', 'verified']`) on route groups, `AdminMiddleware` on admin routes, plus controller-level guards for author-specific actions (delete, self-vote prevention).

---

## 4. Dashboard (Home Page — `/`)

> **Modularity note:** This section fully describes the main public page. Changes here should NOT require reading any other section except the Data Model (Section 2) for column references.

**Browser tab title:** "ARES: Lesson Plans"

**Layout:** Full-width table with counters, search/filter bar, and results table. Public — no login required to browse.

### 4.1 Dashboard Counters

Bordered card above the search bar with three metrics:
- **Unique Classes** — count of distinct class names in the database (large bold number)
- **Total Lesson Plans** — total count of all plan records, counting each revision as one
- **Favorite Lesson Plan** — the plan with the highest net vote score (upvotes minus downvotes). Displays the plan name as a clickable link to the preview page, the author's email, and the rating in green. If no plans have positive votes, shows "No votes yet."

### 4.2 Upload Button

Visible to authenticated + verified users only. A prominent "Upload New Lesson" button (gray-900) displayed in the header (right side). No "+" prefix.

### 4.3 Search & Filter Bar

Contained in a bordered card:
- **Search** (text input): Free-text search across document name, class name, description, and author name. Uses SQL `LIKE %term%` queries.
- **Class** (dropdown): Filters by class name. Options are dynamically populated from the distinct `class_name` values that exist in the database.
- **Latest version only** (checkbox): When unchecked (default), shows ALL versions as separate rows. When checked, restricts to the latest version of each plan family.
- **Search** button (gray-900) and **Clear** link.

### 4.4 Results Table

Eight columns, all sortable by clicking the header:

| # | Column | Alignment | Content |
|---|---|---|---|
| 1 | **Class** | left | Subject name |
| 2 | **Day #** | center | Lesson number |
| 3 | **Author** | left | Teacher Name (from `users.name` via LEFT JOIN; sortable) |
| 4 | **Version** | center | Semantic version string in `major.minor.patch` format (e.g., `1.0.0`, `1.2.3`). Sorted via three-column numeric `ORDER BY version_major, version_minor, version_patch`. |
| 5 | **Rating** | center | **Guest:** readonly "Vote 👍 👎 +N". **Authenticated, unviewed plan:** greyed locked ▲▼ (tooltip: "View this plan to unlock voting"). **Authenticated, viewed plan (not author):** interactive AJAX ▲▼ with live score update. **Author:** greyed (can't self-vote). |
| 6 | **Updated** | left | Date only in "Mon D, YYYY" format (no time) |
| 7 | **Actions** | center | Single button: "View/Edit/Vote" (gray-100, links to plan detail page). **Greyed out and non-clickable for guests and unverified users.** No Download button on the dashboard. |
| 8 | **Favorite** | center | ★ star toggle. Authenticated + verified: yellow (★) when favorited, grey otherwise; AJAX POST to `favorites.toggle`. Guests and unverified users: grey non-clickable star. |

**Sorting:** Clicking a column header sorts by that column. A second click on the same column reverses the direction. The active sort column shows an up/down triangle indicator. Default sort: `updated_at DESC` (most recent first). Sort direction is validated server-side to only allow `asc` or `desc`. Sort whitelist: `class_name`, `lesson_day`, `author_name`, `semantic_version`, `vote_score`, `updated_at`. The `semantic_version` sort uses three numeric columns (`ORDER BY version_major, version_minor, version_patch`). Note: sorting by `author_name` requires a JOIN to the `users` table.

**Pagination:** 10 rows per page. Standard Laravel pagination links. A summary line below the table shows "Showing X–Y of Z plans".

---

## 5. Plan Detail Page (`/lesson-plans/{id}`)

> **Modularity note:** This section fully describes the single-plan detail page. Changes here should NOT require reading any other section.

**Access:** Requires authentication + verified email.

**Layout:** Two-column grid on large screens (2/3 + 1/3), stacks vertically on mobile.

### 5.1 Left Column — Lesson Plan Details Card

- Header: "Lesson Plan Details" as the card heading
- Subheading: "{Class Name} — Day {N}"
- Info line: "Version {N} · by {author} · {date} UTC"
- Monospace canonical name below the info line
- Description text (or "No description provided" in italic)
- Detail grid (2 columns): Class, Lesson Day, Version, Author, File (name + formatted size), Uploaded date
- Action buttons:
  - **Preview File** (gray-900 button) — opens document in embedded viewer; visible if file exists
  - **Download File** (gray-100 outlined button) — direct file download; visible if file exists
  - **Create New Version** (gray-100 outlined button) — visible only to authenticated users
  - **Delete** (red-50 button) — visible only to the plan's author; confirms via browser `confirm()` dialog

### 5.2 Left Column — Community Rating Card

- Large vote score number (green if positive, red if negative, gray if zero)
- Text: "{N} upvotes, {N} downvotes"
- Vote display label: "Vote 👍 👎"
- **If authenticated and not the author:** Interactive upvote/downvote buttons (chevron arrows). The active vote direction is highlighted (green background for upvote, red for downvote). Clicking the same direction again removes the vote (toggle off). Clicking the opposite direction switches the vote. A helper text appears when a vote is active: "Click the same arrow again to remove your vote."
- **If authenticated and is the author:** Text: "You cannot vote on your own lesson plan."
- **If not authenticated:** "Sign in to vote on this plan." with a clickable "Sign in" link that opens the auth modal.

### 5.3 Right Column — Version History Card

- Lists all versions in the plan's family, ordered by version number ascending
- Each entry shows: circular badge with version number (no "v" prefix), class + day label, author, date, vote score
- The current version is highlighted with a gray background
- Other versions are clickable links to their detail pages

---

## 6. Upload Form (`/lesson-plans-create`)

> **Modularity note:** This section fully describes the upload form. Changes here should NOT require reading any other section.

**Access:** Requires authentication + verified email.

**Layout:** Centered, max-width 2xl form in a bordered card.

### 6.1 Form Fields

1. **Class Name** (required, Alpine.js combo widget): A dropdown lists all existing class names from the database, plus an "Other (enter new class name)" option. Selecting "Other" reveals a text input for entering a custom class name (max 100 characters). A hidden `<input>` submits the actual value (either the dropdown selection or the custom text). This allows teachers to create new subjects without requiring code changes.
2. **Lesson Number** (required dropdown): Numbers 1 through 20. Small (w-32) dropdown.
3. **Author** (read-only display): Shows the logged-in user's email address in a gray-50 bordered box. Text: "Plans are always uploaded under your account." Author is always `Auth::id()`.
4. **Description** (optional textarea): 4 rows, max 2000 characters.
5. **Document Name** (info box): Gray-50 box showing the naming format: `{ClassName}_Day{N}_{AuthorName}_{UTC-Timestamp}`.
6. **Lesson Plan File** (required file input): Styled file input. Max 1 MB. Accepted types: DOC, DOCX, TXT, RTF, ODT. Client-side JavaScript validates file size before submission and shows an error if the file exceeds 1 MB.
7. **Upload Lesson Plan** button (gray-900) + **Cancel** link.

### 6.2 On Submit

- Validates all fields via `StoreLessonPlanRequest`
- Generates canonical name from fields + current UTC timestamp
- Renames the uploaded file to the canonical name regardless of original filename
- Checks for duplicate canonical name (rejects if exists)
- Stores file with canonical name in `storage/app/public/lessons/`
- Computes SHA-256 hash
- Creates `LessonPlan` record with `version_number = 1`, no `original_id` or `parent_id`
- Sends confirmation email to the uploader (wrapped in try/catch; failure is logged, not blocking)
- Redirects to plan detail page with upload-success dialog

---

## 7. New Version Form (`/lesson-plans/{id}/new-version`)

> **Modularity note:** This section fully describes the new-version form. Changes here should NOT require reading any other section except Section 6 (Upload Form) for shared behavior.

**Access:** Requires authentication + verified email.

**Layout:** Same as Upload Form, but with an additional instruction box at the top.

**Top instruction box** (gray-50):
- Text: "Step 1: Download the current version, make your improvements, then upload the revised file below."
- **Download v{N}** button (gray-900) — downloads the parent version's file

**Form fields:** Identical to Upload Form, with the following differences:
- Class Name and Lesson Number are pre-filled from the parent version (can be changed)
- Description is pre-filled from the parent version

**On submit:**
- Same validation and naming as Upload Form
- If the uploading user IS the same author as the parent: creates record with `original_id` = root of parent's family, `parent_id` = parent's id, `version_number` = `MAX(version_number)` in the family + 1
- If the uploading user is NOT the same author: creates a brand new plan (version 1) with no `original_id` or `parent_id`. This "breaks the link" — a completely independent plan.

---

## 8. My Plans (`/my-plans`)

> **Modularity note:** This section fully describes the "My Plans" page. Changes here should NOT require reading any other section.

**Access:** Requires authentication + verified email.

**Header:** "My Lesson Plans" heading + "+ Upload New Plan" button (gray-900).

**Table columns:** Document Name (linked), Class, Day #, Version (integer, no "v"), Rating (readonly), Updated, Actions (Download link + Delete button).

**Pagination:** 25 per page. Sorted by `updated_at DESC`.

**Empty state:** "You haven't uploaded any lesson plans yet. Upload your first one!" with a link to the upload form.

---

## 9. Document Preview Page (`/lesson-plans/{id}/preview`)

> **Modularity note:** This section fully describes the document preview page. Changes here should NOT require reading any other section.

**Access:** Requires authentication + verified email.

**Layout:** Centered max-width 5xl page with header bar and embedded document viewer.

### 9.1 Header Bar

- "Preview" label in uppercase gray text above the plan title
- Plan title: "{Class Name} — Day {N}"
- Subtext: version number, author, filename
- Action buttons: **Download File** (gray-900 primary), **View Details** (gray-100 outlined, links to show page), **Back** link

### 9.2 Document Viewer

- Uses Google Docs Viewer to render `.doc`/`.docx` files in an iframe without server-side conversion
- The iframe URL format: `https://docs.google.com/gview?url={public_file_url}&embedded=true`
- The file must be publicly accessible via its storage URL for the viewer to work
- Iframe height: `75vh` (minimum 500px)
- Below the iframe: a gray-50 footer bar with a note about Google Docs Viewer and a secondary download link

**Privacy note:** Because the Google Docs Viewer fetches the file via its public URL, the document's content is transmitted to Google's servers for rendering. Users should be aware that previewed documents are not private. The download button provides direct access without third-party involvement.

**Fallback:** If the plan has no file attached, redirects to the detail page with an error flash message.

### 9.3 Deferred Feature: In-Browser Editing

In-browser editing is intentionally deferred to a future version. When implemented, it would add Edit, Undo, Discard, and Save buttons to the preview page. The Save button would: increment version if the same author saves, or create a new independent plan if a different user saves. This section is a placeholder for future planning.

---

## 10. Stats Page (`/stats`)

> **Modularity note:** This section fully describes the stats page. Changes here should NOT require reading any other section.

**Access:** Public — no login required.

**Layout:** Centered max-width 4xl page with summary counters and four detail cards in a 2×2 grid.

### 10.1 Summary Counters

3-column grid of bordered cards:
- **Total Lesson Plans** — count of all plan records
- **Unique Classes** — count of distinct class names
- **Contributors** — count of distinct author IDs

### 10.2 Detail Cards

2-column grid:
1. **Plans per Class** — each class name with a proportional horizontal bar showing plan count relative to the largest class. Bar width is percentage-based.
2. **Top Rated Plans** — top 5 plans with positive vote scores, sorted by `vote_score DESC` then `updated_at DESC`. Each entry shows class/day as a link to the detail page, author name, and green score badge.
3. **Top Contributors** — top 5 authors by total uploads (all versions counted). Numbered list with upload count.
4. **Most Revised Plan** — the plan family with the most versions. Shows class/day as a link, original author, and version count. Only shown if at least one family has more than 1 version.

**Header:** Black `← Back to Dashboard` button (top-right, same style as show page).

---

## 11. Auth Modal

> **Modularity note:** This section describes the sign-in/sign-up modal dialog. Changes here should NOT require reading any other section.

A single Alpine.js modal dialog that handles both registration and login in one unified form. There are no separate panels. The server determines which action to take based on whether the email address exists and is verified. See Section 3.1 for the three-case server logic.

**Trigger:** Clicking the "Sign In" button in the top-right header dispatches an Alpine.js event (`open-auth-modal`) that opens the modal.

**Form fields:**
- **Teacher Name** (text input, with hint "choose anything unique") — required for new accounts, present but ignored for existing accounts
- **Teacher Email** (email input, with hint "email only")
- **Password** (with Show/Hide toggle button)

**Submit button:** "Sign In / Up" (full-width, gray-900)

**Below submit:** "Forgot your password?" link (navigates to `/forgot-password`)

**Error handling:** If validation fails, the modal reopens automatically (Alpine.js `open` is set to `true` when `$errors->any()` is true). Field-level error messages appear below each input.

**Standalone fallback page:** `auth/login.blade.php` provides a full-page equivalent for when Breeze redirects to `/login` (e.g., after validation failure on very old browsers). Uses the same `<x-layout>` wrapper.

---

## 12. Upload Success Dialog

> **Modularity note:** This section describes the upload confirmation modal. Changes here should NOT require reading any other section.

An Alpine.js modal that appears after a successful upload/new version. Triggered by the `upload_success` session flash.

**Contents:**
- Green checkmark icon
- "Upload Successful" heading
- "Your lesson plan has been saved as:" + monospace canonical filename
- "A confirmation email has been sent to your address."
- "OK" button (gray-900) — closes the dialog

---

## 13. Flash Messages

Three types of flash messages appear below the header:
- **Success** (green border/background): e.g., "Lesson plan deleted." or "Vote recorded."
- **Error** (red border/background): e.g., duplicate name, delete guard violation, self-vote attempt
- **Status** (blue border/background): general informational messages

---

## 14. Voting System

> **Modularity note:** This section fully describes the voting system. Changes here should NOT require reading any other section except the Data Model (Section 2.3) for the votes table.

### 14.1 Behavior

Each user can cast exactly one vote per lesson plan version (enforced by a unique database index on `[lesson_plan_id, user_id]`).

Vote values: +1 (upvote) or -1 (downvote).

**Toggle behavior:**
- If the user has no existing vote → create a new vote with the submitted value
- If the user votes the same direction again → remove the vote entirely (toggle off)
- If the user votes the opposite direction → update the existing vote to the new value

**Self-vote prevention:** Authors cannot vote on their own plans. The controller checks `$lessonPlan->author_id === Auth::id()` and returns an error flash message if violated.

### 14.2 Cached Vote Score

To avoid expensive `SUM()` queries on every dashboard page load, each lesson plan has a `vote_score` column that caches the aggregate. After every vote action (create, delete, update), `recalculateVoteScore()` runs: it queries `SUM(value)` from the votes table and updates the cached value using `DB::table('lesson_plans')->where('id', $this->id)->update(['vote_score' => $score])`. This raw update intentionally bypasses Eloquent so that `updated_at` is NOT changed (a vote cast today should not make a 2-year-old plan appear at the top of the "recently updated" sort).

### 14.3 View-Gated Dashboard Voting

AJAX voting from the dashboard is gated behind a view requirement: a user must have visited a plan's detail page (`lesson-plans.show`) before they can cast an inline vote. This is enforced by the `lesson_plan_views` table (Section 2.4) — a view record is created via `LessonPlanView::firstOrCreate()` when `show()` is called.

The `DashboardController@index` loads two arrays for authenticated users:
- `$userVotes` — their existing votes for the visible plan IDs
- `$viewedIds` — which of the visible plan IDs they have viewed

These are passed to the view and used to determine which voting mode to render for each row.

### 14.4 Vote Display

The `vote-buttons` Blade component operates in four modes:

**Readonly** (guests): Shows the score with label "Vote 👍 👎 +N". No interactive elements.

**Locked** (authenticated; plan not yet viewed, or is author): Greyed ▲ score ▼. Tooltip: "View this plan to unlock voting". No action on click.

**Inline/AJAX** (authenticated; plan viewed; not author): Alpine.js ▲ score ▼ buttons. Clicking sends a `fetch()` POST with `Accept: application/json`. The `VoteController` returns `{ score, userVote }` JSON. Score and highlighting update in place without page reload.

**Form-based** (plan detail page): Standard POST form buttons (upvote/downvote chevrons). Active direction highlighted (green/red). Helper text when vote is active: "Click the same arrow again to remove your vote."

---

## 15. Admin Panel (`/admin`)

> **Modularity note:** This section fully describes the admin panel. Changes here should NOT require reading any other section.

**Access:** Requires authentication + verified email + `is_admin = true`. Enforced by `AdminMiddleware`.

**Layout:** Two-section page within `<x-layout>`. Header row: page title + black `← Back to Dashboard` button (top-right). Admin link appears in the site header for admin users only.

**Setting admin status:** The first admin must be set via tinker on the server. All subsequent promotions can be done through the Admin panel UI.
```bash
php artisan tinker
>>> User::where('email', 'priority2@protonmail.ch')->update(['is_admin' => true]);
```

**Super-admin:** `priority2@protonmail.ch` is the hardcoded super-administrator (`AdminController::SUPER_ADMIN_EMAIL`). Only this account can revoke admin privileges; all other admins can only grant them.

### 15.1 Lesson Plans Table

Displays ALL lesson plans (paginated 50/page) with columns: checkbox, Delete button, Class, Day#, Author, Version, File, Updated.

- **Per-row Delete:** individual form POST with browser `confirm()` guard. Admin can delete any plan (bypasses author check).
- **Bulk Delete:** checkboxes use HTML5 `form="bulk-plans-form"` attribute to link to a `<form>` outside the table (avoids nested forms). Select-all checkbox via inline JS.
- Deletions permanently remove the file from disk and the database record.

### 15.2 Registered Users Table

Displays ALL users (paginated 50/page) with columns: checkbox, Delete button, Teacher Name, Email, Verified, Admin, Registered, Action.

- **Per-row Delete:** admin can delete any user except themselves (self-deletion is blocked both in UI and controller).
- **Bulk Delete:** same checkbox pattern as plans table; own ID is filtered out server-side.
- **Verify button (Action column):** appears only for unverified users. Sends an AJAX `fetch()` POST to `users.send-verification`. Button shows "Email Sent" for 5 seconds on success.
- **Make Admin button (Action column):** appears for non-admin users (not self). Any admin can click to grant `is_admin = true`. Posts to `admin.users.toggle-admin` with browser `confirm()` guard.
- **Revoke Admin button (Action column):** appears only when the current user is `priority2@protonmail.ch` (super-admin), for rows where `is_admin = true` (not self). Posts to same route to set `is_admin = false`.
- Current admin's own row is highlighted in blue (`bg-blue-50`).

---

## 16. Favorites System

> **Modularity note:** This section fully describes the favorites system. Changes here should NOT require reading any other section except the Data Model (Section 2.5) for the favorites table.

### 16.1 Behavior

Each authenticated + verified user can favorite any lesson plan. A ★ star button appears in the Favorite column of the dashboard table. Toggling it sends an AJAX POST to `/lesson-plans/{id}/favorite`. The star shows yellow (★) when favorited, grey otherwise.

**Guest / unverified behavior:** The star (★) is visible but greyed out and non-clickable for guests and unverified users.

### 16.2 Controller

`FavoriteController@toggle` — accepts POST, toggles the favorite record (creates if not exists, deletes if exists). Returns JSON response `{ favorited: true/false }`.

### 16.3 Route

`POST /lesson-plans/{lessonPlan}/favorite` — in the `auth + verified` middleware group. Named `favorites.toggle`.

---

## 17. Email System

> **Modularity note:** This section fully describes all email functionality. Changes here should NOT require reading any other section.

### 17.1 Upload Confirmation

Sent to the authenticated user (the person performing the upload) after each successful upload or new version creation.

**Mailable:** `App\Mail\LessonPlanUploaded`

**Data passed:** recipient name, canonical filename, class name, lesson day, version number, URL to view the plan.

**Failure handling:** Wrapped in try/catch. If the email fails to send, the error is logged to `storage/logs/laravel.log` but the upload itself succeeds normally.

### 17.2 Duplicate Content Removed

Sent to the author of a deleted duplicate when the `DetectDuplicateContent` command removes their file.

**Mailable:** `App\Mail\DuplicateContentRemoved`

**Data passed:** recipient name, deleted plan name, kept plan name, kept plan's author name.

### 17.3 Email Verification

Triggered by the `Registered` event after registration. Uses the same SMTP configuration. See Section 3.4 for the custom verification controller details.

### 17.4 Password Reset Email

Triggered by the standard Laravel Breeze `PasswordResetLinkController`. Uses the same SMTP configuration. See Section 3.3 for the full password reset flow.

### 17.5 SMTP Configuration

| Setting | Value |
|---|---|
| Driver | smtp |
| Host | smtp.dreamhost.com |
| Port | 587 |
| Encryption | TLS |
| Username | david@sheql.com (full email address) |
| From Address | david@sheql.com |
| From Name | ARES Education |

**DreamHost quirk:** The SMTP host MUST be `smtp.dreamhost.com`, NOT `mail.sheql.com`. DreamHost's shared mail servers use a wildcard TLS certificate for `*.dreamhost.com`. Using `mail.sheql.com` causes a TLS certificate mismatch error: `Peer certificate CN='*.dreamhost.com' did not match expected CN='mail.sheql.com'`.

---

## 17. Visual Design Specification

> **Modularity note:** This section covers all visual styling rules. Changes here should NOT require reading any other section.

### 17.1 Design Language

The application uses a clean, monochromatic black-and-white design language with minimal color. No logo image — text-only branding.

**Primary color:** `gray-900` (#111827) — used for buttons, headings, active navigation, important text
**Background:** Pure white (`bg-white`)
**Borders:** `gray-200` (#E5E7EB) — subtle 1px borders on cards, tables, and form inputs
**Hover states:** `gray-700` for buttons, `gray-50` for table rows
**Link style:** `text-gray-900 underline underline-offset-2` with `hover:text-gray-600`
**Typography:** System font stack (Tailwind's default)
**Accent colors used sparingly:**
- Green: positive vote scores, success messages, upload confirmation checkmark
- Red: negative vote scores, error messages, delete buttons
- Blue: informational status messages

### 17.2 Layout Structure

**Max width:** `max-w-6xl` (72rem / 1152px), centered with auto margins
**Horizontal padding:** `px-4 sm:px-6 lg:px-8`
**Vertical rhythm:** `py-8` for main content, `py-6 sm:py-8` for header

### 17.3 Header

**Structure:** Top-level `<header>` with a bottom border (`border-b border-gray-200`).

**Left side:** "ARES Education" heading (`text-3xl sm:text-4xl font-bold text-gray-900`) + "Kenya Lesson Plan Repository" subtitle (`text-base sm:text-lg text-gray-500`). All wrapped in a link to the dashboard. No logo image.

**Right side** (reading left to right, authenticated + verified):
- **"Upload New Lesson"** button (gray-900 pill)
- User's **Teacher Name** (hidden on small screens)
- **Admin** link — visible only when `is_admin = true`; underlined when on admin pages
- **Stats** link — underlined when active
- **Sign Out** form button

**Right side (guest / unverified):**
- **Sign In** button (text-only, dispatches `open-auth-modal`)

**No sub-navigation below branding.** The three nav links (Browse All, My Plans, Upload New Lesson) that previously appeared below the header subtitle have been removed. All navigation is via the header or by direct URL.

### 17.4 Footer

Simple centered text: "© {year} ARES Education — Kenya Lesson Plan Repository" in `text-gray-400`. Separated from content by `border-t border-gray-200` and `mt-16` margin.

### 17.5 Form Styling

- Labels: `text-sm font-medium text-gray-700 mb-1`
- Inputs/selects: `border border-gray-300 rounded-md px-3 py-2 text-sm` with gray-400 focus ring
- Password inputs: Include a "Show/Hide" toggle button positioned absolutely within the input field (Alpine.js controlled)
- File inputs: Custom Tailwind file input styling with gray-100 background
- Error messages: `text-red-600 text-xs mt-1`
- Primary buttons: `bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700`
- Secondary buttons: `bg-gray-100 text-gray-900 border border-gray-300 hover:bg-gray-200`
- Cancel links: `text-sm text-gray-500 hover:text-gray-900`

### 17.6 Table Styling

- Container: bordered card with rounded corners and `overflow-hidden`
- Header: `bg-gray-50 border-b border-gray-200`, uppercase tiny labels
- Rows: `hover:bg-gray-50`, divided by `divide-y divide-gray-100`
- Cell padding: `px-4 py-3`
- Pagination: below table in gray-50 footer band

### 17.7 Card Styling

Content sections use bordered cards: `border border-gray-200 rounded-lg p-6`. No shadows (the design is flat/minimal).

### 17.8 Responsive Behavior

- Header collapses: branding text stacks vertically, user email hidden on mobile
- Navigation links wrap with `flex-wrap gap-4`
- Dashboard search bar fields wrap responsively
- Table uses horizontal scroll on small screens (`overflow-x-auto`)
- Plan detail grid stacks from 3-column to single-column
- Forms are max-width constrained (`max-w-2xl`) and center themselves

---

## 18. Application Routes

> **Modularity note:** This is a reference table of all routes. For behavior details, see the individual page sections above.

### 18.1 Public Routes

| Method | URI | Controller@Method | Name |
|---|---|---|---|
| GET | `/` | DashboardController@index | `dashboard` |
| GET | `/stats` | DashboardController@stats | `stats` |
| POST | `/users/{user}/send-verification` | DashboardController@sendVerification | `users.send-verification` |

### 18.2 Authenticated + Verified Routes

| Method | URI | Controller@Method | Name |
|---|---|---|---|
| GET | `/lesson-plans/{lessonPlan}` | LessonPlanController@show | `lesson-plans.show` |
| GET | `/lesson-plans/{lessonPlan}/preview` | LessonPlanController@preview | `lesson-plans.preview` |
| GET | `/lesson-plans/{lessonPlan}/download` | LessonPlanController@download | `lesson-plans.download` |
| GET | `/my-plans` | LessonPlanController@myPlans | `my-plans` |
| GET | `/lesson-plans-create` | LessonPlanController@create | `lesson-plans.create` |
| POST | `/lesson-plans` | LessonPlanController@store | `lesson-plans.store` |
| GET | `/lesson-plans/{lessonPlan}/new-version` | LessonPlanController@edit | `lesson-plans.new-version` |
| PUT | `/lesson-plans/{lessonPlan}` | LessonPlanController@update | `lesson-plans.update` |
| DELETE | `/lesson-plans/{lessonPlan}` | LessonPlanController@destroy | `lesson-plans.destroy` |
| POST | `/lesson-plans/{lessonPlan}/vote` | VoteController@store | `votes.store` |
| POST | `/lesson-plans/{lessonPlan}/favorite` | FavoriteController@toggle | `favorites.toggle` *(not yet implemented)* |

### 18.3 Admin Routes (auth + verified + is_admin)

Prefix: `/admin`. Middleware: `['auth', 'verified', AdminMiddleware::class]`.

| Method | URI | Controller@Method | Name |
|---|---|---|---|
| GET | `/admin` | AdminController@index | `admin.index` |
| DELETE | `/admin/lesson-plans/{lessonPlan}` | AdminController@destroyPlan | `admin.lesson-plans.destroy` |
| POST | `/admin/lesson-plans/bulk-delete` | AdminController@bulkDestroyPlans | `admin.lesson-plans.bulk-delete` |
| DELETE | `/admin/users/{user}` | AdminController@destroyUser | `admin.users.destroy` |
| POST | `/admin/users/bulk-delete` | AdminController@bulkDestroyUsers | `admin.users.bulk-delete` |

### 18.4 Auth Routes (Breeze)

Standard Laravel Breeze routes in `routes/auth.php`: login, register, logout, password reset, email verification. The email verification route (`verify-email/{id}/{hash}`) is outside the `auth` middleware group — see Section 3.4.

---

## 19. Validation Rules

> **Modularity note:** This section lists all validation rules. Changes here should NOT require reading any other section.

### 19.1 Upload / New Version (StoreLessonPlanRequest)

| Field | Rules |
|---|---|
| class_name | required, string, max 100 characters |
| lesson_day | required, integer, min 1, max 20 |
| description | nullable, string, max 2000 characters |
| file | required, max 1024 KB (1 MB), mimes: doc,docx,txt,rtf,odt |

### 19.2 Voting

| Field | Rules |
|---|---|
| value | required, integer, must be -1 or 1 |

### 19.3 Login / Registration (`AuthenticatedSessionController`)

The same form is used for both. Validation differs by case:

| Field | New account (Case 1) | Existing unverified (Case 2) | Existing verified (Case 3) |
|---|---|---|---|
| name (Teacher Name) | required, non-empty, unique in `users.name` | ignored | ignored |
| email | required, valid email | required | required |
| password | stored (hashed) | ignored | validated against hash |

Validation is handled in `AuthenticatedSessionController@store`, not via a Form Request class.

### 19.4 Dashboard Sort Parameters

| Parameter | Validation |
|---|---|
| sort | Must be in whitelist: `class_name`, `lesson_day`, `author_name`, `semantic_version`, `vote_score`, `updated_at` |
| order | Must be 'asc' or 'desc' (case-insensitive); defaults to 'desc' |

---

## 20. Error Handling

> **Modularity note:** This section lists all error scenarios. Changes here should NOT require reading any other section.

### 20.1 Upload Errors

- **Duplicate canonical name:** Redirects back with error message explaining the collision; suggests waiting a moment and trying again.
- **File too large:** Client-side JavaScript checks file size before form submission. Server-side validation rejects files over 1 MB.
- **File validation failure:** Standard Laravel validation; field-level error messages displayed below each form field.
- **Email failure:** Logged but not shown to the user; the upload itself succeeds.

### 20.2 Delete Errors

- **Not the author:** Returns HTTP 403 with message "You can only delete your own lesson plans."
- **Root plan with children:** Redirects back with error message explaining that newer versions must be deleted first.

### 20.3 Vote Errors

- **Self-vote:** Redirects back with error flash message: "You cannot vote on your own lesson plan."
- **Invalid value:** Standard validation error (value must be -1 or 1).

### 20.4 File Not Found

If a lesson plan's file is missing from disk, the download route redirects back with "File not found." error.

---

## 21. Security Considerations

### 21.1 Authentication

- Passwords are Bcrypt-hashed (Laravel default)
- Email verification required for all authenticated actions
- CSRF protection on all POST/PUT/DELETE routes
- Session uses secure cookies in production (`SESSION_SECURE_COOKIE=true`)

### 21.2 Input Validation

- All user input is validated server-side via Form Request classes
- Sort column and direction are validated against whitelists
- File uploads are validated for size (1 MB max) and MIME type (via `StoreLessonPlanRequest`)
- A second file-extension check is performed in `LessonPlanController::persistUploadedFile()` using `$file->extension()`, which derives the extension from the MIME type (via `finfo`) rather than from the client-supplied filename — prevents extension spoofing where the attacker names a file `evil.php` but sends a DOCX MIME type
- Class names are validated as strings (max 100 characters)

### 21.3 Authorization

- Author identity is always set server-side to `Auth::id()` — never from user input
- Delete operations verify author ownership in the controller
- Vote self-prevention is enforced in the controller

### 21.4 File Security

- Uploaded files are stored outside the web root (in `storage/app/public/lessons/`)
- Files are served through Laravel's `Storage::download()` method
- SHA-256 hashing detects duplicate content
- All uploaded files are renamed to canonical format — original filenames are discarded

### 21.5 Production Settings

- `APP_DEBUG=false` in production (prevents stack trace exposure)
- `APP_ENV=production`
- `.env` file excluded from git
- Config is cached (`php artisan config:cache`) so `.env` is not read at runtime

---

## 22. Artisan Commands

### 22.1 `lessons:detect-duplicates`

**Signature:** `lessons:detect-duplicates {--dry-run}`

**Behavior:**
1. Back-fills `file_hash` for any records where it is NULL
2. Groups all lesson plans by `file_hash` and identifies groups with more than one record
3. Keeps the record with the lowest `id` (earliest upload) and marks later duplicates for deletion
4. **Lineage protection:** Skips any duplicate that has dependent versions
5. For each safe-to-remove duplicate: deletes the stored file, emails the author, deletes associated votes, deletes the database record
6. With `--dry-run`: shows what would happen but takes no action

**Scheduling:** Recommended to run daily at 2:00 AM via cron.

---

## 23. Configuration Constants

### 23.1 Class Names

Class names are not restricted to a fixed list. The upload and edit forms present a dropdown of all existing class names currently in the database, plus an "Other" option that allows teachers to type any new class name (max 100 characters). The initial seed classes (English, History, Mathematics, Science) are defined in `LessonPlanController::CLASS_NAMES` and used to populate the dropdown when no plans exist yet.

### 23.2 Lesson Number Range

1 through 20, defined by `range(1, 20)` in the controller methods.

### 23.3 Pagination

| Page | Items per page |
|---|---|
| Dashboard | 10 |
| My Plans | 25 |

### 23.4 File Upload Limits

| Setting | Value |
|---|---|
| Max file size | 1 MB (1024 KB) |
| Accepted MIME types | doc, docx, txt, rtf, odt |

---

## 24. File Inventory

### 24.1 Controllers

| File | Responsibility |
|---|---|
| `DashboardController` | Public homepage (search, filter, sort, counters, loads userVotes + viewedIds) + Stats page |
| `LessonPlanController` | CRUD for lesson plans: upload, show (records view), preview, new version, delete, download |
| `VoteController` | Cast/toggle votes; returns JSON for AJAX requests |
| `AdminController` | Admin panel: per-row and bulk delete for plans and users |
| `FavoriteController` | Toggle favorite status on lesson plans (AJAX POST, returns JSON) |
| `Auth/AuthenticatedSessionController` | Custom three-case login+register (new/unverified/verified) |
| `Auth/VerifyEmailController` | Custom email verification: works without active session (validates signed URL, logs user in) |

### 24.2 Models

| File | Responsibility |
|---|---|
| `User` | Auth user; Teacher Name (unique); `is_admin` flag; MustVerifyEmail |
| `LessonPlan` | Plan version with versioning, canonical naming, vote caching, family queries |
| `Vote` | Single upvote/downvote on a lesson plan version |
| `LessonPlanView` | View tracking pivot (user_id + lesson_plan_id, created_at only) |
| `Favorite` | User-plan favorite relationship (`user_id`, `lesson_plan_id`, `created_at`) |

### 24.3 Middleware

| File | Responsibility |
|---|---|
| `AdminMiddleware` | Checks `is_admin = true`; aborts 403 otherwise. Applied to `/admin` route group. |

### 24.4 Views

| File | Responsibility |
|---|---|
| `components/layout.blade.php` | Master layout: header, merged auth modal, Admin link, upload dialog, footer |
| `components/vote-buttons.blade.php` | 4-mode vote component (readonly / locked / inline AJAX / form) |
| `dashboard.blade.php` | Main public page with counters, search/filter/sort table, AJAX vote buttons |
| `stats.blade.php` | Archive statistics page with detailed breakdowns |
| `admin/index.blade.php` | Admin panel: lesson plans + users tables with delete/bulk-delete |
| `lesson-plans/show.blade.php` | Plan detail page with voting, version history, Print button |
| `lesson-plans/preview.blade.php` | Document preview with embedded Google Docs Viewer |
| `lesson-plans/create.blade.php` | Upload form for new plans (submit button gated on valid file) |
| `lesson-plans/edit.blade.php` | New version form (submit button gated on valid file) |
| `lesson-plans/my-plans.blade.php` | Authenticated user's own plan list |
| `auth/login.blade.php` | Standalone login page (fallback for modal validation failures) |
| `auth/register.blade.php` | Standalone registration page (fallback) |
| `auth/forgot-password.blade.php` | Password reset request form (enter email) |
| `auth/reset-password.blade.php` | Set new password form (from email link) |
| `auth/verify-email.blade.php` | Email verification notice page with Resend button |

---

## 25. Dependencies

### 25.1 PHP Packages (via Composer)

| Package | Purpose |
|---|---|
| laravel/framework ^12.0 | Core framework |
| laravel/breeze | Authentication scaffolding (Blade stack) |

### 25.2 Frontend (via CDN — No Build Step)

| Library | CDN URL | Purpose |
|---|---|---|
| Tailwind CSS | `https://cdn.tailwindcss.com` | Utility-first CSS framework |
| Alpine.js 3.x | `https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js` | Lightweight JS reactivity for modals, show/hide toggles |

No `package.json`, no `node_modules`, no Vite, no Webpack.
