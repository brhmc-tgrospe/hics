<?php

namespace App\Console\Commands;

use App\Jobs\CreateBackupJob;
use App\Models\BackupSchedule;
use Illuminate\Console\Command;

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
    public function handle(): int
    {
        $schedule = BackupSchedule::first();

        if (!$schedule || (!$schedule->is_enabled && !$this->option('force'))) {
            $this->line("Automated backup is disabled or not configured.");
            return self::SUCCESS;
        }

        if (!$this->option('force')) {
            $now = now()->setTimezone('Asia/Manila');

            // Prevent running multiple times on the same calendar day in UTC+8
            $lastRun = $schedule->last_run_at ? $schedule->last_run_at->setTimezone('Asia/Manila') : null;
            if ($lastRun && $lastRun->isSameDay($now)) {
                $this->line("Scheduled backup has already executed today (UTC+8).");
                return self::SUCCESS;
            }

            // Check execution time (matching HH:mm in UTC+8)
            $currentTime = $now->format('H:i');
            $targetTime = $schedule->execution_time;

            if ($currentTime !== $targetTime) {
                return self::SUCCESS;
            }

            // Check frequency
            if ($schedule->frequency === 'weekly') {
                if ($now->dayOfWeek !== (int) $schedule->day_of_week) {
                    return self::SUCCESS;
                }
            } elseif ($schedule->frequency === 'monthly') {
                if ($now->day !== (int) $schedule->day_of_month) {
                    return self::SUCCESS;
                }
            }
        }

        $this->info("Schedule criteria met. Dispatching backup job...");

        $schedule->update(['last_run_at' => now()]);

        // Dispatch queued job
        CreateBackupJob::dispatch(null);

        $this->info("Backup job dispatched successfully.");
        return self::SUCCESS;
    }
}
