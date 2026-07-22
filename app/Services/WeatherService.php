<?php

namespace App\Services;

use App\Models\WeatherForecastCache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WeatherService
{
    // A run of this many consecutive rainy days (starting today) is treated
    // as "extended_rain" -- the only category that can trigger the Premium
    // upsell. Everything else is same-day only.
    private const EXTENDED_RAIN_THRESHOLD = 3;

    // WMO weather codes (Open-Meteo's `weathercode`) that count as rain/showers/storms.
    private const RAIN_CODES = [51, 53, 55, 56, 57, 61, 63, 65, 66, 67, 80, 81, 82, 95, 96, 99];

    /** Rounds lat/lng to ~1km precision so nearby users share one cached forecast. */
    public function bucketKey(float $lat, float $lng): string
    {
        return number_format($lat, 2) . ',' . number_format($lng, 2);
    }

    /** Cache-or-fetch: one Open-Meteo call per bucket per day. */
    public function getForBucket(string $bucketKey, float $lat, float $lng): ?WeatherForecastCache
    {
        $today = today()->toDateString();

        $cached = WeatherForecastCache::where('bucket_key', $bucketKey)
            ->where('forecast_date', $today)
            ->first();

        if ($cached) {
            return $cached;
        }

        $daily = $this->fetchForecast($lat, $lng);
        if ($daily === null) {
            return null;
        }

        $classified = $this->classify($daily);

        return WeatherForecastCache::create([
            'bucket_key' => $bucketKey,
            'forecast_date' => $today,
            'weather_category' => $classified['weather_category'],
            'consecutive_rain_days' => $classified['consecutive_rain_days'],
            'raw_response' => $daily,
        ]);
    }

    /** @return array{time: string[], weathercode: int[], precipitation_probability_max: int[]}|null */
    public function fetchForecast(float $lat, float $lng): ?array
    {
        try {
            $response = Http::timeout(10)->get('https://api.open-meteo.com/v1/forecast', [
                'latitude' => $lat,
                'longitude' => $lng,
                'daily' => 'weathercode,precipitation_probability_max',
                'timezone' => 'Asia/Manila',
                'forecast_days' => 7,
            ]);

            if (! $response->successful()) {
                Log::warning('Open-Meteo request failed', ['status' => $response->status()]);
                return null;
            }

            return $response->json('daily');
        } catch (\Throwable $e) {
            Log::warning('Open-Meteo request errored', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Maps a 7-day daily forecast into one of our fixed categories plus how
     * many consecutive days (starting today) look rainy. Thresholds are
     * intentionally hardcoded here rather than admin-editable -- they are
     * the one part of this feature that should behave the same every day.
     *
     * @param array{time: string[], weathercode: int[], precipitation_probability_max: int[]} $daily
     * @return array{weather_category: string, consecutive_rain_days: int}
     */
    public function classify(array $daily): array
    {
        $codes = $daily['weathercode'] ?? [];
        $precip = $daily['precipitation_probability_max'] ?? [];
        $days = count($codes);

        $isRainyDay = function (int $i) use ($codes, $precip) {
            $code = $codes[$i] ?? 0;
            $probability = $precip[$i] ?? 0;
            return in_array($code, self::RAIN_CODES, true) || $probability >= 50;
        };

        $consecutiveRainDays = 0;
        for ($i = 0; $i < $days; $i++) {
            if (! $isRainyDay($i)) {
                break;
            }
            $consecutiveRainDays++;
        }

        if ($consecutiveRainDays >= self::EXTENDED_RAIN_THRESHOLD) {
            return ['weather_category' => 'extended_rain', 'consecutive_rain_days' => $consecutiveRainDays];
        }

        $todayCode = $codes[0] ?? 0;
        $todayProbability = $precip[0] ?? 0;

        $category = match (true) {
            $todayProbability >= 70 || in_array($todayCode, [61, 63, 65, 80, 81, 82, 95, 96, 99], true) => 'heavy_rain',
            $todayProbability >= 30 || in_array($todayCode, [51, 53, 55, 56, 57, 66, 67], true) => 'light_rain',
            in_array($todayCode, [2, 3, 45, 48], true) => 'cloudy',
            default => 'sunny',
        };

        return ['weather_category' => $category, 'consecutive_rain_days' => $consecutiveRainDays];
    }
}
