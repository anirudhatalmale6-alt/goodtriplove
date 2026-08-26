<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DataQualityIssue;
use App\Services\DataQualityService;
use Illuminate\Http\Request;

class DataQualityController extends Controller
{
    public function index(Request $request)
    {
        $q = DataQualityIssue::query()->latest();

        if ($request->filled('status')) $q->where('status',$request->status);
        if ($request->filled('severity')) $q->where('severity',$request->severity);
        if ($request->filled('issue_type')) $q->where('issue_type',$request->issue_type);

        return view('admin.data-quality.index', [
            'issues' => $q->paginate(50),
        ]);
    }

    public function resolve(DataQualityIssue $issue, DataQualityService $quality)
    {
        $quality->resolve($issue, auth()->id());

        return back()->with('status','Issue resolved.');
    }
}
