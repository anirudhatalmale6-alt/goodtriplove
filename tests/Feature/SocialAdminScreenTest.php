<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Video;
use App\Support\SocialPlatform;
use App\Support\SystemSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The per-platform admin screens, over HTTP.
 *
 * A service that works and a screen nobody can reach is not a delivered
 * feature, so these go through the router, the middleware and the Blade view
 * rather than calling the importer directly.
 */
class SocialAdminScreenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    private function actingAsAdmin(): self
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'is_active' => true,
            'two_factor_enabled' => true,
            'email_verified_at' => now(),
        ]);

        return $this->actingAs($admin)->withSession(['2fa_passed_at' => now()->timestamp]);
    }

    public function test_each_platform_has_a_screen(): void
    {
        foreach (SocialPlatform::ALL as $platform) {
            $this->actingAsAdmin()
                ->get(route('admin.social.index', ['platform' => $platform]))
                ->assertOk()
                ->assertSee(SocialPlatform::label($platform), false);
        }
    }

    public function test_an_unknown_platform_is_not_a_screen(): void
    {
        $this->actingAsAdmin()
            ->get('/admin/social/vimeo')
            ->assertNotFound();
    }

    /** The whole back office is behind auth; this screen is no exception. */
    public function test_a_visitor_cannot_reach_the_screen(): void
    {
        $this->get(route('admin.social.index', ['platform' => 'tiktok']))
            ->assertRedirect();
    }

    public function test_a_member_cannot_reach_the_screen(): void
    {
        $member = User::factory()->create([
            'role' => User::ROLE_USER,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($member)
            ->get(route('admin.social.index', ['platform' => 'tiktok']))
            ->assertForbidden();
    }

    public function test_an_administrator_adds_a_video_from_the_form(): void
    {
        Http::fake(['www.tiktok.com/oembed*' => Http::response([
            'title' => 'Coucher de soleil sur le Tage à Lisbonne',
            'author_name' => 'visitlisbonfood',
        ])]);

        $this->actingAsAdmin()
            ->post(route('admin.social.store', ['platform' => 'tiktok']), [
                'url' => 'https://www.tiktok.com/@visitlisbonfood/video/7000000000000000010',
            ])
            ->assertRedirect(route('admin.social.index', ['platform' => 'tiktok']))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('videos', [
            'provider' => 'tiktok',
            'provider_video_id' => '7000000000000000010',
            'title' => 'Coucher de soleil sur le Tage à Lisbonne',
        ]);
    }

    /**
     * A URL for another platform pasted into this screen is still imported,
     * under the platform it actually belongs to. Refusing it would be a rule
     * the administrator has no reason to expect.
     */
    public function test_a_url_from_another_platform_lands_on_its_own_platform(): void
    {
        Http::fake(['www.youtube.com/oembed*' => Http::response(['title' => 'Une plage cachée près de Porto'])]);

        $this->actingAsAdmin()
            ->post(route('admin.social.store', ['platform' => 'instagram']), [
                'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            ])
            ->assertRedirect(route('admin.social.index', ['platform' => 'youtube']));

        $this->assertDatabaseHas('videos', ['provider' => 'youtube']);
    }

    public function test_an_unreadable_url_comes_back_with_a_reason(): void
    {
        $this->actingAsAdmin()
            ->post(route('admin.social.store', ['platform' => 'tiktok']), [
                'url' => 'https://www.tiktok.com/@somebody',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(0, Video::count());
    }

    public function test_a_video_can_be_taken_off_the_site_and_put_back(): void
    {
        $video = Video::create([
            'provider' => 'instagram',
            'provider_video_id' => 'CqXpEHRs3lS',
            'title' => 'Hôtel de charme à Paris',
            'status' => Video::STATUS_APPROVED,
        ]);

        $this->actingAsAdmin()
            ->post(route('admin.social.disable', ['video' => $video->id]))
            ->assertRedirect();

        $video->refresh();
        $this->assertFalse((bool) $video->is_available);
        $this->assertSame(Video::STATUS_REJECTED, $video->status);

        // The record survives — that is what stops it being re-imported
        // tomorrow, and what makes the decision reversible.
        $this->assertDatabaseHas('videos', ['id' => $video->id]);

        $this->actingAsAdmin()
            ->post(route('admin.social.enable', ['video' => $video->id]))
            ->assertRedirect();

        $video->refresh();
        $this->assertTrue((bool) $video->is_available);
        $this->assertSame(Video::STATUS_APPROVED, $video->status);
    }

    public function test_the_screen_reports_a_disabled_platform(): void
    {
        SystemSettings::put('social_facebook_enabled', false);

        $this->actingAsAdmin()
            ->get(route('admin.social.index', ['platform' => 'facebook']))
            ->assertOk()
            ->assertSee('est désactivé', false);
    }

    /** Every platform setting must be reachable from the settings screen. */
    public function test_the_platform_settings_are_editable_in_the_admin(): void
    {
        $response = $this->actingAsAdmin()->get(route('admin.system.index'))->assertOk();

        foreach (['social_youtube_enabled', 'social_tiktok_enabled', 'social_instagram_enabled',
            'social_facebook_enabled', 'social_require_approval', 'social_duplicate_check',
            'social_meta_token', 'social_tiktok_token'] as $key) {
            $response->assertSee($key, false);
        }
    }

    /**
     * A control for the trap this project has hit before: a settings key left
     * out of the validate() whitelist is dropped from the validated data and
     * silently never saved.
     */
    public function test_saving_the_meta_token_actually_stores_it(): void
    {
        $this->actingAsAdmin()
            ->put(route('admin.system.update'), [
                'settings' => [
                    'social_meta_token' => 'EAAG-test-token',
                    'social_youtube_enabled' => '1',
                    'social_tiktok_enabled' => '1',
                    'social_instagram_enabled' => '1',
                    'social_facebook_enabled' => '1',
                    'social_require_approval' => '1',
                    'social_duplicate_check' => '1',
                ],
            ])
            ->assertRedirect();

        $this->assertSame('EAAG-test-token', SystemSettings::stored('social_meta_token'));

        // Stored encrypted: the raw row must not contain the token in clear.
        $raw = \App\Models\SiteSetting::where('key', 'social_meta_token')->value('value');
        $this->assertStringNotContainsString('EAAG-test-token', (string) json_encode($raw));
    }

    public function test_switching_a_platform_off_is_saved(): void
    {
        $this->actingAsAdmin()
            ->put(route('admin.system.update'), [
                'settings' => ['social_youtube_enabled' => '1'],
            ])
            ->assertRedirect();

        // Every other switch was absent from the post, which is how an
        // unticked checkbox arrives. They must be off, not left on.
        $this->assertFalse((bool) SystemSettings::effective('social_tiktok_enabled'));
        $this->assertTrue((bool) SystemSettings::effective('social_youtube_enabled'));
    }
}
