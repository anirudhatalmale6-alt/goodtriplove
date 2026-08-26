<?php

namespace App\Http\Middleware;

use App\Services\SecurityLogger;
use App\Services\TurnstileService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Alias: turnstile
 *
 * Adapted from the security module. One behaviour change: when no Turnstile
 * keys are configured the check is skipped instead of failing.
 *
 * The original returned false for a missing secret, which meant an unconfigured
 * site rejected every login, registration and password reset — the captcha
 * would have locked out the real users while blocking nobody. The skip is
 * logged so an unconfigured production install is visible rather than silent.
 */
class VerifyTurnstile
{
    public function __construct(
        private TurnstileService $turnstile,
        private SecurityLogger $logger,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('security.turnstile.secret_key') || ! config('security.turnstile.site_key')) {
            $this->logger->log('turnstile_not_configured', true, 'warning', [
                'path' => $request->path(),
            ]);

            return $next($request);
        }

        if (! $this->turnstile->verify($request->input('cf-turnstile-response'), $request->ip())) {
            $this->logger->log('turnstile_failed', false, 'warning');

            return back()
                ->withErrors(['security' => __('gtl.turnstile_failed')])
                ->withInput($request->except(['password', 'password_confirmation']));
        }

        return $next($request);
    }
}
