<?php

namespace App\Services\Social;

use App\Support\SocialPlatform;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Asks a platform to describe one video, from its URL alone.
 *
 * What comes back differs per platform, and the difference is not a bug we can
 * code around — it is the platform's policy:
 *
 *   YouTube    oEmbed, no key, no quota. Title, author, thumbnail.
 *   TikTok     oEmbed, no key. Title, author, thumbnail.
 *   Facebook   oEmbed returns the player markup; the title needs an approved
 *              Meta application.
 *   Instagram  returns a permission error to everyone without an approved Meta
 *              application. Nothing at all.
 *
 * So this class never promises a title. It returns what it got and says so, and
 * the import flow asks a human for the rest. A fetch that fails is not an
 * error either: it means the video can still be embedded, just not described
 * automatically. Anything else would make a platform outage look like a bug in
 * the site.
 *
 * Using oEmbed for YouTube rather than the Data API is deliberate: pasting a
 * URL then costs zero quota units, leaving the whole 10 000/day budget to the
 * collector.
 */
class SocialMetadataFetcher
{
    private const TIMEOUT = 12;

    /** Meta's oEmbed lives on the Graph API and is versioned. */
    private const GRAPH_VERSION = 'v21.0';

    /**
     * @return array{
     *     title: ?string, author_name: ?string, author_url: ?string,
     *     thumbnail_url: ?string, duration_seconds: ?int, fetched: bool, reason: ?string
     * }
     */
    public function fetch(string $provider, string $url): array
    {
        try {
            return match ($provider) {
                SocialPlatform::YOUTUBE => $this->oembed('https://www.youtube.com/oembed', ['url' => $url, 'format' => 'json']),
                SocialPlatform::TIKTOK => $this->oembed('https://www.tiktok.com/oembed', ['url' => $url]),
                SocialPlatform::FACEBOOK => $this->meta('oembed_video', $url),
                SocialPlatform::INSTAGRAM => $this->meta('instagram_oembed', $url),
                default => $this->empty('plateforme inconnue'),
            };
        } catch (\Throwable $e) {
            Log::info('social metadata could not be fetched', [
                'provider' => $provider,
                'message' => $e->getMessage(),
            ]);

            return $this->empty('la plateforme n\'a pas répondu');
        }
    }

    /**
     * Follows a shortened link to the real one.
     *
     * vm.tiktok.com and fb.watch carry no video id at all, so there is nothing
     * to parse until the redirect has been followed. Returns the original URL
     * unchanged when the platform does not redirect, which the caller will then
     * fail to parse — the honest outcome.
     */
    public function resolveShortLink(string $url): string
    {
        try {
            $response = Http::timeout(self::TIMEOUT)
                ->withHeaders(['User-Agent' => $this->userAgent()])
                ->withOptions(['allow_redirects' => ['max' => 5, 'track_redirects' => true]])
                ->get($url);

            $chain = $response->getHeader('X-Guzzle-Redirect-History');

            return $chain ? (string) end($chain) : (string) $response->effectiveUri();
        } catch (\Throwable $e) {
            Log::info('short link could not be resolved', ['url' => $url, 'message' => $e->getMessage()]);

            return $url;
        }
    }

    /** The open oEmbed endpoints: YouTube and TikTok. */
    private function oembed(string $endpoint, array $query): array
    {
        $response = Http::timeout(self::TIMEOUT)
            ->withHeaders(['User-Agent' => $this->userAgent()])
            ->get($endpoint, $query);

        if (! $response->successful()) {
            return $this->empty('réponse '.$response->status().' de la plateforme');
        }

        $body = $response->json();

        if (! is_array($body)) {
            return $this->empty('réponse illisible');
        }

        return [
            'title' => $this->clean($body['title'] ?? null),
            'author_name' => $this->clean($body['author_name'] ?? null),
            'author_url' => $this->clean($body['author_url'] ?? null),
            'thumbnail_url' => $this->clean($body['thumbnail_url'] ?? null),
            'duration_seconds' => null,
            'fetched' => true,
            'reason' => null,
        ];
    }

    /**
     * Meta's oEmbed. Both endpoints officially require an app access token.
     *
     * The token is optional here rather than required: Facebook's video
     * endpoint answers a plain request with the player markup, which is enough
     * to embed. Instagram does not, and says so. Sending the token when one has
     * been configured is what upgrades both to real metadata.
     */
    private function meta(string $endpoint, string $url): array
    {
        $token = $this->metaToken();

        $response = Http::timeout(self::TIMEOUT)
            ->withHeaders(['User-Agent' => $this->userAgent()])
            ->get(sprintf('https://graph.facebook.com/%s/%s', self::GRAPH_VERSION, $endpoint), array_filter([
                'url' => $url,
                'access_token' => $token,
                'omitscript' => 'true',
            ]));

        $body = $response->json();

        if (isset($body['error'])) {
            return $this->empty($token
                ? 'refusé par Meta : '.($body['error']['message'] ?? 'erreur inconnue')
                : 'Meta demande une application approuvée pour les informations détaillées');
        }

        if (! $response->successful() || ! is_array($body)) {
            return $this->empty('réponse '.$response->status().' de Meta');
        }

        return [
            'title' => $this->clean($body['title'] ?? null),
            'author_name' => $this->clean($body['author_name'] ?? null),
            'author_url' => $this->clean($body['author_url'] ?? null),
            'thumbnail_url' => $this->clean($body['thumbnail_url'] ?? null),
            'duration_seconds' => null,
            // The player markup alone is not a description. Saying "fetched"
            // here would let a row be created with an empty title and no
            // warning, which is exactly the silence we are trying to avoid.
            'fetched' => filled($body['title'] ?? null),
            'reason' => filled($body['title'] ?? null)
                ? null
                : 'Meta n\'a pas renvoyé de titre pour cette vidéo',
        ];
    }

    private function metaToken(): ?string
    {
        $token = config('goodtriplove.social.meta.access_token');

        return filled($token) ? (string) $token : null;
    }

    private function empty(string $reason): array
    {
        return [
            'title' => null,
            'author_name' => null,
            'author_url' => null,
            'thumbnail_url' => null,
            'duration_seconds' => null,
            'fetched' => false,
            'reason' => $reason,
        ];
    }

    private function clean(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    /**
     * TikTok's oEmbed answers a bare request, but several of these endpoints
     * behave differently for a client that does not identify itself at all.
     */
    private function userAgent(): string
    {
        return 'GoodTripLove/1.0 (+https://goodtriplove.com)';
    }
}
