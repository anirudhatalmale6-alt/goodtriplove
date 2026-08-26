<?php

namespace App\Http\Controllers;

use App\Models\LegalDocument;
use Illuminate\View\View;

/**
 * Public legal pages. Every document is versioned per language in the
 * database, so the administration can publish a new version without a deploy
 * and the acceptance records keep pointing at the exact version accepted.
 */
class LegalController extends Controller
{
    /** Keys the site exposes, in menu order. */
    public const KEYS = [
        'legal-notice',
        'terms',
        'privacy',
        'cookies',
        'third-party-content',
        'intellectual-property',
        'content-reporting',
    ];

    public function index(): View
    {
        return view('legal.index', [
            'documents' => LegalDocument::query()
                ->where('locale', app()->getLocale())
                ->where('published', true)
                ->orderByDesc('published_at')
                ->get()
                ->unique('key'),
        ]);
    }

    public function show(string $locale, string $key): View
    {
        abort_unless(in_array($key, self::KEYS, true), 404);

        $document = $this->latest($key, app()->getLocale())
            ?? $this->latest($key, config('goodtriplove.default_locale'));

        abort_unless($document, 404);

        return view('legal.show', [
            'document' => $document,
            'key' => $key,
        ]);
    }

    /** The DSA-style notice form: a reasoned report, not a one-click flag. */
    public function reportForm(): View
    {
        return view('legal.report');
    }

    private function latest(string $key, string $locale): ?LegalDocument
    {
        return LegalDocument::where('key', $key)
            ->where('locale', $locale)
            ->where('published', true)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->first();
    }
}
