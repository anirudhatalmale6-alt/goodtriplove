<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SecurityBlockService;
use App\Services\SecurityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(
        Request $request,
        SecurityLogger $logger,
        SecurityBlockService $blocks,
    ): RedirectResponse {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $email = mb_strtolower($credentials['email']);

        if ($blocks->isBlocked('email', $email) || $blocks->isBlocked('ip', $request->ip())) {
            $logger->log('login_blocked', false, 'warning', ['email' => $email]);

            throw ValidationException::withMessages([
                'email' => __('gtl.auth_temporarily_blocked'),
            ]);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            $logger->log('login_failed', false, 'warning', ['email' => $email]);
            $this->registerFailure($email, $request->ip(), $logger, $blocks);

            // Deliberately generic: it must not reveal whether the address exists.
            throw ValidationException::withMessages([
                'email' => __('gtl.auth_failed'),
            ]);
        }

        $user = $request->user();

        if (! $user->is_active) {
            Auth::logout();
            $logger->log('login_inactive_account', false, 'warning', ['user_id' => $user->id]);

            throw ValidationException::withMessages(['email' => __('gtl.auth_account_disabled')]);
        }

        $request->session()->regenerate();

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        $logger->log('login_success', true, 'info', ['user_id' => $user->id]);

        if (! $user->email_verified_at) {
            return redirect()->route('verification.notice');
        }

        return redirect()->intended(route('home'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    /**
     * After config('security.login.max_attempts') failures inside the block
     * window, the address and the IP are temporarily blocked.
     */
    private function registerFailure(
        string $email,
        ?string $ip,
        SecurityLogger $logger,
        SecurityBlockService $blocks,
    ): void {
        $max = (int) config('security.login.max_attempts', 5);
        $window = (int) config('security.blocking.minutes', 30);

        $failures = \App\Models\SecurityLog::where('event', 'login_failed')
            ->where('ip_address', $ip)
            ->where('created_at', '>=', now()->subMinutes($window))
            ->count();

        if ($failures >= $max) {
            $blocks->block('ip', (string) $ip, 'repeated_login_failures');
            $blocks->block('email', $email, 'repeated_login_failures');
            $logger->log('security_block_applied', true, 'critical', ['email' => $email, 'ip' => $ip]);
        }
    }
}
