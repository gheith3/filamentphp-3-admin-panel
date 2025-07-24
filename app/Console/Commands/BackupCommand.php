<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BackupService;
use App\Services\PerformanceMonitoringService;

class BackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:run 
                            {--type=full : Type of backup (full, database, files, config)}
                            {--verify : Verify backup after creation}
                            {--cleanup : Cleanup old backups}
                            {--monitor : Monitor performance during backup}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create system backups with various options';

    protected BackupService $backupService;
    protected PerformanceMonitoringService $monitor;

    public function __construct(BackupService $backupService, PerformanceMonitoringService $monitor)
    {
        parent::__construct();
        $this->backupService = $backupService;
        $this->monitor = $monitor;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $type = $this->option('type');
        $verify = $this->option('verify');
        $cleanup = $this->option('cleanup');
        $monitorPerformance = $this->option('monitor');

        $this->info("🚀 Starting {$type} backup...");
        $this->newLine();

        // Start performance monitoring if requested
        $monitorId = null;
        if ($monitorPerformance) {
            $monitorId = $this->monitor->startMonitoring("backup_{$type}");
        }

        try {
            $result = match ($type) {
                'full' => $this->createFullBackup($verify, $cleanup),
                'database' => $this->createDatabaseBackup(),
                'files' => $this->createFilesBackup(),
                'config' => $this->createConfigBackup(),
                default => $this->createFullBackup($verify, $cleanup),
            };

            if ($result['success']) {
                $this->displaySuccessResults($result);
                $exitCode = 0;
            } else {
                $this->displayErrorResults($result);
                $exitCode = 1;
            }

        } catch (\Exception $e) {
            $this->error("❌ Backup failed: {$e->getMessage()}");
            $this->error("Stack trace: {$e->getTraceAsString()}");
            $exitCode = 1;
        }

        // Stop performance monitoring and display results
        if ($monitorPerformance && $monitorId) {
            $this->newLine();
            $this->displayPerformanceMetrics($monitorId);
        }

        return $exitCode;
    }

    /**
     * Create a full backup.
     */
    protected function createFullBackup(bool $verify, bool $cleanup): array
    {
        $options = [
            'cleanup_old' => $cleanup,
            'verify' => $verify,
        ];

        $progressBar = $this->output->createProgressBar(4);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');
        $progressBar->setMessage('Initializing backup...');
        $progressBar->start();

        $result = $this->backupService->createFullBackup($options);

        $progressBar->setMessage('Database backup...');
        $progressBar->advance();

        $progressBar->setMessage('Files backup...');
        $progressBar->advance();

        $progressBar->setMessage('Configuration backup...');
        $progressBar->advance();

        $progressBar->setMessage('Finalizing...');
        $progressBar->finish();

        $this->newLine(2);

        return $result;
    }

    /**
     * Create database backup only.
     */
    protected function createDatabaseBackup(): array
    {
        $backupId = "db_" . now()->format('Y-m-d_H-i-s');

        $this->info("📊 Creating database backup: {$backupId}");

        try {
            $result = $this->backupService->backupDatabase($backupId);

            return [
                'success' => true,
                'backup_id' => $backupId,
                'results' => ['database' => $result],
                'message' => 'Database backup completed successfully',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'backup_id' => $backupId,
                'error' => $e->getMessage(),
                'message' => 'Database backup failed',
            ];
        }
    }

    /**
     * Create files backup only.
     */
    protected function createFilesBackup(): array
    {
        $backupId = "files_" . now()->format('Y-m-d_H-i-s');

        $this->info("📁 Creating files backup: {$backupId}");

        try {
            $result = $this->backupService->backupFiles($backupId);

            return [
                'success' => true,
                'backup_id' => $backupId,
                'results' => ['files' => $result],
                'message' => 'Files backup completed successfully',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'backup_id' => $backupId,
                'error' => $e->getMessage(),
                'message' => 'Files backup failed',
            ];
        }
    }

    /**
     * Create configuration backup only.
     */
    protected function createConfigBackup(): array
    {
        $backupId = "config_" . now()->format('Y-m-d_H-i-s');

        $this->info("⚙️ Creating configuration backup: {$backupId}");

        try {
            $result = $this->backupService->backupConfiguration($backupId);

            return [
                'success' => true,
                'backup_id' => $backupId,
                'results' => ['config' => $result],
                'message' => 'Configuration backup completed successfully',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'backup_id' => $backupId,
                'error' => $e->getMessage(),
                'message' => 'Configuration backup failed',
            ];
        }
    }

    /**
     * Display successful backup results.
     */
    protected function displaySuccessResults(array $result): void
    {
        $this->info("✅ Backup completed successfully!");
        $this->info("📦 Backup ID: {$result['backup_id']}");

        if (isset($result['results'])) {
            $this->newLine();
            $this->line("<comment>Backup Details:</comment>");

            foreach ($result['results'] as $type => $details) {
                if (is_array($details) && isset($details['success']) && $details['success']) {
                    $this->line("  • {$type}: ✅");

                    if (isset($details['size_mb'])) {
                        $this->line("    Size: {$details['size_mb']} MB");
                    }

                    if (isset($details['filename'])) {
                        $this->line("    File: {$details['filename']}");
                    }

                    if (isset($details['files_count'])) {
                        $this->line("    Files: {$details['files_count']}");
                    }
                } else {
                    $this->line("  • {$type}: ❌");
                }
            }
        }

        $this->newLine();
        $this->info("💾 Backup stored successfully");
    }

    /**
     * Display error results.
     */
    protected function displayErrorResults(array $result): void
    {
        $this->error("❌ Backup failed!");
        $this->error("📦 Backup ID: {$result['backup_id']}");

        if (isset($result['error'])) {
            $this->error("Error: {$result['error']}");
        }

        if (isset($result['results'])) {
            $this->newLine();
            $this->line("<comment>Backup Details:</comment>");

            foreach ($result['results'] as $type => $details) {
                if (is_array($details) && isset($details['success'])) {
                    $status = $details['success'] ? '✅' : '❌';
                    $this->line("  • {$type}: {$status}");

                    if (!$details['success'] && isset($details['error'])) {
                        $this->line("    Error: {$details['error']}");
                    }
                }
            }
        }
    }

    /**
     * Display performance metrics.
     */
    protected function displayPerformanceMetrics(string $monitorId): void
    {
        $metrics = $this->monitor->stopMonitoring($monitorId);

        if (!empty($metrics)) {
            $this->line("<comment>Performance Metrics:</comment>");
            $this->line("  • Duration: {$metrics['duration_ms']} ms");
            $this->line("  • Memory Used: {$metrics['memory_used_mb']} MB");
            $this->line("  • Peak Memory: {$metrics['peak_memory_mb']} MB");
            $this->line("  • Database Queries: {$metrics['queries_executed']}");

            if ($metrics['duration_ms'] > 30000) { // 30 seconds
                $this->warn("⚠️  Backup took longer than expected. Consider optimizing.");
            }

            if ($metrics['memory_used_mb'] > 100) {
                $this->warn("⚠️  High memory usage detected. Monitor system resources.");
            }
        }
    }
}