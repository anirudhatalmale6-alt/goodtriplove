<?php

return [
    'enabled' => env('SEO_AI_ENABLED', true),
    'provider' => 'openai',
    // The key entered in the admin wins; this is only the fallback.
    'openai_api_key' => env('OPENAI_API_KEY'),
    'openai_model' => env('SEO_AI_OPENAI_MODEL', 'gpt-4o-mini'),
    'quality_threshold' => (int) env('SEO_AI_QUALITY_THRESHOLD', 72),
    'weekly_day' => env('SEO_AI_WEEKLY_DAY', 'monday'),
    'weekly_time' => env('SEO_AI_WEEKLY_TIME', '04:45'),
    'topic_cooldown_weeks' => (int) env('SEO_AI_TOPIC_COOLDOWN_WEEKS', 52),
    'min_places_city_category' => 2,
    'min_places_country_category' => 3,
    'min_places_city_guide' => 3,
    'min_places_category_guide' => 4,
    'max_supporting_places' => 12,
    'max_supporting_videos' => 12,
];
