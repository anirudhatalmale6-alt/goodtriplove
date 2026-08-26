<?php

namespace App\Services;

use App\Models\EmailVerificationCode;
use App\Models\User;
use App\Notifications\VerificationCodeNotification;

/**
 * Email verification by 6-digit code.
 *
 * The code is stored hashed, expires, and is attempt-limited — a 6-digit code
 * is only a million possibilities, so an unlimited form would be brute-forced
 * in minutes.
 */
class EmailVerificationService
{
    public function __construct(private SecurityLogger $logger) {}

    public function send(User $user): void
    {
        // One live code per account: issuing a new one retires the previous.
        EmailVerificationCode::where('user_id', $user->id)
            ->whereNull('verified_at')
            ->delete();

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        EmailVerificationCode::create([
            'user_id' => $user->id,
            'code_hash' => hash('sha256', $code),
            'attempts' => 0,
            'expires_at' => now()->addMinutes((int) config('security.email_code.ttl_minutes', 10)),
        ]);

        $user->notify(new VerificationCodeNotification($code));

        $this->logger->log('email_code_sent', true, 'info', ['user_id' => $user->id]);
    }

    /**
     * @return true|string  true, or a translation key describing the failure
     */
    public function verify(User $user, string $code): true|string
    {
        $record = EmailVerificationCode::where('user_id', $user->id)
            ->whereNull('verified_at')
            ->latest('id')
            ->first();

        if (! $record) {
            return 'code_missing';
        }

        if ($record->expires_at->isPast()) {
            return 'code_expired';
        }

        if ($record->attempts >= (int) config('security.email_code.max_attempts', 5)) {
            $this->logger->log('email_code_attempts_exceeded', false, 'warning', ['user_id' => $user->id]);

            return 'code_attempts_exceeded';
        }

        $record->increment('attempts');

        if (! hash_equals($record->code_hash, hash('sha256', $code))) {
            $this->logger->log('email_code_invalid', false, 'warning', ['user_id' => $user->id]);

            return 'code_invalid';
        }

        $record->update(['verified_at' => now()]);
        $user->forceFill(['email_verified_at' => now()])->save();

        $this->logger->log('email_verified', true, 'info', ['user_id' => $user->id]);

        return true;
    }
}
