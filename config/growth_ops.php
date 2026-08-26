<?php

return [
    'data_quality' => [
        'duplicate_similarity_threshold' => 90,
        'stale_days' => 30,
    ],

    'analytics' => [
        'online_window_minutes' => 5,
        'retention_days' => 180,
    ],

    'seo' => [
        'default_locale' => 'fr',
        'locales' => ['fr','pt','es','it','de','en'],
        'sitemap_chunk_size' => 40000,
    ],

    'monitoring' => [
        'disk_warning_percent' => 80,
        'queue_warning_count' => 500,
    ],
];
