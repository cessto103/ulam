<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SponsoredAd;
use Illuminate\Http\Request;

class SponsoredAdController extends Controller
{
    public function feed(Request $request)
    {
        $placement = $request->string('placement')->value();
        abort_unless(in_array($placement, ['community', 'recipe'], true), 422, 'Invalid placement.');

        $user = $request->user();
        $today = now()->toDateString();

        $ads = SponsoredAd::query()
            ->where('is_enabled', true)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->where($placement === 'community' ? 'show_in_community_feed' : 'show_in_recipe_feed', true)
            ->where($user->isPremium() ? 'show_to_premium' : 'show_to_free', true)
            ->orderBy('id')
            // Explicit whitelist -- amount_paid/payment_received_at/contact_*/notes
            // are admin-only bookkeeping and must never reach the client.
            ->get(['id', 'product_name', 'company_name', 'tagline', 'description', 'image_url', 'link_url', 'cta_label']);

        return response()->json(['ads' => $ads]);
    }

    public function impression(int $id)
    {
        SponsoredAd::findOrFail($id)->increment('impressions_count');

        return response()->json(['message' => 'ok']);
    }

    public function click(int $id)
    {
        SponsoredAd::findOrFail($id)->increment('clicks_count');

        return response()->json(['message' => 'ok']);
    }
}
