<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BackupSchedule extends Model
{
    use HasFactory;

    public const TIMEZONE = 'Asia/Manila';

    protected $fillable = [
        'is_enabled',
        'frequency',
        'day_of_week',
        'day_of_month',
        'execution_time',
        'last_run_at',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'day_of_week' => 'integer',
        'day_of_month' => 'integer',
        'last_run_at' => 'datetime',
    ];

    /**
     * Parse execution_time (HH:mm) into [hour, minute].
     *
     * @return array{0: int, 1: int}
     */
    public function parseExecutionTime(): array
    {
        $time = (string) ($this->execution_time ?: '02:00');
        if (preg_match('/^([01]?\d|2[0-3]):([0-5]\d)$/', $time, $matches)) {
            return [(int) $matches[1], (int) $matches[2]];
        }

        return [2, 0];
    }

    /**
     * Get the most recent scheduled execution slot in UTC+8 (Asia/Manila) that is <= $now.
     */
    public function getLatestScheduledSlot(?CarbonInterface $now = null): CarbonInterface
    {
        $current = ($now ? $now->copy() : now())->setTimezone(self::TIMEZONE);
        [$hour, $minute] = $this->parseExecutionTime();

        if ($this->frequency === 'monthly') {
            $targetDay = max(1, min(31, (int) ($this->day_of_month ?? 1)));
            $thisMonthDay = min($targetDay, $current->daysInMonth);
            $candidate = $current->copy()
                ->setDate($current->year, $current->month, $thisMonthDay)
                ->setTime($hour, $minute, 0);

            if ($candidate->gt($current)) {
                $prevMonth = $current->copy()->startOfMonth()->subMonth();
                $prevMonthDay = min($targetDay, $prevMonth->daysInMonth);
                $candidate = $prevMonth
                    ->setDate($prevMonth->year, $prevMonth->month, $prevMonthDay)
                    ->setTime($hour, $minute, 0);
            }

            return $candidate;
        }

        $targetDayOfWeek = max(0, min(6, (int) ($this->day_of_week ?? 0)));
        $daysSinceTarget = ($current->dayOfWeek - $targetDayOfWeek + 7) % 7;
        $candidate = $current->copy()
            ->subDays($daysSinceTarget)
            ->setTime($hour, $minute, 0);

        if ($candidate->gt($current)) {
            $candidate->subWeek();
        }

        return $candidate;
    }

    /**
     * Get the next upcoming scheduled execution slot in UTC+8 (Asia/Manila).
     */
    public function getNextScheduledSlot(?CarbonInterface $now = null): CarbonInterface
    {
        $latestSlot = $this->getLatestScheduledSlot($now);
        [$hour, $minute] = $this->parseExecutionTime();

        if ($this->frequency === 'monthly') {
            $targetDay = max(1, min(31, (int) ($this->day_of_month ?? 1)));
            $nextMonth = $latestSlot->copy()->startOfMonth()->addMonth();
            $nextMonthDay = min($targetDay, $nextMonth->daysInMonth);

            return $nextMonth
                ->setDate($nextMonth->year, $nextMonth->month, $nextMonthDay)
                ->setTime($hour, $minute, 0);
        }

        return $latestSlot->copy()->addWeek();
    }

    /**
     * Determine whether an automated backup is due as of $now.
     */
    public function isDue(?CarbonInterface $now = null): bool
    {
        if (!$this->is_enabled) {
            return false;
        }

        $latestSlot = $this->getLatestScheduledSlot($now);

        if ($this->last_run_at === null) {
            return true;
        }

        return $this->last_run_at->copy()->setTimezone(self::TIMEZONE)->lt($latestSlot);
    }
}
