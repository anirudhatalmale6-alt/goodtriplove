<?php

namespace Tests\Feature;

use App\Models\SeoOverride;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * An SEO override has to reach the HTML.
 *
 * The overrides table and SeoService shipped in an earlier module, but nothing
 * ever called the service — so anything saved was inert while the admin screen
 * would have looked like it worked. These tests assert on the rendered page, not
 * on the database row.
 */
class SeoOverrideTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\GoodTripLoveSeeder::class);
    }

    public function test_the_site_default_is_used_when_no_override_exists(): void
    {
        $this->get('/fr')
            ->assertStatus(200)
            ->assertSee('<title>', false)
            ->assertDontSee('noindex,nofollow', false);
    }

    public function test_an_override_replaces_the_title_and_description(): void
    {
        SeoOverride::create([
            'page_type' => 'home',
            'page_key' => '*',
            'locale' => 'fr',
            'title' => 'Vidéos de voyage au Portugal — GoodTripLove',
            'description' => 'Restaurants, hôtels et plages en vidéo.',
            'indexable' => true,
        ]);

        $this->get('/fr')
            ->assertStatus(200)
            ->assertSee('<title>Vidéos de voyage au Portugal — GoodTripLove</title>', false)
            ->assertSee('Restaurants, hôtels et plages en vidéo.', false);
    }

    public function test_an_override_can_take_a_page_out_of_the_index(): void
    {
        SeoOverride::create([
            'page_type' => 'home',
            'page_key' => '*',
            'locale' => 'fr',
            'indexable' => false,
        ]);

        $this->get('/fr')->assertSee('noindex,nofollow', false);
    }

    public function test_an_override_for_another_language_does_not_leak(): void
    {
        SeoOverride::create([
            'page_type' => 'home',
            'page_key' => '*',
            'locale' => 'en',
            'title' => 'English only title',
            'indexable' => true,
        ]);

        $this->get('/fr')->assertDontSee('English only title', false);
        $this->get('/en')->assertSee('English only title', false);
    }
}
