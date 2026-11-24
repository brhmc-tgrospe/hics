<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Backup\BackupArchiveService;
use Illuminate\Console\Command;

class CreateBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'backup:create {--user= : Optional user ID who initiated the backup}';

    /**
     * The console command description.
     */
    protected $description = 'Generate an optimized database backup bundled with system health metrics';

    /**
     * Execute the console command.
     */
    public function handle(BackupArchiveService $archiveService): int
    {
        $this->info("Starting database and system health backup...");

        $userId = $this->option('user') ? (int) $this->option('user') : null;
        $initiator = $userId ? User::find($userId) : null;

        try {
            $history = $archiveService->createBackup($userId);

            $logger = activity('backup');
            if ($initiator) {
                $logger->causedBy($initiator);
            }
            $logger->log("Database and system health backup created via CLI: {$history->filename}");

            $this->info("Backup generated successfully!");
            $this->table(
                ['Filename', 'Size', 'Status', 'File Path'],
                [
                    [
                        $history->filename,
                        $history->formatted_file_size,
                        $history->status,
                        $history->file_path,
                    ]
                ]
            );

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $logger = activity('backup');
            if ($initiator) {
                $logger->causedBy($initiator);
            }
            $logger->log('CLI backup failed: ' . $e->getMessage());

            $this->error("Backup failed: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}
