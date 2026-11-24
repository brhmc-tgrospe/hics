<?php

namespace App\Console\Commands;

use App\Models\BackupHistory;
use App\Models\BackupSchedule;
use App\Services\Backup\BackupArchiveService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class RunScheduledBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'backup:run-scheduled {--force : Force execution ignoring day/time conditions}';

    /**
     * The console command description.
     */
    protected $description = 'Evaluate configured backup schedule and trigger backup when due';

    /**
     * Execute the console command.
     */
    public function handle(BackupArchiveService $archiveService): int
    {
        $lock = Cache::lock('hics_scheduled_backup_lock', 1800);

        if (!$lock->get()) {
            $this->line('Scheduled backup check skipped: another backup run is currently in progress.');

            return self::SUCCESS;
        }

        try {
            $schedule = BackupSchedule::first();

            if (!$schedule || (!$schedule->is_enabled && !$this->option('force'))) {
                $this->line('Automated backup is disabled or not configured.');

                return self::SUCCESS;
            }

            BackupHistory::expireStale();

            if (BackupHistory::whereIn('status', ['pending', 'running'])->exists()) {
                $this->line('A backup operation is already in progress. Skipping scheduled run.');

                return self::SUCCESS;
            }

            if (!$this->option('force') && !$schedule->isDue()) {
                $this->line('Scheduled backup is not due yet.');

                return self::SUCCESS;
            }

            $this->info('Schedule criteria met. Executing backup...');

            $schedule->update(['last_run_at' => now()]);

            $history = $archiveService->createBackup(null);

            activity('backup')
                ->log("Automated scheduled backup created successfully: {$history->filename}");

            $this->info("Scheduled backup created successfully: {$history->filename}");

            return self::SUCCESS;
        } catch (Throwable $e) {
            Log::error('Scheduled backup execution failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            activity('backup')
                ->log('Automated scheduled backup failed: ' . $e->getMessage());

            $this->error('Scheduled backup failed: ' . $e->getMessage());

            return self::FAILURE;
        } finally {
            $lock->release();
        }
    }
}
