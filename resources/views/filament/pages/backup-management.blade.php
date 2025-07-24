<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Storage Overview -->
        @php
            $storageInfo = $this->getStorageInfo();
        @endphp

        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Storage Overview</h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-blue-600 dark:text-blue-400">Total Backups</p>
                            <p class="text-2xl font-bold text-blue-700 dark:text-blue-300">
                                {{ $storageInfo['total_files'] }}
                            </p>
                        </div>
                        <x-heroicon-o-archive-box class="w-8 h-8 text-blue-500" />
                    </div>
                </div>

                <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-green-600 dark:text-green-400">Storage Used</p>
                            <p class="text-2xl font-bold text-green-700 dark:text-green-300">
                                {{ $storageInfo['formatted_size'] }}
                            </p>
                        </div>
                        <x-heroicon-o-server-stack class="w-8 h-8 text-green-500" />
                    </div>
                </div>

                <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-purple-600 dark:text-purple-400">Backup Disk</p>
                            <p class="text-lg font-bold text-purple-700 dark:text-purple-300 uppercase">
                                {{ config('backup.disk', 'local') }}
                            </p>
                        </div>
                        <x-heroicon-o-circle-stack class="w-8 h-8 text-purple-500" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Backup Actions -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Quick Backup Actions</h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Full Backup -->
                <div
                    class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 dark:hover:bg-gray-700/50 transition-colors">
                    <div class="text-center">
                        <x-heroicon-o-archive-box-arrow-down class="w-10 h-10 text-green-500 mx-auto mb-3" />
                        <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Full Backup</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                            Complete backup including database, files, and configuration
                        </p>
                        <button wire:click="createBackup('full')"
                            class="w-full bg-green-600 hover:bg-green-700 font-medium py-2 px-4 rounded-lg transition-colors">
                            Create Full Backup
                        </button>
                    </div>
                </div>

                <!-- Database Backup -->
                <div
                    class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 dark:hover:bg-gray-700/50 transition-colors">
                    <div class="text-center">
                        <x-heroicon-o-circle-stack class="w-10 h-10 text-green-500 mx-auto mb-3" />
                        <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Database Only</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                            Backup database tables, data, and structure
                        </p>
                        <button wire:click="createBackup('database')"
                            class="w-full bg-blue-600 hover:bg-blue-700 font-medium py-2 px-4 rounded-lg transition-colors">
                            Backup Database
                        </button>
                    </div>
                </div>

                <!-- Files Backup -->
                <div
                    class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 dark:hover:bg-gray-700/50 transition-colors">
                    <div class="text-center">
                        <x-heroicon-o-folder class="w-10 h-10 text-green-500 mx-auto mb-3" />
                        <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Files Only</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                            Backup application files, uploads, and storage
                        </p>
                        <button wire:click="createBackup('files')"
                            class="w-full bg-orange-600 hover:bg-orange-700 font-medium py-2 px-4 rounded-lg transition-colors">
                            Backup Files
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Backups -->
        @if(!empty($recentBackups))
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Recent Backups</h3>
                    <button wire:click="mount"
                        class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                        <x-heroicon-o-arrow-path class="w-4 h-4 inline mr-1" />
                        Refresh
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Type
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    File
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Size
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Created
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($recentBackups as $backup)
                                <tr class="">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <x-dynamic-component :component="$this->getBackupTypeIcon($backup['type'])"
                                                class="w-5 h-5 text-{{ $this->getBackupTypeColor($backup['type']) }}-500 mr-2" />
                                            <span
                                                class="text-sm font-medium text-{{ $this->getBackupTypeColor($backup['type']) }}-600 dark:text-{{ $this->getBackupTypeColor($backup['type']) }}-400 capitalize">
                                                {{ $backup['type'] }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 dark:text-white font-mono">
                                            {{ basename($backup['file']) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ $this->formatFileSize($backup['size']) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ $this->formatBackupDate($backup['modified']) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex items-center space-x-2">
                                            <button wire:click="downloadBackup('{{ $backup['file'] }}')"
                                                class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                                                title="Download backup">
                                                <x-heroicon-o-arrow-down-tray class="w-4 h-4" />
                                            </button>
                                            <button wire:click="deleteBackup('{{ $backup['file'] }}')"
                                                wire:confirm="Are you sure you want to delete this backup?"
                                                class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                                                title="Delete backup">
                                                <x-heroicon-o-trash class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if(count($backups) > 10)
                    <div class="mt-4 text-center">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Showing 10 of {{ count($backups) }} total backups
                        </p>
                    </div>
                @endif
            </div>
        @else
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 text-center">
                <x-heroicon-o-archive-box class="w-16 h-16 text-gray-400 mx-auto mb-4" />
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No Backups Found</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    You haven't created any backups yet. Use the actions above to create your first backup.
                </p>
            </div>
        @endif

        <!-- Backup Configuration -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Backup Configuration</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-300">Retention Period</span>
                        <span class="text-sm text-gray-900 dark:text-white">
                            {{ config('backup.retention_days', 30) }} days
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-300">Storage Disk</span>
                        <span class="text-sm text-gray-900 dark:text-white uppercase">
                            {{ config('backup.disk', 'local') }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-300">Compression</span>
                        <span class="text-sm text-gray-900 dark:text-white">
                            {{ config('backup.database.compress', true) ? 'Enabled' : 'Disabled' }}
                        </span>
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-300">Backup Verification</span>
                        <span class="text-sm text-gray-900 dark:text-white">
                            {{ config('backup.verification.enabled', true) ? 'Enabled' : 'Disabled' }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-300">Notifications</span>
                        <span class="text-sm text-gray-900 dark:text-white">
                            {{ config('backup.notifications.enabled', false) ? 'Enabled' : 'Disabled' }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-300">Encryption</span>
                        <span class="text-sm text-gray-900 dark:text-white">
                            {{ config('backup.security.encrypt_backups', false) ? 'Enabled' : 'Disabled' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading indicator -->
    <div wire:loading.flex class="fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 flex items-center space-x-3">
            <svg class="animate-spin h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                </path>
            </svg>
            <span class="text-gray-900 dark:text-white">Creating backup...</span>
        </div>
    </div>
</x-filament-panels::page>