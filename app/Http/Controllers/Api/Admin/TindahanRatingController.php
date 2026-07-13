<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tindahan;
use App\Models\TindahanRating;
use Illuminate\Http\Request;

class TindahanRatingController extends Controller
{
    public function index(Request $request)
    {
        $query = TindahanRating::with(['user:id,name,username,avatar', 'tindahan:id,name']);

        if ($request->filled('tindahan_id')) {
            $query->where('tindahan_id', $request->integer('tindahan_id'));
        }

        return response()->json(
            $query->orderByDesc('created_at')->paginate($request->integer('per_page', 15))
        );
    }

    public function destroy(int $id)
    {
        $rating = TindahanRating::findOrFail($id);
        $tindahanId = $rating->tindahan_id;
        $rating->delete();

        $agg = TindahanRating::where('tindahan_id', $tindahanId)->selectRaw('AVG(rating) as avg_r, COUNT(*) as cnt')->first();
        Tindahan::where('id', $tindahanId)->update([
            'average_rating' => round($agg->avg_r ?? 0, 2),
            'ratings_count' => $agg->cnt ?? 0,
        ]);

        return response()->json(['message' => 'Rating removed.']);
    }
}
