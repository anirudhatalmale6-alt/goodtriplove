<?php

namespace App\Http\Controllers;

use App\Models\CookieConsent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Cookie and third-party-embed consent.
 *
 * Accept / Reject / Customize are equally reachable, refusing never blocks
 * browsing, and the choice is stored with the policy version so the evidence
 * still means something after the policy changes.
 */
class CookieConsentController extends Controller
{
    public const CATEGORIES = ['necessary', 'video', 'analytics'];

    public const COOKIE = 'gtl_consent';

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'choices' => ['required', 'array'],
            'choices.*' => ['boolean'],
        ]);

        $choices = ['necessary' => true];

        foreach (self::CATEGORIES as $category) {
            if ($category === 'necessary') {
                continue;
            }

            $choices[$category] = (bool) ($data['choices'][$category] ?? false);
        }

        $key = $request->cookie(self::COOKIE.'_id') ?: (string) Str::uuid();
        $version = (string) config('goodtriplove.legal.cookie_policy_version', '1.0');

        CookieConsent::create([
            'user_id' => $request->user()?->id,
            'consent_key' => $key,
            'policy_version' => $version,
            'choices' => $choices,
            'ip_address' => $request->ip(),
            'consented_at' => now(),
        ]);

        // Six months, the usual maximum for a consent record before it is
        // re-asked. Not httpOnly: the front-end must read it to decide whether
        // an embed may be created.
        $payload = json_encode(['v' => $version, 'c' => $choices]);

        return response()
            ->json(['choices' => $choices, 'version' => $version])
            ->cookie(self::COOKIE, $payload, 60 * 24 * 182, '/', null, $request->secure(), false)
            ->cookie(self::COOKIE.'_id', $key, 60 * 24 * 182, '/', null, $request->secure(), true);
    }

    public function withdraw(Request $request): JsonResponse
    {
        CookieConsent::where('consent_key', $request->cookie(self::COOKIE.'_id'))
            ->whereNull('withdrawn_at')
            ->update(['withdrawn_at' => now()]);

        return response()
            ->json(['withdrawn' => true])
            ->withoutCookie(self::COOKIE);
    }
}
