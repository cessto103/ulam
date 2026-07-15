<?php

namespace Database\Seeders;

use App\Models\ThemePreset;
use Illuminate\Database\Seeder;

// Seeds a few ready-made seasonal presets on top of the "Default" row the
// theme_presets migration already created. None of these are activated here —
// Default stays live until the admin picks one from the Theme page gallery.
class ThemePresetSeeder extends Seeder
{
    public function run(): void
    {
        $palettes = [
            'christmas' => [
                'name'    => 'Christmas',
                'primary'   => '#A63F2B',
                'secondary' => '#2F5233',
                'accent'    => '#E8B923',
                'text'      => '#2B211A',
            ],
            'new-year' => [
                'name'    => 'New Year',
                'primary'   => '#7A2E3A',
                'secondary' => '#1F4033',
                'accent'    => '#D9A62E',
                'text'      => '#241C1F',
            ],
            'araw-ng-kalayaan' => [
                'name'    => 'Araw ng Kalayaan',
                'primary'   => '#B8342A',
                'secondary' => '#2F5F7A',
                'accent'    => '#E8A93C',
                'text'      => '#1F2421',
            ],
            'valentines' => [
                'name'    => "Valentine's Day",
                'primary'   => '#C2495A',
                'secondary' => '#5C7A57',
                'accent'    => '#D9A05C',
                'text'      => '#2A1E20',
            ],
        ];

        foreach ($palettes as $slug => $p) {
            ThemePreset::updateOrCreate(
                ['slug' => $slug],
                [
                    'name'      => $p['name'],
                    'sections'  => $this->sectionsFor($p['primary'], $p['secondary'], $p['accent'], $p['text']),
                    'is_active' => false,
                ]
            );
        }
    }

    /**
     * Maps a 4-color role set (Primary/Secondary/Accent/Text — Background is left
     * to the compiled cream since no section here controls the app's base
     * background) onto every themeable section, following the same role
     * assignment already implicit in the compiled defaults (e.g. the Spending
     * History tile is gold/Accent, Awards tile is green/Secondary, etc).
     */
    private function sectionsFor(string $primary, string $secondary, string $accent, string $text): array
    {
        return [
            'header'                     => ['overlay_colors' => [$primary, $accent]],
            'header_hero'                => ['overlay_colors' => [$primary, $secondary]],
            'header_premium'             => ['overlay_colors' => [$primary]],
            'dashboard_meal_plan'        => ['overlay_colors' => [$text, $primary]],
            'dashboard_my_recipes'       => ['overlay_colors' => [$primary]],
            'dashboard_spending_history' => ['overlay_colors' => [$accent]],
            'dashboard_awards'           => ['overlay_colors' => [$secondary]],
            'dashboard_recipe_book'      => ['overlay_colors' => [$text]],
            'awards_stat_saved'          => ['overlay_colors' => [$accent, $text]],
            'awards_stat_meal_plans'     => ['overlay_colors' => [$secondary, '#FFFFFF']],
            'awards_stat_posts'          => ['overlay_colors' => [$primary, '#FFFFFF']],
            'awards_stat_achievements'   => ['overlay_colors' => [$secondary, '#FFFFFF']],
        ];
    }
}
