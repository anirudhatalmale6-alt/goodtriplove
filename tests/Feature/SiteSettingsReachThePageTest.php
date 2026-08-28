<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * The settings screen is only worth having if what it saves reaches the site.
 *
 * These assert on the rendered HTML, never on the saved row: a row proves the
 * form worked, not that any page reads it. Before this was wired, SiteSetting
 * had no caller outside its own admin screen — an administrator could type a
 * contact address, be told "enregistré", and see it nowhere.
 */
class SiteSettingsReachThePageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::forget('site_settings');
        $this->seed(\Database\Seeders\GoodTripLoveSeeder::class);
    }

    public function test_the_contact_address_appears_in_the_footer(): void
    {
        SiteSetting::put('contact_email', 'bonjour@goodtriplove.com', 'contact');

        $this->get('/fr')
            ->assertOk()
            ->assertSee('mailto:bonjour@goodtriplove.com', false);
    }

    public function test_social_links_are_rendered_only_when_filled(): void
    {
        $this->get('/fr')->assertOk()->assertDontSee('facebook.com/goodtriplove', false);

        SiteSetting::put('social_facebook', 'https://facebook.com/goodtriplove', 'social');

        $this->get('/fr')->assertOk()->assertSee('https://facebook.com/goodtriplove', false);
    }

    public function test_the_site_name_drives_the_footer_copyright(): void
    {
        SiteSetting::put('site_name', 'GoodTripLove Portugal');

        $this->get('/fr')->assertOk()->assertSee('GoodTripLove Portugal', false);
    }

    public function test_a_translatable_setting_follows_the_language(): void
    {
        SiteSetting::put('footer_pitch', [
            'fr' => 'Découvrez le Portugal en vidéo.',
            'en' => 'Discover Portugal in video.',
        ], 'identity');

        $this->get('/fr')->assertSee('Découvrez le Portugal en vidéo.', false);
        $this->get('/en')->assertSee('Discover Portugal in video.', false);
    }

    public function test_a_missing_translation_falls_back_to_french_rather_than_printing_nothing(): void
    {
        SiteSetting::put('footer_pitch', ['fr' => 'Texte en français seulement.'], 'identity');

        // Portuguese has no translation stored; the visitor must still read
        // something rather than an empty paragraph.
        $this->get('/pt')->assertSee('Texte en français seulement.', false);
    }

    public function test_the_admin_form_saves_and_the_site_changes(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'is_active' => true,
            'two_factor_enabled' => true,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->withSession(['2fa_passed_at' => now()->timestamp])
            ->put(route('admin.settings.update'), [
                'settings' => [
                    'contact_email' => 'contact@goodtriplove.com',
                    'social_instagram' => 'https://instagram.com/goodtriplove',
                ],
            ])
            ->assertRedirect();

        $this->get('/fr')
            ->assertSee('mailto:contact@goodtriplove.com', false)
            ->assertSee('https://instagram.com/goodtriplove', false);
    }

    public function test_the_form_refuses_a_malformed_address(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'is_active' => true,
            'two_factor_enabled' => true,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->withSession(['2fa_passed_at' => now()->timestamp])
            ->put(route('admin.settings.update'), ['settings' => ['contact_email' => 'not-an-address']])
            ->assertSessionHasErrors('settings.contact_email');
    }
}
