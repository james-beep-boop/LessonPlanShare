# ARES Education Lesson Plan Archive — Technical Design Document

**Version:** 1.0
**Date:** February 2026
**Status:** Implemented

---

## 1. Project Overview

### 1.1 Purpose

The ARES Education Lesson Plan Archive is a web application that allows high school teachers to upload, share, version, rate, and download lesson plan documents. It serves a small group of educators affiliated with the ARES Education program, providing a centralized repository for collaborative lesson planning.

### 1.2 Target Users

A closed community of 5–30 high school teachers who share lesson plan documents with each other. All users know each other as colleagues; the system is not designed for the general public, though the browsing/download interface is publicly accessible.

### 1.3 Hosting Environment

The application is deployed on DreamHost shared hosting (www.sheql.com) with the following constraints that shape all technical decisions:

- No root/sudo access; shared PHP environment (PHP 8.4)
- MySQL 8.0 on a remote host (mysql.sheql.com)
- No Node.js runtime; no build tools (Vite, Webpack)
- No Redis or Memcached; file-based sessions and cache
- No background workers (queues); all operations are synchronous
- SMTP email via DreamHost mail server (mail.sheql.com, port 587, TLS)
- HTTPS via DreamHost's free Let's Encrypt integration
- Cron jobs available for scheduled commands

### 1.4 Technology Stack

| Component | Technology | Rationale |
|---|---|---|
| Backend Framework | Laravel 12 | PHP framework with built-in auth, ORM, migrations |
| Authentication | Laravel Breeze (Blade) | Lightweight auth scaffolding; no SPA complexity |
| CSS Framework | Tailwind CSS via CDN | No build step required; instant styling |
| JavaScript | Alpine.js via CDN | Lightweight reactivity for modals; no build step |
| Database | MySQL 8.0 | DreamHost-provided; standard Laravel support |
| File Storage | Local disk (`storage/app/public`) | Simplest option for shared hosting |
| Email | SMTP (DreamHost) | Direct SMTP; no third-party mail service needed |
| Session/Cache | File driver | No Redis available on shared hosting |

---

## 2. Information Architecture

### 2.1 Data Model

The application has two custom database tables beyond Laravel's default `users` table.

#### 2.1.1 `users` Table (Laravel Default + Customizations)

| Column | Type | Notes |
|---|---|---|
| id | bigint unsigned PK | Auto-increment |
| name | varchar(255) | Set to the user's email address at registration |
| email | varchar(255) unique | Login identifier; same value as `name` |
| email_verified_at | timestamp nullable | Set when user clicks verification link |
| password | varchar(255) | Bcrypt-hashed |
| remember_token | varchar(100) nullable | Laravel session persistence |
| created_at / updated_at | timestamps | Standard Laravel |

**Design decision — name equals email:** The registration form has a single "Username" field that accepts an email address. The submitted value is stored in both the `name` and `email` columns. This simplifies the UI while maintaining compatibility with Laravel's auth system, which expects a `name` column. The User model implements `MustVerifyEmail`, requiring users to click a confirmation email link before accessing authenticated routes.

#### 2.1.2 `lesson_plans` Table

| Column | Type | Notes |
|---|---|---|
| id | bigint unsigned PK | Auto-increment |
| class_name | varchar(255) | Subject name from restricted list |
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

#### 2.1.3 `votes` Table

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

### 2.2 Version Family Model

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

**Dashboard default:** Only the latest version per family is shown (using a subquery: `MAX(id)` grouped by `COALESCE(original_id, id)`). A "Show all versions" checkbox reveals every version.

**Deletion guard:** The root plan cannot be deleted if child versions exist, because `original_id` uses `onDelete('set null')`, which would orphan the family linkage. Users must delete children first.

### 2.3 Canonical Naming

Every uploaded document is renamed to a canonical format:

```
{ClassName}_Day{N}_{AuthorName}_{YYYYMMDD_HHMMSS}UTC.{ext}
```

Example: `Mathematics_Day5_davidsheqlcom_20260221_143022UTC.pdf`

**Sanitization rules:**
- Spaces → hyphens
- All characters except A-Z, a-z, 0-9, and hyphen are stripped
- The `@` and `.` in email addresses are removed (e.g., `david@sheql.com` → `davidsheqlcom`)
- Timestamp is always UTC

**Uniqueness:** The combination of class + day + author + second-resolution timestamp ensures unique names. A server-side guard rejects uploads if an identical canonical name already exists (which can only happen if the same user uploads the same class/day within the same second).

### 2.4 File Storage

- Files are stored at `storage/app/public/lessons/{canonical_name}.{ext}`
- The `storage:link` artisan command creates a symlink from `public/storage` → `storage/app/public`
- Files are served via the public disk for direct download
- Maximum file size: 10 MB (enforced by validation and PHP settings)
- Accepted formats: PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX, TXT, RTF, ODT, ODP, ODS

### 2.5 Duplicate Content Detection

A SHA-256 hash of each uploaded file's contents is computed and stored in `file_hash`. An artisan command (`lessons:detect-duplicates`) runs on a cron schedule to:

1. Back-fill hashes for any records missing them
2. Group records by hash and identify groups with more than one record
3. Keep the earliest upload (lowest id) and delete all later duplicates
4. Delete the stored file and database record for each duplicate
5. Email the author of each deleted duplicate explaining what happened

The command supports a `--dry-run` flag for preview-only execution.

---

## 3. Authentication and Authorization

### 3.1 Registration Flow

1. User clicks "Sign In" button in the header → Alpine.js modal opens on the "Sign In" panel.
2. User clicks "Sign Up" link within the modal → switches to the "Create Account" panel.
3. Registration form fields: Username (email address), Password, Confirm Password.
4. On submit, the `RegisteredUserController` validates the email, creates the User record (setting `name` = `email`), fires the `Registered` event (which sends a verification email), and logs the user in.
5. The user is redirected to the email verification notice page. They cannot access authenticated routes until they click the link in their verification email.

### 3.2 Authentication Flow

1. User clicks "Sign In" button → modal opens on the "Sign In" panel.
2. Form fields: Username (email), Password.
3. Standard Laravel Breeze login; on success, redirects to dashboard.
4. On failure, modal reopens with validation error messages.
5. The modal remembers which panel (login/register) was active via a hidden `_auth_mode` field, so validation errors reopen the correct panel.

### 3.3 Authorization Rules

| Action | Who Can Do It |
|---|---|
| Browse all plans (dashboard) | Anyone (public) |
| View a single plan | Anyone (public) |
| Download a file | Anyone (public) |
| Upload a new plan | Authenticated + verified email |
| Create a new version | Authenticated + verified email |
| Vote on a plan | Authenticated + verified email (not the author) |
| Delete a plan | Authenticated + verified email (only the plan's author) |
| View "My Plans" | Authenticated + verified email |

Authorization is enforced via Laravel middleware (`['auth', 'verified']`) on route groups, plus controller-level guards for author-specific actions (delete, self-vote prevention).

---

## 4. Application Routes

### 4.1 Public Routes

| Method | URI | Controller@Method | Name | Description |
|---|---|---|---|---|
| GET | `/` | DashboardController@index | `dashboard` | Main page with searchable/sortable plan table |
| GET | `/lesson-plans/{id}` | LessonPlanController@show | `lesson-plans.show` | Single plan detail page |
| GET | `/lesson-plans/{id}/download` | LessonPlanController@download | `lesson-plans.download` | File download |

### 4.2 Authenticated + Verified Routes

| Method | URI | Controller@Method | Name | Description |
|---|---|---|---|---|
| GET | `/my-plans` | LessonPlanController@myPlans | `my-plans` | Logged-in user's own plans |
| GET | `/lesson-plans-create` | LessonPlanController@create | `lesson-plans.create` | Upload form |
| POST | `/lesson-plans` | LessonPlanController@store | `lesson-plans.store` | Process new upload |
| GET | `/lesson-plans/{id}/new-version` | LessonPlanController@edit | `lesson-plans.new-version` | New version form |
| PUT | `/lesson-plans/{id}` | LessonPlanController@update | `lesson-plans.update` | Process new version |
| DELETE | `/lesson-plans/{id}` | LessonPlanController@destroy | `lesson-plans.destroy` | Delete a plan |
| POST | `/lesson-plans/{id}/vote` | VoteController@store | `votes.store` | Cast/toggle vote |

### 4.3 Auth Routes (Breeze)

Standard Laravel Breeze routes in `routes/auth.php`: login, register, logout, password reset, email verification notice/send/verify.

---

## 5. Page-by-Page Specifications

### 5.1 Dashboard (Home Page — `/`)

**Layout:** Full-width table with search/filter bar above it. Public — no login required to browse.

**Search & Filter Bar** (contained in a bordered card):
- **Search** (text input): Free-text search across document name, class name, description, and author name. Uses SQL `LIKE %term%` queries.
- **Class** (dropdown): Filters by class name. Options are dynamically populated from the distinct `class_name` values that exist in the database (not from the upload whitelist).
- **Show all versions** (checkbox): When unchecked (default), shows only the latest version of each plan family. When checked, shows every version as a separate row.
- **Search** button (gray-900) and **Clear** link.

**Results Table** columns (all sortable by clicking the header):
1. **Document Name** — clickable link to the plan detail page; displays the canonical name
2. **Class** — subject name
3. **Day #** — lesson number (centered)
4. **Version** — displayed as "v1", "v2", etc. (centered)
5. **Author** — email address of the plan's author
6. **Rating** — vote score with green up-arrow (positive), red down-arrow (negative), or plain zero; uses the `vote-buttons` component in readonly mode
7. **Updated** — date in "Mon D, YYYY" format
8. **File** — "Download" link if file exists, dash otherwise

**Sorting:** Clicking a column header sorts by that column. A second click on the same column reverses the direction. The active sort column shows an up/down triangle indicator. Default sort: `updated_at DESC` (most recent first). Sorting by "Author" requires a JOIN to the `users` table. Sort direction is validated server-side to only allow `asc` or `desc`.

**Pagination:** 10 rows per page. Standard Laravel pagination links. A summary line below the table shows "Showing X–Y of Z plans".

### 5.2 Plan Detail Page (`/lesson-plans/{id}`)

**Layout:** Two-column grid on large screens (2/3 + 1/3), stacks vertically on mobile. Public — no login required.

**Left Column — Plan Details Card:**
- Header: "{Class Name} — Day {N}" as the page heading
- Subheading: "Version {N} · by {author} · {date} UTC"
- Monospace canonical name below the subheading
- Description text (or "No description provided" in italic)
- Detail grid (2 columns): Class, Lesson Day, Version, Author, File (name + formatted size), Uploaded date
- Action buttons:
  - **Download File** (gray-900 button) — always visible if file exists
  - **Create New Version** (gray-100 outlined button) — visible only to authenticated users
  - **Delete** (red-50 button) — visible only to the plan's author; confirms via browser `confirm()` dialog

**Left Column — Community Rating Card:**
- Large vote score number (green if positive, red if negative, gray if zero)
- Text: "{N} upvotes, {N} downvotes"
- **If authenticated and not the author:** Interactive upvote/downvote buttons (chevron arrows). The active vote direction is highlighted (green background for upvote, red for downvote). Clicking the same direction again removes the vote (toggle off). Clicking the opposite direction switches the vote. A helper text appears when a vote is active: "Click the same arrow again to remove your vote."
- **If authenticated and is the author:** Text: "You cannot vote on your own lesson plan."
- **If not authenticated:** "Sign in to vote on this plan." with a clickable "Sign in" link that opens the auth modal.

**Right Column — Version History Card:**
- Lists all versions in the plan's family, ordered by version number ascending
- Each entry shows: circular badge with "v{N}", class + day label, author, date, vote score
- The current version is highlighted with a gray background
- Other versions are clickable links to their detail pages

### 5.3 Upload Form (`/lesson-plans-create`)

**Layout:** Centered, max-width 2xl form in a bordered card. Requires authentication + verified email.

**Form fields:**
1. **Class Name** (required dropdown): Options from a PHP constant — currently: English, Mathematics, Science. To add subjects, append to the `LessonPlanController::CLASS_NAMES` array.
2. **Lesson Number** (required dropdown): Numbers 1 through 20. Small (w-32) dropdown.
3. **Author** (read-only display): Shows the logged-in user's email address in a gray-50 bordered box. Text: "Plans are always uploaded under your account." Author is always `Auth::id()`.
4. **Description** (optional textarea): 4 rows, max 2000 characters.
5. **Document Name** (info box): Gray-50 box showing the naming format: `{ClassName}_Day{N}_{AuthorName}_{UTC-Timestamp}`.
6. **Lesson Plan File** (required file input): Styled file input. Max 10 MB. Accepted MIME types listed in helper text.
7. **Upload Lesson Plan** button (gray-900) + **Cancel** link.

**On submit:**
- Validates all fields via `StoreLessonPlanRequest`
- Generates canonical name from fields + current UTC timestamp
- Checks for duplicate canonical name (rejects if exists)
- Stores file with canonical name in `storage/app/public/lessons/`
- Computes SHA-256 hash
- Creates `LessonPlan` record with `version_number = 1`, no `original_id` or `parent_id`
- Sends confirmation email to the uploader (wrapped in try/catch; failure is logged, not blocking)
- Redirects to plan detail page with upload-success dialog

### 5.4 New Version Form (`/lesson-plans/{id}/new-version`)

**Layout:** Same as Upload Form, but with an additional instruction box at the top.

**Top instruction box** (gray-50):
- Text: "Step 1: Download the current version, make your improvements, then upload the revised file below."
- **Download v{N}** button (gray-900) — downloads the parent version's file

**Form fields:** Identical to Upload Form, with the following differences:
- Class Name and Lesson Number are pre-filled from the parent version (can be changed)
- Description is pre-filled from the parent version

**On submit:**
- Same validation and naming as Upload Form
- Creates record with `original_id` = root of parent's family, `parent_id` = parent's id
- `version_number` = `MAX(version_number)` in the family + 1

### 5.5 My Plans (`/my-plans`)

**Layout:** Table similar to dashboard, filtered to only plans where `author_id = Auth::id()`. Requires authentication + verified email.

**Header:** "My Lesson Plans" heading + "+ Upload New Plan" button (gray-900).

**Table columns:** Document Name (linked), Class, Day #, Version, Rating (readonly), Updated, Actions (Download link + Delete button).

**Pagination:** 25 per page. Sorted by `updated_at DESC`.

**Empty state:** "You haven't uploaded any lesson plans yet. Upload your first one!" with a link to the upload form.

### 5.6 Auth Modal

A single Alpine.js modal dialog that handles both sign-in and registration. It is injected into the layout for all guest (unauthenticated) visitors.

**Trigger:** Clicking the "Sign In" button in the top-right header dispatches an Alpine.js event (`open-auth-modal`) that opens the modal.

**Sign In Panel:**
- Fields: Username (email input), Password
- Submit button: "Sign In" (full-width, gray-900)
- Footer: "New User? Sign Up" — switches to register panel

**Create Account Panel:**
- Fields: Username (email, with hint "must be a valid email"), Password, Confirm Password
- Submit button: "Sign Up" (full-width, gray-900)
- Footer: "Already have an account? Sign In" — switches to login panel

**Error handling:** If login/register fails validation, the modal reopens automatically (the `open` state is set to `true` when `$errors->any()` is true). The hidden `_auth_mode` field preserves which panel was active.

### 5.7 Upload Success Dialog

An Alpine.js modal that appears after a successful upload/new version. Triggered by the `upload_success` session flash.

**Contents:**
- Green checkmark icon
- "Upload Successful" heading
- "Your lesson plan has been saved as:" + monospace canonical filename
- "A confirmation email has been sent to your address."
- "OK" button (gray-900) — closes the dialog

### 5.8 Flash Messages

Three types of flash messages appear below the header:
- **Success** (green border/background): e.g., "Lesson plan deleted." or "Vote recorded."
- **Error** (red border/background): e.g., duplicate name, delete guard violation, self-vote attempt
- **Status** (blue border/background): general informational messages

---

## 6. Voting System

### 6.1 Behavior

Each user can cast exactly one vote per lesson plan version (enforced by a unique database index on `[lesson_plan_id, user_id]`).

Vote values: +1 (upvote) or -1 (downvote).

**Toggle behavior:**
- If the user has no existing vote → create a new vote with the submitted value
- If the user votes the same direction again → remove the vote entirely (toggle off)
- If the user votes the opposite direction → update the existing vote to the new value

**Self-vote prevention:** Authors cannot vote on their own plans. The controller checks `$lessonPlan->author_id === Auth::id()` and returns an error flash message if violated.

### 6.2 Cached Vote Score

To avoid expensive `SUM()` queries on every dashboard page load, each lesson plan has a `vote_score` column that caches the aggregate. After every vote action (create, delete, update), `recalculateVoteScore()` runs: it queries `SUM(value)` from the votes table and saves the result using `saveQuietly()` (which suppresses model events so observers are not triggered during this background recalculation).

### 6.3 Vote Buttons Component

The `vote-buttons` Blade component operates in two modes:

**Readonly mode** (used in table rows): Shows the score with a colored arrow indicator (green up for positive, red down for negative). No interactive elements.

**Interactive mode** (used on detail page): Two form-based buttons (upvote chevron, downvote chevron) with POST submissions. The active vote direction is highlighted with a colored background. A helper text appears when the user has an active vote.

---

## 7. Email System

### 7.1 Upload Confirmation

Sent to the authenticated user (the person performing the upload, who is always the author) after each successful upload or new version creation.

**Mailable:** `App\Mail\LessonPlanUploaded`

**Data passed:** recipient name, canonical filename, class name, lesson day, version number, URL to view the plan.

**Failure handling:** Wrapped in try/catch. If the email fails to send, the error is logged to `storage/logs/laravel.log` but the upload itself succeeds normally.

### 7.2 Duplicate Content Removed

Sent to the author of a deleted duplicate when the `DetectDuplicateContent` command removes their file.

**Mailable:** `App\Mail\DuplicateContentRemoved`

**Data passed:** recipient name, deleted plan name, kept plan name, kept plan's author name.

### 7.3 Email Verification

Standard Laravel/Breeze email verification. Triggered by the `Registered` event after registration. Uses the same SMTP configuration.

### 7.4 SMTP Configuration

| Setting | Value |
|---|---|
| Driver | smtp |
| Host | mail.sheql.com |
| Port | 587 |
| Encryption | TLS |
| Username | david@sheql.com (full email address) |
| From Address | david@sheql.com |
| From Name | ARES Education |

---

## 8. Visual Design Specification

### 8.1 Design Language

The application uses a clean, monochromatic black-and-white design language with minimal color.

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

### 8.2 Layout Structure

**Max width:** `max-w-6xl` (72rem / 1152px), centered with auto margins
**Horizontal padding:** `px-4 sm:px-6 lg:px-8`
**Vertical rhythm:** `py-8` for main content, `py-6 sm:py-8` for header

### 8.3 Header

**Structure:** Top-level `<header>` with a bottom border (`border-b border-gray-200`).

**Left side:** ARES logo image (`h-14 sm:h-16`) + "ARES Education" heading (`text-3xl sm:text-4xl font-bold text-gray-900`) + "Lesson Plan Archive" subtitle (`text-base sm:text-lg text-gray-500`). All wrapped in a link to the dashboard.

**Right side:**
- Authenticated: user's email address (hidden on small screens) + "Sign Out" link
- Guest: "Sign In" button (no background, just text)

**Navigation** (below branding, only when authenticated): "Browse All", "My Plans", "+ Upload Plan" — horizontal links with active state indicated by underline.

### 8.4 Footer

Simple centered text: "© {year} ARES Education" in `text-gray-400`. Separated from content by `border-t border-gray-200` and `mt-16` margin.

### 8.5 Form Styling

- Labels: `text-sm font-medium text-gray-700 mb-1`
- Inputs/selects: `border border-gray-300 rounded-md px-3 py-2 text-sm` with gray-400 focus ring
- File inputs: Custom Tailwind file input styling with gray-100 background
- Error messages: `text-red-600 text-xs mt-1`
- Primary buttons: `bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700`
- Secondary buttons: `bg-gray-100 text-gray-900 border border-gray-300 hover:bg-gray-200`
- Cancel links: `text-sm text-gray-500 hover:text-gray-900`

### 8.6 Table Styling

- Container: bordered card with rounded corners and `overflow-hidden`
- Header: `bg-gray-50 border-b border-gray-200`, uppercase tiny labels
- Rows: `hover:bg-gray-50`, divided by `divide-y divide-gray-100`
- Cell padding: `px-4 py-3`
- Pagination: below table in gray-50 footer band

### 8.7 Card Styling

Content sections use bordered cards: `border border-gray-200 rounded-lg p-6`. No shadows (the design is flat/minimal).

### 8.8 Responsive Behavior

- Header collapses: logo + text stack, user email hidden on mobile
- Navigation links wrap with `flex-wrap gap-4`
- Dashboard search bar fields wrap responsively
- Table uses horizontal scroll on small screens (`overflow-x-auto`)
- Plan detail grid stacks from 3-column to single-column
- Forms are max-width constrained (`max-w-2xl`) and center themselves

---

## 9. Artisan Commands

### 9.1 `lessons:detect-duplicates`

**Signature:** `lessons:detect-duplicates {--dry-run}`

**Behavior:**
1. Back-fills `file_hash` for any records where it is NULL (reads the file from disk and computes SHA-256)
2. Groups all lesson plans by `file_hash` and identifies groups with more than one record
3. For each group, keeps the record with the lowest `id` (earliest upload) and marks all others for deletion
4. For each duplicate to be deleted: deletes the stored file, emails the author, deletes associated votes, deletes the database record
5. With `--dry-run`: shows what would happen but takes no action

**Scheduling:** Recommended to run daily at 2:00 AM via cron.

---

## 10. Validation Rules

### 10.1 Upload / New Version (StoreLessonPlanRequest)

| Field | Rules |
|---|---|
| class_name | required, string, must be one of the values in `LessonPlanController::CLASS_NAMES` |
| lesson_day | required, integer, min 1, max 20 |
| description | nullable, string, max 2000 characters |
| file | required, max 10240 KB, mimes: pdf,doc,docx,ppt,pptx,xls,xlsx,txt,rtf,odt,odp,ods |

### 10.2 Voting

| Field | Rules |
|---|---|
| value | required, integer, must be -1 or 1 |

### 10.3 Registration

| Field | Rules |
|---|---|
| email | required, string, lowercase, valid email, max 255, unique in users table |
| password | required, confirmed, meets Laravel's default password rules |

### 10.4 Dashboard Sort Parameters

| Parameter | Validation |
|---|---|
| sort | Must be in whitelist: name, class_name, lesson_day, version_number, vote_score, updated_at, created_at, author |
| order | Must be 'asc' or 'desc' (case-insensitive); defaults to 'desc' |

---

## 11. Error Handling

### 11.1 Upload Errors

- **Duplicate canonical name:** Redirects back with error message explaining the collision; suggests waiting a moment and trying again. (This only occurs if the same class/day/author uploads within the same second.)
- **File validation failure:** Standard Laravel validation; field-level error messages displayed below each form field.
- **Email failure:** Logged but not shown to the user; the upload itself succeeds.

### 11.2 Delete Errors

- **Not the author:** Returns HTTP 403 with message "You can only delete your own lesson plans."
- **Root plan with children:** Redirects back with error message explaining that newer versions must be deleted first.

### 11.3 Vote Errors

- **Self-vote:** Redirects back with error flash message: "You cannot vote on your own lesson plan."
- **Invalid value:** Standard validation error (value must be -1 or 1).

### 11.4 File Not Found

If a lesson plan's file is missing from disk (e.g., manually deleted), the download route redirects back with "File not found." error.

---

## 12. Security Considerations

### 12.1 Authentication

- Passwords are Bcrypt-hashed (Laravel default)
- Email verification required for all authenticated actions
- CSRF protection on all POST/PUT/DELETE routes
- Session uses secure cookies in production (`SESSION_SECURE_COOKIE=true`)

### 12.2 Input Validation

- All user input is validated server-side via Form Request classes
- Sort column and direction are validated against whitelists
- File uploads are validated for size and MIME type
- Class names are restricted to a PHP constant whitelist

### 12.3 Authorization

- Author identity is always set server-side to `Auth::id()` — never from user input
- Delete operations verify author ownership in the controller
- Vote self-prevention is enforced in the controller

### 12.4 File Security

- Uploaded files are stored outside the web root (in `storage/app/public/lessons/`)
- Files are served through Laravel's `Storage::download()` method
- SHA-256 hashing detects duplicate content

### 12.5 Production Settings

- `APP_DEBUG=false` in production (prevents stack trace exposure)
- `APP_ENV=production`
- `.env` file excluded from git
- Config is cached (`php artisan config:cache`) so `.env` is not read at runtime

---

## 13. File Inventory

See `DEPLOYMENT.md` for the complete file listing and deployment instructions.

### 13.1 Controllers

| File | Responsibility |
|---|---|
| `DashboardController` | Public homepage: search, filter, sort, paginate lesson plans |
| `LessonPlanController` | CRUD for lesson plans: upload, show, new version, delete, download |
| `VoteController` | Cast/toggle votes on lesson plan versions |
| `Auth/RegisteredUserController` | Custom registration: single email field serves as name + email |

### 13.2 Models

| File | Responsibility |
|---|---|
| `User` | Auth user with MustVerifyEmail; name = email at registration |
| `LessonPlan` | Plan version with versioning, canonical naming, vote caching, family queries |
| `Vote` | Single upvote/downvote on a lesson plan version |

### 13.3 Views

| File | Responsibility |
|---|---|
| `components/layout.blade.php` | Master layout: header, logo, auth modal, upload dialog, footer |
| `components/vote-buttons.blade.php` | Reusable vote display/interaction component |
| `dashboard.blade.php` | Main public page with search/filter/sort table |
| `lesson-plans/show.blade.php` | Plan detail page with voting and version history |
| `lesson-plans/create.blade.php` | Upload form for new plans |
| `lesson-plans/edit.blade.php` | New version form (based on existing plan) |
| `lesson-plans/my-plans.blade.php` | Authenticated user's own plan list |
| `auth/verify-email.blade.php` | Email verification notice page |

---

## 14. Configuration Constants

### 14.1 Allowed Class Names

Defined in `LessonPlanController::CLASS_NAMES`:

```php
public const CLASS_NAMES = ['English', 'Mathematics', 'Science'];
```

To add new subjects, append to this array. The `StoreLessonPlanRequest` validation references this constant dynamically.

### 14.2 Lesson Number Range

1 through 20, defined by `range(1, 20)` in the controller methods.

### 14.3 Pagination

| Page | Items per page |
|---|---|
| Dashboard | 10 |
| My Plans | 25 |

### 14.4 File Upload Limits

| Setting | Value |
|---|---|
| Max file size | 10 MB (10240 KB) |
| Accepted MIME types | pdf, doc, docx, ppt, pptx, xls, xlsx, txt, rtf, odt, odp, ods |

---

## 15. Dependencies

### 15.1 PHP Packages (via Composer)

| Package | Purpose |
|---|---|
| laravel/framework ^12.0 | Core framework |
| laravel/breeze | Authentication scaffolding (Blade stack) |

### 15.2 Frontend (via CDN — No Build Step)

| Library | CDN URL | Purpose |
|---|---|---|
| Tailwind CSS | `https://cdn.tailwindcss.com` | Utility-first CSS framework |
| Alpine.js 3.x | `https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js` | Lightweight JS reactivity for modals |

No `package.json`, no `node_modules`, no Vite, no Webpack.
