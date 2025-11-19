<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupTempFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cleanup:temp-files {--age=3600 : Age in seconds for file deletion (default: 1 hour)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up temporary upload files older than specified age';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $maxAge = (int) $this->option('age');
        $this->info("Cleaning up files older than {$maxAge} seconds...");

        $directories = [
            storage_path('app/uploads/docx'),
            storage_path('app/uploads/xlsx'),
            storage_path('app/uploads'),
        ];

        $totalDeleted = 0;
        $totalErrors = 0;

        foreach ($directories as $directory) {
            if (!is_dir($directory)) {
                $this->warn("Directory does not exist: {$directory}");
                continue;
            }

            $files = glob($directory . '/*');

            if ($files === false) {
                $this->error("Failed to read directory: {$directory}");
                $totalErrors++;
                continue;
            }

            foreach ($files as $file) {
                if (!is_file($file)) {
                    continue;
                }

                $fileAge = time() - filemtime($file);

                if ($fileAge > $maxAge) {
                    if (@unlink($file)) {
                        $totalDeleted++;
                        $this->line("Deleted: " . basename($file) . " (age: {$fileAge}s)");

                        Log::info('Temp file deleted', [
                            'file' => $file,
                            'age_seconds' => $fileAge
                        ]);
                    } else {
                        $totalErrors++;
                        $this->error("Failed to delete: " . basename($file));

                        Log::error('Failed to delete temp file', [
                            'file' => $file,
                            'age_seconds' => $fileAge
                        ]);
                    }
                }
            }
        }

        $this->newLine();
        $this->info("Cleanup complete!");
        $this->info("Files deleted: {$totalDeleted}");

        if ($totalErrors > 0) {
            $this->warn("Errors encountered: {$totalErrors}");
        }

        return $totalErrors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
