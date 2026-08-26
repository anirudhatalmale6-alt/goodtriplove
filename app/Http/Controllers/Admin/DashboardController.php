<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\CollectorRun;
use App\Models\Country;
use App\Models\Place;
use App\Models\User;
use App\Models\Video;
use App\Services\OllamaClient;
use App\Services\YouTubeClient;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(YouTubeClient $youtube, OllamaClient $ollama): View
    {
        return view('admin.dashboard', [
            'counts' => [
                'videos_published' => Video::public()->count(),
                'videos_pending' => Video::where('status', Video::STATUS_PENDING)->count(),
                'places_published' => Place::published()->count(),
                'places_pending' => Place::where('status', Place::STATUS_PENDING)->count(),
                'countries' => Country::count(),
                'cities' => City::count(),
                'users' => User::count(),
            ],
            'quota' => [
                'configured' => $youtube->isConfigured(),
                'used' => $youtube->isConfigured() ? $youtube->unitsUsedToday() : 0,
                'remaining' => $youtube->isConfigured() ? $youtube->unitsRemaining() : 0,
                'limit' => (int) config('core_operations.youtube.daily_quota'),
                'near_limit' => $youtube->isConfigured() && $youtube->isNearLimit(),
            ],
            'ollama' => [
                'enabled' => $ollama->enabled(),
                'up' => $ollama->enabled() ? $ollama->isUp() : false,
                'model' => config('goodtriplove.ollama.model'),
            ],
            'recentRuns' => CollectorRun::with('collectorQuery')->latest()->limit(10)->get(),
            'quotaHistory' => DB::table('youtube_quota_usage')->orderByDesc('usage_date')->limit(7)->get(),
            'latestPending' => Video::where('status', Video::STATUS_PENDING)
                ->with(['city', 'country', 'category'])->latest()->limit(8)->get(),
        ]);
    }
}
