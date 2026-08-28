<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A human correction must outrank every automatic classification.
 *
 * Before this was enforced, editing a video in the admin left the row reading
 * `classified_by = heuristic`, so the hourly gtl:classify picked it back up and
 * reinstated exactly the mistake the administrator had just fixed — and the
 * collector did the same on its next refresh pass.
 */
class AdminCorrectionIsRespectedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\GoodTripLoveSeeder::class);
    }

    public function test_classify_skips_videos_an_administrator_corrected(): void
    {
        $beaches = Category::where('slug', 'beaches')->firstOrFail();
        $restaurants = Category::where('slug', 'restaurants')->firstOrFail();

        // A title the text rules confidently read as "restaurants", which the
        // administrator has deliberately overridden to "beaches".
        $video = Video::create([
            'provider' => 'youtube',
            'provider_video_id' => 'admin-corrected-1',
            'title' => 'Top 10 Lisbon Restaurants: Authentic Portuguese Food Guide',
            'description' => '',
            'status' => Video::STATUS_APPROVED,
            'is_available' => true,
            'category_id' => $beaches->id,
            'classified_by' => 'admin',
            'classification_confidence' => 1.0,
        ]);

        $this->artisan('gtl:classify', ['--rescan' => true, '--no-model' => true, '--limit' => 500])
            ->assertSuccessful();

        $this->assertSame(
            $beaches->id,
            $video->fresh()->category_id,
            'The administrator chose Beaches; re-classification must not put it back to '.$restaurants->slug
        );
        $this->assertSame('admin', $video->fresh()->classified_by);
    }

    public function test_classify_still_processes_machine_classified_videos(): void
    {
        $video = Video::create([
            'provider' => 'youtube',
            'provider_video_id' => 'machine-1',
            'title' => 'Top 10 Lisbon Restaurants: Authentic Portuguese Food Guide',
            'description' => '',
            'status' => Video::STATUS_PENDING,
            'is_available' => true,
            'category_id' => null,
            'classified_by' => 'heuristic',
            'classification_confidence' => 0.35,
        ]);

        $this->artisan('gtl:classify', ['--rescan' => true, '--no-model' => true, '--limit' => 500])
            ->assertSuccessful();

        $this->assertNotNull(
            $video->fresh()->category_id,
            'A machine-classified video should still be re-examined.'
        );
    }
}
