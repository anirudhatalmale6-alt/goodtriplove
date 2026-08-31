<?php

namespace App\Services\Social;

use App\Models\Video;
use App\Services\DuplicateFinder;
use App\Support\SocialPlatform;
use App\Support\SystemSettings;

/**
 * Turns a pasted URL into a catalogue row.
 *
 * This is the one path every platform shares. The collector still imports
 * YouTube in bulk through its own service; this is what a human uses, and it is
 * the only route Instagram, TikTok and Facebook have until Meta and TikTok
 * approve an application.
 *
 * The rule the whole platform is built on holds here too: **we never download
 * or re-host a creator's video.** What gets stored is an identifier, a link and
 * whatever the platform chose to tell us about it.
 */
class SocialImporter
{
    public function __construct(
        private SocialMetadataFetcher $fetcher,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes  admin-supplied fields: title,
     *                                            country_id, city_id, category_id
     */
    public function import(string $url, array $attributes = [], ?int $userId = null): SocialImportResult
    {
        $url = trim($url);

        // A short link carries no id, so it has to be followed before anything
        // can be read from it.
        if (SocialPlatform::isShortLink($url)) {
            $url = $this->fetcher->resolveShortLink($url);
        }

        $parsed = SocialPlatform::parse($url);

        if (! $parsed) {
            return SocialImportResult::unsupported(
                "Cette adresse n'est pas reconnue comme une vidéo YouTube, TikTok, Instagram ou Facebook."
            );
        }

        if (! $this->platformEnabled($parsed['provider'])) {
            return SocialImportResult::unsupported(sprintf(
                "L'import %s est désactivé dans les réglages.",
                SocialPlatform::label($parsed['provider']),
            ));
        }

        if ($existing = $this->existingFor($parsed)) {
            return SocialImportResult::duplicate($existing, 'Cette vidéo est déjà dans le catalogue.');
        }

        $metadata = $this->fetcher->fetch($parsed['provider'], $parsed['url']);

        $title = $this->firstFilled($attributes['title'] ?? null, $metadata['title']);

        if (! $title) {
            return SocialImportResult::needsTitle(sprintf(
                '%s ne renvoie pas de titre pour cette vidéo (%s). Saisis un titre pour l\'ajouter quand même — le lecteur fonctionnera normalement.',
                SocialPlatform::label($parsed['provider']),
                $metadata['reason'] ?? 'aucune information disponible',
            ));
        }

        // The title match is the one duplicate check the unique index cannot
        // do: the same clip reposted under a new id, on the same platform or a
        // different one. It runs after the metadata fetch because until now we
        // had no title to compare.
        if ($this->duplicateCheckEnabled() && $existing = $this->sameTitle($title)) {
            return SocialImportResult::duplicate(
                $existing,
                sprintf('Une vidéo au même titre existe déjà (%s).', SocialPlatform::label($existing->provider)),
            );
        }

        $video = Video::create([
            'provider' => $parsed['provider'],
            'provider_video_id' => $parsed['id'],
            'original_url' => $parsed['url'],
            'title' => $title,
            'channel_title' => $metadata['author_name'],
            'author_url' => $metadata['author_url'],
            'thumbnail_url' => $metadata['thumbnail_url'] ?: SocialPlatform::thumbnailUrl($parsed['provider'], $parsed['id']),
            'duration_seconds' => $metadata['duration_seconds'],
            'country_id' => $attributes['country_id'] ?? null,
            'city_id' => $attributes['city_id'] ?? null,
            'category_id' => $attributes['category_id'] ?? null,
            'status' => $this->initialStatus(),
            'source' => 'admin',
            'submitted_by' => $userId,
            'published_at' => now(),
            'embeddable' => true,
            'is_available' => true,
        ]);

        return SocialImportResult::created(
            $video,
            $metadata['fetched'] ? null : ($metadata['reason'] ? 'Ajoutée sans les informations de la plateforme : '.$metadata['reason'] : null),
        );
    }

    /**
     * The row that already holds this video.
     *
     * Two ways in: the provider id, which the unique index also enforces, and
     * the original URL, which catches the same clip pasted in a different shape
     * — a share link with tracking parameters, or a mobile host.
     */
    private function existingFor(array $parsed): ?Video
    {
        return Video::query()
            ->where(function ($query) use ($parsed) {
                $query->where(fn ($q) => $q->where('provider', $parsed['provider'])->where('provider_video_id', $parsed['id']))
                    ->orWhere('original_url', $parsed['url']);
            })
            ->first();
    }

    /** Cross-platform: the same clip on TikTok and on Instagram is one clip. */
    private function sameTitle(string $title): ?Video
    {
        $key = DuplicateFinder::key($title);

        if (mb_strlen($key) < 12) {
            return null;
        }

        return Video::query()
            ->where('status', '!=', Video::STATUS_REJECTED)
            ->get(['id', 'title', 'provider', 'provider_video_id', 'status'])
            ->first(fn (Video $video) => DuplicateFinder::key($video->title) === $key);
    }

    private function platformEnabled(string $provider): bool
    {
        return (bool) SystemSettings::effective('social_'.$provider.'_enabled');
    }

    private function duplicateCheckEnabled(): bool
    {
        return (bool) SystemSettings::effective('social_duplicate_check');
    }

    /**
     * Manual approval is the default, and deliberately so: a video imported
     * straight to the public site is one nobody has looked at, on a platform
     * whose metadata we may not even have.
     */
    private function initialStatus(): string
    {
        return SystemSettings::effective('social_require_approval')
            ? Video::STATUS_PENDING
            : Video::STATUS_APPROVED;
    }

    private function firstFilled(?string ...$values): ?string
    {
        foreach ($values as $value) {
            if (filled($value)) {
                return trim((string) $value);
            }
        }

        return null;
    }
}
