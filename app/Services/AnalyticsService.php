<?php

namespace App\Services;

use App\Models\AnalyticsEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AnalyticsService
{
    public function track(
        string $event,
        array $context = [],
        ?Request $request = null
    ): void {
        $request ??= request();

        $sessionKey = $request?->session()?->getId()
            ? hash('sha256', (string)$request->session()->getId())
            : null;

        AnalyticsEvent::create([
            'user_id' => auth()->id(),
            'session_key' => $sessionKey,
            'event' => $event,
            'page_type' => $context['page_type'] ?? null,
            'page_key' => $context['page_key'] ?? null,
            'country_code' => $context['country_code'] ?? null,
            'city' => $context['city'] ?? null,
            'device_type' => $this->deviceType((string)$request?->userAgent()),
            'metadata' => $context['metadata'] ?? null,
            'occurred_at' => now(),
        ]);
    }

    private function deviceType(string $ua): string
    {
        $ua = strtolower($ua);

        if (str_contains($ua,'mobile')) return 'mobile';
        if (str_contains($ua,'tablet') || str_contains($ua,'ipad')) return 'tablet';

        return 'desktop';
    }
}
