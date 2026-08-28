<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The member record, and the suspend / restore pair.
 *
 * Deleting is soft on purpose: a business account owns places, and a hard
 * delete would either take them with it or orphan them, with no way back.
 */
class UserAdminDetailTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'is_active' => true,
            'two_factor_enabled' => true,
            'email_verified_at' => now(),
        ]);
    }

    private function actingAsAdmin(?User $admin = null): self
    {
        return $this->actingAs($admin ?? $this->superAdmin())
            ->withSession(['2fa_passed_at' => now()->timestamp]);
    }

    public function test_the_member_record_shows_the_account_details(): void
    {
        $member = User::factory()->create([
            'name' => 'Ana Ferreira',
            'email' => 'ana@example.com',
            'role' => User::ROLE_BUSINESS,
        ]);

        $this->actingAsAdmin()
            ->get(route('admin.users.show', $member->id))
            ->assertOk()
            ->assertSee('Ana Ferreira')
            ->assertSee('ana@example.com');
    }

    public function test_the_list_links_to_the_record(): void
    {
        $member = User::factory()->create();

        // A page nobody can reach is not a delivered feature.
        $this->actingAsAdmin()
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee(route('admin.users.show', $member->id), false);
    }

    public function test_deleting_is_reversible_and_keeps_the_members_places(): void
    {
        $member = User::factory()->create();

        $this->actingAsAdmin()->delete(route('admin.users.destroy', $member->id))->assertRedirect();

        $this->assertSoftDeleted('users', ['id' => $member->id]);
        $this->assertNotNull(User::withTrashed()->find($member->id), 'the row must survive so it can be restored');

        $this->actingAsAdmin()->post(route('admin.users.restore', $member->id))->assertRedirect();

        $this->assertNotSoftDeleted('users', ['id' => $member->id]);
    }

    public function test_a_deleted_account_is_still_reachable_so_it_can_be_restored(): void
    {
        $member = User::factory()->create();
        $member->delete();

        // Route-model binding on the User class would 404 here, because the
        // global scope hides trashed rows — hence binding by id.
        $this->actingAsAdmin()
            ->get(route('admin.users.show', $member->id))
            ->assertOk()
            ->assertSee('Restaurer le compte');
    }

    public function test_an_administrator_cannot_delete_their_own_account(): void
    {
        $admin = $this->superAdmin();

        $this->actingAsAdmin($admin)
            ->delete(route('admin.users.destroy', $admin->id))
            ->assertForbidden();

        $this->assertNotSoftDeleted('users', ['id' => $admin->id]);
    }

    public function test_a_moderator_cannot_delete_an_account(): void
    {
        $moderator = User::factory()->create([
            'role' => User::ROLE_MODERATOR,
            'is_active' => true,
            'two_factor_enabled' => true,
            'email_verified_at' => now(),
        ]);
        $member = User::factory()->create();

        $this->actingAsAdmin($moderator)
            ->delete(route('admin.users.destroy', $member->id))
            ->assertForbidden();

        $this->assertNotSoftDeleted('users', ['id' => $member->id]);
    }
}
