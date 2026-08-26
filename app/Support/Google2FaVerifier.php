<?php

namespace App\Support;

use App\Contracts\TotpVerifier;
use PragmaRX\Google2FA\Google2FA;

/**
 * RFC 6238 TOTP, delegated to pragmarx/google2fa rather than hand-rolled —
 * the security module explicitly asks for a proven library here.
 *
 * A one-step window either side absorbs clock drift between the phone and the
 * server without meaningfully widening the attack surface.
 */
class Google2FaVerifier implements TotpVerifier
{
    public function __construct(private Google2FA $engine) {}

    public function verify(string $secret, string $code): bool
    {
        $code = preg_replace('/\s+/', '', $code) ?? '';

        if (! preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        try {
            return (bool) $this->engine->verifyKey($secret, $code, 1);
        } catch (\Throwable) {
            return false;
        }
    }
}
