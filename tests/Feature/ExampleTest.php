<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Laravel's scaffold test, adapted to this application.
 *
 * Two changes were needed: the middleware stack reads the database on every
 * request (the security block list), so migrations must run; and "/" carries no
 * locale, so it redirects to the localised home rather than answering 200.
 */
class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_root_url_redirects_to_a_localised_home(): void
    {
        $this->get('/')->assertRedirect();
    }

    public function test_the_localised_home_responds(): void
    {
        $this->seed(\Database\Seeders\GoodTripLoveSeeder::class);

        $this->get('/fr')->assertStatus(200);
    }
}
