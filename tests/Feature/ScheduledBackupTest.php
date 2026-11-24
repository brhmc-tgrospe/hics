<?php

namespace Tests\Feature;

use App\Models\BackupHistory;
use App\Models\BackupSchedule;
use App\Models\User;
use App\Services\Backup\BackupArchiveService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ScheduledBackupTest extends TestCase
{
    use RefreshDatabase;

    protected User $developer;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        $developerRole = Role::firstOrCreate(['name' => 'Developer']);
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);

        $this->developer = User::factory()->create();
        $this->developer->assignRole($developerRole);

        $this->admin = User::factory()->create();
        $this->admin->assignRole($adminRole);
    }

    public function test_weekly_schedule_slot_and_due_calculations(): void
    {
        $schedule = BackupSchedule::create([
            'is_enabled' => true,
            'frequency' => 'weekly',
            'day_of_week' => 5, // Friday
            'day_of_month' => 1,
            'execution_time' => '10:00',
            'last_run_at' => Carbon::create(2026, 10, 14, 3, 32, 46, 'Asia/Manila'),
        ]);

        // Friday before execution_time (09:59 UTC+8) -> latest slot is previous Friday (Nov 13)
        $beforeSlot = Carbon::create(2026, 11, 20, 9, 59, 0, 'Asia/Manila');
        $this->assertSame('2026-11-13 10:00:00', $schedule->getLatestScheduledSlot($beforeSlot)->format('Y-m-d H:i:s'));
        $this->assertSame('2026-11-20 10:00:00', $schedule->getNextScheduledSlot($beforeSlot)->format('Y-m-d H:i:s'));
        $this->assertTrue($schedule->isDue($beforeSlot));

        // Friday at exact execution_time (10:00 UTC+8) -> latest slot is today (Nov 20)
        $atSlot = Carbon::create(2026, 11, 20, 10, 0, 0, 'Asia/Manila');
        $this->assertSame('2026-11-20 10:00:00', $schedule->getLatestScheduledSlot($atSlot)->format('Y-m-d H:i:s'));
        $this->assertSame('2026-11-27 10:00:00', $schedule->getNextScheduledSlot($atSlot)->format('Y-m-d H:i:s'));
        $this->assertTrue($schedule->isDue($atSlot));

        // Subsequent Tuesday (Nov 24) when Friday Nov 20 was missed -> still overdue
        $missedLater = Carbon::create(2026, 11, 24, 9, 37, 0, 'Asia/Manila');
        $this->assertSame('2026-11-20 10:00:00', $schedule->getLatestScheduledSlot($missedLater)->format('Y-m-d H:i:s'));
        $this->assertTrue($schedule->isDue($missedLater));

        // After running at Nov 20 10:00 UTC+8 -> no duplicate run until Nov 27 10:00
        $schedule->update(['last_run_at' => Carbon::create(2026, 11, 20, 10, 0, 0, 'Asia/Manila')]);
        $this->assertFalse($schedule->isDue($missedLater));

        // Disabled schedule is never due
        $schedule->update(['is_enabled' => false, 'last_run_at' => null]);
        $this->assertFalse($schedule->isDue($missedLater));
    }

    public function test_monthly_schedule_clamps_short_months_and_evaluates_due_state(): void
    {
        $schedule = BackupSchedule::create([
            'is_enabled' => true,
            'frequency' => 'monthly',
            'day_of_week' => 0,
            'day_of_month' => 31,
            'execution_time' => '02:00',
            'last_run_at' => Carbon::create(2026, 1, 31, 2, 0, 0, 'Asia/Manila'),
        ]);

        // Before Feb 28 02:00 -> latest slot is Jan 31 02:00 (already ran)
        $febBefore = Carbon::create(2026, 2, 28, 1, 59, 0, 'Asia/Manila');
        $this->assertSame('2026-01-31 02:00:00', $schedule->getLatestScheduledSlot($febBefore)->format('Y-m-d H:i:s'));
        $this->assertSame('2026-02-28 02:00:00', $schedule->getNextScheduledSlot($febBefore)->format('Y-m-d H:i:s'));
        $this->assertFalse($schedule->isDue($febBefore));

        // On March 2 after Feb 28 was missed -> latest slot is clamped Feb 28 02:00 -> due
        $marchMissed = Carbon::create(2026, 3, 2, 8, 0, 0, 'Asia/Manila');
        $this->assertSame('2026-02-28 02:00:00', $schedule->getLatestScheduledSlot($marchMissed)->format('Y-m-d H:i:s'));
        $this->assertSame('2026-03-31 02:00:00', $schedule->getNextScheduledSlot($marchMissed)->format('Y-m-d H:i:s'));
        $this->assertTrue($schedule->isDue($marchMissed));

        // Once caught up on March 2 -> not due until March 31 02:00
        $schedule->update(['last_run_at' => $marchMissed->copy()]);
        $this->assertFalse($schedule->isDue(Carbon::create(2026, 3, 30, 23, 59, 0, 'Asia/Manila')));
        $this->assertTrue($schedule->isDue(Carbon::create(2026, 3, 31, 2, 0, 0, 'Asia/Manila')));
    }

    public function test_run_scheduled_backup_command_executes_when_overdue_and_skips_when_already_ran(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 24, 9, 45, 0, 'Asia/Manila'));

        try {
            $schedule = BackupSchedule::create([
                'is_enabled' => true,
                'frequency' => 'weekly',
                'day_of_week' => 5,
                'day_of_month' => 1,
                'execution_time' => '10:00',
                'last_run_at' => Carbon::create(2026, 10, 14, 3, 32, 46, 'Asia/Manila'),
            ]);

            $createdHistory = BackupHistory::create([
                'filename' => 'HICS_2026-11-24_094500.zip',
                'file_path' => 'backups/HICS_2026-11-24_094500.zip',
                'file_size' => 1024,
                'status' => 'completed',
                'triggered_by' => null,
            ]);

            $mock = Mockery::mock(BackupArchiveService::class);
            $mock->shouldReceive('createBackup')
                ->once()
                ->with(null)
                ->andReturn($createdHistory);
            $this->app->instance(BackupArchiveService::class, $mock);

            // First run: overdue -> executes synchronously
            $this->artisan('backup:run-scheduled')
                ->expectsOutput('Schedule criteria met. Executing backup...')
                ->expectsOutput('Scheduled backup created successfully: HICS_2026-11-24_094500.zip')
                ->assertExitCode(0);

            $schedule->refresh();
            $this->assertNotNull($schedule->last_run_at);
            $this->assertTrue($schedule->last_run_at->equalTo(now()));

            // Second run in same cycle: skipped without calling createBackup again
            $this->artisan('backup:run-scheduled')
                ->expectsOutput('Scheduled backup is not due yet.')
                ->assertExitCode(0);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_stale_active_backups_are_expired_before_scheduled_run(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 24, 9, 45, 0, 'Asia/Manila'));

        try {
            BackupSchedule::create([
                'is_enabled' => true,
                'frequency' => 'weekly',
                'day_of_week' => 5,
                'day_of_month' => 1,
                'execution_time' => '10:00',
                'last_run_at' => null,
            ]);

            $stale = BackupHistory::create([
                'filename' => 'HICS_stale.zip',
                'file_path' => 'backups/HICS_stale.zip',
                'status' => 'running',
                'triggered_by' => null,
            ]);
            BackupHistory::whereKey($stale->id)->update([
                'created_at' => now()->subMinutes(45),
            ]);

            $freshHistory = BackupHistory::create([
                'filename' => 'HICS_2026-11-24_094500.zip',
                'file_path' => 'backups/HICS_2026-11-24_094500.zip',
                'file_size' => 2048,
                'status' => 'completed',
                'triggered_by' => null,
            ]);

            $mock = Mockery::mock(BackupArchiveService::class);
            $mock->shouldReceive('createBackup')
                ->once()
                ->with(null)
                ->andReturn($freshHistory);
            $this->app->instance(BackupArchiveService::class, $mock);

            $this->artisan('backup:run-scheduled')->assertExitCode(0);

            $stale->refresh();
            $this->assertSame('failed', $stale->status);
            $this->assertSame('Backup operation timed out.', $stale->error_message);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_developer_can_trigger_synchronous_manual_backup_attributed_to_user(): void
    {
        $history = BackupHistory::create([
            'filename' => 'HICS_manual_test.zip',
            'file_path' => 'backups/HICS_manual_test.zip',
            'file_size' => 4096,
            'status' => 'completed',
            'triggered_by' => $this->developer->id,
        ]);

        $mock = Mockery::mock(BackupArchiveService::class);
        $mock->shouldReceive('createBackup')
            ->once()
            ->with($this->developer->id)
            ->andReturn($history);
        $this->app->instance(BackupArchiveService::class, $mock);

        // Even without passing 'sync', storeBackup defaults to synchronous execution
        $response = $this->actingAs($this->developer)
            ->from(route('system-health.index'))
            ->post(route('system-health.backup.store'));

        $response->assertRedirect(route('system-health.index'));
        $response->assertSessionHas('success', 'Backup completed successfully!');

        // Non-developer cannot trigger backups
        $this->actingAs($this->admin)
            ->post(route('system-health.backup.store'))
            ->assertForbidden();
    }

    public function test_visiting_system_health_catches_up_overdue_backup_and_provides_next_run_prop(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 24, 9, 45, 0, 'Asia/Manila'));

        try {
            BackupSchedule::create([
                'is_enabled' => true,
                'frequency' => 'weekly',
                'day_of_week' => 5,
                'day_of_month' => 1,
                'execution_time' => '10:00',
                'last_run_at' => Carbon::create(2026, 10, 14, 3, 32, 46, 'Asia/Manila'),
            ]);

            $history = BackupHistory::create([
                'filename' => 'HICS_catchup.zip',
                'file_path' => 'backups/HICS_catchup.zip',
                'file_size' => 1024,
                'status' => 'completed',
                'triggered_by' => null,
            ]);

            $mock = Mockery::mock(BackupArchiveService::class);
            $mock->shouldReceive('createBackup')
                ->once()
                ->with(null)
                ->andReturn($history);
            $this->app->instance(BackupArchiveService::class, $mock);

            $response = $this->actingAs($this->developer)
                ->get(route('system-health.index'));

            $response->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component('SystemHealth/Index')
                    ->where('formattedNextRun', 'Fri, Nov 27, 2026 10:00 AM')
                    ->where('formattedLastRun', 'Nov 24, 2026 09:45 AM')
                );
        } finally {
            Carbon::setTestNow();
        }
    }
}
