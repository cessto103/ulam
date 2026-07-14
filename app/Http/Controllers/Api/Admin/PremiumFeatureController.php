<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\Request;

// The "Included in Premium" list shown on the mobile app's Upgrade screen —
// stored as a single JSON AppSetting so the admin can edit it without an
// app release. Null/empty means the mobile app falls back to its
// compiled-in default list.
class PremiumFeatureController extends Controller
{
    public function show()
    {
        $raw = AppSetting::get('premium_features');

        return response()->json(['features' => $raw ? (json_decode($raw, true) ?: []) : []]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'features'             => ['required', 'array', 'min:1', 'max:20'],
            'features.*.emoji'     => ['required', 'string', 'max:8'],
            'features.*.title_en'  => ['required', 'string', 'max:60'],
            'features.*.title_tl'  => ['required', 'string', 'max:60'],
            'features.*.desc_en'   => ['required', 'string', 'max:150'],
            'features.*.desc_tl'   => ['required', 'string', 'max:150'],
            'features.*.free'      => ['required', 'boolean'],
        ]);

        AppSetting::set('premium_features', json_encode($data['features']));

        return response()->json(['features' => $data['features']]);
    }

    public function reset()
    {
        AppSetting::set('premium_features', null);

        return response()->json(['message' => 'Reset to the built-in feature list.']);
    }
}
