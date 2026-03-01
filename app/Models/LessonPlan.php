<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Represents a single version of a lesson plan document.
 *
 * Key concepts:
 * - Each lesson plan belongs to a "family" of versions sharing the same root.
 * - The root plan (version 1) has original_id = NULL and parent_id = NULL.
 * - Subsequent versions link back to the root via original_id and to their
 *   immediate predecessor via parent_id.
 * - Documents are stored on disk with a canonical filename generated from
 *   structured fields: {ClassName}_Day{N}_{AuthorName}_{YYYYMMDD_HHMMSS}UTC_v{major}-{minor}-{patch}.ext
 * - A SHA-256 hash of the file contents is stored for duplicate detection
 *   (see DetectDuplicateContent artisan command).
 * - vote_score is a cached aggregate of all votes for this version, updated
 *   by VoteController after each vote action to avoid expensive SUM() queries.
 * - Semantic versioning (major.minor.patch) is assigned GLOBALLY per
 *   (class_name, lesson_day) pair. A unique DB index enforces uniqueness.
 *
 * Database columns:
 * - id, class_name, lesson_day, description, name (canonical), original_id (FK),
 *   parent_id (FK), version_number, version_major, version_minor, version_patch,
 *   author_id (FK), file_path, file_name, file_size, file_hash, vote_score,
 *   created_at, updated_at.
 */
class LessonPlan extends Model
{
    use HasFactory;

    /**
     * Mass-assignable attributes.
     *
     * Note: vote_score is included because recalculateVoteScore() updates it
     * via a raw DB update, but it should never come from user input.
     */
    protected $fillable = [
        'class_name',      // Subject name (e.g., 'English', 'Mathematics', 'Science')
        'lesson_day',      // Lesson number (1–20)
        'description',     // Optional description of changes or content
        'name',            // Canonical filename (without extension)
        'original_id',     // FK: root plan in this version family (NULL for root)
        'parent_id',       // FK: immediate predecessor version (NULL for root)
        'version_number',  // Auto-incremented within the family (1, 2, 3...)
        'version_major',   // Semantic version: first integer (always 1 in this system)
        'version_minor',   // Semantic version: second integer (bumped on major revision)
        'version_patch',   // Semantic version: third integer (bumped on minor revision)
        'author_id',       // FK: the user who uploaded this version
        'file_path',       // Relative path on the 'public' disk (e.g., 'lessons/...')
        'file_name',       // Canonical filename with extension (for downloads)
        'file_size',       // File size in bytes
        'file_hash',       // SHA-256 hash for duplicate content detection
        'vote_score',      // Cached sum of all vote values (+1/-1)
    ];

    // ══════════════════════════════════════════════════════════════
    //  Relationships
    // ══════════════════════════════════════════════════════════════

    /**
     * The teacher who uploaded this version.
     *
     * Uses 'author_id' instead of the default 'user_id' foreign key
     * for clarity in a system where multiple users interact with plans.
     */
    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * The immediate parent version (the version this was derived from).
     *
     * NULL for the root/original version (version 1).
     * Used to trace the direct lineage of edits.
     */
    public function parent()
    {
        return $this->belongsTo(LessonPlan::class, 'parent_id');
    }

    /**
     * The root/original lesson plan in this family.
     *
     * NULL for the root plan itself. All descendant versions point back
     * to the same root via this FK, making it easy to query all versions
     * of a plan without recursive traversal.
     */
    public function original()
    {
        return $this->belongsTo(LessonPlan::class, 'original_id');
    }

    /**
     * All versions that were derived directly from this version.
     *
     * This is the inverse of the parent() relationship.
     * Used by the delete guard: the root plan cannot be deleted
     * if it has children (because original_id uses onDelete('set null'),
     * which would orphan the family linkage).
     */
    public function children()
    {
        return $this->hasMany(LessonPlan::class, 'parent_id');
    }

    /**
     * All versions in this plan's family (same original_id).
     *
     * For the root plan, original_id is NULL, so we match on id instead.
     * Returns a query builder (not a relationship) so it can be chained.
     * Used by the show page to display the version history sidebar.
     * Ordered by semantic version (minor ASC, patch ASC) for correct display.
     */
    public function familyVersions()
    {
        $rootId = $this->original_id ?? $this->id;
        return LessonPlan::where('id', $rootId)
            ->orWhere('original_id', $rootId)
            ->orderBy('version_minor', 'asc')
            ->orderBy('version_patch', 'asc');
    }

    /**
     * All votes for this specific version.
     *
     * Each version has its own independent vote tally. Votes do not
     * carry over between versions of the same plan.
     */
    public function votes()
    {
        return $this->hasMany(Vote::class);
    }

    // ══════════════════════════════════════════════════════════════
    //  Accessors (virtual attributes accessed as $plan->semantic_version, etc.)
    // ══════════════════════════════════════════════════════════════

    /**
     * The human-readable semantic version string (e.g. "1.2.16").
     *
     * This is the canonical display version used across all views.
     * The underlying integers (version_major, version_minor, version_patch)
     * allow correct numeric sorting — this accessor is display-only.
     */
    public function getSemanticVersionAttribute(): string
    {
        return "{$this->version_major}.{$this->version_minor}.{$this->version_patch}";
    }

    /**
     * Count of upvotes (+1) for this version.
     *
     * Uses the already-loaded votes collection (in-memory filter) when available,
     * avoiding an extra query on pages that eager-load votes (e.g. show page).
     * Falls back to a DB query if the relationship has not been loaded.
     */
    public function getUpvoteCountAttribute(): int
    {
        if ($this->relationLoaded('votes')) {
            return $this->votes->where('value', 1)->count();
        }
        return $this->votes()->where('value', 1)->count();
    }

    /**
     * Count of downvotes (-1) for this version.
     *
     * Same eager-load optimisation as getUpvoteCountAttribute().
     */
    public function getDownvoteCountAttribute(): int
    {
        if ($this->relationLoaded('votes')) {
            return $this->votes->where('value', -1)->count();
        }
        return $this->votes()->where('value', -1)->count();
    }

    /**
     * Human-readable file size (e.g., "1.2 MB", "450 KB").
     *
     * Returns '—' if file_size is null or zero (e.g., legacy records
     * created before file_size tracking was added).
     */
    public function getFileSizeFormattedAttribute(): string
    {
        $bytes = $this->file_size;
        if (!$bytes) return '—';
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 1) . ' MB';
    }

    /**
     * Is this the root/original version (version 1)?
     *
     * The root has no original_id because it IS the original.
     * Used by the delete guard in LessonPlanController::destroy().
     */
    public function getIsOriginalAttribute(): bool
    {
        return is_null($this->original_id);
    }

    /**
     * The root ID for this plan family.
     *
     * For the root plan, this returns its own id.
     * For descendants, this returns original_id.
     * Used to group all versions together.
     *
     * IMPORTANT: This accessor is NOT safe to call on partial Eloquent
     * results (e.g., SELECT with COALESCE aliases) that omit the 'id'
     * or 'original_id' columns — it will throw a TypeError.
     * The DashboardController::stats() method explicitly avoids using
     * the alias 'root_id' for this reason.
     */
    public function getRootIdAttribute(): int
    {
        return $this->original_id ?? $this->id;
    }

    // ══════════════════════════════════════════════════════════════
    //  Scopes (query modifiers used as LessonPlan::latestVersions())
    // ══════════════════════════════════════════════════════════════

    /**
     * Show only the highest-versioned plan for each (class_name, lesson_day) pair.
     *
     * "Latest" means the plan with the greatest semantic version across ALL
     * version families for that class+day combination. For example, if versions
     * 1.0.0, 1.0.1, and 1.0.2 all exist for "Mathematics Day 5", only 1.0.2
     * is returned — regardless of which version family they belong to.
     *
     * Uses a NOT EXISTS anti-join: a plan is included only when no other plan
     * for the same class+day has a higher version_minor, a higher version_patch
     * at the same version_minor, or a higher id at the exact same version
     * (tie-breaking rule for the unique-constraint-protected edge case).
     *
     * The fully-qualified table name is required because the dashboard query
     * also has a LEFT JOIN on users.
     *
     * Used by DashboardController::index() when latest_only=1.
     */
    public function scopeLatestVersions($query)
    {
        return $query->whereNotExists(function ($sub) {
            $sub->selectRaw('1')
                ->from('lesson_plans as lp2')
                ->whereColumn('lp2.class_name', 'lesson_plans.class_name')
                ->whereColumn('lp2.lesson_day',  'lesson_plans.lesson_day')
                ->whereRaw(
                    '(lp2.version_minor > lesson_plans.version_minor
                      OR (lp2.version_minor = lesson_plans.version_minor AND lp2.version_patch > lesson_plans.version_patch)
                      OR (lp2.version_minor = lesson_plans.version_minor AND lp2.version_patch = lesson_plans.version_patch AND lp2.id > lesson_plans.id))'
                );
        });
    }

    // ══════════════════════════════════════════════════════════════
    //  Business Logic
    // ══════════════════════════════════════════════════════════════

    /**
     * Generate a canonical name from the structured fields.
     *
     * Format: "{ClassName}_Day{N}_{AuthorName}_{YYYYMMDD_HHMMSS}UTC_v{major}-{minor}-{patch}"
     *
     * Example: "Mathematics_Day5_davidsheqlcom_20260227_143022UTC_v1-2-16"
     *
     * Sanitization:
     * - Spaces are replaced with hyphens
     * - Special characters (except A-Z, a-z, 0-9, hyphen) are stripped
     *
     * The timestamp defaults to the current UTC time but can be overridden
     * (useful for testing or backfilling historical records).
     *
     * The $semanticVersion array [major, minor, patch] is appended to the name
     * so the filename reflects the exact version it contains.
     *
     * @param  string       $className       Subject name (e.g., 'Mathematics')
     * @param  int          $lessonDay       Lesson number (1–20)
     * @param  string       $authorName      Author's display name
     * @param  Carbon|null  $timestamp       Optional UTC timestamp override
     * @param  array|null   $semanticVersion [major, minor, patch] integers
     * @return string       The canonical name (without file extension)
     */
    public static function generateCanonicalName(
        string $className,
        int $lessonDay,
        string $authorName,
        ?Carbon $timestamp = null,
        ?array $semanticVersion = null
    ): string {
        $ts = ($timestamp ?? Carbon::now('UTC'))->format('Ymd_His');
        // Sanitize: replace spaces with hyphens, remove special chars.
        // Fallback to 'Unknown' if sanitization produces an empty string.
        $cleanClass  = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $className));
        $cleanAuthor = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $authorName));
        $cleanClass  = $cleanClass  !== '' ? $cleanClass  : 'Unknown';
        $cleanAuthor = $cleanAuthor !== '' ? $cleanAuthor : 'Unknown';
        $name = "{$cleanClass}_Day{$lessonDay}_{$cleanAuthor}_{$ts}UTC";
        if ($semanticVersion !== null) {
            [$major, $minor, $patch] = $semanticVersion;
            $name .= "_v{$major}-{$minor}-{$patch}";
        }
        return $name;
    }

    /**
     * Create a new version of this lesson plan.
     *
     * Handles the version-family linkage automatically:
     * - Sets original_id to the root of this family
     * - Sets parent_id to THIS plan's id
     * - Auto-increments the version_number (family-internal counter)
     *
     * The $attributes array MUST include version_major, version_minor, version_patch
     * (computed globally by LessonPlanController::computeNextSemanticVersion()).
     * It can also override any defaults (class_name, lesson_day, description, etc.).
     *
     * Called by LessonPlanController::update() for same-author uploads.
     *
     * @param  array  $attributes  Overrides for the new version's fields
     * @return LessonPlan          The newly created version
     */
    public function createNewVersion(array $attributes): LessonPlan
    {
        $rootId = $this->original_id ?? $this->id;

        // Find the highest version number in this family (family-internal counter)
        $maxVersion = LessonPlan::where('id', $rootId)
            ->orWhere('original_id', $rootId)
            ->max('version_number');

        return LessonPlan::create(array_merge([
            'class_name'     => $this->class_name,
            'lesson_day'     => $this->lesson_day,
            'description'    => $this->description,
            'original_id'    => $rootId,
            'parent_id'      => $this->id,
            'version_number' => $maxVersion + 1,
        ], $attributes));
    }

    /**
     * Recalculate and store the cached vote_score from the votes table.
     *
     * Called by VoteController::store() after every vote action (create,
     * toggle off, or switch direction). Uses a raw DB update to suppress
     * model events and avoid touching updated_at (which would cause
     * voted-on plans to float to the top of the "Updated" sort).
     */
    public function recalculateVoteScore(): void
    {
        $score = $this->votes()->sum('value');
        DB::table('lesson_plans')->where('id', $this->id)->update(['vote_score' => $score]);
        $this->vote_score = $score; // keep local attribute in sync
    }
}
