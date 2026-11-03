<?php

return [
    'path' => env('BACKUP_PATH', storage_path('app/backups')),
    'binary' => env('BACKUP_BINARY'),
    'retention_days' => (int) env('BACKUP_RETENTION_DAYS', 30),
    'timeout' => (int) env('BACKUP_TIMEOUT', 3600),
];
