<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppRelease;
use App\Models\SiteSetting;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(): View
    {
        return view('admin.settings.index', [
            'settings' => SiteSetting::orderBy('group')->orderBy('key')->get(),
            'releases' => AppRelease::orderByDesc('released_at')->get(),
            'locales' => array_keys(config('goodtriplove.locales')),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*' => ['nullable'],
        ]);

        foreach ($data['settings'] as $key => $value) {
            SiteSetting::put($key, $value);
        }

        $this->audit->record('settings.update', null, [], ['keys' => array_keys($data['settings'])]);

        return back()->with('status', __('gtl.saved'));
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
