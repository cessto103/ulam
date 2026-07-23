<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\Request;

class AppSettingController extends Controller
{
    /** Whitelisted keys — everything payment-screen related lives here. */
    private const KEYS = [
        'payments_enabled',
        'gcash_number',
        'gcash_account_name',
        'payment_instructions',
        'payment_support_note',
        'ai_meal_plans_enabled',
        'price_refresh_ai_enabled',
    ];

    public function index()
    {
        $all = AppSetting::allCached();

        return response()->json([
            'settings' => collect(self::KEYS)->mapWithKeys(
                fn ($key) => [$key => $all[$key] ?? null]
            ),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'payments_enabled' => ['sometimes', 'boolean'],
            'gcash_number' => ['nullable', 'string', 'max:20'],
            'gcash_account_name' => ['nullable', 'string', 'max:100'],
            'payment_instructions' => ['nullable', 'string', 'max:2000'],
            'payment_support_note' => ['nullable', 'string', 'max:500'],
            'ai_meal_plans_enabled' => ['sometimes', 'boolean'],
            'price_refresh_ai_enabled' => ['sometimes', 'boolean'],
        ]);

        foreach ($validated as $key => $value) {
            AppSetting::set($key, is_bool($value) ? ($value ? '1' : '0') : $value);
        }

        return $this->index();
    }
}
