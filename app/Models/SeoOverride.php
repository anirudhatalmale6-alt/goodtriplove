<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeoOverride extends Model
{
    protected $fillable = [
        'page_type','page_key','locale','title','description',
        'canonical_url','indexable','structured_data'
    ];

    protected $casts = [
        'indexable' => 'boolean',
        'structured_data' => 'array',
    ];
}
