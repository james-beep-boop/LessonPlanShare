<?php

namespace App\Console\Commands;

use App\Models\LessonPlan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Moves lesson plan files from the public disk (storage/app/public/lessons/)
 * to the local/private disk (storage/app/lessons/).
 *
 * Background:
 *   Files were originally stored on the 'public' disk, making them directly
 *   accessible via URL (/storage/lessons/...) without authentication.
 *   After this migration they live on the local disk and are served only through
 *   authenticated download routes or temporary signed URLs (for external viewers).
 *
 * Safe to run more than once — files already on the local disk are skipped.
 * Files missing from both disks are logged and counted as errors.
 *
 * Always run with --dry-run first:
 *   php artisan lessons:migrate-to-private-storage --dry-run
 *
 * Then run for real:
 *   php artisan lessons:migrate-to-private-storage
 */
class MigrateFilesToPrivateStorage extends Command
{
    protected $signature = 'lessons:migrate-to-private-storage
                            {--dry-run : Preview what would be moved without changing anything}';

    protected $description = 'Move lesson plan files from the public disk to the private local disk.';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN — no files will be moved.');
        }

        $plans = LessonPlan::whereNotNull('file_path')->orderBy('id')->get();

        if ($plans->isEmpty()) {
            $this->info('No lesson plans with file paths found.');
            return 0;
        }

        $moved   = 0;
        $skipped = 0;
        $errors  = 0;

        foreach ($plans as $plan) {
            $path = $plan->file_path;  // e.g. lessons/ClassName_Grade10_Day1_...docx

            // Already on the local (private) disk — nothing to do.
            if (Storage::disk('local')->exists($path)) {
                $skipped++;
                continue;
            }

            // Not on public disk either — file is missing entirely.
            if (!Storage::disk('public')->exists($path)) {
                $this->warn("  MISSING [{$plan->id}] {$path}");
                $errors++;
                continue;
            }

            $this->line("  MOVE [{$plan->id}] {$path}");

            if ($dryRun) {
                $moved++;
                continue;
            }

            // Read from public disk, write to local disk, then remove from public.
            $contents = Storage::disk('public')->get($path);
            Storage::disk('local')->put($path, $contents);
            Storage::disk('public')->delete($path);
            $moved++;
        }

        $this->newLine();
        if ($dryRun) {
            $this->info("DRY RUN complete. {$moved} file(s) would be moved, {$skipped} already on local disk, {$errors} missing.");
        } else {
            $this->info("Done. {$moved} moved, {$skipped} already on local disk, {$errors} missing/errors.");
        }

        return $errors > 0 ? 1 : 0;
    }
}
