<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Video;
use App\Services\DuplicateFinder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The database already refuses two rows for the same provider video id, so the
 * duplicates that actually reach the catalogue are reposts under a *new* id.
 * Production had fifteen copies of one Faro restaurant clip that way.
 */
class DuplicateVideosTest extends TestCase
{
    use RefreshDatabase;

    private int $seq = 0;

    private function video(string $title, array $attributes = []): Video
    {
        return Video::create(array_merge([
            'provider' => 'youtube',
            'provider_video_id' => 'vid'.(++$this->seq),
            'title' => $title,
            'status' => Video::STATUS_PENDING,
        ], $attributes));
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

    public function test_titles_differing_only_by_punctuation_and_hashtags_are_one_group(): void
    {
        // These two are the real pattern from production.
        $this->video('Melhor Restaurante em Faro, Cidade de Faro, Algarve.  #restaurante');
        $this->video('Melhor Restaurante em Faro Cidade de Faro Algarve #restaurantes #faro');

        $groups = app(DuplicateFinder::class)->groups();

        $this->assertCount(1, $groups);
        $this->assertSame(2, $groups->first()['videos']->count());
    }

    public function test_two_genuinely_different_videos_are_not_grouped(): void
    {
        $this->video('Melhor Restaurante em Faro, Algarve');
        $this->video('Les plus belles plages de Madère en 2026');

        $this->assertCount(0, app(DuplicateFinder::class)->groups());
    }

    public function test_a_rejected_copy_is_not_offered_again(): void
    {
        $this->video('Bed and Breakfast Venice Friends Hotel Review');
        $this->video('Bed and Breakfast Venice Friends Hotel Review', ['status' => Video::STATUS_REJECTED]);

        $this->assertCount(0, app(DuplicateFinder::class)->groups(), 'a decision already taken must not be asked again');
    }

    public function test_the_published_copy_is_the_one_suggested_for_keeping(): void
    {
        $pending = $this->video('Roteiro completo por Lisboa em quatro dias', ['view_count' => 900000]);
        $approved = $this->video('Roteiro completo por Lisboa em quatro dias', ['status' => Video::STATUS_APPROVED, 'view_count' => 10]);

        $finder = app(DuplicateFinder::class);
        $keeper = $finder->suggestedKeeper($finder->groups()->first()['videos']);

        $this->assertSame($approved->id, $keeper->id, 'the already published copy is the one links point at');
        $this->assertNotSame($pending->id, $keeper->id);
    }

    public function test_resolving_keeps_one_and_rejects_the_rest_without_deleting(): void
    {
        $keep = $this->video('Os melhores hoteis de charme no Porto');
        $a = $this->video('Os melhores hoteis de charme no Porto');
        $b = $this->video('Os melhores hoteis de charme no Porto');

        $this->actingAsAdmin()
            ->post(route('admin.videos.duplicates.resolve'), [
                'keep' => $keep->id,
                'ids' => [$keep->id, $a->id, $b->id],
            ])
            ->assertRedirect();

        $this->assertSame(Video::STATUS_PENDING, $keep->fresh()->status);
        $this->assertSame(Video::STATUS_REJECTED, $a->fresh()->status);
        $this->assertSame(Video::STATUS_REJECTED, $b->fresh()->status);

        // Nothing is destroyed: a wrong call here has to be reversible.
        $this->assertNotNull(Video::find($a->id));
        $this->assertStringContainsString((string) $keep->id, (string) $a->fresh()->rejection_reason);
    }

    public function test_the_kept_video_must_belong_to_the_group(): void
    {
        $a = $this->video('Guia de viagem por Sevilha em tres dias');
        $b = $this->video('Guia de viagem por Sevilha em tres dias');
        $unrelated = $this->video('Une autre video entierement differente');

        // Otherwise a hand-made request could reject a whole group and leave
        // nothing behind.
        $this->actingAsAdmin()
            ->post(route('admin.videos.duplicates.resolve'), [
                'keep' => $unrelated->id,
                'ids' => [$a->id, $b->id],
            ])
            ->assertStatus(422);

        $this->assertSame(Video::STATUS_PENDING, $a->fresh()->status);
        $this->assertSame(Video::STATUS_PENDING, $b->fresh()->status);
    }

    public function test_the_screen_renders_and_is_linked_from_the_admin_menu(): void
    {
        $this->video('Melhores praias do Algarve para visitar');
        $this->video('Melhores praias do Algarve para visitar');

        $this->actingAsAdmin()
            ->get(route('admin.videos.duplicates'))
            ->assertOk()
            ->assertSee('Melhores praias do Algarve');

        $this->actingAsAdmin()
            ->get(route('admin.videos.index'))
            ->assertOk()
            ->assertSee(route('admin.videos.duplicates'), false);
    }
}
