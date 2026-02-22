<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Represents a single version of a lesson plan document.
 *
 * Key concepts:
 * - Each lesson plan belongs to a "family" of versions sharing the same root.
 * - The root plan (version 1) has original_id = NULL and parent_id = NULL.
 * - Subsequent versions link back to the root via original_id and to their
 *   immediate predecessor via parent_id.
 * - Documents are stored on disk with a canonical filename generated from
 *   structured fields: {ClassName}_Day{N}_{AuthorName}_{YYYYMMDD_HHMMSS}UTC.ext
 * - A SHA-256 hash of the file contents is stored for duplicate detection
 *   (see DetectDuplicateContent artisan command).
 * - vote_score is a cached aggregate of all votes for this version, updated
 *   by VoteController after each vote action to avoid expensive SUM() queries.
 *
 * Database columns:
 * - id, class_name, lesson_day, description, name (canonical), original_id (FK),
 *   parent_id (FK), version_number, author_id (FK), file_path, file_name,
 *   file_size, file_hash, vote_score, created_at, updated_at.
 */
class LessonPlan extends Model
{
    use HasFactory;

    /**
     * Mass-assignable attributes.
     *
     * Note: vote_score is included because recalculateVoteScore() updates it
     * via saveQuietly(), but it should never come from user input.
     */
    protected $fillable = [
        'class_name',      // Subject name (e.g., 'English', 'Mathematics', 'Science')
        'lesson_day',      // Lesson number (1–20)
        'description',     // Optional description of changes or content
        'name',            // Canonical filename (without extension)
        'original_id',     // FK: root plan in this version family (NULL for root)
        'parent_id',       // FK: immediate predecessor version (NULL for root)
        'version_number',  // Auto-incremented within the family (1, 2, 3...)
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
     */
    public function familyVersions()
    {
        $rootId = $this->original_id ?? $this->id;
        return LessonPlan::where('id', $rootId)
            ->orWhere('original_id', $rootId)
            ->orderBy('version_number', 'asc');
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
    //  Accessors (virtual attributes accessed as $plan->upvote_count, etc.)
    // ══════════════════════════════════════════════════════════════

    /**
     * Count of upvotes (+1) for this version.
     *
     * Note: This queries the database each time it's accessed.
     * For display-heavy pages, prefer using the cached vote_score column.
     */
    public function getUpvoteCountAttribute(): int
    {
        return $this->votes()->where('value', 1)->count();
    }

    /**
     * Count of downvotes (-1) for this version.
     *
     * Same performance note as getUpvoteCountAttribute().
     */
    public function getDownvoteCountAttribute(): int
    {
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
     */
    public function getRootIdAttribute(): int
    {
        return $this->original_id ?? $this->id;
    }

    // ══════════════════════════════════════════════════════════════
    //  Scopes (query modifiers used as LessonPlan::latestVersions())
    // ══════════════════════════════════════════════════════════════

    /**
     * Show only the latest version of each plan family on the dashboard.
     *
     * Groups plans by their root ID (COALESCE handles the root plan
     * where original_id is NULL) and picks the highest id in each group.
     * This avoids showing every version as a separate row.
     *
     * Used by DashboardController::index() for the main plan listing.
     */
    public function scopeLatestVersions($query)
    {
        return $query->whereIn('id', function ($sub) {
            $sub->selectRaw('MAX(id)')
                ->from('lesson_plans')
                ->groupByRaw('COALESCE(original_id, id)');
        });
    }

    // ══════════════════════════════════════════════════════════════
    //  Business Logic
    // ══════════════════════════════════════════════════════════════

    /**
     * Generate a canonical name from the structured fields.
     *
     * Format: "{ClassName}_Day{N}_{AuthorName}_{YYYYMMDD_HHMMSS}UTC"
     *
     * Example: "Mathematics_Day5_david@sheql.com_20260221_143022UTC"
     *
     * Sanitization:
     * - Spaces are replaced with hyphens
     * - Special characters (except A-Z, a-z, 0-9, hyphen) are stripped
     * - The @ and . in email addresses are removed, which is intentional
     *   (e.g., "david@sheql.com" becomes "davidsheqlcom")
     *
     * The timestamp defaults to the current UTC time but can be overridden
     * (useful for testing or backfilling historical records).
     *
     * @param  string       $className   Subject name (e.g., 'Mathematics')
     * @param  int          $lessonDay   Lesson number (1–20)
     * @param  string       $authorName  Author's display name (email address)
     * @param  Carbon|null  $timestamp   Optional UTC timestamp override
     * @return string       The canonical name (without file extension)
     */
    public static function generateCanonicalName(string $className, int $lessonDay, string $authorName, ?Carbon $timestamp = null): string
    {
        $ts = ($timestamp ?? Carbon::now('UTC'))->format('Ymd_His');
        // Sanitize: replace spaces with hyphens, remove special chars
        $cleanClass  = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $className));
        $cleanAuthor = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $authorName));
        return "{$cleanClass}_Day{$lessonDay}_{$cleanAuthor}_{$ts}UTC";
    }

    /**
     * Create a new version of this lesson plan.
     *
     * Handles the version-family linkage automatically:
     * - Sets original_id to the root of this family
     * - Sets parent_id to THIS plan's id
     * - Auto-increments the version number
     *
     * The $attributes array can override any defaults (class_name, lesson_day,
     * description, etc.) — this allows re-categorizing a plan in a new version.
     *
     * Called by LessonPlanController::update().
     *
     * @param  array  $attributes  Overrides for the new version's fields
     * @return LessonPlan          The newly created version
     */
    public function createNewVersion(array $attributes): LessonPlan
    {
        $rootId = $this->original_id ?? $this->id;

        // Find the highest version number in this family
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
     * toggle off, or switch direction). Uses saveQuietly() to avoid
     * triggering model events (we don't want updated_at to change
     * just because someone voted).
     *
     * The cached score is displayed on the dashboard and show pages,
     * avoiding an expensive SUM() join on every page load.
     */
    public function recalculateVoteScore(): void
    {
        $this->vote_score = $this->votes()->sum('value');
        $this->saveQuietly();
    }
}
