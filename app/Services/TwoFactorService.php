<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Crypt;

class TwoFactorService
{
    public function enable(User $user, string $secret): void
    {
        $user->forceFill([
            'two_factor_enabled' => true,
            'two_factor_secret' => Crypt::encryptString($secret),
        ])->save();
    }

    public function disable(User $user): void
    {
        $user->forceFill([
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
        ])->save();
    }

    public function secret(User $user): ?string
    {
        if (!$user->two_factor_secret) return null;

        return Crypt::decryptString($user->two_factor_secret);
    }

    public function verifyTotp(string $secret, string $code): bool
    {
        // Placeholder volontaire.
        // Le freelance doit brancher une librairie TOTP éprouvée (RFC 6238),
        // et ne pas réimplémenter l'algorithme manuellement.
        return app(\App\Contracts\TotpVerifier::class)->verify($secret, $code);
    }
}
