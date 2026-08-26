<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\LegalController;
use App\Models\LegalAcceptance;
use App\Models\LegalDocument;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Legal Manager: one versioned document per key and language.
 *
 * Publishing never edits a published version in place — it creates a new one,
 * so the acceptance records keep pointing at the exact text the user accepted.
 */
class LegalAdminController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(): View
    {
        return view('admin.legal.index', [
            'keys' => LegalController::KEYS,
            'locales' => array_keys(config('goodtriplove.locales')),
            'documents' => LegalDocument::orderByDesc('published_at')->orderByDesc('id')->get()
                ->groupBy(fn (LegalDocument $d) => $d->key.'|'.$d->locale),
            'acceptances' => LegalAcceptance::selectRaw('document_key, version, count(*) as total')
                ->groupBy('document_key', 'version')->get(),
        ]);
    }

    public function edit(Request $request, string $key, string $locale): View
    {
        abort_unless(in_array($key, LegalController::KEYS, true), 404);
        abort_unless(array_key_exists($locale, config('goodtriplove.locales')), 404);

        $current = LegalDocument::where(['key' => $key, 'locale' => $locale])
            ->orderByDesc('published_at')->orderByDesc('id')->first();

        return view('admin.legal.edit', [
            'key' => $key,
            'locale' => $locale,
            'current' => $current,
            'history' => LegalDocument::where(['key' => $key, 'locale' => $locale])
                ->orderByDesc('id')->get(),
            // Offered as a starting point when a language has no text yet.
            'reference' => $current ? null : LegalDocument::where('key', $key)
                ->whereIn('locale', [config('goodtriplove.default_locale'), 'en'])
                ->where('published', true)
                ->orderByDesc('published_at')->first(),
        ]);
    }

    public function store(Request $request, string $key, string $locale): RedirectResponse
    {
        abort_unless(in_array($key, LegalController::KEYS, true), 404);

        $data = $request->validate([
            'version' => ['required', 'string', 'max:40'],
            'title' => ['required', 'string', 'max:190'],
            'content' => ['required', 'string'],
            'publish' => ['nullable', 'boolean'],
        ]);

        $exists = LegalDocument::where([
            'key' => $key, 'locale' => $locale, 'version' => $data['version'],
        ])->exists();

        if ($exists) {
            return back()->withInput()->withErrors([
                'version' => 'Cette version existe déjà pour cette langue. Utilisez un nouveau numéro de version.',
            ]);
        }

        $document = LegalDocument::create([
            'key' => $key,
            'locale' => $locale,
            'version' => $data['version'],
            'title' => $data['title'],
            'content' => $data['content'],
            'published' => $request->boolean('publish'),
            'published_at' => $request->boolean('publish') ? now() : null,
            'updated_by' => $request->user()->id,
        ]);

        $this->audit->record('legal.publish', $document, [], [
            'key' => $key, 'locale' => $locale, 'version' => $data['version'],
        ]);

        return redirect()
            ->route('admin.legal.edit', ['key' => $key, 'locale' => $locale])
            ->with('status', 'Version enregistrée.');
    }

    public function publish(Request $request, LegalDocument $document): RedirectResponse
    {
        $document->update(['published' => true, 'published_at' => now()]);

        $this->audit->record('legal.publish', $document, [], ['published' => true]);

        return back()->with('status', 'Version publiée.');
    }
}
