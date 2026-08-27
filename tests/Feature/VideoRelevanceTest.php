<?php

namespace Tests\Feature;

use App\Models\Video;
use App\Services\VideoClassifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The relevance gate, pinned against the real results that broke it.
 *
 * Every "drop" case below was actually imported into the live catalogue from a
 * sensible travel query, because *madeira* is Portuguese for wood and YouTube
 * search is associative rather than literal. Every "keep" case was a genuine
 * travel video that an over-strict version of this gate discarded.
 *
 * Keep both lists: tightening the gate to kill the junk is easy, and it is
 * exactly what throws the good content away.
 */
class VideoRelevanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\GoodTripLoveSeeder::class);
    }

    public static function cases(): array
    {
        return [
            // --- must be kept: real travel videos -----------------------
            'city named with a descriptor noun' => ['PORTO a cidade mais incrível de PORTUGAL', true],
            'general travel advice' => ['5 erreurs à ne surtout pas faire à Madère au Portugal', true],
            'bare category noun in the title' => ['Lisbonne : Bars insolites et vraiment sympas !', true],
            'category noun plus city' => ['Top 10 Lisbon Restaurants: Authentic Portuguese Food Guide', true],
            'accented travel intent' => ['L’itinéraire parfait pour une semaine à Madère', true],
            'singular category noun' => ['Bar Funchal Madeira 07.2019', true],

            // --- must be dropped: what "madeira" actually returned -------
            'prefabricated cabin' => ['Casa pré fabricada em madeira de eucalipto rosa !', false],
            'wood varnishing' => ['como passar verniz madeira rústica #madeira #rustic', false],
            'decking carpentry' => ['Trabalho em madeira eucalipto tratado deck suspenso com casa', false],
            'architecture, no place' => ['two bedroom house plans', false],
            'a spider, not an island' => ['Famosa Aranha Armadeira', false],
            'a video game' => ['This Layout Made Me 1000000 in Restaurant Tycoon 3', false],
            'facade paint' => ['Combinação de tintas de cor marrom para fachada de casa', false],
            'an embroidery pen' => ['NOVO HOBBY PARA 2025 COM ESSA CANETA MÁGICA DE BORDADO', false],
            'property abroad' => ['2023 Conchas Chinas, Puerto Vallarta Villa for sale', false],
        ];
    }

    #[DataProvider('cases')]
    public function test_the_gate_judges_a_title_correctly(string $title, bool $expected): void
    {
        $verdict = app(VideoClassifier::class)
            ->relevance(new Video(['title' => $title, 'description' => '']), null);

        $this->assertSame($expected, $verdict['relevant'], sprintf(
            '"%s" should have been %s but the gate said %s (%s)',
            $title,
            $expected ? 'kept' : 'dropped',
            $verdict['relevant'] ? 'keep' : 'drop',
            $verdict['reason']
        ));
    }
}
