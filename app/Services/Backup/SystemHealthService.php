<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Exception;

class SystemHealthService
{
    /**
     * Gather complete system, database, and environment health metrics.
     */
    public function getHealthSnapshot(): array
    {
        $now = now()->setTimezone('Asia/Manila');

        return [
            'timestamp' => $now->toIso8601String(),
            'timestamp_formatted' => $now->format('M d, Y h:i:s A (T, \U\T\C+8)'),
            'timezone' => 'UTC+8 (Asia/Manila)',
            'server' => $this->getServerMetrics(),
            'storage' => $this->getStorageMetrics(),
            'database' => $this->getDatabaseMetrics(),
            'application' => $this->getApplicationMetrics(),
        ];
    }

    /**
     * Metrics for the server and PHP runtime.
     */
    protected function getServerMetrics(): array
    {
        return [
            'os' => PHP_OS_FAMILY,
            'os_description' => php_uname('s') . ' ' . php_uname('r') . ' (' . php_uname('m') . ')',
            'php_version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'current_memory_usage' => $this->formatBytes(memory_get_usage(true)),
            'peak_memory_usage' => $this->formatBytes(memory_get_peak_usage(true)),
            'max_execution_time' => ini_get('max_execution_time') . 's',
            'opcache_enabled' => function_exists('opcache_get_status') && !empty(opcache_get_status()),
        ];
    }

    /**
     * Disk storage metrics for drive where application is located.
     */
    protected function getStorageMetrics(): array
    {
        $basePath = base_path();
        $totalBytes = @disk_total_space($basePath) ?: 0;
        $freeBytes = @disk_free_space($basePath) ?: 0;
        $usedBytes = max($totalBytes - $freeBytes, 0);
        $usedPercentage = $totalBytes > 0 ? round(($usedBytes / $totalBytes) * 100, 1) : 0;

        return [
            'path' => $basePath,
            'total_bytes' => $totalBytes,
            'free_bytes' => $freeBytes,
            'used_bytes' => $usedBytes,
            'total_formatted' => $this->formatBytes($totalBytes),
            'free_formatted' => $this->formatBytes($freeBytes),
            'used_formatted' => $this->formatBytes($usedBytes),
            'used_percentage' => $usedPercentage,
            'is_low_space' => $freeBytes < (1024 * 1024 * 1024), // < 1GB
        ];
    }

    /**
     * MySQL database stats, size, and table row counts.
     */
    protected function getDatabaseMetrics(): array
    {
        $connectionName = config('database.default');
        $dbName = config("database.connections.{$connectionName}.database");

        $metrics = [
            'connection' => $connectionName,
            'database_name' => $dbName,
            'status' => 'connected',
            'version' => 'Unknown',
            'total_size_bytes' => 0,
            'total_size_formatted' => '0 B',
            'total_tables' => 0,
            'total_rows' => 0,
            'active_threads' => null,
            'uptime_seconds' => null,
            'tables' => [],
        ];

        try {
            // Version
            $versionResult = DB::select("SELECT VERSION() as version");
            if (!empty($versionResult)) {
                $metrics['version'] = $versionResult[0]->version;
            }

            // Status variables
            $threads = DB::select("SHOW STATUS LIKE 'Threads_connected'");
            if (!empty($threads)) {
                $metrics['active_threads'] = (int) $threads[0]->Value;
            }

            $uptime = DB::select("SHOW STATUS LIKE 'Uptime'");
            if (!empty($uptime)) {
                $metrics['uptime_seconds'] = (int) $uptime[0]->Value;
            }

            // Tables info from information_schema
            $tablesData = DB::select(
                "SELECT 
                    TABLE_NAME AS table_name,
                    TABLE_ROWS AS table_rows,
                    DATA_LENGTH AS data_size,
                    INDEX_LENGTH AS index_size,
                    (DATA_LENGTH + INDEX_LENGTH) AS total_size
                FROM information_schema.TABLES 
                WHERE TABLE_SCHEMA = ?
                ORDER BY (DATA_LENGTH + INDEX_LENGTH) DESC",
                [$dbName]
            );

            $totalSizeBytes = 0;
            $totalRows = 0;
            $tables = [];

            foreach ($tablesData as $t) {
                $totalSize = (int) ($t->total_size ?? 0);
                $dataSize = (int) ($t->data_size ?? 0);
                $indexSize = (int) ($t->index_size ?? 0);
                $rows = (int) ($t->table_rows ?? 0);

                $totalSizeBytes += $totalSize;
                $totalRows += $rows;

                $tables[] = [
                    'name' => $t->table_name,
                    'rows' => $rows,
                    'data_size_bytes' => $dataSize,
                    'data_size_formatted' => $this->formatBytes($dataSize),
                    'index_size_bytes' => $indexSize,
                    'index_size_formatted' => $this->formatBytes($indexSize),
                    'total_size_bytes' => $totalSize,
                    'total_size_formatted' => $this->formatBytes($totalSize),
                    'size_bytes' => $totalSize,
                    'size_formatted' => $this->formatBytes($totalSize),
                ];
            }

            $metrics['total_size_bytes'] = $totalSizeBytes;
            $metrics['total_size_formatted'] = $this->formatBytes($totalSizeBytes);
            $metrics['total_tables'] = count($tables);
            $metrics['total_rows'] = $totalRows;
            $metrics['tables'] = $tables;

        } catch (Exception $e) {
            $metrics['status'] = 'error: ' . $e->getMessage();
        }

        return $metrics;
    }

    /**
     * Application state and environment info.
     */
    protected function getApplicationMetrics(): array
    {
        return [
            'app_name' => config('app.name', 'BRHMC - HICS'),
            'environment' => app()->environment(),
            'laravel_version' => app()->version(),
            'debug_mode' => config('app.debug'),
            'queue_connection' => config('queue.default'),
            'storage_writable' => is_writable(storage_path()),
            'timezone' => config('app.timezone'),
        ];
    }

    /**
     * Generate human-readable Markdown report from snapshot.
     */
    public function generateMarkdownReport(array $snapshot): string
    {
        $server = $snapshot['server'] ?? [];
        $storage = $snapshot['storage'] ?? [];
        $db = $snapshot['database'] ?? [];
        $app = $snapshot['application'] ?? [];
        $timestamp = $snapshot['timestamp'] ?? now()->toIso8601String();

        $md = "# System Health Diagnostic Report\n\n";
        $md .= "**Captured At:** {$timestamp}  \n";
        $md .= "**Application:** {$app['app_name']} ({$app['environment']})  \n";
        $md .= "**Laravel Version:** {$app['laravel_version']}  \n\n";

        $md .= "## 1. Storage & Server Vitality\n\n";
        $md .= "| Metric | Value |\n";
        $md .= "| :--- | :--- |\n";
        $md .= "| **Total Disk Space** | {$storage['total_formatted']} |\n";
        $md .= "| **Used Disk Space** | {$storage['used_formatted']} ({$storage['used_percentage']}%) |\n";
        $md .= "| **Free Disk Space** | {$storage['free_formatted']} |\n";
        $md .= "| **Operating System** | {$server['os_description']} |\n";
        $md .= "| **PHP Version** | {$server['php_version']} |\n";
        $md .= "| **PHP Memory Limit** | {$server['memory_limit']} |\n";
        $md .= "| **Max Execution Time** | {$server['max_execution_time']} |\n\n";

        $md .= "## 2. Database Overview\n\n";
        $md .= "| Metric | Value |\n";
        $md .= "| :--- | :--- |\n";
        $md .= "| **Database Name** | `{$db['database_name']}` |\n";
        $md .= "| **Engine / Version** | MySQL {$db['version']} |\n";
        $md .= "| **Overall Size** | {$db['total_size_formatted']} |\n";
        $md .= "| **Total Tables** | {$db['total_tables']} |\n";
        $md .= "| **Total Estimated Rows** | " . number_format($db['total_rows'] ?? 0) . " |\n";
        if (isset($db['active_threads'])) {
            $md .= "| **Active Connections** | {$db['active_threads']} |\n";
        }
        $md .= "\n";

        $md .= "### Table Summary Breakdown\n\n";
        $md .= "| Table | Est. Rows | Data Size | Index Size | Total Size |\n";
        $md .= "| :--- | :--- | :--- | :--- | :--- |\n";

        $topTables = array_slice($db['tables'] ?? [], 0, 30);
        foreach ($topTables as $table) {
            $dataSize = $table['data_size_formatted'] ?? '0 B';
            $idxSize = $table['index_size_formatted'] ?? '0 B';
            $totSize = $table['total_size_formatted'] ?? $table['size_formatted'];
            $md .= "| `{$table['name']}` | " . number_format($table['rows']) . " | {$dataSize} | {$idxSize} | **{$totSize}** |\n";
        }

        if (count($db['tables'] ?? []) > 25) {
            $remaining = count($db['tables']) - 25;
            $md .= "\n*...and {$remaining} additional tables.*\n";
        }

        return $md;
    }

    /**
     * Format bytes into human-readable representation.
     */
    public function formatBytes(int|float $bytes, int $precision = 2): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $base = log($bytes, 1024);
        $power = min((int) floor($base), count($units) - 1);

        return round(pow(1024, $base - $power), $precision) . ' ' . $units[$power];
    }
}
