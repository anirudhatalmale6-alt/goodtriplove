<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Reference data: the launch categories with their per-language search terms
 * (these are what the collector actually sends to YouTube), plus a starting
 * set of countries and their main cities.
 *
 * Idempotent — safe to re-run on every deploy without duplicating anything.
 */
class GoodTripLoveSeeder extends Seeder
{
    public function run(): void
    {
        $this->categories();
        $this->countries();
        $this->announcement();
    }

    private function categories(): void
    {
        $categories = [
            [
                'slug' => 'restaurants',
                'icon' => '🍽️',
                'name' => ['fr' => 'Restaurants', 'pt' => 'Restaurantes', 'es' => 'Restaurantes', 'it' => 'Ristoranti', 'de' => 'Restaurants', 'en' => 'Restaurants'],
                'search_terms' => [
                    'fr' => ['meilleurs restaurants', 'où manger', 'restaurant'],
                    'pt' => ['melhores restaurantes', 'onde comer', 'restaurante'],
                    'es' => ['mejores restaurantes', 'dónde comer', 'restaurante'],
                    'it' => ['migliori ristoranti', 'dove mangiare', 'ristorante'],
                    'de' => ['beste restaurants', 'wo essen', 'restaurant'],
                    'en' => ['best restaurants', 'where to eat', 'restaurant'],
                ],
            ],
            [
                'slug' => 'local-food',
                'icon' => '🥘',
                'name' => ['fr' => 'Manger local', 'pt' => 'Comida local', 'es' => 'Comida local', 'it' => 'Cucina locale', 'de' => 'Lokale Küche', 'en' => 'Local food'],
                'search_terms' => [
                    'fr' => ['spécialités locales', 'cuisine locale', 'street food'],
                    'pt' => ['especialidades locais', 'comida típica', 'street food'],
                    'es' => ['especialidades locales', 'comida típica', 'street food'],
                    'it' => ['specialità locali', 'cucina tipica', 'street food'],
                    'de' => ['lokale spezialitäten', 'typisches essen', 'street food'],
                    'en' => ['local specialties', 'traditional food', 'street food'],
                ],
            ],
            [
                'slug' => 'hotels',
                'icon' => '🏨',
                'name' => ['fr' => 'Hôtels', 'pt' => 'Hotéis', 'es' => 'Hoteles', 'it' => 'Hotel', 'de' => 'Hotels', 'en' => 'Hotels'],
                'search_terms' => [
                    'fr' => ['meilleurs hôtels', 'hôtel'],
                    'pt' => ['melhores hotéis', 'hotel'],
                    'es' => ['mejores hoteles', 'hotel'],
                    'it' => ['migliori hotel', 'hotel'],
                    'de' => ['beste hotels', 'hotel'],
                    'en' => ['best hotels', 'hotel tour'],
                ],
            ],
            [
                'slug' => 'guest-houses',
                'icon' => '🏡',
                'name' => ['fr' => 'Chambres d’hôtes', 'pt' => 'Casas de hóspedes', 'es' => 'Casas rurales', 'it' => 'Bed and breakfast', 'de' => 'Gästehäuser', 'en' => 'Guest houses'],
                'search_terms' => [
                    'fr' => ['chambre d’hôtes', 'maison d’hôtes'],
                    'pt' => ['casa de hóspedes', 'alojamento local'],
                    'es' => ['casa rural', 'casa de huéspedes'],
                    'it' => ['bed and breakfast', 'agriturismo'],
                    'de' => ['gästehaus', 'pension'],
                    'en' => ['guest house', 'bed and breakfast'],
                ],
            ],
            [
                'slug' => 'bars-cafes',
                'icon' => '☕',
                'name' => ['fr' => 'Bars & cafés', 'pt' => 'Bares e cafés', 'es' => 'Bares y cafés', 'it' => 'Bar e caffè', 'de' => 'Bars & Cafés', 'en' => 'Bars & cafés'],
                'search_terms' => [
                    'fr' => ['meilleurs bars', 'café', 'rooftop'],
                    'pt' => ['melhores bares', 'café', 'rooftop'],
                    'es' => ['mejores bares', 'cafetería', 'rooftop'],
                    'it' => ['migliori bar', 'caffè', 'rooftop'],
                    'de' => ['beste bars', 'café', 'rooftop'],
                    'en' => ['best bars', 'coffee shop', 'rooftop'],
                ],
            ],
            [
                'slug' => 'activities',
                'icon' => '🎿',
                'name' => ['fr' => 'Activités', 'pt' => 'Atividades', 'es' => 'Actividades', 'it' => 'Attività', 'de' => 'Aktivitäten', 'en' => 'Activities'],
                'search_terms' => [
                    'fr' => ['activités à faire', 'excursion'],
                    'pt' => ['o que fazer', 'excursão'],
                    'es' => ['qué hacer', 'excursión'],
                    'it' => ['cosa fare', 'escursione'],
                    'de' => ['was tun', 'ausflug'],
                    'en' => ['things to do', 'day trip'],
                ],
            ],
            [
                'slug' => 'places-to-visit',
                'icon' => '🏛️',
                'name' => ['fr' => 'À visiter', 'pt' => 'Para visitar', 'es' => 'Para visitar', 'it' => 'Da visitare', 'de' => 'Sehenswürdigkeiten', 'en' => 'Places to visit'],
                'search_terms' => [
                    'fr' => ['que visiter', 'incontournables', 'visite'],
                    'pt' => ['o que visitar', 'imperdíveis'],
                    'es' => ['qué visitar', 'imprescindibles'],
                    'it' => ['cosa visitare', 'da non perdere'],
                    'de' => ['was besichtigen', 'sehenswürdigkeiten'],
                    'en' => ['what to visit', 'must see'],
                ],
            ],
            [
                'slug' => 'beaches',
                'icon' => '🏖️',
                'name' => ['fr' => 'Plages', 'pt' => 'Praias', 'es' => 'Playas', 'it' => 'Spiagge', 'de' => 'Strände', 'en' => 'Beaches'],
                'search_terms' => [
                    'fr' => ['plus belles plages', 'plage'],
                    'pt' => ['melhores praias', 'praia'],
                    'es' => ['mejores playas', 'playa'],
                    'it' => ['spiagge più belle', 'spiaggia'],
                    'de' => ['schönste strände', 'strand'],
                    'en' => ['best beaches', 'beach'],
                ],
            ],
        ];

        foreach ($categories as $index => $data) {
            Category::updateOrCreate(
                ['slug' => $data['slug']],
                $data + ['sort_order' => $index * 10, 'is_active' => true, 'show_on_home' => true]
            );
        }
    }

    private function countries(): void
    {
        $countries = [
            ['PT', '🇵🇹', ['fr' => 'Portugal', 'pt' => 'Portugal', 'es' => 'Portugal', 'it' => 'Portogallo', 'de' => 'Portugal', 'en' => 'Portugal'], [
                ['Lisbonne', 'Lisboa', 'Lisboa', 'Lisbona', 'Lissabon', 'Lisbon', 38.7223, -9.1393, true],
                ['Porto', 'Porto', 'Oporto', 'Porto', 'Porto', 'Porto', 41.1579, -8.6291, true],
                ['Faro', 'Faro', 'Faro', 'Faro', 'Faro', 'Faro', 37.0194, -7.9304, false],
                ['Madère', 'Madeira', 'Madeira', 'Madera', 'Madeira', 'Madeira', 32.7607, -16.9595, true],
            ]],
            ['FR', '🇫🇷', ['fr' => 'France', 'pt' => 'França', 'es' => 'Francia', 'it' => 'Francia', 'de' => 'Frankreich', 'en' => 'France'], [
                ['Paris', 'Paris', 'París', 'Parigi', 'Paris', 'Paris', 48.8566, 2.3522, true],
                ['Marseille', 'Marselha', 'Marsella', 'Marsiglia', 'Marseille', 'Marseille', 43.2965, 5.3698, true],
                ['Lyon', 'Lião', 'Lyon', 'Lione', 'Lyon', 'Lyon', 45.7640, 4.8357, false],
                ['Nice', 'Nice', 'Niza', 'Nizza', 'Nizza', 'Nice', 43.7102, 7.2620, true],
                ['Bordeaux', 'Bordéus', 'Burdeos', 'Bordeaux', 'Bordeaux', 'Bordeaux', 44.8378, -0.5792, false],
            ]],
            ['ES', '🇪🇸', ['fr' => 'Espagne', 'pt' => 'Espanha', 'es' => 'España', 'it' => 'Spagna', 'de' => 'Spanien', 'en' => 'Spain'], [
                ['Barcelone', 'Barcelona', 'Barcelona', 'Barcellona', 'Barcelona', 'Barcelona', 41.3874, 2.1686, true],
                ['Madrid', 'Madrid', 'Madrid', 'Madrid', 'Madrid', 'Madrid', 40.4168, -3.7038, true],
                ['Séville', 'Sevilha', 'Sevilla', 'Siviglia', 'Sevilla', 'Seville', 37.3891, -5.9845, true],
                ['Valence', 'Valência', 'Valencia', 'Valencia', 'Valencia', 'Valencia', 39.4699, -0.3763, false],
            ]],
            ['IT', '🇮🇹', ['fr' => 'Italie', 'pt' => 'Itália', 'es' => 'Italia', 'it' => 'Italia', 'de' => 'Italien', 'en' => 'Italy'], [
                ['Rome', 'Roma', 'Roma', 'Roma', 'Rom', 'Rome', 41.9028, 12.4964, true],
                ['Florence', 'Florença', 'Florencia', 'Firenze', 'Florenz', 'Florence', 43.7696, 11.2558, true],
                ['Naples', 'Nápoles', 'Nápoles', 'Napoli', 'Neapel', 'Naples', 40.8518, 14.2681, true],
                ['Venise', 'Veneza', 'Venecia', 'Venezia', 'Venedig', 'Venice', 45.4408, 12.3155, true],
            ]],
            ['DE', '🇩🇪', ['fr' => 'Allemagne', 'pt' => 'Alemanha', 'es' => 'Alemania', 'it' => 'Germania', 'de' => 'Deutschland', 'en' => 'Germany'], [
                ['Berlin', 'Berlim', 'Berlín', 'Berlino', 'Berlin', 'Berlin', 52.5200, 13.4050, true],
                ['Munich', 'Munique', 'Múnich', 'Monaco di Baviera', 'München', 'Munich', 48.1351, 11.5820, true],
                ['Hambourg', 'Hamburgo', 'Hamburgo', 'Amburgo', 'Hamburg', 'Hamburg', 53.5511, 9.9937, false],
            ]],
            ['GB', '🇬🇧', ['fr' => 'Royaume-Uni', 'pt' => 'Reino Unido', 'es' => 'Reino Unido', 'it' => 'Regno Unito', 'de' => 'Vereinigtes Königreich', 'en' => 'United Kingdom'], [
                ['Londres', 'Londres', 'Londres', 'Londra', 'London', 'London', 51.5074, -0.1278, true],
                ['Édimbourg', 'Edimburgo', 'Edimburgo', 'Edimburgo', 'Edinburgh', 'Edinburgh', 55.9533, -3.1883, false],
            ]],
        ];

        foreach ($countries as $index => [$code, $flag, $names, $cities]) {
            $country = Country::updateOrCreate(
                ['code' => $code],
                [
                    'slug' => Str::slug($names['en']),
                    'name' => $names,
                    'flag_emoji' => $flag,
                    'default_locale' => Str::lower($code) === 'gb' ? 'en' : Str::lower($code),
                    'sort_order' => $index * 10,
                    'is_active' => true,
                    'is_featured' => $index < 3,
                ]
            );

            foreach ($cities as $order => [$fr, $pt, $es, $it, $de, $en, $lat, $lng, $popular]) {
                City::updateOrCreate(
                    ['country_id' => $country->id, 'slug' => Str::slug($en)],
                    [
                        'name' => ['fr' => $fr, 'pt' => $pt, 'es' => $es, 'it' => $it, 'de' => $de, 'en' => $en],
                        'latitude' => $lat,
                        'longitude' => $lng,
                        'is_popular' => $popular,
                        'is_active' => true,
                        'sort_order' => $order * 10,
                    ]
                );
            }
        }
    }

    private function announcement(): void
    {
        if (Announcement::count() > 0) {
            return;
        }

        Announcement::create([
            'text' => [
                'fr' => 'GoodTripLove — regarde, découvre, mange local, visite, séjourne.',
                'pt' => 'GoodTripLove — vê, descobre, come local, visita, fica.',
                'es' => 'GoodTripLove — mira, descubre, come local, visita, alójate.',
                'it' => 'GoodTripLove — guarda, scopri, mangia locale, visita, soggiorna.',
                'de' => 'GoodTripLove — schau, entdecke, iss lokal, besuche, bleib.',
                'en' => 'GoodTripLove — watch, discover, eat local, visit, stay.',
            ],
            'emoji' => '🌍',
            'is_active' => true,
            'sort_order' => 0,
        ]);
    }
}
