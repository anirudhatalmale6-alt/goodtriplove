<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\Place;
use App\Models\Video;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FavoriteController extends Controller
{
    private const TYPES = ['video' => Video::class, 'place' => Place::class];

    public function toggle(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => 'required|in:video,place',
            'id' => 'required|integer',
        ]);

        $class = self::TYPES[$data['type']];
        $model = $class::findOrFail($data['id']);

        $favorite = Favorite::where([
            'user_id' => $request->user()->id,
            'favoritable_type' => $class,
            'favoritable_id' => $model->id,
        ])->first();

        if ($favorite) {
            $favorite->delete();
            $model->decrement('favorites_count');

            return response()->json(['favorited' => false]);
        }

        Favorite::create([
            'user_id' => $request->user()->id,
            'favoritable_type' => $class,
            'favoritable_id' => $model->id,
        ]);

        $model->increment('favorites_count');

        return response()->json(['favorited' => true]);
    }

    public function index(Request $request): View
    {
        $favorites = $request->user()->favorites()->with('favoritable')->latest()->paginate(24);

        return view('favorites', compact('favorites'));
    }
}
