<?php

namespace App\Http\Controllers;

use App\Models\AppRelease;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AppDownloadController extends Controller
{
    public function index(): View
    {
        return view('app-download', [
            'android' => $this->activeRelease('android'),
            'ios' => $this->activeRelease('ios'),
        ]);
    }

    /**
     * Serves the official APK over HTTPS, from our own storage, with the
     * SHA-256 published next to it so the file can be verified.
     */
    public function android(): BinaryFileResponse|RedirectResponse
    {
        $release = $this->activeRelease('android');

        abort_if(! $release, 404);

        if ($release->store_url && ! $release->file_path) {
            return redirect()->away($release->store_url);
        }

        abort_unless(config('goodtriplove.app_download.apk_enabled'), 404);

        $path = storage_path('app/private/releases/'.basename((string) $release->file_path));

        abort_unless(is_file($path), 404);

        $release->increment('downloads');

        return response()->download($path, 'GoodTripLove-'.$release->version.'.apk');
    }

    private function activeRelease(string $platform): ?AppRelease
    {
        return AppRelease::where('platform', $platform)
            ->where('is_active', true)
            ->latest('released_at')
            ->first();
    }
}
