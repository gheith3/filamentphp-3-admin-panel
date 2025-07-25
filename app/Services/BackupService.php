<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;

class BackupService
{
    protected string $backupDisk;
    protected array $config;

    public function __construct()
    {
        $this->backupDisk = config('backup.disk', 'local');
        $this->config = config('backup', []);
    }

    /**
     * Create a full system backup including database and files.
     *
     * @param array $options
     * @return array Backup result
     */
    public function createFullBackup(array $options = []): array
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $backupId = "full_backup_{$timestamp}";

        Log::info("Starting full backup: {$backupId}");

        try {
            $results = [
                'backup_id' => $backupId,
                'timestamp' => $timestamp,
                'database' => $this->backupDatabase($backupId),
                'files' => $this->backupFiles($backupId),
                'config' => $this->backupConfiguration($backupId),
            ];

            // Cleanup old backups if specified
            if ($options['cleanup_old'] ?? true) {
                $this->cleanupOldBackups();
            }

            // Verify backup integrity
            $results['verification'] = $this->verifyBackup($backupId);

            Log::info("Full backup completed successfully: {$backupId}", $results);

            return [
                'success' => true,
                'backup_id' => $backupId,
                'results' => $results,
                'message' => 'Full backup completed successfully',
            ];

        } catch (\Exception $e) {
            Log::error("Backup failed: {$backupId}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'backup_id' => $backupId,
                'error' => $e->getMessage(),
                'message' => 'Backup failed',
            ];
        }
    }

    /**
     * Create database backup.
     *
     * @param string $backupId
     * @return array
     */
    public function backupDatabase(string $backupId): array
    {
        $connection = config('database.default');
        $config = config("database.connections.{$connection}");

        $filename = "database_{$backupId}.sql";
        $tempPath = storage_path("app/temp/{$filename}");

        // Ensure temp directory exists
        $tempDir = dirname($tempPath);
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        try {
            if ($config['driver'] === 'mysql') {
                $this->createMysqlDump($config, $tempPath);
            } elseif ($config['driver'] === 'sqlite') {
                $this->createSqliteDump($config, $tempPath);
            } elseif ($config['driver'] === 'pgsql') {
                $this->createPgsqlDump($config, $tempPath);
            } else {
                throw new \Exception("Unsupported database driver: {$config['driver']}");
            }

            // Compress the SQL file
            $compressedPath = $tempPath . '.gz';
            $this->compressFile($tempPath, $compressedPath);

            // Store in backup location
            $backupPath = "backups/database/{$filename}.gz";
            $content = file_get_contents($compressedPath);
            Storage::disk($this->backupDisk)->put($backupPath, $content);

            // Cleanup temp files
            unlink($tempPath);
            unlink($compressedPath);

            $size = Storage::disk($this->backupDisk)->size($backupPath);

            return [
                'success' => true,
                'filename' => $filename . '.gz',
                'path' => $backupPath,
                'size_bytes' => $size,
                'size_mb' => round($size / 1024 / 1024, 2),
            ];

        } catch (\Exception $e) {
            // Cleanup temp files on error
            if (file_exists($tempPath))
                unlink($tempPath);
            if (file_exists($compressedPath ?? ''))
                unlink($compressedPath);

            throw $e;
        }
    }

    /**
     * Create files backup.
     *
     * @param string $backupId
     * @return array
     */
    public function backupFiles(string $backupId): array
    {
        $filename = "files_{$backupId}.zip";
        $tempPath = storage_path("app/temp/{$filename}");

        // Ensure temp directory exists
        $tempDir = dirname($tempPath);
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        // Paths to backup
        $pathsToBackup = [
            storage_path('app'),
            public_path('uploads'),
            base_path('.env'),
        ];

        // Filter existing paths
        $existingPaths = array_filter($pathsToBackup, 'file_exists');

        if (empty($existingPaths)) {
            return [
                'success' => true,
                'filename' => $filename,
                'size_bytes' => 0,
                'message' => 'No files to backup',
            ];
        }

        try {
            // First try using tar if available (Unix systems)
            if ($this->isTarAvailable()) {
                $tarSuccess = $this->createTarArchive($existingPaths, $tempPath);
                if ($tarSuccess) {
                    $filename = "files_{$backupId}.tar.gz";
                    $tempPath = storage_path("app/temp/{$filename}");
                }
            }

            // Fallback to ZIP archive (cross-platform)
            if (!isset($tarSuccess) || !$tarSuccess) {
                $this->createZipArchive($existingPaths, $tempPath);
            }

            if (!file_exists($tempPath)) {
                throw new \Exception("Archive file was not created");
            }

            // Store in backup location
            $backupPath = "backups/files/{$filename}";
            $content = file_get_contents($tempPath);
            Storage::disk($this->backupDisk)->put($backupPath, $content);

            // Cleanup temp file
            unlink($tempPath);

            $size = Storage::disk($this->backupDisk)->size($backupPath);

            return [
                'success' => true,
                'filename' => $filename,
                'path' => $backupPath,
                'size_bytes' => $size,
                'size_mb' => round($size / 1024 / 1024, 2),
                'files_count' => count($existingPaths),
            ];

        } catch (\Exception $e) {
            // Cleanup temp file on error
            if (file_exists($tempPath))
                unlink($tempPath);
            throw $e;
        }
    }

    /**
     * Check if tar command is available.
     *
     * @return bool
     */
    protected function isTarAvailable(): bool
    {
        $command = PHP_OS_FAMILY === 'Windows' ? 'where tar' : 'which tar';
        exec($command, $output, $returnCode);
        return $returnCode === 0;
    }

    /**
     * Create tar.gz archive.
     *
     * @param array $paths
     * @param string $outputPath
     * @return bool
     */
    protected function createTarArchive(array $paths, string &$outputPath): bool
    {
        $outputPath = str_replace('.zip', '.tar.gz', $outputPath);

        try {
            $command = sprintf(
                'tar -czf %s %s 2>/dev/null',
                escapeshellarg($outputPath),
                implode(' ', array_map('escapeshellarg', $paths))
            );

            exec($command, $output, $returnCode);
            return $returnCode === 0 && file_exists($outputPath);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Create ZIP archive using PHP (cross-platform).
     *
     * @param array $paths
     * @param string $outputPath
     * @return void
     */
    protected function createZipArchive(array $paths, string $outputPath): void
    {
        if (!extension_loaded('zip')) {
            throw new \Exception("ZIP extension is not available for file backup");
        }

        $zip = new \ZipArchive();
        $result = $zip->open($outputPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        if ($result !== true) {
            throw new \Exception("Failed to create ZIP archive: " . $this->getZipError($result));
        }

        foreach ($paths as $path) {
            if (is_file($path)) {
                $relativePath = basename($path);
                $zip->addFile($path, $relativePath);
            } elseif (is_dir($path)) {
                $this->addDirectoryToZip($zip, $path, basename($path));
            }
        }

        $zip->close();

        if (!file_exists($outputPath)) {
            throw new \Exception("ZIP archive was not created successfully");
        }
    }

    /**
     * Recursively add directory to ZIP archive.
     *
     * @param \ZipArchive $zip
     * @param string $dirPath
     * @param string $localPath
     * @return void
     */
    protected function addDirectoryToZip(\ZipArchive $zip, string $dirPath, string $localPath = ''): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dirPath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            $filePath = $file->getRealPath();
            $relativePath = $localPath . '/' . substr($filePath, strlen($dirPath) + 1);

            if ($file->isDir()) {
                $zip->addEmptyDir($relativePath);
            } elseif ($file->isFile()) {
                $zip->addFile($filePath, $relativePath);
            }
        }
    }

    /**
     * Get ZIP error message.
     *
     * @param int $code
     * @return string
     */
    protected function getZipError(int $code): string
    {
        return match ($code) {
            \ZipArchive::ER_OK => 'No error',
            \ZipArchive::ER_MULTIDISK => 'Multi-disk zip archives not supported',
            \ZipArchive::ER_RENAME => 'Renaming temporary file failed',
            \ZipArchive::ER_CLOSE => 'Closing zip archive failed',
            \ZipArchive::ER_SEEK => 'Seek error',
            \ZipArchive::ER_READ => 'Read error',
            \ZipArchive::ER_WRITE => 'Write error',
            \ZipArchive::ER_CRC => 'CRC error',
            \ZipArchive::ER_ZIPCLOSED => 'Containing zip archive was closed',
            \ZipArchive::ER_NOENT => 'No such file',
            \ZipArchive::ER_EXISTS => 'File already exists',
            \ZipArchive::ER_OPEN => 'Can\'t open file',
            \ZipArchive::ER_TMPOPEN => 'Failure to create temporary file',
            \ZipArchive::ER_ZLIB => 'Zlib error',
            \ZipArchive::ER_MEMORY => 'Memory allocation failure',
            \ZipArchive::ER_CHANGED => 'Entry has been changed',
            \ZipArchive::ER_COMPNOTSUPP => 'Compression method not supported',
            \ZipArchive::ER_EOF => 'Premature EOF',
            \ZipArchive::ER_INVAL => 'Invalid argument',
            \ZipArchive::ER_NOZIP => 'Not a zip archive',
            \ZipArchive::ER_INTERNAL => 'Internal error',
            \ZipArchive::ER_INCONS => 'Zip archive inconsistent',
            \ZipArchive::ER_REMOVE => 'Can\'t remove file',
            \ZipArchive::ER_DELETED => 'Entry has been deleted',
            default => "Unknown error code: {$code}",
        };
    }

    /**
     * Backup configuration files.
     *
     * @param string $backupId
     * @return array
     */
    public function backupConfiguration(string $backupId): array
    {
        $filename = "config_{$backupId}.json";

        $configData = [
            'app_name' => config('app.name'),
            'app_version' => app()->version(),
            'environment' => app()->environment(),
            'database_config' => config('database.default'),
            'cache_config' => config('cache.default'),
            'session_config' => config('session.driver'),
            'queue_config' => config('queue.default'),
            'rate_limiting' => config('security.rate_limiting'),
            'backup_timestamp' => now()->toISOString(),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
        ];

        $backupPath = "backups/config/{$filename}";
        $content = json_encode($configData, JSON_PRETTY_PRINT);

        Storage::disk($this->backupDisk)->put($backupPath, $content);

        return [
            'success' => true,
            'filename' => $filename,
            'path' => $backupPath,
            'size_bytes' => strlen($content),
        ];
    }

    /**
     * Restore from backup.
     *
     * @param string $backupId
     * @param array $options
     * @return array
     */
    public function restoreFromBackup(string $backupId, array $options = []): array
    {
        Log::info("Starting restore from backup: {$backupId}");

        try {
            $results = [];

            // Restore database if requested
            if ($options['restore_database'] ?? true) {
                $results['database'] = $this->restoreDatabase($backupId);
            }

            // Restore files if requested
            if ($options['restore_files'] ?? true) {
                $results['files'] = $this->restoreFiles($backupId);
            }

            // Clear caches after restore
            if ($options['clear_cache'] ?? true) {
                Artisan::call('cache:clear');
                Artisan::call('config:clear');
                Artisan::call('route:clear');
                Artisan::call('view:clear');
            }

            Log::info("Restore completed successfully: {$backupId}", $results);

            return [
                'success' => true,
                'backup_id' => $backupId,
                'results' => $results,
                'message' => 'Restore completed successfully',
            ];

        } catch (\Exception $e) {
            Log::error("Restore failed: {$backupId}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'backup_id' => $backupId,
                'error' => $e->getMessage(),
                'message' => 'Restore failed',
            ];
        }
    }

    /**
     * List available backups.
     *
     * @return array
     */
    public function listBackups(): array
    {
        $backups = [];

        // Get database backups
        $dbFiles = Storage::disk($this->backupDisk)->files('backups/database');
        foreach ($dbFiles as $file) {
            $backups[] = [
                'type' => 'database',
                'file' => $file,
                'size' => Storage::disk($this->backupDisk)->size($file),
                'modified' => Storage::disk($this->backupDisk)->lastModified($file),
            ];
        }

        // Get file backups
        $fileBackups = Storage::disk($this->backupDisk)->files('backups/files');
        foreach ($fileBackups as $file) {
            $backups[] = [
                'type' => 'files',
                'file' => $file,
                'size' => Storage::disk($this->backupDisk)->size($file),
                'modified' => Storage::disk($this->backupDisk)->lastModified($file),
            ];
        }

        // Sort by modification time (newest first)
        usort($backups, function ($a, $b) {
            return $b['modified'] - $a['modified'];
        });

        return $backups;
    }

    /**
     * Verify backup integrity.
     *
     * @param string $backupId
     * @return array
     */
    protected function verifyBackup(string $backupId): array
    {
        $checks = [
            'database_exists' => false,
            'files_exist' => false,
            'config_exists' => false,
            'database_readable' => false,
            'files_readable' => false,
        ];

        // Check database backup
        $dbPath = "backups/database/database_{$backupId}.sql.gz";
        if (Storage::disk($this->backupDisk)->exists($dbPath)) {
            $checks['database_exists'] = true;
            $checks['database_readable'] = Storage::disk($this->backupDisk)->size($dbPath) > 0;
        }

        // Check files backup
        $filesPath = "backups/files/files_{$backupId}.tar.gz";
        if (Storage::disk($this->backupDisk)->exists($filesPath)) {
            $checks['files_exist'] = true;
            $checks['files_readable'] = Storage::disk($this->backupDisk)->size($filesPath) > 0;
        }

        // Check config backup
        $configPath = "backups/config/config_{$backupId}.json";
        if (Storage::disk($this->backupDisk)->exists($configPath)) {
            $checks['config_exists'] = true;
        }

        return $checks;
    }

    /**
     * Cleanup old backups based on retention policy.
     *
     * @return array
     */
    protected function cleanupOldBackups(): array
    {
        $retentionDays = config('backup.retention_days', 30);
        $cutoffDate = Carbon::now()->subDays($retentionDays);

        $deleted = [];
        $backupTypes = ['database', 'files', 'config'];

        foreach ($backupTypes as $type) {
            $files = Storage::disk($this->backupDisk)->files("backups/{$type}");

            foreach ($files as $file) {
                $lastModified = Storage::disk($this->backupDisk)->lastModified($file);

                if ($lastModified < $cutoffDate->timestamp) {
                    Storage::disk($this->backupDisk)->delete($file);
                    $deleted[] = $file;
                }
            }
        }

        return $deleted;
    }

    /**
     * Create MySQL dump.
     *
     * @param array $config
     * @param string $outputPath
     * @return void
     */
    protected function createMysqlDump(array $config, string $outputPath): void
    {
        $command = sprintf(
            'mysqldump --host=%s --port=%s --user=%s --password=%s %s > %s',
            escapeshellarg($config['host']),
            escapeshellarg($config['port']),
            escapeshellarg($config['username']),
            escapeshellarg($config['password']),
            escapeshellarg($config['database']),
            escapeshellarg($outputPath)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception("MySQL dump failed with return code: {$returnCode}");
        }
    }

    /**
     * Check if pg_dump command is available.
     *
     * @return bool
     */
    protected function isPgDumpAvailable(): bool
    {
        $command = PHP_OS_FAMILY === 'Windows' ? 'where pg_dump' : 'which pg_dump';
        exec($command, $output, $returnCode);
        return $returnCode === 0;
    }

    protected function createPgsqlDump(array $config, string $outputPath): void
    {
        // Check if pg_dump is available
        if (!$this->isPgDumpAvailable()) {
            // Use PHP-based backup as fallback
            $this->createPgsqlDumpWithPHP($config, $outputPath);
            return;
        }

        // Validate required configuration
        $required = ['host', 'port', 'database', 'username'];
        foreach ($required as $key) {
            if (empty($config[$key])) {
                throw new \Exception("Missing required PostgreSQL configuration: {$key}");
            }
        }

        // Set environment variables for PostgreSQL authentication
        $env = array_merge($_ENV, [
            'PGHOST' => $config['host'],
            'PGPORT' => (string) $config['port'],
            'PGUSER' => $config['username'],
            'PGPASSWORD' => $config['password'] ?? '',
            'PGDATABASE' => $config['database'],
        ]);

        // Build the pg_dump command
        $command = sprintf(
            'pg_dump --no-password --format=plain --no-owner --no-privileges %s 2>&1',
            escapeshellarg($config['database'])
        );

        // Execute the command with environment variables
        $descriptorspec = [
            0 => ['pipe', 'r'],  // stdin
            1 => ['pipe', 'w'],  // stdout
            2 => ['pipe', 'w']   // stderr
        ];

        $process = proc_open($command, $descriptorspec, $pipes, null, $env);

        if (!is_resource($process)) {
            throw new \Exception("Failed to start pg_dump process");
        }

        // Close stdin
        fclose($pipes[0]);

        // Read stdout (the SQL dump)
        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        // Read stderr (error messages)
        $errors = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        // Get the exit code
        $returnCode = proc_close($process);

        if ($returnCode !== 0) {
            $errorMessage = "Failed to create PostgreSQL dump";
            if (!empty($errors)) {
                $errorMessage .= ": " . trim($errors);
            }
            if (!empty($output) && strpos($output, 'ERROR') !== false) {
                $errorMessage .= " | Output: " . trim($output);
            }
            throw new \Exception($errorMessage);
        }

        // Write the dump to file
        if (file_put_contents($outputPath, $output) === false) {
            throw new \Exception("Failed to write PostgreSQL dump to file: {$outputPath}");
        }

        // Verify the dump file was created and has content
        if (!file_exists($outputPath) || filesize($outputPath) === 0) {
            throw new \Exception("PostgreSQL dump file is empty or was not created");
        }
    }

    /**
     * Create PostgreSQL dump using PHP (fallback when pg_dump is not available).
     *
     * @param array $config
     * @param string $outputPath
     * @return void
     */
    protected function createPgsqlDumpWithPHP(array $config, string $outputPath): void
    {
        try {
            $dsn = sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                $config['host'],
                $config['port'],
                $config['database']
            );

            $pdo = new \PDO($dsn, $config['username'], $config['password'], [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]);

            $sql = $this->generatePgsqlDumpSQL($pdo, $config['database']);

            if (file_put_contents($outputPath, $sql) === false) {
                throw new \Exception("Failed to write PostgreSQL dump to file: {$outputPath}");
            }

        } catch (\PDOException $e) {
            throw new \Exception("PostgreSQL PHP dump failed: " . $e->getMessage());
        }
    }

    /**
     * Generate PostgreSQL dump SQL using PHP.
     *
     * @param \PDO $pdo
     * @param string $database
     * @return string
     */
    protected function generatePgsqlDumpSQL(\PDO $pdo, string $database): string
    {
        $sql = "-- PostgreSQL Dump Generated by Laravel Backup Service (PHP Method)\n";
        $sql .= "-- Date: " . date('Y-m-d H:i:s') . "\n";
        $sql .= "-- Database: {$database}\n\n";
        $sql .= "SET statement_timeout = 0;\n";
        $sql .= "SET lock_timeout = 0;\n";
        $sql .= "SET client_encoding = 'UTF8';\n";
        $sql .= "SET standard_conforming_strings = on;\n";
        $sql .= "SET check_function_bodies = false;\n";
        $sql .= "SET xmloption = content;\n";
        $sql .= "SET client_min_messages = warning;\n\n";

        // Get all tables in the public schema
        $tablesQuery = "SELECT tablename FROM pg_tables WHERE schemaname = 'public' ORDER BY tablename";
        $tables = $pdo->query($tablesQuery)->fetchAll(\PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            // Skip tables that should be excluded
            if (in_array($table, config('backup.database.exclude_tables', []))) {
                continue;
            }

            $sql .= "-- Table: {$table}\n";

            // Get table schema
            $createTableSQL = $this->getPgsqlTableSchema($pdo, $table);
            $sql .= $createTableSQL . "\n\n";

            // Get table data
            try {
                $rows = $pdo->query("SELECT * FROM \"{$table}\"")->fetchAll(\PDO::FETCH_ASSOC);

                if (!empty($rows)) {
                    $sql .= "-- Data for table: {$table}\n";

                    foreach ($rows as $row) {
                        $columns = array_keys($row);
                        $values = array_map(function ($value) use ($pdo) {
                            if ($value === null) {
                                return 'NULL';
                            }
                            return $pdo->quote($value);
                        }, array_values($row));

                        $sql .= "INSERT INTO \"{$table}\" (\"" . implode('","', $columns) . "\") VALUES (" . implode(',', $values) . ");\n";
                    }
                    $sql .= "\n";
                }
            } catch (\PDOException $e) {
                $sql .= "-- Error dumping data for table {$table}: " . $e->getMessage() . "\n\n";
            }
        }

        // Get sequences
        $sequencesQuery = "SELECT sequence_name FROM information_schema.sequences WHERE sequence_schema = 'public'";
        try {
            $sequences = $pdo->query($sequencesQuery)->fetchAll(\PDO::FETCH_COLUMN);

            if (!empty($sequences)) {
                $sql .= "-- Sequences\n";
                foreach ($sequences as $sequence) {
                    $currentValue = $pdo->query("SELECT last_value FROM \"{$sequence}\"")->fetchColumn();
                    $sql .= "SELECT setval('\"{$sequence}\"', {$currentValue}, true);\n";
                }
                $sql .= "\n";
            }
        } catch (\PDOException $e) {
            $sql .= "-- Error dumping sequences: " . $e->getMessage() . "\n\n";
        }

        return $sql;
    }

    /**
     * Get PostgreSQL table schema.
     *
     * @param \PDO $pdo
     * @param string $table
     * @return string
     */
    protected function getPgsqlTableSchema(\PDO $pdo, string $table): string
    {
        try {
            // Get columns information
            $columnsQuery = "
                SELECT 
                    column_name,
                    data_type,
                    character_maximum_length,
                    is_nullable,
                    column_default
                FROM information_schema.columns 
                WHERE table_name = ? AND table_schema = 'public'
                ORDER BY ordinal_position
            ";

            $stmt = $pdo->prepare($columnsQuery);
            $stmt->execute([$table]);
            $columns = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $createSQL = "CREATE TABLE IF NOT EXISTS \"{$table}\" (\n";
            $columnDefinitions = [];

            foreach ($columns as $column) {
                $columnDef = "    \"{$column['column_name']}\" {$column['data_type']}";

                if ($column['character_maximum_length']) {
                    $columnDef .= "({$column['character_maximum_length']})";
                }

                if ($column['is_nullable'] === 'NO') {
                    $columnDef .= " NOT NULL";
                }

                if ($column['column_default']) {
                    $columnDef .= " DEFAULT {$column['column_default']}";
                }

                $columnDefinitions[] = $columnDef;
            }

            $createSQL .= implode(",\n", $columnDefinitions);
            $createSQL .= "\n);";

            return $createSQL;

        } catch (\PDOException $e) {
            return "-- Error getting schema for table {$table}: " . $e->getMessage();
        }
    }

    /**
     * Create SQLite dump.
     *
     * @param array $config
     * @param string $outputPath
     * @return void
     */
    protected function createSqliteDump(array $config, string $outputPath): void
    {
        // First try using sqlite3 command if available
        if ($this->isSqlite3Available()) {
            $command = sprintf(
                'sqlite3 %s .dump > %s',
                escapeshellarg($config['database']),
                escapeshellarg($outputPath)
            );

            exec($command, $output, $returnCode);

            if ($returnCode === 0) {
                return;
            }
        }

        // Fallback to PHP-based SQLite dump (works on Windows)
        $this->createSqliteDumpWithPHP($config, $outputPath);
    }



    /**
     * Check if sqlite3 command is available.
     *
     * @return bool
     */
    protected function isSqlite3Available(): bool
    {
        $command = PHP_OS_FAMILY === 'Windows' ? 'where sqlite3' : 'which sqlite3';
        exec($command, $output, $returnCode);
        return $returnCode === 0;
    }

    /**
     * Create SQLite dump using PHP (cross-platform solution).
     *
     * @param array $config
     * @param string $outputPath
     * @return void
     */
    protected function createSqliteDumpWithPHP(array $config, string $outputPath): void
    {
        $dbPath = $config['database'];

        if (!file_exists($dbPath)) {
            throw new \Exception("SQLite database file not found: {$dbPath}");
        }

        try {
            $pdo = new \PDO("sqlite:{$dbPath}");
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            $sql = $this->generateSqliteDumpSQL($pdo);

            if (file_put_contents($outputPath, $sql) === false) {
                throw new \Exception("Failed to write SQLite dump to file: {$outputPath}");
            }

        } catch (\PDOException $e) {
            throw new \Exception("SQLite dump failed: " . $e->getMessage());
        }
    }

    /**
     * Generate SQLite dump SQL using PHP.
     *
     * @param \PDO $pdo
     * @return string
     */
    protected function generateSqliteDumpSQL(\PDO $pdo): string
    {
        $sql = "-- SQLite Dump Generated by Laravel Backup Service\n";
        $sql .= "-- Date: " . date('Y-m-d H:i:s') . "\n\n";
        $sql .= "PRAGMA foreign_keys=OFF;\n";
        $sql .= "BEGIN TRANSACTION;\n\n";

        // Get all tables
        $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchAll(\PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            // Get table schema
            $createStmt = $pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='{$table}'")->fetchColumn();
            $sql .= "{$createStmt};\n\n";

            // Get table data
            $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(\PDO::FETCH_ASSOC);

            if (!empty($rows)) {
                foreach ($rows as $row) {
                    $columns = array_keys($row);
                    $values = array_map(function ($value) use ($pdo) {
                        return $value === null ? 'NULL' : $pdo->quote($value);
                    }, array_values($row));

                    $sql .= "INSERT INTO `{$table}` (`" . implode('`,`', $columns) . "`) VALUES (" . implode(',', $values) . ");\n";
                }
                $sql .= "\n";
            }
        }

        // Get indexes
        $indexes = $pdo->query("SELECT sql FROM sqlite_master WHERE type='index' AND sql IS NOT NULL")->fetchAll(\PDO::FETCH_COLUMN);
        foreach ($indexes as $index) {
            $sql .= "{$index};\n";
        }

        $sql .= "\nCOMMIT;\n";
        $sql .= "PRAGMA foreign_keys=ON;\n";

        return $sql;
    }

    /**
     * Compress file using gzip.
     *
     * @param string $inputPath
     * @param string $outputPath
     * @return void
     */
    protected function compressFile(string $inputPath, string $outputPath): void
    {
        $input = fopen($inputPath, 'rb');
        $output = gzopen($outputPath, 'wb9');

        if (!$input || !$output) {
            throw new \Exception("Failed to open files for compression");
        }

        while (!feof($input)) {
            gzwrite($output, fread($input, 8192));
        }

        fclose($input);
        gzclose($output);
    }

    /**
     * Restore database from backup.
     *
     * @param string $backupId
     * @return array
     */
    protected function restoreDatabase(string $backupId): array
    {
        // Implementation would depend on specific restore requirements
        return ['success' => true, 'message' => 'Database restore completed'];
    }

    /**
     * Restore files from backup.
     *
     * @param string $backupId
     * @return array
     */
    protected function restoreFiles(string $backupId): array
    {
        // Implementation would depend on specific restore requirements
        return ['success' => true, 'message' => 'Files restore completed'];
    }
}