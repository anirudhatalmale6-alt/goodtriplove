<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppRelease extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'release_notes' => 'array',
            'is_active' => 'boolean',
            'released_at' => 'datetime',
        ];
    }

    public function sizeForHumans(): ?string
    {
        if (! $this->file_size) {
            return null;
        }

        return number_format($this->file_size / 1048576, 1).' MB';
    }
}
