<?php

namespace App\Console\Commands;

use App\Mail\DuplicateContentRemoved;
use App\Models\LessonPlan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * Scans all lesson plans for duplicate file content (by SHA-256 hash).
 *
 * When duplicates are found:
 * - The EARLIEST upload (lowest id) is kept.
 * - Later duplicates are deleted (both DB record and stored file).
 * - The author of each deleted duplicate receives an email notification.
 *
 * This command also back-fills file_hash for any records that are missing it
 * (e.g., records created before the hash column was added).
 *
 * Schedule: run daily or weekly via Laravel's scheduler or a cron job.
 *   php artisan lessons:detect-duplicates
 */
class DetectDuplicateContent extends Command
{
    protected $signature = 'lessons:detect-duplicates
                            {--dry-run : Show what would be deleted without actually deleting}';

    protected $description = 'Detect and remove lesson plans with duplicate file content, keeping the earliest upload.';

    public function handle(): int
    {
        $this->info('Starting duplicate content scan...');

        // Step 1: Back-fill missing hashes
        $this->backfillHashes();

        // Step 2: Find duplicates grouped by hash
        $duplicates = LessonPlan::whereNotNull('file_hash')
            ->select('file_hash')
            ->groupBy('file_hash')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('file_hash');

        if ($duplicates->isEmpty()) {
            $this->info('No duplicate content found. All clear!');
            return Command::SUCCESS;
        }

        $this->info("Found {$duplicates->count()} duplicate hash group(s).");

        $totalRemoved = 0;

        foreach ($duplicates as $hash) {
            // Get all plans with this hash, ordered by id (earliest first)
            $plans = LessonPlan::where('file_hash', $hash)
                ->with('author')
                ->orderBy('id', 'asc')
                ->get();

            $keeper = $plans->first();
            $dupes  = $plans->slice(1);

            $keeperAuthor = $keeper->author?->name ?? '?';
            $this->line("  Hash: {$hash}");
            $this->line("    Keeping: [{$keeper->id}] {$keeper->name} by {$keeperAuthor}");

            foreach ($dupes as $dupe) {
                $authorEmail = $dupe->author?->email;
                $authorName  = $dupe->author?->name ?? 'Unknown';

                // ── Lineage protection ──
                // Skip deletion if this plan is referenced as a parent or
                // original by other plans. Deleting it would SET NULL those
                // foreign keys and orphan the version family linkage.
                $hasChildren = LessonPlan::where('parent_id', $dupe->id)
                    ->orWhere('original_id', $dupe->id)
                    ->exists();

                if ($hasChildren) {
                    $this->warn("    Skipping: [{$dupe->id}] {$dupe->name} — has dependent versions");
                    continue;
                }

                $this->line("    Removing: [{$dupe->id}] {$dupe->name} by {$authorName}");

                if ($this->option('dry-run')) {
                    $this->warn("      [DRY RUN] Would delete and email {$authorEmail}");
                    continue;
                }

                // Delete the stored file
                if ($dupe->file_path && Storage::disk('public')->exists($dupe->file_path)) {
                    Storage::disk('public')->delete($dupe->file_path);
                }

                // Send notification email to the author of the duplicate
                if ($authorEmail) {
                    try {
                        Mail::to($authorEmail)->send(new DuplicateContentRemoved(
                            recipientName:   $authorName,
                            deletedPlanName: $dupe->name,
                            keptPlanName:    $keeper->name,
                            keptAuthorName:  $keeperAuthor,
                        ));
                        $this->line("      Notified {$authorEmail}");
                    } catch (\Exception $e) {
                        $this->error("      Failed to email {$authorEmail}: {$e->getMessage()}");
                    }
                }

                // Delete the duplicate record (safe — no dependents)
                $dupe->votes()->delete();
                $dupe->delete();
                $totalRemoved++;
            }
        }

        $this->info("Done. Removed {$totalRemoved} duplicate(s).");
        return Command::SUCCESS;
    }

    /**
     * Back-fill file_hash for any lesson plan records that are missing it.
     */
    private function backfillHashes(): void
    {
        $missing = LessonPlan::whereNull('file_hash')
            ->whereNotNull('file_path')
            ->get();

        if ($missing->isEmpty()) {
            return;
        }

        $this->info("Back-filling hashes for {$missing->count()} record(s)...");

        foreach ($missing as $plan) {
            $fullPath = Storage::disk('public')->path($plan->file_path);
            if (file_exists($fullPath)) {
                $plan->file_hash = hash_file('sha256', $fullPath);
                $plan->saveQuietly();
            }
        }
    }
}
