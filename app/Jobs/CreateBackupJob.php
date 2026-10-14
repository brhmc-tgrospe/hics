<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\Backup\BackupArchiveService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class CreateBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Timeout for backup job in seconds (30 minutes).
     */
    public int $timeout = 1800;

    /**
     * Delete the job if its models no longer exist.
     */
    public bool $deleteWhenMissingModels = true;

    public function __construct(
        public ?int $userId = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(BackupArchiveService $archiveService): void
    {
        try {
            $history = $archiveService->createBackup($this->userId);

            $initiator = $this->userId ? User::find($this->userId) : null;
            $logger = activity('backup');
            if ($initiator) {
                $logger->causedBy($initiator);
            }
            $logger->log("Database and system health backup created successfully: {$history->filename}");

        } catch (Throwable $e) {
            Log::error("Backup creation job failed: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => $this->userId,
            ]);

            $initiator = $this->userId ? User::find($this->userId) : null;
            $logger = activity('backup');
            if ($initiator) {
                $logger->causedBy($initiator);
            }
            $logger->log("Backup creation failed: " . $e->getMessage());

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::critical("CreateBackupJob permanently failed: " . $exception->getMessage());
    }
}
