<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\Request;

class PremiumPricingController extends Controller
{
    private const DEFAULTS = [
        'premium_price_monthly' => '59',
        'premium_price_yearly' => '499',
        'premium_promo_enabled' => '0',
        'premium_promo_label' => '',
        'premium_promo_price_monthly' => '',
        'premium_promo_price_yearly' => '',
    ];

    /** GET /admin/premium-pricing */
    public function show()
    {
        return response()->json($this->currentValues());
    }

    /** PUT /admin/premium-pricing */
    public function update(Request $request)
    {
        $data = $request->validate([
            'premium_price_monthly' => ['required', 'numeric', 'min:0'],
            'premium_price_yearly' => ['required', 'numeric', 'min:0'],
            'premium_promo_enabled' => ['required', 'boolean'],
            'premium_promo_label' => ['nullable', 'string', 'max:120'],
            'premium_promo_price_monthly' => ['nullable', 'numeric', 'min:0'],
            'premium_promo_price_yearly' => ['nullable', 'numeric', 'min:0'],
        ]);

        AppSetting::set('premium_price_monthly', (string) $data['premium_price_monthly']);
        AppSetting::set('premium_price_yearly', (string) $data['premium_price_yearly']);
        AppSetting::set('premium_promo_enabled', $data['premium_promo_enabled'] ? '1' : '0');
        AppSetting::set('premium_promo_label', $data['premium_promo_label'] ?? '');
        AppSetting::set('premium_promo_price_monthly', isset($data['premium_promo_price_monthly']) ? (string) $data['premium_promo_price_monthly'] : '');
        AppSetting::set('premium_promo_price_yearly', isset($data['premium_promo_price_yearly']) ? (string) $data['premium_promo_price_yearly'] : '');

        return response()->json($this->currentValues());
    }

    private function currentValues(): array
    {
        $values = [];
        foreach (self::DEFAULTS as $key => $default) {
            $values[$key] = AppSetting::get($key, $default);
        }
        return $values;
    }
}
