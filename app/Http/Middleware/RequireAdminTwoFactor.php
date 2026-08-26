<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Alias: require.2fa
 *
 * Extends the security module\'s original check. Having 2FA *enabled* is not
 * the same as having *passed* it in this session, so an administrator is sent
 * to the challenge on every new session, and to enrolment if they have never
 * set it up.
 */
class RequireAdminTwoFactor
{
    /** A passed challenge is trusted for this long before being asked again. */
    private const TTL_SECONDS = 43200; // 12 h

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        if (! config('security_center.admin_2fa_required')) {
            return $next($request);
        }

        if (! in_array($user->role, ['admin', 'super_admin'], true)) {
            return $next($request);
        }

        if (! $user->two_factor_enabled) {
            return redirect()->route('security.2fa.setup');
        }

        $passedAt = (int) $request->session()->get('2fa_passed_at', 0);

        if ($passedAt < now()->timestamp - self::TTL_SECONDS) {
            return redirect()->route('security.2fa.challenge');
        }

        return $next($request);
    }
}
