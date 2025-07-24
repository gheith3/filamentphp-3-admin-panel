<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PerformanceMonitoringService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class SystemHealthCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:health 
                            {--detailed : Show detailed metrics}
                            {--json : Output in JSON format}
                            {--check=* : Specific checks to run (database,cache,storage,security)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check system health and performance metrics';

    protected PerformanceMonitoringService $monitor;

    public function __construct(PerformanceMonitoringService $monitor)
    {
        parent::__construct();
        $this->monitor = $monitor;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $detailed = $this->option('detailed');
        $jsonOutput = $this->option('json');
        $specificChecks = $this->option('check');

        if (!$jsonOutput) {
            $this->info('🔍 Running system health check...');
            $this->newLine();
        }

        // Determine which checks to run
        $checksToRun = empty($specificChecks)
            ? ['database', 'cache', 'storage', 'security', 'performance']
            : $specificChecks;

        $results = [
            'timestamp' => now()->toISOString(),
            'environment' => app()->environment(),
            'overall_status' => 'healthy',
            'checks' => [],
        ];

        $overallHealthy = true;

        foreach ($checksToRun as $check) {
            try {
                $result = match ($check) {
                    'database' => $this->checkDatabase(),
                    'cache' => $this->checkCache(),
                    'storage' => $this->checkStorage(),
                    'security' => $this->checkSecurity(),
                    'performance' => $this->checkPerformance(),
                    default => ['status' => 'skipped', 'message' => 'Unknown check'],
                };

                $results['checks'][$check] = $result;

                if ($result['status'] !== 'healthy') {
                    $overallHealthy = false;
                }

                if (!$jsonOutput) {
                    $this->displayCheckResult($check, $result, $detailed);
                }

            } catch (\Exception $e) {
                $results['checks'][$check] = [
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ];
                $overallHealthy = false;

                if (!$jsonOutput) {
                    $this->error("❌ {$check}: ERROR - {$e->getMessage()}");
                }
            }
        }

        $results['overall_status'] = $overallHealthy ? 'healthy' : 'unhealthy';

        if ($jsonOutput) {
            $this->line(json_encode($results, JSON_PRETTY_PRINT));
        } else {
            $this->newLine();
            $this->displayOverallStatus($results['overall_status']);
        }

        return $overallHealthy ? 0 : 1;
    }

    /**
     * Check database connectivity and performance.
     */
    protected function checkDatabase(): array
    {
        try {
            $startTime = microtime(true);

            // Test connection
            DB::connection()->getPdo();

            // Test basic query
            $result = DB::select('SELECT 1 as test');
            $queryTime = round((microtime(true) - $startTime) * 1000, 2);

            // Check database size
            $dbMetrics = $this->monitor->getDatabaseMetrics();

            $status = 'healthy';
            $issues = [];

            if ($queryTime > 100) {
                $status = 'warning';
                $issues[] = 'Slow database response time';
            }

            if ($dbMetrics['connection_count'] > 80) {
                $status = 'warning';
                $issues[] = 'High connection count';
            }

            return [
                'status' => $status,
                'response_time_ms' => $queryTime,
                'connections' => $dbMetrics['connection_count'],
                'database_size_mb' => $dbMetrics['database_size_mb'],
                'issues' => $issues,
                'message' => empty($issues) ? 'Database is healthy' : implode(', ', $issues),
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'message' => 'Database connection failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check cache system performance.
     */
    protected function checkCache(): array
    {
        try {
            $testKey = 'health_check_' . time();
            $testValue = 'test_data';

            $startTime = microtime(true);

            // Test cache write
            Cache::put($testKey, $testValue, 60);

            // Test cache read
            $retrieved = Cache::get($testKey);

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            // Cleanup
            Cache::forget($testKey);

            $status = 'healthy';
            $issues = [];

            if ($retrieved !== $testValue) {
                $status = 'critical';
                $issues[] = 'Cache write/read failed';
            }

            if ($responseTime > 50) {
                $status = 'warning';
                $issues[] = 'Slow cache response';
            }

            return [
                'status' => $status,
                'response_time_ms' => $responseTime,
                'driver' => config('cache.default'),
                'issues' => $issues,
                'message' => empty($issues) ? 'Cache is healthy' : implode(', ', $issues),
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'message' => 'Cache system failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check storage system.
     */
    protected function checkStorage(): array
    {
        try {
            $testFile = 'health_check_' . time() . '.txt';
            $testContent = 'health check test';

            $startTime = microtime(true);

            // Test file write
            Storage::put($testFile, $testContent);

            // Test file read
            $retrieved = Storage::get($testFile);

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            // Get storage info
            $diskSpace = $this->getDiskSpace();

            // Cleanup
            Storage::delete($testFile);

            $status = 'healthy';
            $issues = [];

            if ($retrieved !== $testContent) {
                $status = 'critical';
                $issues[] = 'Storage write/read failed';
            }

            if ($responseTime > 100) {
                $status = 'warning';
                $issues[] = 'Slow storage response';
            }

            if ($diskSpace['used_percent'] > 85) {
                $status = 'warning';
                $issues[] = 'High disk usage';
            }

            return [
                'status' => $status,
                'response_time_ms' => $responseTime,
                'disk_usage_percent' => $diskSpace['used_percent'],
                'disk_free_gb' => $diskSpace['free_gb'],
                'issues' => $issues,
                'message' => empty($issues) ? 'Storage is healthy' : implode(', ', $issues),
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'message' => 'Storage system failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check security configuration.
     */
    protected function checkSecurity(): array
    {
        $issues = [];
        $warnings = [];

        // Check environment
        if (app()->environment('production')) {
            if (config('app.debug') === true) {
                $issues[] = 'Debug mode enabled in production';
            }
        }

        // Check HTTPS configuration
        if (!config('session.secure') && app()->environment('production')) {
            $warnings[] = 'Secure cookies not enabled';
        }

        // Check encryption key
        if (empty(config('app.key'))) {
            $issues[] = 'Application key not set';
        }

        // Check CORS configuration
        $corsOrigins = config('cors.allowed_origins');
        if (in_array('*', $corsOrigins) && app()->environment('production')) {
            $warnings[] = 'CORS allows all origins in production';
        }

        // Check rate limiting
        if (!config('security.rate_limiting.enabled', true)) {
            $warnings[] = 'Rate limiting disabled';
        }

        $status = 'healthy';
        if (!empty($issues)) {
            $status = 'critical';
        } elseif (!empty($warnings)) {
            $status = 'warning';
        }

        return [
            'status' => $status,
            'critical_issues' => $issues,
            'warnings' => $warnings,
            'message' => empty($issues) && empty($warnings)
                ? 'Security configuration is healthy'
                : 'Security issues detected',
        ];
    }

    /**
     * Check overall system performance.
     */
    protected function checkPerformance(): array
    {
        $systemMetrics = $this->monitor->getSystemMetrics();
        $healthCheck = $this->monitor->healthCheck();

        return [
            'status' => $healthCheck['status'],
            'memory_usage_mb' => $systemMetrics['memory_usage_mb'],
            'memory_peak_mb' => $systemMetrics['memory_peak_mb'],
            'cpu_usage_percent' => $systemMetrics['cpu_usage_percent'],
            'cache_hit_ratio' => $systemMetrics['cache_hit_ratio'],
            'checks' => $healthCheck['checks'],
            'message' => "System performance is {$healthCheck['status']}",
        ];
    }

    /**
     * Get disk space information.
     */
    protected function getDiskSpace(): array
    {
        $total = disk_total_space('/');
        $free = disk_free_space('/');

        if ($total && $free) {
            $used = $total - $free;
            $usedPercent = round(($used / $total) * 100, 2);
            $freeGB = round($free / 1024 / 1024 / 1024, 2);

            return [
                'used_percent' => $usedPercent,
                'free_gb' => $freeGB,
            ];
        }

        return ['used_percent' => 0, 'free_gb' => 0];
    }

    /**
     * Display check result.
     */
    protected function displayCheckResult(string $check, array $result, bool $detailed): void
    {
        $status = $result['status'];
        $icon = match ($status) {
            'healthy' => '✅',
            'warning' => '⚠️',
            'critical' => '❌',
            default => '❓',
        };

        $checkName = ucfirst($check);
        $message = $result['message'] ?? '';

        $this->line("{$icon} {$checkName}: {$status} - {$message}");

        if ($detailed && isset($result['response_time_ms'])) {
            $this->line("   Response time: {$result['response_time_ms']} ms");
        }

        if ($detailed && !empty($result['issues'])) {
            foreach ($result['issues'] as $issue) {
                $this->line("   • {$issue}");
            }
        }

        if ($detailed && !empty($result['warnings'])) {
            foreach ($result['warnings'] as $warning) {
                $this->line("   ⚠️  {$warning}");
            }
        }

        if ($detailed && !empty($result['critical_issues'])) {
            foreach ($result['critical_issues'] as $issue) {
                $this->line("   ❌ {$issue}");
            }
        }
    }

    /**
     * Display overall system status.
     */
    protected function displayOverallStatus(string $status): void
    {
        if ($status === 'healthy') {
            $this->info('🎉 Overall system status: HEALTHY');
            $this->info('All systems are operating normally.');
        } else {
            $this->error('⚠️  Overall system status: UNHEALTHY');
            $this->error('Some systems require attention.');
        }
    }
}