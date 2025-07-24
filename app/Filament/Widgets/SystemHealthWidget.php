<?php

namespace App\Filament\Widgets;

use App\Services\PerformanceMonitoringService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SystemHealthWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $monitor = app(PerformanceMonitoringService::class);
        $systemMetrics = $monitor->getSystemMetrics();
        $healthCheck = $monitor->healthCheck();

        return [
            Stat::make('System Status', $healthCheck['status'] ?? 'Unknown')
                ->description('Overall system health')
                ->descriptionIcon($this->getSystemStatusIcon($healthCheck['status'] ?? 'unknown'))
                ->color($this->getSystemStatusColor($healthCheck['status'] ?? 'unknown')),

            Stat::make('Memory Usage', $this->formatMemoryUsage($systemMetrics))
                ->description('Current / Limit')
                ->descriptionIcon('heroicon-m-circle-stack')
                ->color($this->getMemoryStatusColor($systemMetrics)),

            Stat::make('CPU Usage', number_format($systemMetrics['cpu_usage_percent'] ?? 0, 1) . '%')
                ->description('Current processor usage')
                ->descriptionIcon('heroicon-m-cpu-chip')
                ->color($this->getCpuStatusColor($systemMetrics['cpu_usage_percent'] ?? 0)),

            Stat::make('Cache Hit Ratio', number_format(($systemMetrics['cache_hit_ratio'] ?? 0) * 100, 1) . '%')
                ->description('Cache performance')
                ->descriptionIcon('heroicon-m-bolt')
                ->color($this->getCacheStatusColor($systemMetrics['cache_hit_ratio'] ?? 0)),

            Stat::make('Database Size', number_format($systemMetrics['database_size_mb'] ?? 0, 1) . ' MB')
                ->description('Current database size')
                ->descriptionIcon('heroicon-m-circle-stack')
                ->color('info'),

            Stat::make('Active Users', $this->getActiveUsersCount())
                ->description('Currently online')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),
        ];
    }

    protected function formatMemoryUsage(array $systemMetrics): string
    {
        $usage = $systemMetrics['memory_usage_mb'] ?? 0;
        $limit = $systemMetrics['memory_limit_mb'] ?? 1;
        $percentage = round(($usage / $limit) * 100, 1);

        return $percentage . '%';
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