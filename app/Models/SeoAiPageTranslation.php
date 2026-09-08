<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeoAiPageTranslation extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'content_json' => 'array',
            'faq_json' => 'array',
            'keywords_json' => 'array',
        ];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(SeoAiPage::class, 'seo_ai_page_id');
    }
}
