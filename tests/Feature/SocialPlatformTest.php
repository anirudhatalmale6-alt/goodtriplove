<?php

namespace Tests\Feature;

use App\Models\Video;
use App\Services\Social\SocialImporter;
use App\Services\Social\SocialImportResult;
use App\Services\Social\SocialMetadataFetcher;
use App\Support\SocialPlatform;
use App\Support\SystemSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The four platforms, from a pasted URL to a playing frame.
 *
 * Every network call is faked. What these tests pin is our behaviour when a
 * platform answers, and — more importantly — when it refuses, because refusing
 * is the normal case for Instagram and will stay so until Meta approves an
 * application.
 */
class SocialPlatformTest extends TestCase
{
    use RefreshDatabase;

    private int $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();

        // No test may reach the real platforms. A forgotten fake would make the
        // suite depend on TikTok being up.
        Http::preventStrayRequests();
    }

    private function video(array $attributes = []): Video
    {
        $video = Video::create(array_merge([
            'provider' => 'youtube',
            'provider_video_id' => 'vid'.(++$this->seq),
            'title' => 'Une vidéo de test',
            'status' => Video::STATUS_PENDING,
        ], $attributes));

        // create() leaves the columns it did not set unloaded, so `embeddable`
        // reads as null rather than as the database default of true. Reading
        // the row back is what makes these assertions about a real record.
        return $video->refresh();
    }

    /* ---------------------------------------------------------------------
     | Reading a URL
     * ------------------------------------------------------------------- */

    public function test_it_recognises_a_video_url_on_each_platform(): void
    {
        $cases = [
            'https://www.youtube.com/watch?v=dQw4w9WgXcQ' => ['youtube', 'dQw4w9WgXcQ'],
            'https://youtu.be/dQw4w9WgXcQ' => ['youtube', 'dQw4w9WgXcQ'],
            'https://www.youtube.com/shorts/dQw4w9WgXcQ' => ['youtube', 'dQw4w9WgXcQ'],
            'https://www.tiktok.com/@scout2015/video/6718335390845095173' => ['tiktok', '6718335390845095173'],
            'https://www.instagram.com/reel/CqXpEHRs3lS/' => ['instagram', 'CqXpEHRs3lS'],
            'https://www.instagram.com/p/CqXpEHRs3lS/' => ['instagram', 'CqXpEHRs3lS'],
            'https://www.facebook.com/facebook/videos/10153231379946729/' => ['facebook', '10153231379946729'],
            'https://www.facebook.com/watch/?v=10153231379946729' => ['facebook', '10153231379946729'],
            'https://www.facebook.com/reel/1234567890' => ['facebook', '1234567890'],
        ];

        foreach ($cases as $url => [$provider, $id]) {
            $parsed = SocialPlatform::parse($url);

            $this->assertNotNull($parsed, "failed to parse {$url}");
            $this->assertSame($provider, $parsed['provider'], "wrong platform for {$url}");
            $this->assertSame($id, $parsed['id'], "wrong id for {$url}");
        }
    }

    public function test_it_refuses_a_url_that_is_not_a_video(): void
    {
        $refused = [
            'https://www.youtube.com/@SomeChannel',
            'https://www.youtube.com/playlist?list=PL1234567890',
            'https://www.instagram.com/somebody/',
            'https://www.tiktok.com/@somebody',
            'https://www.facebook.com/somepage',
            'https://vimeo.com/123456789',
            'not a url at all',
            '',
        ];

        foreach ($refused as $url) {
            $this->assertNull(SocialPlatform::parse($url), "should not have parsed {$url}");
        }
    }

    /**
     * A control: an eleven-character id is what makes a YouTube link valid, so
     * a ten-character one must fail. Without this the parser would happily
     * accept a channel handle and build a player showing nothing.
     */
    public function test_a_youtube_id_of_the_wrong_length_is_refused(): void
    {
        $this->assertNull(SocialPlatform::parse('https://www.youtube.com/watch?v=tooshort12'));
        $this->assertNotNull(SocialPlatform::parse('https://www.youtube.com/watch?v=tooshort123'));
    }

    /* ---------------------------------------------------------------------
     | Embedding
     * ------------------------------------------------------------------- */

    public function test_every_platform_produces_an_embed_address(): void
    {
        foreach (SocialPlatform::ALL as $provider) {
            $video = $this->video([
                'provider' => $provider,
                'provider_video_id' => '1234567890',
                'original_url' => 'https://example.com/video/1234567890',
            ]);

            $this->assertNotNull($video->embedUrl(), "{$provider} produced no embed URL");
            $this->assertTrue($video->isPlayable(), "{$provider} is not playable");
        }
    }

    public function test_the_facebook_embed_carries_the_original_address(): void
    {
        $video = $this->video([
            'provider' => 'facebook',
            'provider_video_id' => '10153231379946729',
            'original_url' => 'https://www.facebook.com/facebook/videos/10153231379946729',
        ]);

        // Facebook's player takes the URL, not an id — an embed built from the
        // id alone would show nothing.
        $this->assertStringContainsString(
            urlencode('https://www.facebook.com/facebook/videos/10153231379946729'),
            $video->embedUrl(),
        );
    }

    /**
     * The player markup must carry the platform's own embed address.
     *
     * Before this, the address was built in JavaScript against
     * youtube-nocookie.com, so a TikTok row rendered a YouTube frame and
     * played nothing. Asserting on the rendered component is what catches a
     * regression there — the service being right is not enough.
     */
    public function test_the_rendered_player_carries_the_right_platform(): void
    {
        $expected = [
            'youtube' => 'youtube-nocookie.com/embed/',
            'tiktok' => 'tiktok.com/embed/v2/',
            'instagram' => 'instagram.com/p/',
            'facebook' => 'facebook.com/plugins/video.php',
        ];

        // Every public route is under /{locale}; middleware supplies it on a
        // real request, and rendering the component on its own does not.
        \Illuminate\Support\Facades\URL::defaults(['locale' => 'fr']);

        foreach ($expected as $provider => $fragment) {
            $video = $this->video([
                'provider' => $provider,
                'provider_video_id' => 'abcdefghijk',
                'original_url' => 'https://example.com/v/abcdefghijk',
            ]);

            $html = $this->blade('<x-player :video="$video" />', ['video' => $video]);

            $html->assertSee('data-provider="'.$provider.'"', false);
            $html->assertSee($fragment, false);
            $html->assertSee('data-aspect="'.$video->aspectRatio().'"', false);
        }
    }

    public function test_an_unknown_platform_is_not_playable(): void
    {
        $video = $this->video(['provider' => 'vimeo', 'provider_video_id' => '123']);

        $this->assertFalse($video->isPlayable());
        $this->assertNull($video->embedUrl());
    }

    public function test_vertical_platforms_declare_a_vertical_frame(): void
    {
        $this->assertSame('9 / 16', SocialPlatform::aspectRatio('tiktok'));
        $this->assertSame('9 / 16', SocialPlatform::aspectRatio('instagram'));
        $this->assertSame('16 / 9', SocialPlatform::aspectRatio('youtube'));
    }

    public function test_a_platform_without_a_thumbnail_falls_back_to_a_local_placeholder(): void
    {
        $video = $this->video([
            'provider' => 'instagram',
            'provider_video_id' => 'CqXpEHRs3lS',
            'thumbnail_url' => null,
            'thumbnail_hq_url' => null,
        ]);

        $this->assertStringContainsString('img/platform/instagram.svg', $video->thumbnail());
        $this->assertFalse($video->hasRealThumbnail());

        // YouTube's is guessable, so it is never a placeholder.
        $youtube = $this->video([
            'provider' => 'youtube',
            'provider_video_id' => 'dQw4w9WgXcQ',
            'thumbnail_url' => null,
            'thumbnail_hq_url' => null,
        ]);

        $this->assertTrue($youtube->hasRealThumbnail());
        $this->assertStringContainsString('dQw4w9WgXcQ', $youtube->thumbnail());
    }

    /* ---------------------------------------------------------------------
     | Importing
     * ------------------------------------------------------------------- */

    public function test_it_imports_a_tiktok_video_from_its_url(): void
    {
        Http::fake(['www.tiktok.com/oembed*' => Http::response([
            'title' => 'Meilleur restaurant de Lisbonne au coucher du soleil',
            'author_name' => 'visitlisbonfood',
            'author_url' => 'https://www.tiktok.com/@visitlisbonfood',
            'thumbnail_url' => 'https://p16.tiktokcdn.com/thumb.jpg',
        ])]);

        $result = app(SocialImporter::class)->import('https://www.tiktok.com/@visitlisbonfood/video/6718335390845095173');

        $this->assertTrue($result->successful(), $result->message ?? '');
        $this->assertSame('tiktok', $result->video->provider);
        $this->assertSame('Meilleur restaurant de Lisbonne au coucher du soleil', $result->video->title);
        $this->assertSame('visitlisbonfood', $result->video->channel_title);
        $this->assertSame('https://p16.tiktokcdn.com/thumb.jpg', $result->video->thumbnail_url);
        $this->assertSame(
            'https://www.tiktok.com/@visitlisbonfood/video/6718335390845095173',
            $result->video->original_url,
        );
    }

    /**
     * The case Paulo will actually hit. Instagram answers everyone with a
     * permission error, so the import must ask for a title rather than
     * inventing one or saving an empty row.
     */
    public function test_instagram_without_an_approved_app_asks_for_a_title(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response([
            'error' => ['message' => 'Media Not Found', 'code' => 24],
        ], 400)]);

        $importer = app(SocialImporter::class);

        $refused = $importer->import('https://www.instagram.com/reel/CqXpEHRs3lS/');

        $this->assertSame(SocialImportResult::NEEDS_TITLE, $refused->outcome);
        $this->assertSame(0, Video::count(), 'nothing may be saved when the title is unknown');

        // With a title typed by the administrator it goes in, and still plays.
        $accepted = $importer->import(
            'https://www.instagram.com/reel/CqXpEHRs3lS/',
            ['title' => 'Plage cachée près de Porto'],
        );

        $this->assertTrue($accepted->successful());
        $this->assertSame('Plage cachée près de Porto', $accepted->video->title);
        $this->assertTrue($accepted->video->isPlayable());
    }

    public function test_the_same_video_cannot_be_imported_twice(): void
    {
        Http::fake(['www.tiktok.com/oembed*' => Http::response([
            'title' => 'Boutique hotel in Paris with a rooftop',
            'author_name' => 'stayparis',
        ])]);

        $importer = app(SocialImporter::class);
        $url = 'https://www.tiktok.com/@stayparis/video/7000000000000000001';

        $this->assertTrue($importer->import($url)->successful());

        $second = $importer->import($url);

        $this->assertSame(SocialImportResult::DUPLICATE, $second->outcome);
        $this->assertSame(1, Video::count());
    }

    /**
     * The check the unique index cannot do: the same clip reposted on another
     * platform under a different id.
     */
    public function test_a_repost_on_another_platform_is_caught_as_a_duplicate(): void
    {
        Http::fake([
            'www.tiktok.com/oembed*' => Http::response(['title' => 'Meilleur restaurant de Faro en Algarve']),
            'www.youtube.com/oembed*' => Http::response(['title' => 'Meilleur restaurant de Faro en Algarve !!! #faro']),
        ]);

        $importer = app(SocialImporter::class);

        $this->assertTrue($importer->import('https://www.tiktok.com/@x/video/7000000000000000002')->successful());

        $second = $importer->import('https://www.youtube.com/watch?v=dQw4w9WgXcQ');

        $this->assertSame(SocialImportResult::DUPLICATE, $second->outcome);
        $this->assertSame(1, Video::count());
    }

    /** Control: with the check off, the same repost is allowed through. */
    public function test_the_duplicate_check_can_be_switched_off(): void
    {
        Http::fake([
            'www.tiktok.com/oembed*' => Http::response(['title' => 'Meilleur restaurant de Faro en Algarve']),
            'www.youtube.com/oembed*' => Http::response(['title' => 'Meilleur restaurant de Faro en Algarve !!! #faro']),
        ]);

        SystemSettings::put('social_duplicate_check', false);

        $importer = app(SocialImporter::class);
        $importer->import('https://www.tiktok.com/@x/video/7000000000000000002');
        $second = $importer->import('https://www.youtube.com/watch?v=dQw4w9WgXcQ');

        $this->assertTrue($second->successful());
        $this->assertSame(2, Video::count());
    }

    public function test_a_disabled_platform_refuses_new_videos(): void
    {
        SystemSettings::put('social_tiktok_enabled', false);

        $result = app(SocialImporter::class)->import('https://www.tiktok.com/@x/video/7000000000000000003');

        $this->assertSame(SocialImportResult::UNSUPPORTED, $result->outcome);
        $this->assertSame(0, Video::count());
    }

    public function test_an_imported_video_waits_for_approval_by_default(): void
    {
        Http::fake(['www.youtube.com/oembed*' => Http::response(['title' => 'Un titre suffisamment long'])]);

        $result = app(SocialImporter::class)->import('https://www.youtube.com/watch?v=dQw4w9WgXcQ');

        $this->assertSame(Video::STATUS_PENDING, $result->video->status);

        SystemSettings::put('social_require_approval', false);
        Http::fake(['www.tiktok.com/oembed*' => Http::response(['title' => 'Un autre titre bien distinct'])]);

        $direct = app(SocialImporter::class)->import('https://www.tiktok.com/@x/video/7000000000000000004');

        $this->assertSame(Video::STATUS_APPROVED, $direct->video->status);
    }

    /**
     * A platform being down must not look like a bug in the site: the video is
     * still embeddable, we simply have nothing to describe it with.
     */
    public function test_a_platform_that_does_not_answer_still_allows_a_manual_import(): void
    {
        Http::fake(['www.tiktok.com/oembed*' => Http::response('', 503)]);

        $result = app(SocialImporter::class)->import(
            'https://www.tiktok.com/@x/video/7000000000000000005',
            ['title' => 'Titre saisi à la main par le modérateur'],
        );

        $this->assertTrue($result->successful());
        $this->assertNotNull($result->message, 'the administrator must be told the metadata is missing');
    }

    /**
     * The cookie notice is a disclosure, not decoration.
     *
     * It named YouTube as the only third-party player. Embedding three more
     * platforms without changing it would have made the site tell visitors
     * something untrue about who receives their data.
     */
    public function test_the_consent_notice_names_every_embedded_platform(): void
    {
        foreach (['fr', 'en', 'pt', 'es', 'it', 'de'] as $locale) {
            $notice = __('gtl.cookie_video', [], $locale);

            foreach (['YouTube', 'TikTok', 'Instagram', 'Facebook'] as $platform) {
                $this->assertStringContainsString(
                    $platform,
                    $notice,
                    "the {$locale} cookie notice does not mention {$platform}",
                );
            }
        }
    }

    /** Every locale must carry the label used by the player. */
    public function test_the_watch_link_is_translated_everywhere(): void
    {
        foreach (['fr', 'en', 'pt', 'es', 'it', 'de'] as $locale) {
            $label = __('gtl.watch_on_platform', ['platform' => 'TikTok'], $locale);

            $this->assertNotSame('gtl.watch_on_platform', $label, "missing in {$locale}");
            $this->assertStringContainsString('TikTok', $label, "placeholder not filled in {$locale}");
        }
    }

    public function test_metadata_is_never_invented_when_the_platform_refuses(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'nope']], 400)]);

        $metadata = app(SocialMetadataFetcher::class)->fetch('instagram', 'https://www.instagram.com/p/CqXpEHRs3lS/');

        $this->assertFalse($metadata['fetched']);
        $this->assertNull($metadata['title']);
        $this->assertNotNull($metadata['reason']);
    }
}
