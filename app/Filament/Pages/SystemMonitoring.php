<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Contracts\View\View;
use Filament\Support\Enums\MaxWidth;
use Filament\Notifications\Notification;
use App\Services\PerformanceMonitoringService;
use Spatie\ResponseCache\Facades\ResponseCache;

class SystemMonitoring extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
    protected static ?string $navigationLabel = 'System Health';
    protected static ?string $navigationGroup = 'System';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.pages.system-monitoring';

    public array $systemMetrics = [];
    public array $databaseMetrics = [];
    public array $healthCheck = [];

    protected PerformanceMonitoringService $monitor;

    public function boot(): void
    {
        $this->monitor = app(PerformanceMonitoringService::class);
        $this->loadMetrics();
    }

    public function mount(): void
    {
        $this->loadMetrics();
    }

    protected function loadMetrics(): void
    {
        $this->systemMetrics = $this->monitor->getSystemMetrics();
        $this->databaseMetrics = $this->monitor->getDatabaseMetrics();
        $this->healthCheck = $this->monitor->healthCheck();
    }

    protected function getActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh Metrics')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    $this->loadMetrics();
                    $this->dispatch('metrics-refreshed');
                }),

            Action::make('runHealthCheck')
                ->label('Run Full Health Check')
                ->icon('heroicon-o-heart')
                ->color('success')
                ->action(function () {
                    $this->healthCheck = $this->monitor->healthCheck();
                    $this->dispatch('health-check-completed');
                }),

            Action::make('clearCache')
                ->label('Clear Cache')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->action(function () {
                    $this->clearCache();
                    Notification::make()
                        ->title('Cache Cleared')
                        ->body('The cache has been cleared successfully.')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function getSystemStatusColor(): string
    {
        return match ($this->healthCheck['status'] ?? 'unknown') {
            'healthy' => 'success',
            'warning' => 'warning',
            'critical' => 'danger',
            default => 'gray',
        };
    }

    public function getSystemStatusIcon(): string
    {
        return match ($this->healthCheck['status'] ?? 'unknown') {
            'healthy' => 'heroicon-o-check-circle',
            'warning' => 'heroicon-o-exclamation-triangle',
            'critical' => 'heroicon-o-x-circle',
            default => 'heroicon-o-question-mark-circle',
        };
    }

    public function getMemoryUsagePercentage(): float
    {
        $usage = $this->systemMetrics['memory_usage_mb'] ?? 0;
        $limit = $this->systemMetrics['memory_limit_mb'] ?? 1;
        return round(($usage / $limit) * 100, 1);
    }

    public function getCpuStatusColor(): string
    {
        $usage = $this->systemMetrics['cpu_usage_percent'] ?? 0;
        if ($usage >= 80)
            return 'danger';
        if ($usage >= 60)
            return 'warning';
        return 'success';
    }

    public function getMemoryStatusColor(): string
    {
        $percentage = $this->getMemoryUsagePercentage();
        if ($percentage >= 80)
            return 'danger';
        if ($percentage >= 60)
            return 'warning';
        return 'success';
    }

    public function getDiskStatusColor(): string
    {
        $usage = $this->systemMetrics['disk_usage_percent'] ?? 0;
        if ($usage >= 85)
            return 'danger';
        if ($usage >= 70)
            return 'warning';
        return 'success';
    }

    public function getCacheStatusColor(): string
    {
        $ratio = $this->systemMetrics['cache_hit_ratio'] ?? 0;
        if ($ratio >= 0.8)
            return 'success';
        if ($ratio >= 0.6)
            return 'warning';
        return 'danger';
    }

    public function clearCache(): void
    {
        ResponseCache::clear();
        $this->loadMetrics();
    }
}