# LessonPlanShare Stress Test Plan

## 1) Purpose
This test set is designed to stress the highest-risk areas in the current codebase:
- Authentication + email verification flow
- Authorization boundaries
- Upload, preview, and download paths
- Versioning rules and deletion guards
- Voting correctness under repeated actions
- Duplicate-content command behavior
- Deployment/config drift checks on DreamHost

This plan is written for the full Laravel install on server (`~/LessonPlanShare`), not the overlay repo alone.

## 2) Preconditions
- App deployed and reachable at `https://www.sheql.com`
- Mail configured (`MAIL_HOST=smtp.dreamhost.com`, `MAIL_PORT=587`, `MAIL_ENCRYPTION=tls`) — **must be `smtp.dreamhost.com`**, not `mail.dreamhost.com` (TLS cert mismatch on the latter)
- At least 3 test users:
  - `user_a` (verified)
  - `user_b` (verified)
  - `user_c` (unverified)
- At least one sample file per allowed type: `.doc`, `.docx`, `.txt`, `.rtf`, `.odt`
- One oversized file `>1MB`

## 3) Fast Smoke (run first)

### SM-01 Registration + verification email send
- Register a new account from the Sign In / Up modal (Teacher Name + email + password). Note: `/register` redirects to the dashboard — all registration happens through the modal.
- Expected: user record created, verification email sent, redirect to `/verify-email`.

### SM-02 Verification link validity
- Click fresh verification link in same browser and incognito window.
- Expected: user becomes verified and lands on dashboard; no 403.

### SM-03 Auth boundary
- As guest, open `/lesson-plans/{id}`.
- Expected: redirect to login/verification gate (not full content).

### SM-04 Upload + download round trip
- Upload valid file as verified user.
- Open detail, preview, and download.
- Expected: canonical filename stored, preview opens, download returns file.

### SM-05 Vote toggle
- As non-author: upvote, upvote again, downvote.
- Expected score sequence: `+1 -> 0 -> -1`.

## 4) Authentication & Verification Stress

### AUTH-01 Repeated registration attempts
- Attempt 20 registrations with same email.
- Expected: first succeeds, remaining fail with uniqueness validation.

### AUTH-02 Invalid email formats
- Submit malformed emails (`a@`, `abc`, `a@a`, unicode edge cases).
- Expected: validation reject; no user created.

### AUTH-03 Verification signature tamper
- Edit `hash`, `expires`, or `signature` query params in verification URL.
- Expected: 403 every time.

### AUTH-04 Expired link
- Wait beyond `expires` or force-expired URL in DB/test.
- Expected: 403 on expired link.

### AUTH-05 Cross-user verification link misuse
- While logged in as `user_b`, click `user_a` verification link.
- Expected: only `user_a` gets verified per link payload, no privilege escalation.

### AUTH-06 Password reset stress
- Request reset 10 times quickly for same user.
- Expected: throttling/normal handling, latest valid token works, invalid token rejected.

## 5) Authorization Stress

### AUTHZ-01 Unverified access block
- Log in as `user_c` (unverified), attempt:
  - `/lesson-plans/{id}`
  - `/lesson-plans/{id}/download`
  - `/lesson-plans-create`
- Expected: blocked by `verified` middleware.

### AUTHZ-02 Delete ownership enforcement
- `user_b` tries deleting `user_a` plan.
- Expected: 403.

### AUTHZ-03 Self-vote prevention
- Author tries voting own plan repeatedly.
- Expected: always rejected with error flash; no vote row created.

## 6) Upload/Download/Preview Stress

### FILE-01 Allowed MIME matrix
- Upload one file of each allowed extension.
- Expected: all accepted and downloadable.

### FILE-02 Block disallowed MIME
- Upload `.pdf`, `.exe`, renamed fake extension, and double extension (`file.doc.exe`).
- Expected: reject all disallowed uploads.

### FILE-03 Size boundary
- Upload exactly near 1MB and >1MB.
- Expected: near-limit accepted, >1MB rejected client-side and server-side.

### FILE-04 Missing file on disk
- Remove one stored file manually, then hit download route.
- Expected: graceful "File not found." error (no 500).

### FILE-05 Concurrent same-second upload collision
- Fire parallel uploads (same class/day/author) within same second.
- Expected: one succeeds, others reject on duplicate canonical name / DB unique name.

## 7) Versioning & Lineage Stress

### VER-01 Same-author version chain
- `user_a` creates v1, then v2, then v3.
- Expected:
  - `version_number` increments
  - `original_id` points to root
  - `parent_id` points to immediate predecessor

### VER-02 Different-author branch behavior (spec-critical)
- `user_b` creates "new version" from `user_a` plan.
- Expected per spec: **new independent plan family** (version 1, `original_id=NULL`, `parent_id=NULL`).
- Note: CURRENT_STATUS indicates this may currently fail. Keep this as a regression gate.

### VER-03 Deletion guards
- Try deleting root with descendants.
- Try deleting intermediate node with children.
- Expected: blocked with explanatory errors.

### VER-04 Leaf deletion safety
- Delete leaf version.
- Expected: record removed, file removed, votes removed, family integrity preserved.

## 8) Voting Stress

### VOTE-01 Toggle idempotency
- Repeat same vote 50 times via browser automation or scripted POST.
- Expected: final state deterministic (odd toggles: voted, even toggles: no vote).

### VOTE-02 Opposite-direction race
- Send near-simultaneous upvote/downvote requests from same user/session.
- Expected: single final vote row per unique constraint; score matches stored vote.

### VOTE-03 Multi-user contention
- 20 users vote same plan in quick succession.
- Expected: `vote_score == sum(votes.value)` and no duplicate `(lesson_plan_id,user_id)` rows.

## 9) Dashboard/Search/Sort/Pagination Stress

### DASH-01 Sort whitelist hardening
- Pass invalid `sort` and `order` query values (`sort=drop table`, `order=sideways`).
- Expected: fallback to safe defaults, no SQL errors.

### DASH-02 Search with JOIN fields
- Search by stripped author value and full email variants.
- Expected: stable query behavior, no ambiguous-column SQL errors.

### DASH-03 Pagination stability
- Traverse pages under active filters/sorts.
- Expected: query params preserved, row count stable, no missing/duplicated rows.

## 10) Duplicate-Content Command Stress

### DEDUP-01 Dry-run correctness
- Seed duplicate hashes; run `php artisan lessons:detect-duplicates --dry-run`.
- Expected: reports candidates only; no DB/file changes.

### DEDUP-02 Live-run deletion safety
- Run live command on duplicates without dependents.
- Expected: keep earliest, delete later duplicates, notify authors.

### DEDUP-03 Lineage protection
- Ensure duplicate candidate has dependents (`parent_id` or `original_id` references).
- Expected: skipped with warning; no orphaning.

### DEDUP-04 Hash backfill
- Null out `file_hash` on records with files; run command.
- Expected: hashes backfilled without errors.

## 11) Deployment/Runtime Guard Tests

### DEP-01 Route cache integrity
- `php artisan optimize:clear && php artisan route:cache`.
- Expected: app still serves dashboard routes (not Laravel default welcome screen).

### DEP-02 Mail TLS sanity
- Send verification mail after config cache rebuild.
- Expected: no STARTTLS certificate CN mismatch in logs.

### DEP-03 Overlay safety check
- Validate update process never deletes Laravel core files (`app/Providers`, Breeze controllers, `routes/auth.php`).
- Expected: overlay copy only, no stale-file deletion pass.

## 12) Minimal Command Set (server)

```bash
cd ~/LessonPlanShare
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force
php artisan lessons:detect-duplicates --dry-run
tail -n 200 ~/LessonPlanShare/storage/logs/laravel.log
```

## 13) Pass/Fail Exit Criteria
- No 500s across auth/upload/version/vote flows
- Verification links consistently succeed for fresh links
- Authorization boundaries enforced (guest/unverified/non-owner)
- File and DB state remain consistent under repeated and concurrent actions
- Duplicate-content command does not break version lineage
- Deployment cache rebuild does not revert to default Laravel routes/pages
