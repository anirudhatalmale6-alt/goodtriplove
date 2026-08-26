<?php

namespace App\Http\Controllers;

use App\Services\ContentNoticeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Public content notice ("Signaler") endpoint.
 *
 * Adapted from the legal module: the validation is unchanged, the confirmation
 * is translated, and the reference number is kept so the reporter can quote it.
 */
class ContentNoticeController extends Controller
{
    public function store(Request $request, ContentNoticeService $service): RedirectResponse
    {
        $data = $request->validate([
            'reporter_email' => ['nullable', 'email', 'max:255'],
            'target_type' => ['required', 'string', 'max:50'],
            'target_id' => ['nullable', 'integer'],
            'target_url' => ['nullable', 'url', 'max:1000'],
            'reason' => ['required', 'string', 'max:100'],
            'explanation' => ['required', 'string', 'max:5000'],
        ]);

        $notice = $service->submit($data);

        return back()->with('status', __('gtl.report_received_reference', ['reference' => $notice->id]));
    }
}
