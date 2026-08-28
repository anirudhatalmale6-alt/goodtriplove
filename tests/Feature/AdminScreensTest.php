<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Every admin screen must actually render for a real administrator.
 *
 * Asserting on the status alone is not enough here: an unauthenticated or
 * un-enrolled admin is *redirected*, and a redirect is a perfectly healthy
 * 302 that says nothing about whether the page works. So the session carries a
 * passed 2FA challenge and the assertion is on 200.
 */
class AdminScreensTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsSuperAdmin(): self
    {
        $user = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'is_active' => true,
            'two_factor_enabled' => true,
            'email_verified_at' => now(),
        ]);

        // RequireAdminTwoFactor reads `2fa_passed_at`, a timestamp — not a
        // boolean flag. A wrong key here silently redirects every request.
        return $this->actingAs($user)->withSession(['2fa_passed_at' => now()->timestamp]);
    }

    public static function adminRoutes(): array
    {
        return [
            'seo' => ['admin.seo.index'],
            'audit log' => ['admin.audit.index'],
            'videos' => ['admin.videos.index'],
            'users' => ['admin.users.index'],
            'businesses' => ['admin.businesses.index'],
            'duplicates' => ['admin.videos.duplicates'],
            'ads' => ['admin.ads.index'],
            'settings' => ['admin.settings.index'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('adminRoutes')]
    public function test_the_admin_screen_renders(string $route): void
    {
        $this->seed(\Database\Seeders\GoodTripLoveSeeder::class);

        $response = $this->actingAsSuperAdmin()->get(route($route));

        $this->assertSame(
            200,
            $response->status(),
            $route.' returned '.$response->status().' ('.($response->headers->get('Location') ?? 'no redirect').')'
        );
    }
}
