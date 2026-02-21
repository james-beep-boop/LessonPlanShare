<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class LessonPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_name',
        'lesson_day',
        'description',
        'name',
        'original_id',
        'parent_id',
        'version_number',
        'author_id',
        'file_path',
        'file_name',
        'file_size',
        'file_hash',
        'vote_score',
    ];

    // ── Relationships ──

    /**
     * The teacher who uploaded this version.
     */
    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * The immediate parent version (the version this was derived from).
     */
    public function parent()
    {
        return $this->belongsTo(LessonPlan::class, 'parent_id');
    }

    /**
     * The root/original lesson plan in this family.
     */
    public function original()
    {
        return $this->belongsTo(LessonPlan::class, 'original_id');
    }

    /**
     * All versions that were derived directly from this version.
     */
    public function children()
    {
        return $this->hasMany(LessonPlan::class, 'parent_id');
    }

    /**
     * All versions in this plan's family (shares the same original_id).
     * For the root plan, original_id is NULL, so we match on id.
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
     */
    public function votes()
    {
        return $this->hasMany(Vote::class);
    }

    // ── Accessors ──

    /**
     * Count of upvotes.
     */
    public function getUpvoteCountAttribute(): int
    {
        return $this->votes()->where('value', 1)->count();
    }

    /**
     * Count of downvotes.
     */
    public function getDownvoteCountAttribute(): int
    {
        return $this->votes()->where('value', -1)->count();
    }

    /**
     * Human-readable file size.
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
     * Is this the root/original version?
     */
    public function getIsOriginalAttribute(): bool
    {
        return is_null($this->original_id);
    }

    /**
     * The root ID for this plan family.
     */
    public function getRootIdAttribute(): int
    {
        return $this->original_id ?? $this->id;
    }

    // ── Scopes ──

    /**
     * Show only the latest version of each plan family on the dashboard.
     */
    public function scopeLatestVersions($query)
    {
        return $query->whereIn('id', function ($sub) {
            $sub->selectRaw('MAX(id)')
                ->from('lesson_plans')
                ->groupByRaw('COALESCE(original_id, id)');
        });
    }

    // ── Business Logic ──

    /**
     * Generate a canonical name from the structured fields.
     * Format: "{ClassName}_Day{N}_{AuthorName}_{UTC timestamp}"
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
     * Recalculate and store the vote_score from the votes table.
     */
    public function recalculateVoteScore(): void
    {
        $this->vote_score = $this->votes()->sum('value');
        $this->saveQuietly();
    }
}
