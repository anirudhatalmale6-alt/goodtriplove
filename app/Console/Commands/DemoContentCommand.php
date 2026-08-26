<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\City;
use App\Models\Place;
use App\Models\Video;
use App\Services\VideoScorer;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Fills the site with obviously-synthetic placeholder content so the design
 * can be reviewed before the YouTube key exists.
 *
 * This is NOT sample data pretending to be real: every row is flagged
 * source=demo, the thumbnails are locally generated placeholders and no real
 * creator, channel or view count is reproduced. `gtl:demo-content --purge`
 * removes every trace of it.
 */
class DemoContentCommand extends Command
{
    protected $signature = 'gtl:demo-content {--purge : Remove all demo content}';

    protected $description = 'Create or remove placeholder content for design review';

    public function handle(VideoScorer $scorer): int
    {
        if ($this->option('purge')) {
            $videos = Video::where('source', 'demo')->pluck('id');
            Video::whereKey($videos)->delete();
            Place::where('source', 'demo')->delete();

            $this->info('Demo content removed.');

            return self::SUCCESS;
        }

        $titles = [
            'restaurants' => ['Le meilleur restaurant de %s', 'Où bien manger à %s', 'Adresses gourmandes à %s'],
            'local-food' => ['Les 10 plats à goûter à %s', 'Street food à %s', 'Spécialités locales de %s'],
            'hotels' => ['Nuit dans un hôtel de charme à %s', 'Les plus beaux hôtels de %s'],
            'guest-houses' => ['Chambre d’hôtes à %s', 'Séjour chez l’habitant à %s'],
            'bars-cafes' => ['Rooftop avec vue sur %s', 'Les cafés à ne pas manquer à %s'],
            'activities' => ['Que faire à %s en 48 h', 'Balade en bateau à %s'],
            'places-to-visit' => ['Visite du centre historique de %s', 'Les incontournables de %s'],
            'beaches' => ['Les plus belles plages près de %s', 'Journée plage à %s'],
        ];

        $categories = Category::active()->roots()->get()->keyBy('slug');
        $cities = City::active()->with('country')->get();
        $created = 0;

        foreach ($cities as $cityIndex => $city) {
            foreach ($categories as $slug => $category) {
                $pattern = $titles[$slug][$cityIndex % count($titles[$slug])] ?? '%s';
                $title = sprintf($pattern, $city->displayName('fr'));

                // Deterministic pseudo-identifier: 11 chars like a YouTube id,
                // but prefixed so it can never collide with a real one.
                $identifier = 'demo'.substr(md5($city->slug.$slug), 0, 7);

                $video = Video::updateOrCreate(
                    ['provider' => 'demo', 'provider_video_id' => $identifier],
                    [
                        'title' => $title,
                        'description' => 'Contenu de démonstration GoodTripLove — remplacé par de vraies vidéos dès que le collecteur tourne.',
                        'channel_title' => 'Démonstration GoodTripLove',
                        'published_at' => now()->subDays(($cityIndex * 7) + 3),
                        'duration_seconds' => 240 + (($cityIndex * 37 + strlen($slug) * 11) % 500),
                        'thumbnail_url' => '/img/demo/'.$slug.'.svg',
                        'thumbnail_hq_url' => '/img/demo/'.$slug.'.svg',
                        'language' => 'fr',
                        'embeddable' => true,
                        'is_available' => true,
                        'view_count' => 4000 + (crc32($identifier) % 900000),
                        'like_count' => 100 + (crc32($identifier) % 9000),
                        'comment_count' => 5 + (crc32($identifier) % 400),
                        'metrics_updated_at' => now(),
                        'previous_view_count' => 3000 + (crc32($identifier) % 800000),
                        'previous_metrics_at' => now()->subDays(7),
                        'country_id' => $city->country_id,
                        'city_id' => $city->id,
                        'category_id' => $category->id,
                        'status' => Video::STATUS_APPROVED,
                        'relevance_score' => 0.8,
                        'classified_by' => 'demo',
                        'classification_confidence' => 1,
                        'classified_at' => now(),
                        'source' => 'demo',
                        'gtl_views' => 200 + (crc32($identifier) % 18000),
                        'is_featured' => $cityIndex === 1 && $slug === 'restaurants',
                        'is_tv_eligible' => true,
                    ]
                );

                $scorer->score($video)->save();
                $created++;
            }

            // One demo place per city so the place pages can be reviewed too.
            $place = Place::updateOrCreate(
                ['city_id' => $city->id, 'slug' => 'demo-'.Str::slug($city->displayName('en'))],
                [
                    'country_id' => $city->country_id,
                    'category_id' => $categories['restaurants']->id ?? null,
                    'name' => 'Restaurant de démonstration — '.$city->displayName('fr'),
                    'description' => ['fr' => 'Fiche de démonstration. Elle sera remplacée par de vraies fiches validées en administration.'],
                    'address' => $city->displayName('fr'),
                    'latitude' => $city->latitude,
                    'longitude' => $city->longitude,
                    'status' => Place::STATUS_PUBLISHED,
                    'published_at' => now(),
                    'source' => 'demo',
                    'gtl_views' => 120 + (crc32($city->slug) % 4000),
                ]
            );

            $cityVideos = Video::where('city_id', $city->id)->where('source', 'demo')->limit(4)->get();

            foreach ($cityVideos as $index => $video) {
                $video->places()->syncWithoutDetaching([
                    $place->id => [
                        'match_score' => 0.9 - ($index * 0.1),
                        'match_reason' => 'demo',
                        'is_primary' => $index === 0,
                        'confirmed' => true,
                    ],
                ]);
            }

            $place->update(['videos_count' => $cityVideos->count()]);
        }

        $this->info("Demo content ready: {$created} placeholder videos. Remove it with gtl:demo-content --purge.");

        return self::SUCCESS;
    }
}
