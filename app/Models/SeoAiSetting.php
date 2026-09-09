<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeoAiSetting extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'auto_publish' => 'boolean',
            'api_key' => 'encrypted',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'enabled' => config('seo_ai.enabled', true),
            'provider' => config('seo_ai.provider', 'openai'),
            'model' => config('seo_ai.openai_model'),
            'ollama_url' => config('seo_ai.ollama.url'),
            'ollama_model' => config('seo_ai.ollama.model'),
            'quality_threshold' => config('seo_ai.quality_threshold', 72),
            'auto_publish' => false,
            'weekly_day' => config('seo_ai.weekly_day', 'monday'),
            'weekly_time' => config('seo_ai.weekly_time', '04:45'),
        ]);
    }
}
