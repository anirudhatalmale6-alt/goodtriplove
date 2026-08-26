<?php

return [
    'alerts' => [
        'email' => env('SECURITY_CENTER_ALERT_EMAIL'),
        'new_device' => (bool) env('SECURITY_CENTER_NEW_DEVICE_ALERT', true),
    ],

    'admin_2fa_required' => (bool) env('SECURITY_CENTER_REQUIRE_ADMIN_2FA', true),

    'backup_path' => env('SECURITY_CENTER_BACKUP_PATH', storage_path('app/security-backups')),

    'health_token' => env('SECURITY_CENTER_HEALTH_TOKEN'),

    'video_check_batch' => (int) env('SECURITY_CENTER_VIDEO_CHECK_BATCH', 100),

    'ollama' => [
        'base_url' => env('OLLAMA_BASE_URL', 'http://127.0.0.1:11434'),
        'model' => env('OLLAMA_MODEL', 'qwen3:1.7b'),
    ],
];
