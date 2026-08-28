<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppRelease;
use App\Models\SiteSetting;
use App\Services\AuditService;
use App\Support\SiteSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(): View
    {
        $locales = array_keys(config('goodtriplove.locales'));

        // The current value per key, and per locale for the translatable ones,
        // so the form shows what the site is actually rendering right now.
        $current = [];
        foreach (SiteSettings::keys() as $key) {
            if (SiteSettings::isTranslatable($key)) {
                $stored = SiteSetting::get($key);
                $stored = is_array($stored) ? $stored : [];
                foreach ($locales as $locale) {
                    $current[$key][$locale] = $stored[$locale] ?? '';
                }
            } else {
                $current[$key] = SiteSettings::value($key);
            }
        }

        return view('admin.settings.index', [
            'definitions' => SiteSettings::DEFINITIONS,
            'current' => $current,
            'releases' => AppRelease::orderByDesc('released_at')->get(),
            'locales' => $locales,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $locales = array_keys(config('goodtriplove.locales'));

        // Validated against the same declaration that built the form, so a key
        // that is not offered cannot be written by hand-crafting a request.
        $data = $request->validate(SiteSettings::rules($locales));

        $before = [];
        $after = [];

        foreach (SiteSettings::keys() as $key) {
            if (! array_key_exists($key, $data['settings'] ?? [])) {
                continue;
            }

            $value = $data['settings'][$key];

            if (SiteSettings::isTranslatable($key)) {
                // Drop empty languages rather than storing blanks: an absent
                // translation is what triggers the fallback to the default one.
                $value = array_filter(
                    Arr::only(is_array($value) ? $value : [], $locales),
                    fn ($text) => filled($text),
                );
            }

            $existing = SiteSetting::get($key);
            if ($existing !== $value) {
                $before[$key] = $existing;
                $after[$key] = $value;
                SiteSetting::put($key, $value, $this->groupOf($key));
            }
        }

        if ($after !== []) {
            $this->audit->record('settings.update', null, $before, $after);
        }

        return back()->with('status', __('gtl.saved'));
    }

    private function groupOf(string $key): string
    {
        foreach (SiteSettings::DEFINITIONS as $group => $definition) {
            if (isset($definition['items'][$key])) {
                return $group;
            }
        }

        return 'general';
    }

    /**
     * Publishes an Android build. The SHA-256 is computed here, from the file
     * that will actually be served, so the fingerprint on the download page
     * cannot drift from the binary.
     */
    public function storeRelease(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'platform' => ['required', 'in:android,ios'],
            'version' => ['required', 'string', 'max:32'],
            'version_code' => ['nullable', 'integer'],
            'store_url' => ['nullable', 'url', 'max:255'],
            'apk' => ['nullable', 'file', 'mimetypes:application/vnd.android.package-archive,application/octet-stream', 'max:204800'],
            'release_notes' => ['nullable', 'array'],
            'release_notes.*' => ['nullable', 'string', 'max:2000'],
        ]);

        $attributes = [
            'platform' => $data['platform'],
            'version' => $data['version'],
            'version_code' => $data['version_code'] ?? null,
            'store_url' => $data['store_url'] ?? null,
            'release_notes' => $data['release_notes'] ?? null,
            'released_at' => now(),
            'is_active' => true,
        ];

        if ($request->hasFile('apk')) {
            $file = $request->file('apk');
            $name = 'goodtriplove-'.$data['version'].'.apk';
            $file->storeAs('private/releases', $name);

            $attributes['file_path'] = $name;
            $attributes['file_size'] = $file->getSize();
            $attributes['sha256'] = hash_file('sha256', storage_path('app/private/releases/'.$name));
        }

        // Only one active build per platform.
        AppRelease::where('platform', $data['platform'])->update(['is_active' => false]);

        AppRelease::create($attributes);

        $this->audit->record('app_release.create', null, [], [
            'platform' => $data['platform'],
            'version' => $data['version'],
        ]);

        return back()->with('status', __('gtl.saved'));
    }
}
