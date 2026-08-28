<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnouncementPlacementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\GoodTripLoveSeeder::class);
        // The seeder ships a ticker line; these tests are about what the admin
        // adds on top of it.
        Announcement::query()->delete();
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

    /**
     * An unticked checkbox posts nothing, so reading is_active out of the
     * validated data left it untouched: an announcement could be switched on
     * and never off, while the form still reported success.
     */
    public function test_an_announcement_can_be_switched_back_off(): void
    {
        $announcement = Announcement::create([
            'text' => ['fr' => 'Promotion de printemps'],
            'placement' => Announcement::PLACEMENT_TICKER,
            'is_active' => true,
        ]);

        $this->get('/fr')->assertSee('Promotion de printemps', false);

        // The browser sends no is_active key at all when the box is unticked.
        $this->actingAsAdmin()
            ->put(route('admin.announcements.update', $announcement), [
                'text' => ['fr' => 'Promotion de printemps'],
                'placement' => Announcement::PLACEMENT_TICKER,
            ])
            ->assertRedirect();

        $this->assertFalse($announcement->fresh()->is_active);
        $this->get('/fr')->assertDontSee('Promotion de printemps', false);
    }

    public function test_a_footer_announcement_does_not_appear_in_the_top_bar(): void
    {
        Announcement::create([
            'text' => ['fr' => 'Mention en pied de page'],
            'placement' => Announcement::PLACEMENT_FOOTER,
            'is_active' => true,
        ]);

        $body = $this->get('/fr')->assertOk()->getContent();

        $this->assertStringContainsString('footer-notice', $body);
        $this->assertStringContainsString('Mention en pied de page', $body);
        // It must not have been rendered into the scrolling bar as well.
        $this->assertStringNotContainsString('ticker__item', explode('<footer', $body)[0] ?? '');
    }

    public function test_a_homepage_only_announcement_stays_on_the_homepage(): void
    {
        Announcement::create([
            'text' => ['fr' => 'Visible seulement sur la page daccueil'],
            'placement' => Announcement::PLACEMENT_TICKER,
            'home_only' => true,
            'is_active' => true,
        ]);

        $this->get('/fr')->assertSee('Visible seulement sur la page daccueil', false);
        $this->get('/fr/videos')->assertDontSee('Visible seulement sur la page daccueil', false);
    }

    public function test_the_order_set_by_the_admin_is_the_order_rendered(): void
    {
        Announcement::create(['text' => ['fr' => 'Deuxieme message'], 'placement' => 'ticker', 'sort_order' => 2, 'is_active' => true]);
        Announcement::create(['text' => ['fr' => 'Premier message'], 'placement' => 'ticker', 'sort_order' => 1, 'is_active' => true]);

        $body = $this->get('/fr')->getContent();

        $this->assertLessThan(
            strpos($body, 'Deuxieme message'),
            strpos($body, 'Premier message'),
            'sort_order must decide the order on the page'
        );
    }

    public function test_every_language_can_be_edited_from_the_admin(): void
    {
        $announcement = Announcement::create([
            'text' => ['fr' => 'Texte francais'],
            'placement' => Announcement::PLACEMENT_TICKER,
            'is_active' => true,
        ]);

        $this->actingAsAdmin()
            ->put(route('admin.announcements.update', $announcement), [
                'text' => ['fr' => 'Texte francais', 'pt' => 'Texto portugues', 'en' => 'English text'],
                'placement' => Announcement::PLACEMENT_TICKER,
                'is_active' => '1',
            ])
            ->assertRedirect();

        $this->get('/pt')->assertSee('Texto portugues', false);
        $this->get('/en')->assertSee('English text', false);
    }
}
