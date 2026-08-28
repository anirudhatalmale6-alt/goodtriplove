<?php

namespace Tests\Feature;

use App\Models\EmailVerificationCode;
use App\Models\User;
use App\Services\EmailVerificationService;
use App\Services\SecurityLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Registration used to return a 500 when the mail transport failed, which is
 * the worst possible moment for it: the account row is already written, so the
 * visitor could neither finish signing up nor start over — their own address
 * was now taken by the half-made account.
 *
 * On this host the transport failed for a very ordinary reason: the web PHP has
 * `proc_open` in `disable_functions`, and the sendmail transport shells out.
 * The command line has no such restriction, so a tinker send reported success
 * while every real registration died.
 */
class RegistrationSurvivesMailFailureTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Test Visitor',
            'email' => 'visitor@gmail.com',
            'password' => 'CorrectHorse12',
            'password_confirmation' => 'CorrectHorse12',
            'account_type' => 'user',
            'terms' => 'on',
        ], $overrides);
    }

    /** The happy path: the account is created and a code is issued. */
    public function test_registration_creates_the_account_and_a_code(): void
    {
        Notification::fake();

        $this->post('/fr/register', $this->payload())
            ->assertRedirect(route('verification.notice'));

        $user = User::where('email', 'visitor@gmail.com')->first();
        $this->assertNotNull($user, 'the account should have been created');
        $this->assertSame(1, EmailVerificationCode::where('user_id', $user->id)->count());
    }

    public function test_a_mail_failure_does_not_500_and_does_not_strand_the_account(): void
    {
        $this->app->bind(EmailVerificationService::class, fn ($app) => new class($app->make(SecurityLogger::class)) extends EmailVerificationService
        {
            public function send(User $user): void
            {
                throw new \RuntimeException('Call to undefined function proc_open()');
            }
        });

        $response = $this->post('/fr/register', $this->payload());

        $this->assertSame(302, $response->status(), 'a mail outage must not surface as a server error');
        $response->assertRedirect(route('verification.notice'));
        $response->assertSessionHas('warning');

        // The account must still exist, so the visitor can use "resend code"
        // rather than being locked out by the uniqueness rule on their own
        // address.
        $this->assertNotNull(User::where('email', 'visitor@gmail.com')->first());
    }
}
