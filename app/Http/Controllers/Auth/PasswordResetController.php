<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\SecurityLogger;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function request(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Always answers the same thing, whether or not the address exists —
     * otherwise the form becomes an account-enumeration oracle.
     */
    public function email(Request $request, SecurityLogger $logger): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        Password::sendResetLink($request->only('email'));

        $logger->log('password_reset_requested', true, 'info');

        return back()->with('status', __('gtl.password_reset_sent'));
    }

    public function reset(string $locale, string $token): View
    {
        return view('auth.reset-password', ['token' => $token]);
    }

    public function update(Request $request, SecurityLogger $logger): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(10)->letters()->numbers()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($logger) {
                $user->forceFill([
                    'password' => Hash::make(request('password')),
                    'remember_token' => Str::random(60),
                ])->save();

                $logger->log('password_reset_completed', true, 'warning', ['user_id' => $user->id]);

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PasswordReset
            ? redirect()->route('login')->with('status', __('gtl.password_reset_done'))
            : back()->withErrors(['email' => __('gtl.password_reset_failed')]);
    }
}
