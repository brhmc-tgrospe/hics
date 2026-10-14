<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Exception;
use RuntimeException;

class DatabaseBackupService
{
    /**
     * Dump the configured database directly to an SQL file.
     *
     * @param string $outputPath Absolute file path for the .sql dump
     * @return array Metadata about the dump (size, execution_time, tool_used)
     */
    public function dumpDatabase(string $outputPath): array
    {
        $startTime = microtime(true);
        $connection = config('database.default');

        if ($connection !== 'mysql') {
            throw new RuntimeException("Database backup is only configured for MySQL (current connection: {$connection}).");
        }

        $host = config("database.connections.{$connection}.host", '127.0.0.1');
        $port = config("database.connections.{$connection}.port", '3306');
        $database = config("database.connections.{$connection}.database");
        $username = config("database.connections.{$connection}.username");
        $password = config("database.connections.{$connection}.password");

        $mysqldumpPath = $this->resolveMysqldumpBinary();

        if ($mysqldumpPath) {
            try {
                $this->runMysqldump($mysqldumpPath, $host, $port, $database, $username, $password, $outputPath);
                $tool = 'mysqldump (native binary)';
            } catch (Exception $e) {
                // If mysqldump fails for any reason, fall back to PHP PDO streaming
                $this->fallbackPhpDump($outputPath);
                $tool = 'PHP PDO Streaming (fallback after mysqldump error: ' . $e->getMessage() . ')';
            }
        } else {
            $this->fallbackPhpDump($outputPath);
            $tool = 'PHP PDO Streaming (mysqldump binary not found)';
        }

        if (!file_exists($outputPath) || filesize($outputPath) === 0) {
            throw new RuntimeException("Database dump failed: output file is missing or empty.");
        }

        $duration = round(microtime(true) - $startTime, 2);
        $sizeBytes = filesize($outputPath);

        return [
            'file_path' => $outputPath,
            'file_size' => $sizeBytes,
            'duration_seconds' => $duration,
            'tool_used' => $tool,
        ];
    }

    /**
     * Run mysqldump using Symfony Process with zero table locking.
     */
    protected function runMysqldump(
        string $binary,
        string $host,
        string $port,
        string $database,
        string $username,
        string $password,
        string $outputPath
    ): void {
        // Windows cmd / powershell safe execution
        $cmd = [
            $binary,
            '--host=' . $host,
            '--port=' . $port,
            '--user=' . $username,
            '--single-transaction', // Consistent InnoDB read snapshot with zero locking
            '--quick',              // Stream row by row; prevents buffering entire tables in RAM
            '--skip-lock-tables',   // Do not lock tables
            '--default-character-set=utf8mb4',
            '--routines',
            '--triggers',
            $database,
        ];

        // Use environment variable for password to avoid exposing password in process list
        $env = array_merge($_ENV, [
            'MYSQL_PWD' => $password,
        ]);

        $process = new Process($cmd, null, $env, null, 1800); // 30 mins max timeout

        $fileHandle = fopen($outputPath, 'w');
        if (!$fileHandle) {
            throw new RuntimeException("Unable to open output file for writing: {$outputPath}");
        }

        try {
            $process->run(function ($type, $buffer) use ($fileHandle) {
                if ($type === Process::OUT) {
                    fwrite($fileHandle, $buffer);
                }
            });

            if (!$process->isSuccessful()) {
                throw new RuntimeException("mysqldump failed: " . $process->getErrorOutput());
            }
        } finally {
            fclose($fileHandle);
        }
    }

    /**
     * Fallback pure-PHP PDO streaming export.
     * Streams CREATE TABLE and batched INSERTs with low memory consumption.
     */
    protected function fallbackPhpDump(string $outputPath): void
    {
        $handle = fopen($outputPath, 'w');
        if (!$handle) {
            throw new RuntimeException("Cannot open {$outputPath} for writing.");
        }

        try {
            fwrite($handle, "-- BRHMC - HICS Pure PHP Database Backup\n");
            fwrite($handle, "-- Generated: " . now()->toIso8601String() . "\n");
            fwrite($handle, "SET foreign_key_checks = 0;\n\n");

            $tables = DB::select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
            $keyName = 'Tables_in_' . config('database.connections.mysql.database');

            foreach ($tables as $t) {
                $tableName = $t->$keyName ?? array_values((array) $t)[0];

                fwrite($handle, "--\n-- Table structure for `{$tableName}`\n--\n");
                fwrite($handle, "DROP TABLE IF EXISTS `{$tableName}`;\n");

                $createTable = DB::select("SHOW CREATE TABLE `{$tableName}`");
                if (!empty($createTable)) {
                    $createSql = $createTable[0]->{'Create Table'} ?? array_values((array) $createTable[0])[1];
                    fwrite($handle, $createSql . ";\n\n");
                }

                fwrite($handle, "--\n-- Dumping data for table `{$tableName}`\n--\n");

                // Stream rows in chunks of 500
                $pdo = DB::getPdo();
                $stmt = $pdo->query("SELECT * FROM `{$tableName}`");

                $batch = [];
                $count = 0;

                while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                    $escapedValues = array_map(function ($val) use ($pdo) {
                        return $val === null ? 'NULL' : $pdo->quote($val);
                    }, $row);

                    $batch[] = '(' . implode(', ', $escapedValues) . ')';
                    $count++;

                    if ($count >= 250) {
                        $cols = '`' . implode('`, `', array_keys($row)) . '`';
                        fwrite($handle, "INSERT INTO `{$tableName}` ({$cols}) VALUES\n" . implode(",\n", $batch) . ";\n");
                        $batch = [];
                        $count = 0;
                    }
                }

                if (!empty($batch)) {
                    $sample = reset($batch);
                    // Get columns from last row
                    $cols = '`' . implode('`, `', array_keys($row)) . '`';
                    fwrite($handle, "INSERT INTO `{$tableName}` ({$cols}) VALUES\n" . implode(",\n", $batch) . ";\n");
                }

                fwrite($handle, "\n");
            }

            fwrite($handle, "SET foreign_key_checks = 1;\n");
        } finally {
            fclose($handle);
        }
    }

    /**
     * Locate mysqldump binary in common Laragon paths or PATH.
     */
    public function resolveMysqldumpBinary(): ?string
    {
        // 1. Explicit environment or config variable
        $customPath = env('MYSQLDUMP_PATH');
        if ($customPath && file_exists($customPath)) {
            return $customPath;
        }

        // 2. Search Laragon MySQL directories
        $laragonBase = 'C:\\laragon\\bin\\mysql';
        if (is_dir($laragonBase)) {
            $versions = glob($laragonBase . '\\*\\bin\\mysqldump.exe');
            if (!empty($versions) && file_exists($versions[0])) {
                return $versions[0];
            }
        }

        // 3. Search system PATH
        $paths = explode(PATH_SEPARATOR, getenv('PATH') ?: '');
        foreach ($paths as $path) {
            $candidate = rtrim($path, '\\/') . DIRECTORY_SEPARATOR . 'mysqldump.exe';
            if (file_exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
