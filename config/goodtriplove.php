<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Languages
    |--------------------------------------------------------------------------
    | The six launch languages. The order here is the order of the language
    | switcher, and 'fallback' is used whenever a translated string is missing.
    */
    'locales' => [
        'fr' => ['name' => 'Français',  'native' => 'Français',  'flag' => '🇫🇷', 'region' => 'FR'],
        'pt' => ['name' => 'Portuguese', 'native' => 'Português', 'flag' => '🇵🇹', 'region' => 'PT'],
        'es' => ['name' => 'Spanish',    'native' => 'Español',   'flag' => '🇪🇸', 'region' => 'ES'],
        'it' => ['name' => 'Italian',    'native' => 'Italiano',  'flag' => '🇮🇹', 'region' => 'IT'],
        'de' => ['name' => 'German',     'native' => 'Deutsch',   'flag' => '🇩🇪', 'region' => 'DE'],
        'en' => ['name' => 'English',    'native' => 'English',   'flag' => '🇬🇧', 'region' => 'GB'],
    ],

    'default_locale' => env('GTL_DEFAULT_LOCALE', 'fr'),

    /*
    |--------------------------------------------------------------------------
    | YouTube Data API v3
    |--------------------------------------------------------------------------
    | The free tier is 10 000 units/day. A search costs 100 units, a videos.list
    | call costs 1 unit for up to 50 ids. The collector therefore searches
    | sparingly and refreshes metrics in batches of 50.
    */
    'youtube' => [
        'api_key' => env('YOUTUBE_API_KEY'),
        'base_url' => 'https://www.googleapis.com/youtube/v3',
        'daily_quota' => (int) env('YOUTUBE_DAILY_QUOTA', 10000),
        'quota_safety_margin' => (int) env('YOUTUBE_QUOTA_MARGIN', 500),
        'cost' => [
            'search' => 100,
            'videos' => 1,
        ],
        'batch_size' => 50,
        'timeout' => 20,
        // See YouTubeClient::request(): the key is restricted to this server's
        // IPv4 address, but the host also has IPv6 and prefers it by default.
        'force_ipv4' => (bool) env('YOUTUBE_FORCE_IPV4', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Local AI classification (Ollama)
    |--------------------------------------------------------------------------
    | Runs on the same server as PauloTrip, so it is deliberately capped: one
    | request at a time, short context, hard timeout. If Ollama is unreachable
    | the collector falls back to the heuristic classifier and keeps working.
    */
    'ollama' => [
        'enabled' => env('OLLAMA_ENABLED', true),
        'base_url' => env('OLLAMA_BASE_URL', 'http://127.0.0.1:11434'),
        'model' => env('OLLAMA_MODEL', 'qwen3:1.7b'),
        'timeout' => (int) env('OLLAMA_TIMEOUT', 90),
        'num_ctx' => (int) env('OLLAMA_NUM_CTX', 2048),
        'num_thread' => (int) env('OLLAMA_NUM_THREAD', 2),
        'keep_alive' => env('OLLAMA_KEEP_ALIVE', '5m'),
        'lock_seconds' => (int) env('OLLAMA_LOCK_SECONDS', 120),
        'max_per_run' => (int) env('OLLAMA_MAX_PER_RUN', 20),
        // Category confidence below which a video is re-examined and shown to
        // the administrator as "needs review".
        'review_below' => (float) env('GTL_REVIEW_BELOW', 0.65),
    ],

    /*
    |--------------------------------------------------------------------------
    | Video scoring
    |--------------------------------------------------------------------------
    | Weights behind "most viewed / most popular / trending / most relevant".
    | Popularity blends raw reach with engagement so a 3 M-view video with no
    | engagement does not automatically beat a 200 k video the audience loved.
    */
    'scoring' => [
        'popularity' => [
            'views_weight' => 0.6,
            'engagement_weight' => 0.25,
            'freshness_weight' => 0.15,
            'freshness_halflife_days' => 540,
        ],
        'trending' => [
            'window_days' => 14,
            'min_views' => 500,
        ],
        'relevance' => [
            'min_publishable' => 0.35,
            'title_place_weight' => 0.45,
            'title_city_weight' => 0.2,
            'category_weight' => 0.2,
            'ai_weight' => 0.15,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Player / performance
    |--------------------------------------------------------------------------
    | Nothing embeds a real player until the visitor clicks. Cards render a
    | thumbnail with a play button (facade pattern) so a page with 40 videos
    | still loads a single iframe at most.
    */
    'player' => [
        'facade' => true,
        'privacy_domain' => 'https://www.youtube-nocookie.com',
        'thumbnail_domain' => 'https://i.ytimg.com',
        'aspect_ratio' => '16 / 9',
    ],

    'tv' => [
        'playlist_size' => 12,
        'autoplay_next' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Legal
    |--------------------------------------------------------------------------
    | The publisher/host/controller details are placeholders on purpose: they
    | are factual statements about a real company and must be filled in and
    | validated before the site goes public.
    */
    'legal' => [
        'cookie_policy_version' => env('GTL_COOKIE_POLICY_VERSION', '1.0'),
        'publisher' => env('GTL_LEGAL_PUBLISHER', ''),
        'publisher_address' => env('GTL_LEGAL_ADDRESS', ''),
        'publisher_registration' => env('GTL_LEGAL_REGISTRATION', ''),
        'publication_director' => env('GTL_LEGAL_DIRECTOR', ''),
        'host' => env('GTL_LEGAL_HOST', ''),
        'contact_email' => env('GTL_LEGAL_CONTACT', 'contact@goodtriplove.com'),
        'dpo_email' => env('GTL_LEGAL_DPO', ''),
    ],

    'app_download' => [
        'android_store_url' => env('GTL_ANDROID_STORE_URL'),
        'ios_store_url' => env('GTL_IOS_STORE_URL'),
        'apk_enabled' => env('GTL_APK_ENABLED', false),
    ],
];
