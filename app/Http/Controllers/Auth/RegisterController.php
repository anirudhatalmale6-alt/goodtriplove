<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\EmailVerificationService;
use App\Services\SecurityLogger;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Register -> Turnstile (middleware) -> rate limit (middleware) ->
     * create account -> send 6-digit code -> verify email.
     * The account exists but stays unverified until the code is entered.
     */
    public function store(
        Request $request,
        EmailVerificationService $verification,
        SecurityLogger $logger,
    ): RedirectResponse {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc,dns', 'max:190', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->numbers()],
            'account_type' => ['required', 'in:user,business'],
            'terms' => ['accepted'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['account_type'] === 'business' ? User::ROLE_BUSINESS : User::ROLE_USER,
            'locale' => app()->getLocale(),
        ]);

        event(new Registered($user));

        $logger->log('user_registered', true, 'info', ['user_id' => $user->id, 'role' => $user->role]);

        Auth::login($user);
        $request->session()->regenerate();

        $verification->send($user);

        return redirect()->route('verification.notice');
    }
}
