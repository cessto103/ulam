<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\WeatherPhrase;
use Illuminate\Http\Request;

class WeatherPhraseController extends Controller
{
    public function index()
    {
        return response()->json([
            'weather_phrases' => WeatherPhrase::orderBy('weather_category')
                ->orderBy('variant_type')
                ->orderBy('sort')
                ->orderBy('id')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $phrase = WeatherPhrase::create($this->validated($request));

        return response()->json(['weather_phrase' => $phrase], 201);
    }

    public function update(Request $request, int $id)
    {
        $phrase = WeatherPhrase::findOrFail($id);
        $phrase->update($this->validated($request));

        return response()->json(['weather_phrase' => $phrase->fresh()]);
    }

    public function destroy(int $id)
    {
        WeatherPhrase::findOrFail($id)->delete();

        return response()->json(['message' => 'Weather phrase deleted.']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'weather_category' => ['required', 'in:sunny,cloudy,light_rain,heavy_rain,extended_rain'],
            'variant_type' => ['required', 'in:info,meal_promo,premium_promo'],
            'phrase_text' => ['required', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
            'sort' => ['sometimes', 'integer', 'min:0', 'max:65535'],
        ]);
    }
}
