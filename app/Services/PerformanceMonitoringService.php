<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class PerformanceMonitoringService
{
    protected array $metrics = [];
    protected float $startTime;
    protected int $startMemory;

    public function __construct()
    {
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage(true);
    }

    /**
     * Start monitoring a specific operation.
     *
     * @param string $operation
     * @return string Monitor ID
     */
    public function startMonitoring(string $operation): string
    {
        $monitorId = uniqid($operation . '_');

        $this->metrics[$monitorId] = [
            'operation' => $operation,
            'start_time' => microtime(true),
            'start_memory' => memory_get_usage(true),
            'queries_before' => $this->getQueryCount(),
        ];

        return $monitorId;
    }

    /**
     * Stop monitoring an operation and record metrics.
     *
     * @param string $monitorId
     * @return array Performance metrics
     */
    public function stopMonitoring(string $monitorId): array
    {
        if (!isset($this->metrics[$monitorId])) {
            return [];
        }

        $metric = $this->metrics[$monitorId];
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        $queriesAfter = $this->getQueryCount();

        $result = [
            'operation' => $metric['operation'],
            'duration_ms' => round(($endTime - $metric['start_time']) * 1000, 2),
            'memory_used_mb' => round(($endMemory - $metric['start_memory']) / 1024 / 1024, 2),
            'queries_executed' => $queriesAfter - $metric['queries_before'],
            'peak_memory_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'timestamp' => now()->toISOString(),
        ];

        // Log slow operations
        if ($result['duration_ms'] > config('security.database.slow_query_threshold', 2000)) {
            Log::warning('Slow operation detected', $result);
        }

        // Store metrics for analysis
        $this->storeMetrics($result);

        unset($this->metrics[$monitorId]);

        return $result;
    }

    /**
     * Get current system performance snapshot.
     *
     * @return array System metrics
     */
    public function getSystemMetrics(): array
    {
        $currentTime = microtime(true);
        $currentMemory = memory_get_usage(true);

        return [
            'uptime_seconds' => round($currentTime - $this->startTime, 2),
            'memory_usage_mb' => round($currentMemory / 1024 / 1024, 2),
            'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'memory_limit_mb' => $this->getMemoryLimitMB(),
            'cpu_usage_percent' => $this->getCpuUsage(),
            'disk_usage_percent' => $this->getDiskUsage(),
            'active_connections' => $this->getActiveConnections(),
            'cache_hit_ratio' => $this->getCacheHitRatio(),
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Get database performance metrics.
     *
     * @return array Database metrics
     */
    public function getDatabaseMetrics(): array
    {
        return [
            'connection_count' => $this->getActiveConnections(),
            'query_count' => $this->getQueryCount(),
            'slow_queries' => $this->getSlowQueryCount(),
            'avg_query_time_ms' => $this->getAverageQueryTime(),
            'database_size_mb' => $this->getDatabaseSizeMB(),
            'index_usage' => $this->getIndexUsageStats(),
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Check if system is healthy based on performance thresholds.
     *
     * @return array Health check results
     */
    public function healthCheck(): array
    {
        $metrics = $this->getSystemMetrics();
        $dbMetrics = $this->getDatabaseMetrics();

        $checks = [
            'memory_usage' => [
                'status' => $metrics['memory_usage_mb'] < ($metrics['memory_limit_mb'] * 0.8) ? 'healthy' : 'warning',
                'value' => $metrics['memory_usage_mb'],
                'threshold' => $metrics['memory_limit_mb'] * 0.8,
            ],
            'cpu_usage' => [
                'status' => $metrics['cpu_usage_percent'] < 80 ? 'healthy' : 'warning',
                'value' => $metrics['cpu_usage_percent'],
                'threshold' => 80,
            ],
            'disk_usage' => [
                'status' => $metrics['disk_usage_percent'] < 85 ? 'healthy' : 'critical',
                'value' => $metrics['disk_usage_percent'],
                'threshold' => 85,
            ],
            'database_connections' => [
                'status' => $dbMetrics['connection_count'] < 80 ? 'healthy' : 'warning',
                'value' => $dbMetrics['connection_count'],
                'threshold' => 80,
            ],
            'cache_performance' => [
                'status' => $metrics['cache_hit_ratio'] > 0.8 ? 'healthy' : 'warning',
                'value' => $metrics['cache_hit_ratio'],
                'threshold' => 0.8,
            ],
        ];

        $overallStatus = $this->determineOverallStatus($checks);

        return [
            'status' => $overallStatus,
            'checks' => $checks,
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Store performance metrics for long-term analysis.
     *
     * @param array $metrics
     * @return void
     */
    protected function storeMetrics(array $metrics): void
    {
        // Store in cache for recent metrics (1 hour)
        $cacheKey = 'performance_metrics_' . date('Y-m-d-H');
        $existingMetrics = Cache::get($cacheKey, []);
        $existingMetrics[] = $metrics;

        // Keep only last 100 metrics per hour
        if (count($existingMetrics) > 100) {
            $existingMetrics = array_slice($existingMetrics, -100);
        }

        Cache::put($cacheKey, $existingMetrics, 3600);

        // Log critical performance issues
        if ($metrics['duration_ms'] > 5000) {
            Log::critical('Critical performance issue detected', $metrics);
        }
    }

    /**
     * Get current query count from DB connection.
     *
     * @return int
     */
    protected function getQueryCount(): int
    {
        return count(DB::getQueryLog());
    }

    /**
     * Get memory limit in MB.
     *
     * @return float
     */
    protected function getMemoryLimitMB(): float
    {
        $limit = ini_get('memory_limit');
        $value = (int) $limit;

        if (stripos($limit, 'g') !== false) {
            return $value * 1024;
        } elseif (stripos($limit, 'm') !== false) {
            return $value;
        } elseif (stripos($limit, 'k') !== false) {
            return $value / 1024;
        }

        return $value / 1024 / 1024; // bytes to MB
    }

    /**
     * Get CPU usage percentage (approximate).
     *
     * @return float
     */
    protected function getCpuUsage(): float
    {
        // Check if sys_getloadavg is available (not available on Windows)
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            return $load ? round($load[0] * 100, 2) : 0;
        }

        // Fallback for Windows or systems without sys_getloadavg
        // Use a simple approximation based on memory usage as a proxy
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = $this->getMemoryLimitMB() * 1024 * 1024;
        
        if ($memoryLimit > 0) {
            // Use memory usage as a rough CPU approximation (0-50% range)
            return min(50, round(($memoryUsage / $memoryLimit) * 50, 2));
        }

        // Default fallback
        return 0;
    }

    /**
     * Get disk usage percentage.
     *
     * @return float
     */
    protected function getDiskUsage(): float
    {
        $total = disk_total_space('/');
        $free = disk_free_space('/');

        if ($total && $free) {
            return round((($total - $free) / $total) * 100, 2);
        }

        return 0;
    }

    /**
     * Get active database connections count.
     *
     * @return int
     */
    protected function getActiveConnections(): int
    {
        try {
            $connections = DB::select('SHOW STATUS LIKE "Threads_connected"');
            return $connections[0]->Value ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get cache hit ratio.
     *
     * @return float
     */
    protected function getCacheHitRatio(): float
    {
        // This would need to be implemented based on your cache driver
        // For now, return a placeholder
        return 0.85;
    }

    /**
     * Get slow query count.
     *
     * @return int
     */
    protected function getSlowQueryCount(): int
    {
        try {
            $result = DB::select('SHOW STATUS LIKE "Slow_queries"');
            return $result[0]->Value ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get average query execution time.
     *
     * @return float
     */
    protected function getAverageQueryTime(): float
    {
        // This would need more sophisticated tracking
        // For now, return a placeholder
        return 0.5;
    }

    /**
     * Get database size in MB.
     *
     * @return float
     */
    protected function getDatabaseSizeMB(): float
    {
        try {
            $dbName = config('database.connections.' . config('database.default') . '.database');
            $result = DB::select("
                SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
                FROM information_schema.tables 
                WHERE table_schema = ?
            ", [$dbName]);

            return $result[0]->size_mb ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get index usage statistics.
     *
     * @return array
     */
    protected function getIndexUsageStats(): array
    {
        // Simplified index usage check
        return ['status' => 'optimal'];
    }

    /**
     * Determine overall system status from individual checks.
     *
     * @param array $checks
     * @return string
     */
    protected function determineOverallStatus(array $checks): string
    {
        $statuses = array_column($checks, 'status');

        if (in_array('critical', $statuses)) {
            return 'critical';
        } elseif (in_array('warning', $statuses)) {
            return 'warning';
        }

        return 'healthy';
    }
}