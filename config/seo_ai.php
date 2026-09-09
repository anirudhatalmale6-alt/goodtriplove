<?php

return [
    'enabled' => env('SEO_AI_ENABLED', true),
    'provider' => env('SEO_AI_PROVIDER', 'ollama'),
    // The key entered in the admin wins; this is only the fallback.
    'openai_api_key' => env('OPENAI_API_KEY'),
    'openai_model' => env('SEO_AI_OPENAI_MODEL', 'gpt-4o-mini'),
    // Local generation. num_thread is deliberately below the core count:
    // this machine also serves the websites, and inference will take every
    // core it is given.
    'ollama' => [
        'url' => env('SEO_AI_OLLAMA_URL', 'http://127.0.0.1:11434'),
        'model' => env('SEO_AI_OLLAMA_MODEL', 'qwen3:4b'),
        'num_thread' => (int) env('SEO_AI_OLLAMA_THREADS', 4),
        'num_predict' => (int) env('SEO_AI_OLLAMA_NUM_PREDICT', 2400),
        'num_ctx' => (int) env('SEO_AI_OLLAMA_NUM_CTX', 8192),
        'temperature' => (float) env('SEO_AI_OLLAMA_TEMPERATURE', 0.35),
        // One locale of ~900 words at ~6 tokens/second needs real headroom.
        'timeout' => (int) env('SEO_AI_OLLAMA_TIMEOUT', 900),
    ],

    'quality_threshold' => (int) env('SEO_AI_QUALITY_THRESHOLD', 72),
    'weekly_day' => env('SEO_AI_WEEKLY_DAY', 'monday'),
    'weekly_time' => env('SEO_AI_WEEKLY_TIME', '04:45'),
    'topic_cooldown_weeks' => (int) env('SEO_AI_TOPIC_COOLDOWN_WEEKS', 52),
    // Support is videos + places. The old keys counted places only, which on a
    // catalogue with zero places meant no topic could ever qualify.
    'min_support_city_category' => (int) env('SEO_AI_MIN_CITY_CATEGORY', 2),
    'min_support_country_category' => (int) env('SEO_AI_MIN_COUNTRY_CATEGORY', 3),
    'min_support_city_guide' => (int) env('SEO_AI_MIN_CITY_GUIDE', 3),
    'min_support_category_guide' => (int) env('SEO_AI_MIN_CATEGORY_GUIDE', 4),
    'max_supporting_places' => 12,
    'max_supporting_videos' => 12,
];
