<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditService;
use App\Services\SecurityLogger;
use App\Services\TwoFactorService;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use PragmaRX\Google2FA\Google2FA;

/**
 * Two-factor enrolment and challenge for staff accounts.
 *
 * The QR code is rendered on the server as inline SVG — sending the secret to
 * an external chart API would defeat the point of the second factor.
 */
class TwoFactorController extends Controller
{
    public function __construct(
        private TwoFactorService $twoFactor,
        private Google2FA $engine,
        private SecurityLogger $logger,
        private AuditService $audit,
    ) {}

    public function setup(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->two_factor_enabled) {
            return redirect()->route('security.2fa.challenge');
        }

        // Held in the session until confirmed, so an abandoned enrolment never
        // leaves a half-configured secret on the account.
        $secret = $request->session()->get('2fa_pending_secret');

        if (! $secret) {
            $secret = $this->engine->generateSecretKey();
            $request->session()->put('2fa_pending_secret', $secret);
        }

        $uri = $this->engine->getQRCodeUrl('GoodTripLove', $user->email, $secret);

        return view('auth.two-factor-setup', [
            'secret' => $secret,
            'qr' => $this->qrSvg($uri),
        ]);
    }

    public function confirm(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'digits:6']]);

        $user = $request->user();
        $secret = $request->session()->get('2fa_pending_secret');

        if (! $secret) {
            return redirect()->route('security.2fa.setup');
        }

        if (! $this->twoFactor->verifyTotp($secret, (string) $request->input('code'))) {
            $this->logger->log('2fa_setup_failed', false, 'warning', ['user_id' => $user->id]);

            throw ValidationException::withMessages(['code' => __('gtl.code_invalid')]);
        }

        $this->twoFactor->enable($user, $secret);
        $request->session()->forget('2fa_pending_secret');
        $request->session()->put('2fa_passed_at', now()->timestamp);

        $this->audit->record('2fa.enabled', $user);
        $this->logger->log('2fa_enabled', true, 'warning', ['user_id' => $user->id]);

        return redirect()->intended(url('/admin'))->with('status', __('gtl.two_factor_enabled'));
    }

    public function challenge(Request $request): View|RedirectResponse
    {
        if (! $request->user()->two_factor_enabled) {
            return redirect()->route('security.2fa.setup');
        }

        return view('auth.two-factor-challenge');
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'digits:6']]);

        $user = $request->user();
        $secret = $this->twoFactor->secret($user);

        if (! $secret || ! $this->twoFactor->verifyTotp($secret, (string) $request->input('code'))) {
            $this->logger->log('2fa_challenge_failed', false, 'warning', ['user_id' => $user->id]);

            throw ValidationException::withMessages(['code' => __('gtl.code_invalid')]);
        }

        $request->session()->put('2fa_passed_at', now()->timestamp);
        $this->logger->log('2fa_challenge_passed', true, 'info', ['user_id' => $user->id]);

        return redirect()->intended(url('/admin'));
    }

    private function qrSvg(string $uri): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle(210, 1), new SvgImageBackEnd));

        return $writer->writeString($uri);
    }
}
