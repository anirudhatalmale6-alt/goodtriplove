<?php

namespace Tests\Feature;

use App\Models\Place;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessAdminTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_only_business_accounts_are_listed(): void
    {
        $business = User::factory()->create(['role' => User::ROLE_BUSINESS, 'company_name' => 'Padaria do Porto']);
        $visitor = User::factory()->create(['role' => User::ROLE_USER, 'name' => 'Simple Visiteur']);

        $this->actingAsAdmin()
            ->get(route('admin.businesses.index'))
            ->assertOk()
            ->assertSee('Padaria do Porto')
            ->assertDontSee('Simple Visiteur');
    }

    public function test_an_account_with_a_place_awaiting_a_decision_is_listed_first(): void
    {
        $this->seed(\Database\Seeders\GoodTripLoveSeeder::class);

        $quiet = User::factory()->create(['role' => User::ROLE_BUSINESS, 'company_name' => 'Sans demande']);
        $waiting = User::factory()->create(['role' => User::ROLE_BUSINESS, 'company_name' => 'Avec demande']);

        Place::create([
            'owner_id' => $waiting->id,
            'country_id' => \App\Models\Country::query()->value('id'),
            'name' => 'Restaurante pendente',
            'slug' => 'restaurante-pendente',
            'status' => Place::STATUS_PENDING,
        ]);

        $body = $this->actingAsAdmin()->get(route('admin.businesses.index'))->assertOk()->getContent();

        $this->assertLessThan(
            strpos($body, 'Sans demande'),
            strpos($body, 'Avec demande'),
            'the account holding up a decision has to be the one you see first'
        );
    }

    public function test_the_screen_links_to_the_member_record(): void
    {
        $business = User::factory()->create(['role' => User::ROLE_BUSINESS]);

        $this->actingAsAdmin()
            ->get(route('admin.businesses.index'))
            ->assertSee(route('admin.users.show', $business->id), false);
    }
}
