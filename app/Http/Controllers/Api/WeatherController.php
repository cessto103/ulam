<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WeatherPhraseSelector;
use App\Services\WeatherService;
use Illuminate\Http\Request;

class WeatherController extends Controller
{
    public function today(Request $request, WeatherService $weather, WeatherPhraseSelector $selector)
    {
        $user = $request->user();

        if (! $user->latitude || ! $user->longitude) {
            return response()->json(['message' => 'Location not set.'], 422);
        }

        $bucketKey = $weather->bucketKey($user->latitude, $user->longitude);
        $forecast = $weather->getForBucket($bucketKey, $user->latitude, $user->longitude);

        if (! $forecast) {
            return response()->json(['message' => 'Weather unavailable right now.'], 503);
        }

        $message = null;
        if ($forecast->weather_category === 'extended_rain' && ! $user->isPremium()) {
            $message = $selector->selectPremiumPromo($forecast);
        }
        $message ??= $selector->selectStandard($forecast);

        if (! $message) {
            return response()->json(['message' => 'No weather update available.'], 404);
        }

        return response()->json($message);
    }
}
