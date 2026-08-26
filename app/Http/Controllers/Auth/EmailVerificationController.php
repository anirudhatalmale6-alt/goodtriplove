<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\EmailVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $this->verification->send($request->user());

        return back()->with('status', __('gtl.code_resent'));
    }
}
