<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditEntry;
use App\Models\ContentReport;
use App\Models\SecurityHealthCheck;
use App\Models\UserDevice;
use Illuminate\Http\Request;

class SecurityCenterController extends Controller
{
    public function index()
    {
        return view('admin.security-center.index', [
            'health' => SecurityHealthCheck::latest('checked_at')->take(20)->get()->groupBy('service'),
            'audit' => AuditEntry::latest()->take(50)->get(),
            'reportsPending' => ContentReport::where('status','pending')->count(),
            'devices' => UserDevice::whereNull('revoked_at')->count(),
        ]);
    }
}
