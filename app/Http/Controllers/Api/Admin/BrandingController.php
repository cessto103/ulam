<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BrandingController extends Controller
{
    private const KEYS = [
        'default' => 'branding_logo',
        'light' => 'branding_logo_light',
    ];

    /** GET /admin/branding */
    public function show()
    {
        return response()->json([
            'logo' => AppSetting::get('branding_logo'),
            'logo_light' => AppSetting::get('branding_logo_light'),
        ]);
    }

    /**
     * POST /admin/branding/logo — upload a replacement logo.
     * variant 'default' shows on light/cream backgrounds; 'light' is the
     * white version for terracotta headers.
     */
    public function upload(Request $request)
    {
        $request->validate([
            'logo' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'variant' => ['required', 'in:default,light'],
        ]);

        $key = self::KEYS[$request->variant];

        // Remove the previous custom file, if any.
        $old = AppSetting::get($key);
        if ($old && str_starts_with($old, '/storage/')) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $old));
        }

        $path = $request->file('logo')->store('branding', 'public');
        AppSetting::set($key, '/storage/' . $path);

        return response()->json(['url' => '/storage/' . $path]);
    }

    /** DELETE /admin/branding/logo?variant=… — back to the built-in uLam logo. */
    public function reset(Request $request)
    {
        $request->validate(['variant' => ['required', 'in:default,light']]);

        $key = self::KEYS[$request->variant];

        $old = AppSetting::get($key);
        if ($old && str_starts_with($old, '/storage/')) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $old));
        }

        AppSetting::set($key, null);

        return response()->json(['message' => 'Reset to the built-in logo.']);
    }
}
