<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsRollup;
use App\Models\ContentModerationItem;
use App\Models\DataQualityIssue;
use App\Models\ServiceHealth;

class GrowthOpsDashboardController extends Controller
{
    public function index()
    {
        return view('admin.growth-ops.index', [
            'qualityOpen' => DataQualityIssue::where('status','open')->count(),
            'moderationPending' => ContentModerationItem::where('status','pending')->count(),
            'health' => ServiceHealth::latest('checked_at')->take(30)->get()->groupBy('service'),
            'analytics' => AnalyticsRollup::latest('date')->take(30)->get(),
        ]);
    }
}
