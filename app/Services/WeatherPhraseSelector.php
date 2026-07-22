<?php

namespace App\Services;

use App\Models\Recipe;
use App\Models\WeatherForecastCache;
use App\Models\WeatherPhrase;

class WeatherPhraseSelector
{
    private const TITLES = [
        'sunny' => '☀️ Sunny day ahead',
        'cloudy' => '☁️ Cloudy today',
        'light_rain' => '🌦️ Light rain expected',
        'heavy_rain' => '🌧️ Heavy rain today',
        'extended_rain' => '🌧️ Rainy days ahead',
    ];

    /**
     * The message shown to everyone (falls back from meal_promo to info
     * depending on which pools the admin has actually filled in). Callers
     * should compute this once per bucket, since it never depends on which
     * specific user is reading it -- only the day's forecast and the
     * current top recipe, both of which are the same for the whole bucket.
     */
    public function selectStandard(WeatherForecastCache $forecast): ?array
    {
        $topRecipe = $this->topRecipe();

        if ($topRecipe) {
            $withRecipe = $this->buildMessage($forecast, 'meal_promo', $topRecipe);
            if ($withRecipe) {
                return $withRecipe;
            }
        }

        return $this->buildMessage($forecast, 'info', null);
    }

    /**
     * The Premium upsell variant, or null if there's no configured phrase
     * for it (or the weather doesn't warrant it). Only meaningful for
     * `extended_rain` -- callers should only call this in that case.
     */
    public function selectPremiumPromo(WeatherForecastCache $forecast): ?array
    {
        return $this->buildMessage($forecast, 'premium_promo', null);
    }

    private function buildMessage(WeatherForecastCache $forecast, string $variantType, ?Recipe $recipe): ?array
    {
        $phrase = WeatherPhrase::where('weather_category', $forecast->weather_category)
            ->where('variant_type', $variantType)
            ->where('is_active', true)
            ->inRandomOrder()
            ->first();

        if (! $phrase) {
            return null;
        }

        $tokens = ['{{days}}' => (string) $forecast->consecutive_rain_days];

        $recipeData = null;
        if ($recipe) {
            $author = $recipe->user->name ?? $recipe->user->username ?? 'a uLam cook';
            $tokens += [
                '{{recipe_name}}' => $recipe->title,
                '{{recipe_author}}' => $author,
                '{{rating}}' => number_format((float) $recipe->average_rating, 1),
                '{{thumbs_count}}' => (string) $recipe->vote_up_count,
            ];
            $recipeData = [
                'id' => $recipe->id,
                'title' => $recipe->title,
                'author' => $author,
                'rating' => (float) $recipe->average_rating,
                'thumbs_count' => $recipe->vote_up_count,
                'image_url' => $recipe->image_url,
            ];
        }

        return [
            'title' => self::TITLES[$forecast->weather_category] ?? '🔔 Weather update',
            'body' => strtr($phrase->phrase_text, $tokens),
            'data' => [
                'weather_category' => $forecast->weather_category,
                'variant_type' => $variantType,
                'consecutive_rain_days' => $forecast->consecutive_rain_days,
                'recipe' => $recipeData,
                'show_upgrade_cta' => $variantType === 'premium_promo',
            ],
        ];
    }

    private function topRecipe(): ?Recipe
    {
        return Recipe::with('user:id,name,username')
            ->where('is_published', true)
            ->where('ratings_count', '>', 0)
            ->orderByDesc('average_rating')
            ->orderByDesc('vote_up_count')
            ->first();
    }
}
