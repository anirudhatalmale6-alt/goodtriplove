<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentModerationItem;
use App\Services\ModerationService;
use Illuminate\Http\Request;

class ModerationController extends Controller
{
    public function index()
    {
        return view('admin.moderation.index', [
            'items' => ContentModerationItem::latest()->paginate(50),
        ]);
    }

    public function resolve(
        Request $request,
        ContentModerationItem $item,
        ModerationService $moderation
    ) {
        $data = $request->validate([
            'notes' => ['nullable','string','max:3000'],
        ]);

        $moderation->resolve($item, auth()->id(), $data['notes'] ?? null);

        return back()->with('status','Moderation item resolved.');
    }
}
