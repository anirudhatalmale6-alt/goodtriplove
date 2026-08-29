<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use App\Support\SystemSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The point of the screen is that a value saved in it actually takes effect.
 * A form that stores a YouTube key nothing ever reads would look identical in
 * the browser, so every test here asserts on the behaviour, not on the row.
 */
class SystemSettingsTest extends TestCase
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

        return $this->actingAs($user)->withSession(['2fa_passed_at' => now()->timestamp]);
    }

    public function test_a_saved_youtube_key_reaches_the_configuration(): void
    {
        $this->actingAsSuperAdmin()
            ->put(route('admin.system.update'), ['settings' => ['youtube_api_key' => 'AIza-test-key-123']])
            ->assertRedirect();

        // This is what AppServiceProvider::boot() does on the next request.
        SystemSettings::apply();

        $this->assertSame('AIza-test-key-123', config('goodtriplove.youtube.api_key'));
    }

    public function test_a_secret_is_not_stored_in_clear_text(): void
    {
        $this->actingAsSuperAdmin()
            ->put(route('admin.system.update'), ['settings' => ['youtube_api_key' => 'super-secret-value']]);

        $raw = DB::table('site_settings')->where('key', 'youtube_api_key')->value('value');

        $this->assertStringNotContainsString('super-secret-value', (string) $raw);
        $this->assertSame('super-secret-value', SystemSettings::stored('youtube_api_key'));
    }

    public function test_an_untouched_secret_field_does_not_wipe_the_saved_key(): void
    {
        SystemSettings::put('youtube_api_key', 'the-working-key');

        // The field is rendered masked and empty, so an administrator saving
        // the form for an unrelated reason posts nothing for it.
        $this->actingAsSuperAdmin()->put(route('admin.system.update'), [
            'settings' => ['youtube_api_key' => '', 'mail_host' => 'ssl0.ovh.net'],
        ]);

        $this->assertSame('the-working-key', SystemSettings::stored('youtube_api_key'));
    }

    public function test_the_clear_box_removes_a_saved_key(): void
    {
        SystemSettings::put('youtube_api_key', 'the-working-key');

        $this->actingAsSuperAdmin()->put(route('admin.system.update'), [
            'settings' => ['youtube_api_key' => ''],
            'clear' => ['youtube_api_key' => '1'],
        ]);

        $this->assertNull(SystemSettings::stored('youtube_api_key'));
    }

    public function test_the_secret_value_never_appears_on_the_page(): void
    {
        SystemSettings::put('turnstile_secret_key', '0xSECRETVALUE9999');

        $this->actingAsSuperAdmin()
            ->get(route('admin.system.index'))
            ->assertOk()
            ->assertDontSee('0xSECRETVALUE9999')
            ->assertSee('9999');   // the masked tail, enough to recognise it
    }

    public function test_two_factor_can_be_switched_off_and_back_on(): void
    {
        $admin = $this->actingAsSuperAdmin();

        $admin->put(route('admin.system.update'), ['settings' => ['admin_2fa_required' => '0']]);
        SystemSettings::apply();
        $this->assertFalse((bool) config('security_center.admin_2fa_required'));

        $admin->put(route('admin.system.update'), ['settings' => ['admin_2fa_required' => '1']]);
        SystemSettings::apply();
        $this->assertTrue((bool) config('security_center.admin_2fa_required'));
    }

    /**
     * The switch that only ever switches on is the classic checkbox bug: an
     * unticked box posts nothing, so a value read from the payload keeps its
     * old value forever.
     */
    public function test_turning_a_switch_off_is_recorded(): void
    {
        SystemSettings::put('turnstile_enabled', true);

        $this->actingAsSuperAdmin()->put(route('admin.system.update'), ['settings' => []]);

        $this->assertFalse((bool) SystemSettings::stored('turnstile_enabled'));
    }

    public function test_turnstile_is_inactive_until_both_keys_and_the_switch_are_set(): void
    {
        config(['security.turnstile.site_key' => null, 'security.turnstile.secret_key' => null]);
        SystemSettings::put('turnstile_enabled', true);
        $this->assertFalse(SystemSettings::turnstileActive(), 'the switch alone must not claim protection');

        config(['security.turnstile.site_key' => '0xSITE', 'security.turnstile.secret_key' => '0xSECRET']);
        $this->assertTrue(SystemSettings::turnstileActive());

        SystemSettings::put('turnstile_enabled', false);
        $this->assertFalse(SystemSettings::turnstileActive(), 'the admin switch must be able to turn it off');
    }

    /**
     * config/mail.php disables the certificate check for the loopback hop. If
     * that survived a move to a real server the mailbox password would cross
     * the network in clear, so the host decides, not the .env.
     */
    public function test_a_remote_mail_host_forces_tls_verification_back_on(): void
    {
        config([
            'mail.mailers.smtp.auto_tls' => false,
            'mail.mailers.smtp.verify_peer' => false,
        ]);

        SystemSettings::put('mail_host', 'ssl0.ovh.net');
        SystemSettings::apply();

        $this->assertTrue(config('mail.mailers.smtp.auto_tls'));
        $this->assertTrue(config('mail.mailers.smtp.verify_peer'));
    }

    public function test_the_loopback_relay_keeps_its_exception(): void
    {
        config([
            'mail.mailers.smtp.auto_tls' => false,
            'mail.mailers.smtp.verify_peer' => false,
        ]);

        SystemSettings::put('mail_host', '127.0.0.1');
        SystemSettings::apply();

        $this->assertFalse(config('mail.mailers.smtp.auto_tls'), 'the local MTA cert is issued for its own hostname');
    }

    public function test_an_unsaved_setting_leaves_the_env_value_alone(): void
    {
        config(['goodtriplove.youtube.api_key' => 'key-from-the-env']);

        SystemSettings::apply();

        $this->assertSame('key-from-the-env', config('goodtriplove.youtube.api_key'));
    }

    public function test_the_audit_trail_records_the_change_but_not_the_secret(): void
    {
        $this->actingAsSuperAdmin()->put(route('admin.system.update'), [
            'settings' => ['youtube_api_key' => 'do-not-log-me'],
        ]);

        $row = DB::table('audit_entries')->where('action', 'system_settings.update')->latest('id')->first();

        $this->assertNotNull($row, 'a key change has to leave a trace');
        $this->assertStringNotContainsString('do-not-log-me', json_encode($row));
    }

    /**
     * The switch has to change what happens to a person, not just a config
     * value. An administrator who has never enrolled is normally pushed to the
     * enrolment screen; with the requirement lifted they must get through.
     */
    public function test_the_two_factor_switch_changes_what_the_middleware_does(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
            'is_active' => true,
            'two_factor_enabled' => false,      // never enrolled
            'email_verified_at' => now(),
        ]);

        SystemSettings::put('admin_2fa_required', true);
        SystemSettings::apply();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('security.2fa.setup'));

        SystemSettings::put('admin_2fa_required', false);
        SystemSettings::apply();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk();
    }

    /**
     * The widget and the check must agree. A page that enforces Turnstile
     * without rendering its widget rejects every visitor, which is worse than
     * having no captcha at all.
     */
    public function test_the_widget_appears_on_the_public_form_only_when_active(): void
    {
        $this->seed(\Database\Seeders\GoodTripLoveSeeder::class);

        config(['security.turnstile.site_key' => '0xSITEKEY', 'security.turnstile.secret_key' => '0xSECRET']);
        SystemSettings::put('turnstile_enabled', true);

        $this->get(route('register', ['locale' => 'fr']))
            ->assertOk()
            ->assertSee('cf-turnstile', false)
            ->assertSee('0xSITEKEY', false);

        SystemSettings::put('turnstile_enabled', false);

        $this->get(route('register', ['locale' => 'fr']))
            ->assertOk()
            ->assertDontSee('cf-turnstile', false);
    }

    public function test_a_corrupt_secret_reports_absent_instead_of_crashing(): void
    {
        // What an APP_KEY rotation leaves behind. Throwing here would 500 every
        // page, including the screen used to type the key in again.
        SiteSetting::put('youtube_api_key', 'enc::not-a-valid-payload', 'system');

        $this->assertNull(SystemSettings::stored('youtube_api_key'));

        $this->actingAsSuperAdmin()->get(route('admin.system.index'))->assertOk();
    }
}
