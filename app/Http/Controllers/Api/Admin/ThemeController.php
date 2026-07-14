<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

// Admin-controlled backgrounds for the header, the 5 Home dashboard boxes, and the
// 4 Awards "your stats" boxes — one JSON AppSetting key holding a map of
// section id => { image, focal_x, focal_y, fit, overlay_colors, overlay_opacity }.
// Any field left unset falls back to the mobile app's compiled-in default.
class ThemeController extends Controller
{
    private const SECTIONS = [
        'header',
        'header_hero',
        'header_premium',
        'dashboard_meal_plan',
        'dashboard_my_recipes',
        'dashboard_spending_history',
        'dashboard_awards',
        'dashboard_recipe_book',
        'awards_stat_saved',
        'awards_stat_meal_plans',
        'awards_stat_posts',
        'awards_stat_achievements',
    ];

    private function all(): array
    {
        $raw = AppSetting::get('theme_sections');
        return $raw ? (json_decode($raw, true) ?: []) : [];
    }

    private function save(array $map): void
    {
        AppSetting::set('theme_sections', json_encode($map));
    }

    public function show()
    {
        return response()->json(['sections' => $this->all()]);
    }

    public function uploadImage(Request $request, string $section)
    {
        abort_unless(in_array($section, self::SECTIONS), 404);

        $request->validate([
            'image' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:3072'],
        ]);

        $map = $this->all();

        $old = $map[$section]['image'] ?? null;
        if ($old && str_starts_with($old, '/storage/')) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $old));
        }

        $path = $request->file('image')->store('theme', 'public');
        $map[$section] = array_merge($map[$section] ?? [], ['image' => '/storage/' . $path]);
        $this->save($map);

        return response()->json(['sections' => $map]);
    }

    public function updateSettings(Request $request, string $section)
    {
        abort_unless(in_array($section, self::SECTIONS), 404);

        $data = $request->validate([
            'focal_x'          => ['nullable', 'numeric', 'min:0', 'max:100'],
            'focal_y'          => ['nullable', 'numeric', 'min:0', 'max:100'],
            'fit'              => ['nullable', 'in:cover,contain'],
            'overlay_colors'   => ['nullable', 'array', 'max:2'],
            'overlay_colors.*' => ['string'],
            'overlay_opacity'  => ['nullable', 'numeric', 'min:0', 'max:1'],
        ]);

        $map = $this->all();
        $map[$section] = array_merge(
            $map[$section] ?? [],
            array_filter($data, fn ($v) => $v !== null)
        );
        $this->save($map);

        return response()->json(['sections' => $map]);
    }

    public function resetSection(string $section)
    {
        abort_unless(in_array($section, self::SECTIONS), 404);

        $map = $this->all();

        $old = $map[$section]['image'] ?? null;
        if ($old && str_starts_with($old, '/storage/')) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $old));
        }

        unset($map[$section]);
        $this->save($map);

        return response()->json(['sections' => $map]);
    }
}
