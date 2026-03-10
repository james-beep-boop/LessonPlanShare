<?php

namespace App\Traits;

use App\Models\LessonPlan;
use Illuminate\Support\Facades\Storage;

/**
 * Shared diff utilities used by LessonPlanController (show/compare pages)
 * and AdminController (admin compare panel).
 *
 * All methods are protected so they are accessible from any controller that
 * uses this trait but are not callable from outside the class hierarchy.
 */
trait DiffHelperTrait
{
    /**
     * Read a lesson plan into comparable lines.
     *
     * Supports .txt (raw) and .docx (text extracted via ZipArchive).
     * Other extensions return a warning reason.
     * Line endings are normalised to \n before splitting.
     *
     * @return array{0: bool, 1: array<int, string>, 2: string|null}
     */
    protected function readPlanLinesForDiff(LessonPlan $plan): array
    {
        if (! $plan->file_path) {
            return [false, [], 'Comparison unavailable: one of the selected versions has no file path.'];
        }

        $disk = $this->resolveFileDisk($plan->file_path);
        if (! $disk) {
            return [false, [], 'Comparison unavailable: one of the selected files is missing from storage.'];
        }

        $extension    = strtolower(pathinfo($plan->file_name ?? $plan->file_path, PATHINFO_EXTENSION));
        $absolutePath = $disk->path($plan->file_path);

        if ($extension === 'docx') {
            $lines = $this->extractTextLinesFromDocx($absolutePath);
            if ($lines === null) {
                return [false, [], 'Comparison failed: could not extract text from one of the DOCX files.'];
            }
            return [true, $lines, null];
        }

        if ($extension === 'txt') {
            $content = @file_get_contents($absolutePath);
            if ($content === false) {
                return [false, [], 'Comparison failed: could not read one of the selected files.'];
            }
            $normalized = str_replace(["\r\n", "\r"], "\n", $content);
            return [true, explode("\n", $normalized), null];
        }

        return [false, [], "Comparison supports .txt and .docx files. The '{$extension}' format cannot be compared as text."];
    }

    /**
     * Extract plain-text lines from a .docx file using PHP's ZipArchive.
     *
     * A .docx is a ZIP archive; the text lives in word/document.xml.
     * We replace closing paragraph tags with newlines before stripping all XML,
     * so each Word paragraph becomes a separate line.
     *
     * Returns null on any failure (missing extension, bad ZIP, missing entry).
     *
     * @return array<int, string>|null
     */
    protected function extractTextLinesFromDocx(string $absolutePath): ?array
    {
        if (! class_exists(\ZipArchive::class)) {
            return null;
        }

        $zip = new \ZipArchive();
        if ($zip->open($absolutePath) !== true) {
            return null;
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($xml === false) {
            return null;
        }

        // Each </w:p> closes a paragraph → newline; <w:br/> is a line break.
        $xml  = preg_replace('/<\/w:p>/', "\n", $xml) ?? $xml;
        $xml  = preg_replace('/<w:br[^>]*\/>/', "\n", $xml) ?? $xml;
        $text = strip_tags($xml);

        $lines = explode("\n", $text);
        // Collapse runs of whitespace within each line, trim edges.
        $lines = array_map(fn ($l) => trim((string) preg_replace('/\s+/', ' ', $l)), $lines);

        // Strip leading and trailing blank lines only.
        while (count($lines) && $lines[0] === '') {
            array_shift($lines);
        }
        while (count($lines) && end($lines) === '') {
            array_pop($lines);
        }

        return $lines;
    }

    /**
     * Resolve which disk holds a lesson plan file.
     *
     * New uploads are stored on the local (private) disk. Legacy files uploaded
     * before the MigrateFilesToPrivateStorage command was run may still be on the
     * public disk. Checks local first, then public.
     *
     * Returns null if the file is not found on either disk.
     *
     * @return \Illuminate\Contracts\Filesystem\Filesystem|null
     */
    protected function resolveFileDisk(string $path): ?\Illuminate\Contracts\Filesystem\Filesystem
    {
        if ($path === '') {
            return null;
        }
        if (Storage::disk('local')->exists($path)) {
            return Storage::disk('local');
        }
        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public');
        }
        return null;
    }

    /**
     * Build line-level diff operations via a longest-common-subsequence matrix.
     *
     * Returns an array of operations, each with 'type' (equal | add | remove)
     * and 'line' (the text of that line).
     *
     * @param array<int, string> $oldLines
     * @param array<int, string> $newLines
     * @return array<int, array{type: string, line: string}>
     */
    protected function buildLineDiffOperations(array $oldLines, array $newLines): array
    {
        $oldCount = count($oldLines);
        $newCount = count($newLines);

        $lcs = array_fill(0, $oldCount + 1, array_fill(0, $newCount + 1, 0));

        for ($i = $oldCount - 1; $i >= 0; $i--) {
            for ($j = $newCount - 1; $j >= 0; $j--) {
                if ($oldLines[$i] === $newLines[$j]) {
                    $lcs[$i][$j] = $lcs[$i + 1][$j + 1] + 1;
                } else {
                    $lcs[$i][$j] = max($lcs[$i + 1][$j], $lcs[$i][$j + 1]);
                }
            }
        }

        $ops = [];
        $i = 0;
        $j = 0;
        while ($i < $oldCount && $j < $newCount) {
            if ($oldLines[$i] === $newLines[$j]) {
                $ops[] = ['type' => 'equal', 'line' => $oldLines[$i]];
                $i++;
                $j++;
                continue;
            }

            if ($lcs[$i + 1][$j] >= $lcs[$i][$j + 1]) {
                $ops[] = ['type' => 'remove', 'line' => $oldLines[$i]];
                $i++;
            } else {
                $ops[] = ['type' => 'add', 'line' => $newLines[$j]];
                $j++;
            }
        }

        while ($i < $oldCount) {
            $ops[] = ['type' => 'remove', 'line' => $oldLines[$i]];
            $i++;
        }
        while ($j < $newCount) {
            $ops[] = ['type' => 'add', 'line' => $newLines[$j]];
            $j++;
        }

        return $ops;
    }

    /**
     * Build high-level summary counts from line-level operations.
     *
     * 'changed' is an approximation: the smaller of added vs removed counts,
     * since paired add/remove sequences typically represent modified lines.
     *
     * @param array<int, array{type: string, line: string}> $ops
     * @return array{added: int, removed: int, changed: int}
     */
    protected function buildDiffSummary(array $ops): array
    {
        $added = 0;
        $removed = 0;

        foreach ($ops as $op) {
            if ($op['type'] === 'add') {
                $added++;
            } elseif ($op['type'] === 'remove') {
                $removed++;
            }
        }

        return [
            'added'   => $added,
            'removed' => $removed,
            // Approximation: paired adds/removes typically represent modified lines.
            'changed' => min($added, $removed),
        ];
    }

    /**
     * Reformat flat inline diff operations into side-by-side row pairs.
     *
     * Consecutive remove+add pairs are treated as modified lines (shown on the
     * same row). Solo removes appear only on the left; solo adds only on the right.
     * Equal lines are shown in both columns.
     *
     * @param array<int, array{type: string, line: string}> $ops
     * @return array<int, array{left: string, right: string, type: string}>
     */
    protected function buildSideBySideDiff(array $ops): array
    {
        $rows  = [];
        $count = count($ops);
        $i     = 0;

        while ($i < $count) {
            $op = $ops[$i];

            if ($op['type'] === 'equal') {
                $rows[] = ['left' => $op['line'], 'right' => $op['line'], 'type' => 'equal'];
                $i++;
            } elseif ($op['type'] === 'remove' && isset($ops[$i + 1]) && $ops[$i + 1]['type'] === 'add') {
                // Paired remove+add → treat as a modified line
                $rows[] = ['left' => $ops[$i]['line'], 'right' => $ops[$i + 1]['line'], 'type' => 'change'];
                $i += 2;
            } elseif ($op['type'] === 'remove') {
                $rows[] = ['left' => $op['line'], 'right' => '', 'type' => 'remove'];
                $i++;
            } else {
                // 'add'
                $rows[] = ['left' => '', 'right' => $op['line'], 'type' => 'add'];
                $i++;
            }
        }

        return $rows;
    }
}
