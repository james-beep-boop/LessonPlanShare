<?php

namespace App\Console\Commands;

use App\Models\LessonPlan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Renames stored lesson plan files to include the grade level in the filename.
 *
 * Old format: {ClassName}_Day{N}_{Author}_{timestamp}UTC_v{v}.{ext}
 * New format: {ClassName}_Grade{N}_Day{N}_{Author}_{timestamp}UTC_v{v}.{ext}
 *
 * Only files that do NOT already contain "_Grade" are processed.
 * Renames the file on disk (Storage::disk('public')) and updates
 * the file_path, file_name, and name columns in the database.
 *
 * Always run with --dry-run first to preview what will change:
 *   php artisan lessons:backfill-grade-in-filenames --dry-run
 *
 * Then run for real:
 *   php artisan lessons:backfill-grade-in-filenames
 */
class BackfillGradeInFilenames extends Command
{
    protected $signature = 'lessons:backfill-grade-in-filenames
                            {--dry-run : Preview changes without modifying any files or database records}';

    protected $description = 'Add grade level to existing lesson plan filenames (e.g. Chemistry_Day1 → Chemistry_Grade10_Day1).';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN — no files or database records will be changed.');
        }

        // Load all plans that have a file and whose filename does not yet contain _Grade
        $plans = LessonPlan::whereNotNull('file_name')
            ->whereNotNull('file_path')
            ->where('file_name', 'not like', '%\_Grade%')
            ->orderBy('id')
            ->get();

        if ($plans->isEmpty()) {
            $this->info('No files need renaming — all filenames already contain _Grade.');
            return 0;
        }

        $this->info("Found {$plans->count()} file(s) to rename.");

        $renamed  = 0;
        $skipped  = 0;
        $errors   = 0;

        foreach ($plans as $plan) {
            $oldFileName = $plan->file_name;  // e.g. Chemistry_Day1_Alice_20260101_120000UTC_v1-0-0.docx
            $oldFilePath = $plan->file_path;  // e.g. lessons/Chemistry_Day1_Alice_...docx
            $oldName     = $plan->name;       // canonical name without extension

            // Insert _Grade{N} before _Day in both file_name and name
            $grade = (int) $plan->grade;

            $newFileName = preg_replace(
                '/^(.+?)(_Day\d+_.+)$/',
                '$1_Grade' . $grade . '$2',
                $oldFileName
            );

            // If the regex didn't match (unexpected filename format), skip safely
            if ($newFileName === $oldFileName) {
                $this->warn("  SKIP  [{$plan->id}] Pattern not matched: {$oldFileName}");
                $skipped++;
                continue;
            }

            $newFilePath = 'lessons/' . $newFileName;

            // Derive new display name (file_name without extension)
            $newName = pathinfo($newFileName, PATHINFO_FILENAME);

            $this->line("  [{$plan->id}]");
            $this->line("    OLD: {$oldFileName}");
            $this->line("    NEW: {$newFileName}");

            if ($dryRun) {
                $renamed++;
                continue;
            }

            // Rename on disk
            if (!Storage::disk('public')->exists($oldFilePath)) {
                $this->warn("    SKIP (file not found on disk): {$oldFilePath}");
                $skipped++;
                continue;
            }

            try {
                Storage::disk('public')->move($oldFilePath, $newFilePath);
            } catch (\Exception $e) {
                $this->error("    ERROR renaming file: " . $e->getMessage());
                $errors++;
                continue;
            }

            // Update database — use DB::table to avoid touching updated_at
            DB::table('lesson_plans')->where('id', $plan->id)->update([
                'file_name' => $newFileName,
                'file_path' => $newFilePath,
                'name'      => $newName,
            ]);

            $renamed++;
        }

        $this->newLine();
        if ($dryRun) {
            $this->info("DRY RUN complete. {$renamed} file(s) would be renamed, {$skipped} skipped.");
        } else {
            $this->info("Done. {$renamed} renamed, {$skipped} skipped, {$errors} errors.");
        }

        return $errors > 0 ? 1 : 0;
    }
}
