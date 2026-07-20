<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SponsoredAd;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SponsoredAdController extends Controller
{
    public function index(Request $request)
    {
        $query = SponsoredAd::query();

        if ($request->filled('search')) {
            $s = $request->string('search');
            $query->where(function ($q) use ($s) {
                $q->where('product_name', 'like', "%{$s}%")
                  ->orWhere('company_name', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) {
            $status = $request->string('status')->value();
            $today = now()->toDateString();

            match ($status) {
                'disabled' => $query->where('is_enabled', false),
                'scheduled' => $query->where('is_enabled', true)->whereDate('start_date', '>', $today),
                'ended' => $query->where('is_enabled', true)->whereDate('end_date', '<', $today),
                'running' => $query->where('is_enabled', true)->whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today),
                default => null,
            };
        }

        $ads = $query->orderByDesc('created_at')->paginate($request->integer('per_page', 15));
        $ads->getCollection()->transform(fn ($ad) => $this->withComputedStatus($ad));

        return response()->json($ads);
    }

    public function show(int $id)
    {
        $ad = SponsoredAd::findOrFail($id);

        return response()->json(['sponsored_ad' => $this->withComputedStatus($ad)]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());
        $validated['cta_label'] = $this->resolveCtaLabel($validated['cta_label'] ?? null);

        $ad = SponsoredAd::create($validated);

        return response()->json(['sponsored_ad' => $this->withComputedStatus($ad)], 201);
    }

    public function update(Request $request, int $id)
    {
        $ad = SponsoredAd::findOrFail($id);
        $validated = $request->validate($this->rules(sometimes: true));

        if (array_key_exists('cta_label', $validated)) {
            $validated['cta_label'] = $this->resolveCtaLabel($validated['cta_label']);
        }

        $ad->update($validated);

        return response()->json(['sponsored_ad' => $this->withComputedStatus($ad->fresh())]);
    }

    public function destroy(int $id)
    {
        $ad = SponsoredAd::findOrFail($id);

        if ($ad->image_url && str_starts_with($ad->image_url, '/storage/')) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $ad->image_url));
        }

        $ad->delete();

        return response()->json(['message' => 'Sponsored ad deleted.']);
    }

    /** Standalone -- a brand-new ad has no id yet during creation, so this just returns a URL for the form to hold. */
    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:3072'],
        ]);

        $path = $request->file('image')->store('sponsored-ads', 'public');

        return response()->json(['url' => '/storage/' . $path]);
    }

    private function resolveCtaLabel(?string $label): string
    {
        $trimmed = trim((string) $label);

        return $trimmed !== '' ? $trimmed : 'Learn More';
    }

    private function withComputedStatus(SponsoredAd $ad): SponsoredAd
    {
        $ad->setAttribute('display_status', $ad->displayStatus());
        $ad->setAttribute('is_currently_running', $ad->isCurrentlyRunning());

        return $ad;
    }

    private function rules(bool $sometimes = false): array
    {
        $req = $sometimes ? 'sometimes' : 'required';

        return [
            'product_name' => [$req, 'string', 'max:120'],
            'company_name' => [$req, 'string', 'max:120'],
            'tagline' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:300'],
            'image_url' => ['nullable', 'string', 'max:500'],
            'link_url' => ['nullable', 'string', 'max:500'],
            'cta_label' => ['nullable', 'string', 'max:40'],
            'amount_paid' => [$req, 'numeric', 'min:0'],
            'payment_received_at' => ['nullable', 'date'],
            'start_date' => [$req, 'date'],
            'end_date' => [$req, 'date', 'after_or_equal:start_date'],
            'is_enabled' => ['sometimes', 'boolean'],
            'show_to_free' => ['sometimes', 'boolean'],
            'show_to_premium' => ['sometimes', 'boolean'],
            'show_in_recipe_feed' => ['sometimes', 'boolean'],
            'show_in_community_feed' => ['sometimes', 'boolean'],
            'contact_name' => ['nullable', 'string', 'max:120'],
            'contact_email' => ['nullable', 'string', 'email', 'max:150'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
