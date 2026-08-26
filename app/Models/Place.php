<?php

namespace App\Models;

use App\Support\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Place extends Model
{
    use HasFactory;
    use Translatable;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_REJECTED = 'rejected';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'description' => 'array',
            'gallery' => 'array',
            'social_links' => 'array',
            'opening_hours' => 'array',
            'is_featured' => 'boolean',
            'latitude' => 'float',
            'longitude' => 'float',
            'rating' => 'float',
            'reviewed_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'subcategory_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function videos(): BelongsToMany
    {
        return $this->belongsToMany(Video::class)
            ->withPivot(['match_score', 'match_reason', 'is_primary', 'confirmed'])
            ->withTimestamps();
    }

    /** Videos actually shown on the public page: approved, available, embeddable. */
    public function publicVideos(): BelongsToMany
    {
        return $this->videos()
            ->where('videos.status', Video::STATUS_APPROVED)
            ->where('videos.is_available', true);
    }

    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
