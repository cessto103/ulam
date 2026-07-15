<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ThemePreset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// Admin-controlled backgrounds for the header, the 5 Home dashboard boxes, and the
// 4 Awards "your stats" boxes — grouped into named, switchable presets (Default,
// Christmas, New Year, ...). Each preset holds a map of
// section id => { image, focal_x, focal_y, fit, overlay_colors, overlay_opacity }.
// Any field left unset falls back to the mobile app's compiled-in default.
// Exactly one preset is "active" at a time — that's the one GET /theme (public)
// serves to the app.
class ThemeController extends Controller
{
    public const SECTIONS = [
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

    public function index()
    {
        return response()->json([
            'presets' => ThemePreset::orderByDesc('is_active')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'           => ['required', 'string', 'max:60'],
            'duplicate_from' => ['nullable', 'integer', 'exists:theme_presets,id'],
        ]);

        $sections = [];
        if ($data['duplicate_from'] ?? null) {
            $sections = ThemePreset::find($data['duplicate_from'])->sections ?? [];
        }

        $preset = ThemePreset::create([
            'name'      => $data['name'],
            'slug'      => $this->uniqueSlug($data['name']),
            'sections'  => $sections,
            'is_active' => false,
        ]);

        return response()->json(['preset' => $preset], 201);
    }

    public function update(Request $request, ThemePreset $preset)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
        ]);

        $preset->update(['name' => $data['name']]);

        return response()->json(['preset' => $preset]);
    }

    public function activate(ThemePreset $preset)
    {
        ThemePreset::where('id', '!=', $preset->id)->update(['is_active' => false]);
        $preset->update(['is_active' => true]);

        return response()->json(['preset' => $preset]);
    }

    public function destroy(ThemePreset $preset)
    {
        if ($preset->is_active) {
            return response()->json(['message' => 'Activate a different preset before deleting this one.'], 422);
        }

        foreach ($preset->sections ?? [] as $section) {
            $image = $section['image'] ?? null;
            if ($image && str_starts_with($image, '/storage/')) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $image));
            }
        }

        $preset->delete();

        return response()->json(['message' => 'Preset deleted.']);
    }

    public function uploadImage(Request $request, ThemePreset $preset, string $section)
    {
        abort_unless(in_array($section, self::SECTIONS), 404);

        $request->validate([
            'image' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:3072'],
        ]);

        $sections = $preset->sections ?? [];

        $old = $sections[$section]['image'] ?? null;
        if ($old && str_starts_with($old, '/storage/')) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $old));
        }

        $path = $request->file('image')->store('theme', 'public');
        $sections[$section] = array_merge($sections[$section] ?? [], ['image' => '/storage/' . $path]);
        $preset->update(['sections' => $sections]);

        return response()->json(['preset' => $preset]);
    }

    public function updateSettings(Request $request, ThemePreset $preset, string $section)
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

        $sections = $preset->sections ?? [];
        $sections[$section] = array_merge(
            $sections[$section] ?? [],
            array_filter($data, fn ($v) => $v !== null)
        );
        $preset->update(['sections' => $sections]);

        return response()->json(['preset' => $preset]);
    }

    public function resetSection(ThemePreset $preset, string $section)
    {
        abort_unless(in_array($section, self::SECTIONS), 404);

        $sections = $preset->sections ?? [];

        $old = $sections[$section]['image'] ?? null;
        if ($old && str_starts_with($old, '/storage/')) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $old));
        }

        unset($sections[$section]);
        $preset->update(['sections' => $sections]);

        return response()->json(['preset' => $preset]);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'preset';
        $slug = $base;
        $i    = 2;
        while (ThemePreset::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }
        return $slug;
    }
}
