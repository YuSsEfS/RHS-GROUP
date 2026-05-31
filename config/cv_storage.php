<?php

return [
    'enable_compression' => env('CV_STORAGE_ENABLE_COMPRESSION', true),
    'keep_originals' => env('CV_STORAGE_KEEP_ORIGINALS', false),
    'compression_disk' => env('CV_STORAGE_COMPRESSION_DISK', 'local'),
    'allowed_extensions' => ['pdf', 'doc', 'docx', 'txt'],
    'min_size_to_compress' => (int) env('CV_STORAGE_MIN_SIZE_TO_COMPRESS', 65536),
    'eta_recent_window_minutes' => (int) env('CV_STORAGE_ETA_RECENT_WINDOW_MINUTES', 10),
];
