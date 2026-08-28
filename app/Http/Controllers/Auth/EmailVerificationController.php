<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\EmailVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EmailVerificationController extends Controller
{
    public function __construct(private EmailVerificationService $verification) {}

    public function show(Request $request): View|RedirectResponse
    {
        if ($request->user()->email_verified_at) {
            return redirect()->route('home');
        }

        return view('auth.verify-email');
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'digits:6']]);

        $result = $this->verification->verify($request->user(), (string) $request->input('code'));

        if ($result !== true) {
            throw ValidationException::withMessages(['code' => __('gtl.'.$result)]);
        }

        return redirect()->route('home')->with('status', __('gtl.email_verified'));
    }

    public function resend(Request $request): RedirectResponse
    {
        // Same reasoning as at registration: the visitor is already stuck on
        // this screen, so a mail outage must show a message they can act on
        // rather than a 500 page with no way forward.
        try {
            $this->verification->send($request->user());
        } catch (\Throwable $e) {
            Log::error('verification code could not be resent', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('warning', __('gtl.verification_send_failed'));
        }

        return back()->with('status', __('gtl.code_resent'));
    }
}
