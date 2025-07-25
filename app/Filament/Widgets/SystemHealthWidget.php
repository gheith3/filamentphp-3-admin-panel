<?php

namespace App\Filament\Widgets;

use App\Services\PerformanceMonitoringService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Log;

class SystemHealthWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        try {
            $monitor = app(PerformanceMonitoringService::class);
            $systemMetrics = $monitor->getSystemMetrics();
            $healthCheck = $monitor->healthCheck();

            // Get database metrics with fallback
            try {
                $dbMetrics = $monitor->getDatabaseMetrics();
            } catch (\Exception $e) {
                Log::warning('Failed to get database metrics', ['error' => $e->getMessage()]);
                $dbMetrics = [
                    'connection_count' => 0,
                    'database_size_mb' => 0,
                    'slow_queries' => 0,
                ];
            }

            return [
                Stat::make('System Status', $healthCheck['status'] ?? 'Unknown')
                    ->description('Overall system health')
                    ->descriptionIcon($this->getSystemStatusIcon($healthCheck['status'] ?? 'unknown'))
                    ->color($this->getSystemStatusColor($healthCheck['status'] ?? 'unknown')),

                Stat::make('Database', $this->getDatabaseInfo())
                    ->description($this->getDatabaseDescription())
                    ->descriptionIcon($this->getDatabaseIcon())
                    ->color($this->getDatabaseStatusColor($dbMetrics)),

                Stat::make('Memory Usage', $this->formatMemoryUsage($systemMetrics))
                    ->description($this->getMemoryDescription($systemMetrics))
                    ->descriptionIcon('heroicon-m-circle-stack')
                    ->color($this->getMemoryStatusColor($systemMetrics)),

                Stat::make('Database Size', $this->formatDatabaseSize($dbMetrics))
                    ->description('Current database size')
                    ->descriptionIcon('heroicon-m-circle-stack')
                    ->color($this->getDatabaseSizeColor($dbMetrics)),

                Stat::make('Active Connections', $this->formatActiveConnections($dbMetrics))
                    ->description($this->getConnectionsDescription())
                    ->descriptionIcon('heroicon-m-link')
                    ->color($this->getConnectionsStatusColor($dbMetrics)),

                Stat::make('Cache Performance', $this->formatCacheRatio($systemMetrics))
                    ->description('Cache hit ratio')
                    ->descriptionIcon('heroicon-m-bolt')
                    ->color($this->getCacheStatusColor($systemMetrics['cache_hit_ratio'] ?? 0)),
            ];
        } catch (\Exception $e) {
            Log::error('SystemHealthWidget error', ['error' => $e->getMessage()]);

            return [
                Stat::make('System Status', 'Error')
                    ->description('Unable to load system metrics')
                    ->descriptionIcon('heroicon-m-exclamation-triangle')
                    ->color('danger'),

                Stat::make('Database', 'Unavailable')
                    ->description('Database metrics unavailable')
                    ->descriptionIcon('heroicon-m-x-circle')
                    ->color('danger'),

                Stat::make('Memory', 'Unknown')
                    ->description('System metrics unavailable')
                    ->descriptionIcon('heroicon-m-x-circle')
                    ->color('gray'),
            ];
        }
    }

    protected function getDatabaseInfo(): string
    {
        $driver = config('database.connections.' . config('database.default') . '.driver');

        return match ($driver) {
            'mysql' => 'MySQL',
            'mariadb' => 'MariaDB',
            'pgsql' => 'PostgreSQL',
            'sqlite' => 'SQLite',
            'sqlsrv' => 'SQL Server',
            default => ucfirst($driver),
        };
    }

    protected function getDatabaseDescription(): string
    {
        $connection = config('database.default');
        $config = config("database.connections.{$connection}");
        $driver = $config['driver'] ?? 'unknown';

        try {
            return match ($driver) {
                'mysql', 'mariadb', 'pgsql' => sprintf(
                    '%s:%s/%s',
                    $config['host'] ?? 'localhost',
                    $config['port'] ?? ($driver === 'pgsql' ? '5432' : '3306'),
                    $config['database'] ?? 'unknown'
                ),
                'sqlite' => file_exists($config['database'] ?? '') ? 'SQLite file exists' : 'SQLite file missing',
                'sqlsrv' => sprintf('%s/%s', $config['host'] ?? 'localhost', $config['database'] ?? 'unknown'),
                default => 'Connected',
            };
        } catch (\Exception $e) {
            return 'Connection info unavailable';
        }
    }

    protected function getDatabaseIcon(): string
    {
        $driver = config('database.connections.' . config('database.default') . '.driver');

        return match ($driver) {
            'mysql', 'mariadb' => 'heroicon-m-circle-stack',
            'pgsql' => 'heroicon-m-server-stack',
            'sqlite' => 'heroicon-m-document',
            'sqlsrv' => 'heroicon-m-building-office',
            default => 'heroicon-m-circle-stack',
        };
    }

    protected function formatMemoryUsage(array $systemMetrics): string
    {
        $usage = $systemMetrics['memory_usage_mb'] ?? 0;
        $limit = $systemMetrics['memory_limit_mb'] ?? 1;

        if ($limit <= 0)
            $limit = 1; // Prevent division by zero

        $percentage = round(($usage / $limit) * 100, 1);
        return $percentage . '%';
    }

    protected function getMemoryDescription(array $systemMetrics): string
    {
        $usage = $systemMetrics['memory_usage_mb'] ?? 0;
        $limit = $systemMetrics['memory_limit_mb'] ?? 0;
        $peak = $systemMetrics['memory_peak_mb'] ?? 0;

        if ($limit > 0) {
            return sprintf('%.1f MB / %.1f MB (Peak: %.1f MB)', $usage, $limit, $peak);
        }

        return sprintf('%.1f MB (Peak: %.1f MB)', $usage, $peak);
    }

    protected function formatDatabaseSize(array $dbMetrics): string
    {
        $size = $dbMetrics['database_size_mb'] ?? 0;

        if ($size <= 0) {
            return 'Unknown';
        }

        if ($size >= 1024) {
            return round($size / 1024, 2) . ' GB';
        }

        return round($size, 1) . ' MB';
    }

    protected function formatActiveConnections(array $dbMetrics): string
    {
        $connections = $dbMetrics['connection_count'] ?? 0;
        $driver = config('database.connections.' . config('database.default') . '.driver');

        if ($driver === 'sqlite') {
            return '1'; // SQLite is single connection
        }

        if ($connections <= 0) {
            return 'Unknown';
        }

        return (string) $connections;
    }

    protected function getConnectionsDescription(): string
    {
        $driver = config('database.connections.' . config('database.default') . '.driver');

        return match ($driver) {
            'sqlite' => 'Single connection',
            'mysql', 'mariadb' => 'Active threads',
            'pgsql' => 'Active sessions',
            'sqlsrv' => 'Active sessions',
            default => 'Database connections',
        };
    }

    protected function formatCacheRatio(array $systemMetrics): string
    {
        $ratio = $systemMetrics['cache_hit_ratio'] ?? 0;
        return number_format($ratio * 100, 1) . '%';
    }

    protected function getActiveUsersCount(): int
    {
        // This would typically check active sessions or online users
        // For now, return a placeholder value
        return rand(1, 10);
    }

    protected function getSystemStatusIcon(string $status): string
    {
        return match ($status) {
            'healthy' => 'heroicon-m-check-circle',
            'warning' => 'heroicon-m-exclamation-triangle',
            'critical' => 'heroicon-m-x-circle',
            default => 'heroicon-m-question-mark-circle',
        };
    }

    protected function getSystemStatusColor(string $status): string
    {
        return match ($status) {
            'healthy' => 'success',
            'warning' => 'warning',
            'critical' => 'danger',
            default => 'gray',
        };
    }

    protected function getDatabaseStatusColor(array $dbMetrics): string
    {
        $connections = $dbMetrics['connection_count'] ?? 0;
        $driver = config('database.connections.' . config('database.default') . '.driver');

        // SQLite always shows as success since it's single connection
        if ($driver === 'sqlite') {
            return 'success';
        }

        if ($connections >= 80)
            return 'danger';
        if ($connections >= 50)
            return 'warning';
        return 'success';
    }

    protected function getDatabaseSizeColor(array $dbMetrics): string
    {
        $size = $dbMetrics['database_size_mb'] ?? 0;

        // Color coding based on database size
        if ($size >= 1024)
            return 'warning'; // 1GB+
        if ($size >= 10240)
            return 'danger'; // 10GB+
        return 'info';
    }

    protected function getConnectionsStatusColor(array $dbMetrics): string
    {
        $connections = $dbMetrics['connection_count'] ?? 0;
        $driver = config('database.connections.' . config('database.default') . '.driver');

        if ($driver === 'sqlite') {
            return 'info';
        }

        if ($connections >= 80)
            return 'danger';
        if ($connections >= 50)
            return 'warning';
        return 'success';
    }

    protected function getMemoryStatusColor(array $systemMetrics): string
    {
        $usage = $systemMetrics['memory_usage_mb'] ?? 0;
        $limit = $systemMetrics['memory_limit_mb'] ?? 1;
        $percentage = ($usage / $limit) * 100;

        if ($percentage >= 80)
            return 'danger';
        if ($percentage >= 60)
            return 'warning';
        return 'success';
    }

    protected function getCpuStatusColor(float $usage): string
    {
        if ($usage >= 80)
            return 'danger';
        if ($usage >= 60)
            return 'warning';
        return 'success';
    }

    protected function getCacheStatusColor(float $ratio): string
    {
        if ($ratio >= 0.8)
            return 'success';
        if ($ratio >= 0.6)
            return 'warning';
        return 'danger';
    }
}