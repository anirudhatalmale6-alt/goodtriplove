<?php

namespace App\Services\Social;

use App\Models\Video;

/**
 * The outcome of importing one URL.
 *
 * Made explicit rather than returning a Video or null, because "we refused
 * this" has four different meanings and the administrator needs to be told
 * which one. A null would collapse "already in the catalogue" and "Instagram
 * would not give us a title" into the same shrug.
 */
class SocialImportResult
{
    public const CREATED = 'created';
    public const DUPLICATE = 'duplicate';
    public const UNSUPPORTED = 'unsupported';
    public const NEEDS_TITLE = 'needs_title';

    private function __construct(
        public readonly string $outcome,
        public readonly ?Video $video = null,
        public readonly ?Video $existing = null,
        public readonly ?string $message = null,
        public readonly ?string $duplicateReason = null,
    ) {}

    public static function created(Video $video, ?string $message = null): self
    {
        return new self(self::CREATED, video: $video, message: $message);
    }

    public static function duplicate(Video $existing, string $reason): self
    {
        return new self(self::DUPLICATE, existing: $existing, duplicateReason: $reason);
    }

    public static function unsupported(string $message): self
    {
        return new self(self::UNSUPPORTED, message: $message);
    }

    public static function needsTitle(string $message): self
    {
        return new self(self::NEEDS_TITLE, message: $message);
    }

    public function successful(): bool
    {
        return $this->outcome === self::CREATED;
    }
}
