<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use Illuminate\Http\RedirectResponse;

class AdClickController extends Controller
{
    /**
     * Counts the click, then sends the visitor on. The destination comes from
     * the stored ad, never from the query string — a redirect that trusts its
     * own input is an open redirect on our own domain.
     */
    public function redirect(string $locale, Ad $ad): RedirectResponse
    {
        abort_unless($ad->is_active && filled($ad->target_url), 404);

        $host = parse_url($ad->target_url, PHP_URL_SCHEME);

        abort_unless(in_array($host, ['http', 'https'], true), 404);

        $ad->increment('clicks');

        return redirect()->away($ad->target_url);
    }
}
