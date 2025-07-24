<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Services\BackupService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class BackupManagement extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-archive-box';
    protected static ?string $navigationLabel = 'Backup Management';
    protected static ?string $navigationGroup = 'System';
    protected static ?int $navigationSort = 2;
    protected static string $view = 'filament.pages.backup-management';

    public array $backups = [];
    public array $recentBackups = [];

    protected BackupService $backupService;

    public function boot(): void
    {
        $this->backupService = app(BackupService::class);
        $this->loadBackups();
    }

    public function mount(): void
    {
        $this->loadBackups();
    }

    protected function loadBackups(): void
    {
        $this->backups = $this->backupService->listBackups();
        $this->recentBackups = array_slice($this->backups, 0, 10);
    }

    protected function getActions(): array
    {
        return [
            Action::make('createFullBackup')
                ->label('Create Full Backup')
                ->icon('heroicon-o-archive-box-arrow-down')
                ->color('success')
                ->action(function () {
                    $this->createBackup('full');
                }),

            Action::make('createDatabaseBackup')
                ->label('Database Backup')
                ->icon('heroicon-o-circle-stack')
                ->color('info')
                ->action(function () {
                    $this->createBackup('database');
                }),

            Action::make('createFilesBackup')
                ->label('Files Backup')
                ->icon('heroicon-o-folder')
                ->color('warning')
                ->action(function () {
                    $this->createBackup('files');
                }),
        ];
    }

    public function createBackup(string $type): void
    {
        try {
            $result = match ($type) {
                'full' => $this->backupService->createFullBackup(['cleanup_old' => true]),
                'database' => $this->createDatabaseBackup(),
                'files' => $this->createFilesBackup(),
                default => ['success' => false, 'message' => 'Unknown backup type'],
            };

            if ($result['success']) {
                Notification::make()
                    ->title('Backup Created Successfully')
                    ->body("Backup ID: {$result['backup_id']}")
                    ->success()
                    ->duration(5000)
                    ->send();

                $this->loadBackups();
            } else {
                Notification::make()
                    ->title('Backup Failed')
                    ->body($result['error'] ?? 'Unknown error occurred')
                    ->danger()
                    ->duration(5000)
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Backup Error')
                ->body($e->getMessage())
                ->danger()
                ->duration(5000)
                ->send();
        }
    }

    protected function createDatabaseBackup(): array
    {
        $backupId = "db_" . now()->format('Y-m-d_H-i-s');

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

    protected function createFilesBackup(): array
    {
        $backupId = "files_" . now()->format('Y-m-d_H-i-s');

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

    public function downloadBackup(string $file): mixed
    {
        try {
            $disk = config('backup.disk', 'local');

            if (!Storage::disk($disk)->exists($file)) {
                Notification::make()
                    ->title('File Not Found')
                    ->body('The backup file does not exist.')
                    ->danger()
                    ->send();
            } else {
                return Storage::disk($disk)->download($file);
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Download Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function deleteBackup(string $file): void
    {
        try {
            $disk = config('backup.disk', 'local');

            if (Storage::disk($disk)->exists($file)) {
                Storage::disk($disk)->delete($file);

                Notification::make()
                    ->title('Backup Deleted')
                    ->body('The backup file has been deleted successfully.')
                    ->success()
                    ->send();

                $this->loadBackups();
            } else {
                Notification::make()
                    ->title('File Not Found')
                    ->body('The backup file does not exist.')
                    ->warning()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Delete Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function getBackupTypeColor(string $type): string
    {
        return match ($type) {
            'database' => 'info',
            'files' => 'warning',
            'config' => 'success',
            default => 'gray',
        };
    }

    public function getBackupTypeIcon(string $type): string
    {
        return match ($type) {
            'database' => 'heroicon-o-circle-stack',
            'files' => 'heroicon-o-folder',
            'config' => 'heroicon-o-cog-6-tooth',
            default => 'heroicon-o-archive-box',
        };
    }

    public function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    public function formatBackupDate(int $timestamp): string
    {
        return date('Y-m-d H:i:s', $timestamp);
    }

    public function getStorageInfo(): array
    {
        $disk = config('backup.disk', 'local');
        $backupPath = 'backups';

        try {
            $files = Storage::disk($disk)->allFiles($backupPath);
            $totalSize = 0;

            foreach ($files as $file) {
                $totalSize += Storage::disk($disk)->size($file);
            }

            return [
                'total_files' => count($files),
                'total_size' => $totalSize,
                'formatted_size' => $this->formatFileSize($totalSize),
            ];
        } catch (\Exception $e) {
            return [
                'total_files' => 0,
                'total_size' => 0,
                'formatted_size' => '0 B',
            ];
        }
    }
}