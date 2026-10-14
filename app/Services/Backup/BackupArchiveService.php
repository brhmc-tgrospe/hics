<?php

namespace App\Services\Backup;

use App\Models\BackupHistory;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use ZipArchive;
use Exception;
use RuntimeException;

class BackupArchiveService
{
    public const MAX_RETAINED_BACKUPS = 4;

    public function __construct(
        protected DatabaseBackupService $databaseService,
        protected SystemHealthService $healthService
    ) {}

    /**
     * Orchestrate complete backup: health snapshot, db dump, zip bundling, and retention pruning.
     */
    public function createBackup(?int $triggeredByUserId = null): BackupHistory
    {
        $backupTime = now()->setTimezone('Asia/Manila');
        $date = $backupTime->format('Y-m-d');
        $time = $backupTime->format('His');
        $filename = "HICS_{$date}_{$time}.zip";
        $relativePath = "backups/{$filename}";

        // Create pending history entry
        $history = BackupHistory::create([
            'filename' => $filename,
            'file_path' => $relativePath,
            'status' => 'running',
            'triggered_by' => $triggeredByUserId,
        ]);

        $tempDir = storage_path("app/temp/backup_HICS_{$date}_{$time}");
        $backupDir = storage_path("app/backups");

        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        try {
            // 1. Pre-flight check: ensure enough disk space (> 500MB free)
            $freeSpace = @disk_free_space(base_path()) ?: 0;
            if ($freeSpace < (500 * 1024 * 1024)) {
                throw new RuntimeException("Insufficient server disk space. Less than 500MB free on drive.");
            }

            // 2. Setup temporary folders
            $dbTempDir = "{$tempDir}/database";
            $healthTempDir = "{$tempDir}/system_health";
            File::makeDirectory($dbTempDir, 0755, true);
            File::makeDirectory($healthTempDir, 0755, true);

            // 3. Gather System Health
            $healthSnapshot = $this->healthService->getHealthSnapshot();
            $healthMarkdown = $this->healthService->generateMarkdownReport($healthSnapshot);

            File::put("{$healthTempDir}/health_summary.json", json_encode($healthSnapshot, JSON_PRETTY_PRINT));
            File::put("{$healthTempDir}/health_summary.md", $healthMarkdown);

            // 4. Dump Database
            $sqlDumpPath = "{$dbTempDir}/database_dump.sql";
            $dumpResult = $this->databaseService->dumpDatabase($sqlDumpPath);

            // 5. Generate Manifest
            $initiator = $triggeredByUserId ? User::find($triggeredByUserId) : null;
            $manifest = [
                'app_name' => config('app.name', 'BRHMC - HICS'),
                'backup_version' => '1.0',
                'created_at' => $backupTime->toIso8601String(),
                'timezone' => 'UTC+8 (Asia/Manila)',
                'triggered_by' => $initiator ? [
                    'id' => $initiator->id,
                    'name' => $initiator->first_name . ' ' . $initiator->last_name,
                    'email' => $initiator->email,
                ] : 'Automated Scheduler',
                'database' => [
                    'dump_file' => 'database/database_dump.sql',
                    'dump_size_bytes' => $dumpResult['file_size'],
                    'dump_duration_seconds' => $dumpResult['duration_seconds'],
                    'tool_used' => $dumpResult['tool_used'],
                    'sha256_checksum' => hash_file('sha256', $sqlDumpPath),
                    'total_tables' => $healthSnapshot['database']['total_tables'] ?? 0,
                    'total_rows' => $healthSnapshot['database']['total_rows'] ?? 0,
                ],
                'system_health' => [
                    'server_os' => $healthSnapshot['server']['os_description'] ?? 'Unknown',
                    'php_version' => $healthSnapshot['server']['php_version'] ?? PHP_VERSION,
                    'disk_free' => $healthSnapshot['storage']['free_formatted'] ?? 'Unknown',
                ],
            ];

            File::put("{$tempDir}/manifest.json", json_encode($manifest, JSON_PRETTY_PRINT));

            // 6. Compress into .zip archive
            $zipDestination = storage_path("app/{$relativePath}");
            $this->createZipArchive($tempDir, $zipDestination);

            // 7. Update history record to completed
            $history->update([
                'status' => 'completed',
                'file_size' => filesize($zipDestination),
                'health_summary' => [
                    'database_size' => $healthSnapshot['database']['total_size_formatted'] ?? '0 B',
                    'total_tables' => $healthSnapshot['database']['total_tables'] ?? 0,
                    'total_rows' => $healthSnapshot['database']['total_rows'] ?? 0,
                    'free_disk' => $healthSnapshot['storage']['free_formatted'] ?? '0 B',
                    'used_disk_pct' => $healthSnapshot['storage']['used_percentage'] ?? 0,
                ],
            ]);

            // 8. Enforce strict 4-backup retention limit
            $this->enforceRetention();

            return $history->fresh();

        } catch (Exception $e) {
            $history->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        } finally {
            // Clean up temporary directory
            if (File::isDirectory($tempDir)) {
                File::deleteDirectory($tempDir);
            }
        }
    }

    /**
     * Compress contents of directory into a ZIP file.
     */
    protected function createZipArchive(string $sourceDir, string $destinationZip): void
    {
        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            if ($zip->open($destinationZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException("Cannot open or create zip archive at {$destinationZip}");
            }

            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($sourceDir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($files as $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($sourceDir) + 1);
                    // Standardize slashes for cross-platform archive compatibility
                    $zipPath = str_replace('\\', '/', $relativePath);
                    $zip->addFile($filePath, $zipPath);
                }
            }

            $zip->close();
            return;
        }

        // Fallback to PowerShell Compress-Archive on Windows
        if (PHP_OS_FAMILY === 'Windows') {
            $escapedSource = escapeshellarg($sourceDir . '\*');
            $escapedDest = escapeshellarg($destinationZip);
            $cmd = "powershell -NoProfile -Command \"Compress-Archive -Path {$escapedSource} -DestinationPath {$escapedDest} -Force\"";
            $process = Process::fromShellCommandline($cmd);
            $process->setTimeout(600);
            $process->run();

            if (!$process->isSuccessful() || !file_exists($destinationZip)) {
                throw new RuntimeException("PowerShell zip compression failed: " . $process->getErrorOutput());
            }
            return;
        }

        throw new RuntimeException("No zip compression tool available on server.");
    }

    /**
     * Enforce strict 4-backup retention.
     * Keeps the 4 most recent completed backups and deletes older physical files & records.
     */
    public function enforceRetention(): int
    {
        $completedBackups = BackupHistory::where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($completedBackups->count() <= self::MAX_RETAINED_BACKUPS) {
            return 0;
        }

        $toPrune = $completedBackups->slice(self::MAX_RETAINED_BACKUPS);
        $prunedCount = 0;

        foreach ($toPrune as $oldBackup) {
            // Delete file from disk
            if (Storage::exists($oldBackup->file_path)) {
                Storage::delete($oldBackup->file_path);
            } else {
                $fullPath = storage_path("app/{$oldBackup->file_path}");
                if (file_exists($fullPath)) {
                    @unlink($fullPath);
                }
            }

            // Remove database history record
            $oldBackup->delete();
            $prunedCount++;
        }

        return $prunedCount;
    }

    /**
     * Manually delete a backup file and its record.
     */
    public function deleteBackup(BackupHistory $history): bool
    {
        if (Storage::exists($history->file_path)) {
            Storage::delete($history->file_path);
        } else {
            $fullPath = storage_path("app/{$history->file_path}");
            if (file_exists($fullPath)) {
                @unlink($fullPath);
            }
        }

        return $history->delete();
    }
}
