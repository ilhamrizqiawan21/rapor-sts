<?php

return [
    // Folder penyimpanan berkas backup (.sql.gz).
    'path' => storage_path('app/backups'),

    // Backup otomatis (butuh cron: * * * * * php artisan schedule:run). Nilai: daily, weekly, off.
    'schedule' => env('BACKUP_SCHEDULE', 'daily'),
    'time' => env('BACKUP_TIME', '01:30'),

    // Jumlah berkas terbaru yang disimpan; sisanya dihapus otomatis.
    'keep' => (int) env('BACKUP_KEEP', 14),

    // Path mysqldump; kosongkan untuk mencari otomatis, fallback ke ekspor PHP bila tidak ada.
    'mysqldump_path' => env('BACKUP_MYSQLDUMP_PATH'),
    'mysql_path' => env('BACKUP_MYSQL_PATH'),
    'restore_max_bytes' => (int) env('BACKUP_RESTORE_MAX_BYTES', 536870912),
];
