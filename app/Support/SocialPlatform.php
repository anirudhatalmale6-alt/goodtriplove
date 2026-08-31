<?php

namespace App\Support;

/**
 * Everything that differs between YouTube, TikTok, Instagram and Facebook.
 *
 * The video table has carried a `provider` column since day one, but the rest
 * of the application only ever knew about YouTube: the embed URL, the watch
 * link, the thumbnail fallback and the badge in the player were all written
 * against youtube.com. Adding three platforms by copying those four decisions
 * three times is how a codebase acquires a bug that only shows on Instagram.
 *
 * So the platform knowledge lives here, once, and the model, the player and the
 * admin all ask this class.
 *
 * Two things are worth knowing before reading further.
 *
 * **Every one of the four embeds in a plain iframe.** TikTok, Instagram and
 * Facebook all document a JavaScript SDK (a blockquote plus an external
 * script), and that is what most integrations use. They also all expose a
 * direct iframe endpoint, which is what we use instead: the SDK would load
 * third-party JavaScript into the page on arrival, and the whole point of the
 * facade player is that nothing reaches a platform until the visitor asks. All
 * four endpoints were checked before this was written.
 *
 * **Metadata is a different question from embedding.** Embedding needs nothing
 * but the URL. Reading the title, author and thumbnail needs an endpoint that
 * will answer us, and only YouTube and TikTok do that without an approved
 * application. See {@see \App\Services\Social\SocialMetadataFetcher}.
 */
class SocialPlatform
{
    public const YOUTUBE = 'youtube';
    public const TIKTOK = 'tiktok';
    public const INSTAGRAM = 'instagram';
    public const FACEBOOK = 'facebook';

    /** Display order — YouTube first because it is the one already collecting. */
    public const ALL = [self::YOUTUBE, self::TIKTOK, self::INSTAGRAM, self::FACEBOOK];

    /**
     * `metadata` is what the platform will tell us about a video from its URL
     * alone, with no application approved:
     *   full    — title, author and thumbnail
     *   partial — enough to embed, not enough to describe
     *   none    — nothing at all without a token
     */
    public const DEFINITIONS = [
        self::YOUTUBE => [
            'label' => 'YouTube',
            'colour' => '#ff0000',
            'metadata' => 'full',
            'auto_import' => true,
            'credential_hint' => 'Clé API YouTube Data v3, déjà configurée dans Clés & sécurité.',
        ],
        self::TIKTOK => [
            'label' => 'TikTok',
            'colour' => '#25f4ee',
            'metadata' => 'full',
            'auto_import' => false,
            'credential_hint' => "L'import automatique d'un compte entier demande une application TikTok validée par TikTok. La récupération par URL fonctionne sans clé.",
        ],
        self::INSTAGRAM => [
            'label' => 'Instagram',
            'colour' => '#e1306c',
            'metadata' => 'none',
            'auto_import' => false,
            'credential_hint' => 'Instagram ne renvoie ni titre ni miniature sans une application Meta approuvée. Le lecteur officiel fonctionne malgré tout.',
        ],
        self::FACEBOOK => [
            'label' => 'Facebook',
            'colour' => '#1877f2',
            'metadata' => 'partial',
            'auto_import' => false,
            'credential_hint' => 'Le lecteur officiel fonctionne sans clé. Les informations détaillées demandent une application Meta approuvée.',
        ],
    ];

    public static function exists(?string $provider): bool
    {
        return $provider !== null && isset(self::DEFINITIONS[$provider]);
    }

    public static function label(?string $provider): string
    {
        return self::DEFINITIONS[$provider]['label'] ?? ucfirst((string) $provider);
    }

    public static function colour(?string $provider): string
    {
        return self::DEFINITIONS[$provider]['colour'] ?? '#8b95a5';
    }

    public static function metadataLevel(?string $provider): string
    {
        return self::DEFINITIONS[$provider]['metadata'] ?? 'none';
    }

    /** True when the platform hands us a usable title without an approved app. */
    public static function describesItself(?string $provider): bool
    {
        return self::metadataLevel($provider) === 'full';
    }

    /* ---------------------------------------------------------------------
     | Reading a URL
     * ------------------------------------------------------------------- */

    /**
     * Works out which platform a pasted URL belongs to and what identifies the
     * video on it.
     *
     * Returns null for anything unrecognised — a profile page, a search result,
     * a shortened link we have not resolved yet. Refusing is deliberate: a row
     * created from a URL we could not read would render a broken player.
     *
     * @return array{provider: string, id: string, url: string}|null
     */
    public static function parse(?string $url): ?array
    {
        $url = trim((string) $url);

        if ($url === '') {
            return null;
        }

        // A bare id is not accepted: it would be ambiguous between platforms.
        if (! preg_match('~^https?://~i', $url)) {
            $url = 'https://'.ltrim($url, '/');
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $host = preg_replace('/^www\./', '', $host);
        $path = (string) parse_url($url, PHP_URL_PATH);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        return match (true) {
            in_array($host, ['youtube.com', 'm.youtube.com', 'youtube-nocookie.com'], true)
                => self::parseYouTube($path, $query),
            $host === 'youtu.be'
                => self::youTubeResult(ltrim($path, '/')),
            in_array($host, ['tiktok.com', 'm.tiktok.com'], true)
                => self::parseTikTok($path),
            $host === 'instagram.com' || $host === 'instagr.am'
                => self::parseInstagram($path),
            in_array($host, ['facebook.com', 'm.facebook.com', 'web.facebook.com'], true)
                => self::parseFacebook($url, $path, $query),
            default => null,
        };
    }

    /** Short links carry no id at all; the platform has to be asked. */
    public static function isShortLink(?string $url): bool
    {
        $host = strtolower((string) parse_url((string) $url, PHP_URL_HOST));
        $host = preg_replace('/^www\./', '', $host);

        return in_array($host, ['vm.tiktok.com', 'vt.tiktok.com', 'fb.watch', 'youtu.be'], true)
            || ($host === 'tiktok.com' && str_starts_with((string) parse_url((string) $url, PHP_URL_PATH), '/t/'));
    }

    private static function parseYouTube(string $path, array $query): ?array
    {
        if (isset($query['v'])) {
            return self::youTubeResult((string) $query['v']);
        }

        if (preg_match('~^/(?:shorts|embed|live|v)/([^/?#]+)~', $path, $m)) {
            return self::youTubeResult($m[1]);
        }

        return null;
    }

    private static function youTubeResult(string $id): ?array
    {
        $id = preg_replace('/[^A-Za-z0-9_-]/', '', $id);

        // YouTube ids are exactly eleven characters. Anything else is a channel
        // handle or a playlist, and would produce a player showing nothing.
        if (strlen((string) $id) !== 11) {
            return null;
        }

        return [
            'provider' => self::YOUTUBE,
            'id' => $id,
            'url' => 'https://www.youtube.com/watch?v='.$id,
        ];
    }

    private static function parseTikTok(string $path): ?array
    {
        if (preg_match('~^/@([^/]+)/video/(\d+)~', $path, $m)) {
            return [
                'provider' => self::TIKTOK,
                'id' => $m[2],
                'url' => 'https://www.tiktok.com/@'.$m[1].'/video/'.$m[2],
            ];
        }

        // The account is not always in the path when the link was copied from
        // the web player; the numeric id alone is enough to embed.
        if (preg_match('~^/video/(\d+)~', $path, $m)) {
            return [
                'provider' => self::TIKTOK,
                'id' => $m[1],
                'url' => 'https://www.tiktok.com/video/'.$m[1],
            ];
        }

        return null;
    }

    private static function parseInstagram(string $path): ?array
    {
        if (preg_match('~^/(?:reels?|p|tv)/([A-Za-z0-9_-]+)~', $path, $m)) {
            return [
                'provider' => self::INSTAGRAM,
                'id' => $m[1],
                // Reels and posts share one canonical shape for embedding.
                'url' => 'https://www.instagram.com/p/'.$m[1].'/',
            ];
        }

        return null;
    }

    private static function parseFacebook(string $url, string $path, array $query): ?array
    {
        // Facebook's embed takes the whole URL rather than an id, so the
        // canonical form keeps whatever identifies the post.
        if (preg_match('~^/(?:[^/]+)/videos/(?:[^/]+/)?(\d+)~', $path, $m)) {
            return [
                'provider' => self::FACEBOOK,
                'id' => $m[1],
                'url' => self::stripQuery($url),
            ];
        }

        if (preg_match('~^/reel/(\d+)~', $path, $m)) {
            return [
                'provider' => self::FACEBOOK,
                'id' => $m[1],
                'url' => 'https://www.facebook.com/reel/'.$m[1],
            ];
        }

        if (str_starts_with($path, '/watch') && isset($query['v']) && ctype_digit((string) $query['v'])) {
            return [
                'provider' => self::FACEBOOK,
                'id' => (string) $query['v'],
                'url' => 'https://www.facebook.com/watch/?v='.$query['v'],
            ];
        }

        return null;
    }

    private static function stripQuery(string $url): string
    {
        $parts = parse_url($url);

        return sprintf(
            'https://www.facebook.com%s',
            rtrim($parts['path'] ?? '', '/'),
        );
    }

    /* ---------------------------------------------------------------------
     | Rendering
     * ------------------------------------------------------------------- */

    /**
     * The iframe source used once the visitor has clicked and consented.
     *
     * Never called at render time — the facade only builds this on click, which
     * is what keeps a page of forty videos free of third-party requests.
     */
    public static function embedUrl(string $provider, string $id, ?string $originalUrl = null): ?string
    {
        return match ($provider) {
            self::YOUTUBE => sprintf(
                '%s/embed/%s?%s',
                rtrim((string) config('goodtriplove.player.privacy_domain'), '/'),
                $id,
                http_build_query(['autoplay' => 1, 'rel' => 0, 'modestbranding' => 1, 'playsinline' => 1]),
            ),
            self::TIKTOK => 'https://www.tiktok.com/embed/v2/'.$id,
            self::INSTAGRAM => 'https://www.instagram.com/p/'.$id.'/embed/',
            self::FACEBOOK => 'https://www.facebook.com/plugins/video.php?'.http_build_query([
                'href' => $originalUrl ?: self::watchUrl($provider, $id, null),
                'show_text' => 'false',
                'autoplay' => 'true',
            ]),
            default => null,
        };
    }

    /** Where "watch on the original platform" points. */
    public static function watchUrl(string $provider, string $id, ?string $originalUrl = null): string
    {
        if ($originalUrl) {
            return $originalUrl;
        }

        return match ($provider) {
            self::YOUTUBE => 'https://www.youtube.com/watch?v='.$id,
            self::TIKTOK => 'https://www.tiktok.com/video/'.$id,
            self::INSTAGRAM => 'https://www.instagram.com/p/'.$id.'/',
            self::FACEBOOK => 'https://www.facebook.com/watch/?v='.$id,
            default => '#',
        };
    }

    /**
     * A thumbnail we can build without calling anyone.
     *
     * Only YouTube publishes one at a predictable address. For the others a
     * stored thumbnail is the only real one, and when it is missing the player
     * falls back to a generated placeholder rather than a broken image.
     */
    public static function thumbnailUrl(string $provider, string $id): ?string
    {
        if ($provider !== self::YOUTUBE) {
            return null;
        }

        return sprintf(
            '%s/vi/%s/hqdefault.jpg',
            rtrim((string) config('goodtriplove.player.thumbnail_domain'), '/'),
            $id,
        );
    }

    /**
     * The aspect ratio the platform's own player uses.
     *
     * TikTok and Instagram Reels are vertical; forcing them into the 16/9 frame
     * built for YouTube leaves a video letterboxed into a strip in the middle.
     */
    public static function aspectRatio(string $provider): string
    {
        return match ($provider) {
            self::TIKTOK, self::INSTAGRAM => '9 / 16',
            default => (string) config('goodtriplove.player.aspect_ratio', '16 / 9'),
        };
    }
}
