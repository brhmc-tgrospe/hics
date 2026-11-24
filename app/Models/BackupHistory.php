<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BackupHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'filename',
        'file_path',
        'file_size',
        'status',
        'error_message',
        'triggered_by',
        'health_summary',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'health_summary' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    public function getFormattedFileSizeAttribute(): string
    {
        if (!$this->file_size) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($this->file_size, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Mark stale pending or running backup records older than $timeoutMinutes as failed.
     */
    public static function expireStale(int $timeoutMinutes = 30): int
    {
        return static::whereIn('status', ['pending', 'running'])
            ->where('created_at', '<=', now()->subMinutes($timeoutMinutes))
            ->update([
                'status' => 'failed',
                'error_message' => 'Backup operation timed out.',
            ]);
    }
}
