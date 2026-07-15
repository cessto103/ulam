<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\Request;

class AboutController extends Controller
{
    private const DEFAULTS = [
        'about_title' => 'About uLam',
        'about_body' => "uLam helps Filipino households plan meals, track grocery prices, and manage their food budget, all in one app. With AI-generated meal plans, community price reports, and local market listings, uLam makes it easier to eat well without overspending.\n\nWhether you're stretching a tight weekly budget or just want to plan smarter, uLam is built for the everyday Filipino household.",
        'about_company' => 'Cessto Web Solutions',
        'about_company_url' => 'http://cesstowebsolutions.com',
    ];

    /** GET /admin/about */
    public function show()
    {
        return response()->json($this->currentValues());
    }

    /** PUT /admin/about */
    public function update(Request $request)
    {
        $data = $request->validate([
            'about_title' => ['required', 'string', 'max:120'],
            'about_body' => ['required', 'string', 'max:5000'],
            'about_company' => ['required', 'string', 'max:120'],
            'about_company_url' => ['required', 'string', 'max:255', 'url'],
        ]);

        foreach ($data as $key => $value) {
            AppSetting::set($key, $value);
        }

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
