<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Backup Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for automated backup and recovery procedures.
    | These settings control how backups are created, stored, and managed.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Backup Storage Disk
    |--------------------------------------------------------------------------
    |
    | The storage disk where backups will be stored. For production,
    | consider using a remote disk like S3 for offsite backup storage.
    |
    */

    'disk' => env('BACKUP_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Backup Retention Policy
    |--------------------------------------------------------------------------
    |
    | How long to keep backups before automatic cleanup.
    | Set in days. Use 0 to disable automatic cleanup.
    |
    */

    'retention_days' => env('BACKUP_RETENTION_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Backup Schedule
    |--------------------------------------------------------------------------
    |
    | Automatic backup schedule configuration.
    | Set to true to enable scheduled backups.
    |
    */

    'schedule' => [
        'enabled' => env('BACKUP_SCHEDULE_ENABLED', false),
        'frequency' => env('BACKUP_FREQUENCY', 'daily'), // daily, weekly, monthly
        'time' => env('BACKUP_TIME', '02:00'), // Time in 24-hour format
        'timezone' => env('BACKUP_TIMEZONE', 'UTC'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Backup Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for database backup procedures.
    |
    */

    'database' => [
        'enabled' => true,
        'compress' => true,
        'compression_level' => 9, // 1-9, higher = better compression
        'exclude_tables' => [
            // Add table names to exclude from backup
            'cache',
            'sessions',
            'failed_jobs',
        ],
        'timeout' => env('BACKUP_DB_TIMEOUT', 300), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Files Backup Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for file system backup procedures.
    |
    */

    'files' => [
        'enabled' => true,
        'paths' => [
            storage_path('app'),
            public_path('uploads'),
            base_path('.env'),
        ],
        'exclude_patterns' => [
            '*.log',
            'cache/*',
            'sessions/*',
            'temp/*',
            '.DS_Store',
            'Thumbs.db',
        ],
        'timeout' => env('BACKUP_FILES_TIMEOUT', 600), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Backup Verification
    |--------------------------------------------------------------------------
    |
    | Settings for backup integrity verification.
    |
    */

    'verification' => [
        'enabled' => true,
        'check_file_sizes' => true,
        'check_compression' => true,
        'verify_restore' => env('BACKUP_VERIFY_RESTORE', false), // Test restore on backup
    ],

    /*
    |--------------------------------------------------------------------------
    | Backup Notifications
    |--------------------------------------------------------------------------
    |
    | Configure notifications for backup events.
    |
    */

    'notifications' => [
        'enabled' => env('BACKUP_NOTIFICATIONS_ENABLED', false),
        'channels' => ['mail', 'slack'],
        'on_success' => env('BACKUP_NOTIFY_SUCCESS', false),
        'on_failure' => env('BACKUP_NOTIFY_FAILURE', true),
        'recipients' => [
            'mail' => [
                env('BACKUP_MAIL_TO', 'admin@example.com'),
            ],
            'slack' => [
                'webhook_url' => env('BACKUP_SLACK_WEBHOOK'),
                'channel' => env('BACKUP_SLACK_CHANNEL', '#alerts'),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    |
    | Performance-related backup settings.
    |
    */

    'performance' => [
        'memory_limit' => env('BACKUP_MEMORY_LIMIT', '512M'),
        'max_execution_time' => env('BACKUP_MAX_EXECUTION_TIME', 3600), // seconds
        'chunk_size' => env('BACKUP_CHUNK_SIZE', 8192), // bytes for file operations
        'parallel_processing' => env('BACKUP_PARALLEL', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Security settings for backup operations.
    |
    */

    'security' => [
        'encrypt_backups' => env('BACKUP_ENCRYPT', false),
        'encryption_key' => env('BACKUP_ENCRYPTION_KEY'),
        'sign_backups' => env('BACKUP_SIGN', false),
        'signing_key' => env('BACKUP_SIGNING_KEY'),
        'secure_delete' => true, // Securely delete temp files
    ],

    /*
    |--------------------------------------------------------------------------
    | Cloud Storage Settings
    |--------------------------------------------------------------------------
    |
    | Settings for cloud backup storage providers.
    |
    */

    'cloud' => [
        's3' => [
            'enabled' => env('BACKUP_S3_ENABLED', false),
            'bucket' => env('AWS_BUCKET'),
            'region' => env('AWS_DEFAULT_REGION'),
            'path' => env('BACKUP_S3_PATH', 'backups'),
            'storage_class' => env('BACKUP_S3_STORAGE_CLASS', 'STANDARD_IA'),
        ],

        'google_cloud' => [
            'enabled' => env('BACKUP_GCS_ENABLED', false),
            'bucket' => env('GOOGLE_CLOUD_STORAGE_BUCKET'),
            'path' => env('BACKUP_GCS_PATH', 'backups'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring & Logging
    |--------------------------------------------------------------------------
    |
    | Backup monitoring and logging configuration.
    |
    */

    'monitoring' => [
        'log_level' => env('BACKUP_LOG_LEVEL', 'info'),
        'log_channel' => env('BACKUP_LOG_CHANNEL', 'daily'),
        'metrics_enabled' => env('BACKUP_METRICS_ENABLED', true),
        'health_check_url' => env('BACKUP_HEALTH_CHECK_URL'), // External monitoring URL
    ],

    /*
    |--------------------------------------------------------------------------
    | Disaster Recovery
    |--------------------------------------------------------------------------
    |
    | Settings for disaster recovery procedures.
    |
    */

    'disaster_recovery' => [
        'enabled' => env('DISASTER_RECOVERY_ENABLED', true),
        'recovery_point_objective' => env('RECOVERY_POINT_OBJECTIVE', 24), // hours
        'recovery_time_objective' => env('RECOVERY_TIME_OBJECTIVE', 4), // hours
        'offsite_backup' => env('OFFSITE_BACKUP_ENABLED', false),
        'test_restore_frequency' => env('TEST_RESTORE_FREQUENCY', 'monthly'),
    ],

];