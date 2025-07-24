<x-filament-panels::page>
    <div class="space-y-6">
        <!-- System Status Overview -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">System Status</h3>
                <div class="flex items-center space-x-2">
                    <x-heroicon-o-check-circle class="w-5 h-5 text-{{ $this->getSystemStatusColor() }}-500" />
                    <span
                        class="text-sm font-medium text-{{ $this->getSystemStatusColor() }}-600 dark:text-{{ $this->getSystemStatusColor() }}-400 uppercase">
                        {{ $healthCheck['status'] ?? 'Unknown' }}
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- CPU Usage -->
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-300">CPU Usage</p>
                            <p class="text-2xl font-bold text-{{ $this->getCpuStatusColor() }}-600">
                                {{ number_format($systemMetrics['cpu_usage_percent'] ?? 0, 1) }}%
                            </p>
                        </div>
                        <x-heroicon-o-cpu-chip class="w-8 h-8 text-{{ $this->getCpuStatusColor() }}-500" />
                    </div>
                </div>

                <!-- Memory Usage -->
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Memory Usage</p>
                            <p class="text-2xl font-bold text-{{ $this->getMemoryStatusColor() }}-600">
                                {{ $this->getMemoryUsagePercentage() }}%
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ number_format($systemMetrics['memory_usage_mb'] ?? 0, 1) }} MB /
                                {{ number_format($systemMetrics['memory_limit_mb'] ?? 0, 1) }} MB
                            </p>
                        </div>
                        <x-heroicon-o-circle-stack class="w-8 h-8 text-{{ $this->getMemoryStatusColor() }}-500" />
                    </div>
                </div>

                <!-- Disk Usage -->
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Disk Usage</p>
                            <p class="text-2xl font-bold text-{{ $this->getDiskStatusColor() }}-600">
                                {{ number_format($systemMetrics['disk_usage_percent'] ?? 0, 1) }}%
                            </p>
                        </div>
                        <x-heroicon-o-server-stack class="w-8 h-8 text-{{ $this->getDiskStatusColor() }}-500" />
                    </div>
                </div>

                <!-- Cache Hit Ratio -->
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Cache Hit Ratio</p>
                            <p class="text-2xl font-bold text-{{ $this->getCacheStatusColor() }}-600">
                                {{ number_format(($systemMetrics['cache_hit_ratio'] ?? 0) * 100, 1) }}%
                            </p>
                        </div>
                        <x-heroicon-o-bolt class="w-8 h-8 text-{{ $this->getCacheStatusColor() }}-500" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Database Metrics -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Database Performance</h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Active Connections</p>
                            <p class="text-2xl font-bold text-blue-600">
                                {{ $databaseMetrics['connection_count'] ?? 0 }}
                            </p>
                        </div>
                        <x-heroicon-o-link class="w-8 h-8 text-blue-500" />
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Database Size</p>
                            <p class="text-2xl font-bold text-green-600">
                                {{ number_format($databaseMetrics['database_size_mb'] ?? 0, 1) }} MB
                            </p>
                        </div>
                        <x-heroicon-o-circle-stack class="w-8 h-8 text-green-500" />
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Slow Queries</p>
                            <p class="text-2xl font-bold text-orange-600">
                                {{ $databaseMetrics['slow_queries'] ?? 0 }}
                            </p>
                        </div>
                        <x-heroicon-o-clock class="w-8 h-8 text-orange-500" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Health Check Details -->
        @if(isset($healthCheck['checks']) && !empty($healthCheck['checks']))
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Health Check Details</h3>

                <div class="space-y-3">
                    @foreach($healthCheck['checks'] as $checkName => $check)
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="flex items-center space-x-3">
                                @php
                                    $statusColor = match ($check['status'] ?? 'unknown') {
                                        'healthy' => 'text-green-500',
                                        'warning' => 'text-yellow-500',
                                        'critical' => 'text-red-500',
                                        default => 'text-gray-500'
                                    };
                                    $statusIcon = match ($check['status'] ?? 'unknown') {
                                        'healthy' => 'heroicon-o-check-circle',
                                        'warning' => 'heroicon-o-exclamation-triangle',
                                        'critical' => 'heroicon-o-x-circle',
                                        default => 'heroicon-o-question-mark-circle'
                                    };
                                @endphp

                                <x-dynamic-component :component="$statusIcon" class="w-5 h-5 {{ $statusColor }}" />
                                <span
                                    class="font-medium text-gray-900 dark:text-white capitalize">{{ str_replace('_', ' ', $checkName) }}</span>
                            </div>

                            <div class="text-right">
                                <span class="text-sm font-medium {{ $statusColor }} uppercase">
                                    {{ $check['status'] ?? 'Unknown' }}
                                </span>
                                @if(isset($check['value']) && isset($check['threshold']))
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ number_format($check['value'], 1) }} / {{ number_format($check['threshold'], 1) }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- System Information -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">System Information</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-300">Uptime</span>
                        <span class="text-sm text-gray-900 dark:text-white">
                            {{ number_format($systemMetrics['uptime_seconds'] ?? 0, 0) }} seconds
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-300">Peak Memory</span>
                        <span class="text-sm text-gray-900 dark:text-white">
                            {{ number_format($systemMetrics['memory_peak_mb'] ?? 0, 1) }} MB
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-300">Environment</span>
                        <span class="text-sm text-gray-900 dark:text-white capitalize">
                            {{ app()->environment() }}
                        </span>
                    </div>
                </div>

                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-300">Laravel Version</span>
                        <span class="text-sm text-gray-900 dark:text-white">
                            {{ app()->version() }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-300">PHP Version</span>
                        <span class="text-sm text-gray-900 dark:text-white">
                            {{ PHP_VERSION }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-300">Last Updated</span>
                        <span class="text-sm text-gray-900 dark:text-white">
                            {{ now()->format('Y-m-d H:i:s') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Auto-refresh script -->
    <script>
        // Auto-refresh every 30 seconds
        setInterval(function () {
            if (document.visibilityState === 'visible') {
                window.Livewire.dispatch('refresh');
            }
        }, 30000);
    </script>
</x-filament-panels::page>