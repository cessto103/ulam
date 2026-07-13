<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdSubscription;
use App\Services\SellerSubscriptionService;
use Illuminate\Http\Request;

class SellerSubscriptionController extends Controller
{
    public function __construct(private SellerSubscriptionService $service)
    {
    }

    /** GET /admin/seller-subscriptions — filterable list + queue counts. */
    public function index(Request $request)
    {
        $query = AdSubscription::where('type', 'tindahan_listing')
            ->with(['user:id,name,username,email', 'activatedBy:id,name']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('search')) {
            $q = $request->string('search');
            $query->where(function ($w) use ($q) {
                $w->where('payment_reference', 'like', "%{$q}%")
                    ->orWhereHas('user', function ($u) use ($q) {
                        $u->where('name', 'like', "%{$q}%")
                            ->orWhere('username', 'like', "%{$q}%")
                            ->orWhere('email', 'like', "%{$q}%");
                    });
            });
        }

        $page = $query->orderByRaw("FIELD(status, 'pending') DESC")
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 15));

        return response()->json(array_merge($page->toArray(), [
            'counts' => [
                'pending' => AdSubscription::where('type', 'tindahan_listing')->where('status', 'pending')->count(),
                'active' => AdSubscription::activeSeller()->count(),
            ],
        ]));
    }

    public function show(int $id)
    {
        $subscription = AdSubscription::where('type', 'tindahan_listing')
            ->with(['user:id,name,username,email', 'activatedBy:id,name'])
            ->findOrFail($id);

        return response()->json(['subscription' => $subscription]);
    }

    /** POST /admin/seller-subscriptions/{id}/approve */
    public function approve(Request $request, int $id)
    {
        $subscription = AdSubscription::where('type', 'tindahan_listing')->findOrFail($id);

        if ($subscription->status !== 'pending') {
            return response()->json(['message' => 'Only pending submissions can be approved.'], 422);
        }

        $subscription = $this->service->approve($subscription, $request->user());

        return response()->json(['message' => 'Subscription activated.', 'subscription' => $subscription]);
    }

    /** POST /admin/seller-subscriptions/{id}/reject */
    public function reject(Request $request, int $id)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:200'],
        ]);

        $subscription = AdSubscription::where('type', 'tindahan_listing')->findOrFail($id);

        if ($subscription->status !== 'pending') {
            return response()->json(['message' => 'Only pending submissions can be rejected.'], 422);
        }

        $subscription = $this->service->reject($subscription, $request->user(), $validated['reason']);

        return response()->json(['message' => 'Submission rejected.', 'subscription' => $subscription]);
    }

    /**
     * POST /admin/seller-subscriptions/{id}/refund — ends access immediately.
     * The actual GCash send-back happens outside the app; do that first.
     */
    public function refund(Request $request, int $id)
    {
        $subscription = AdSubscription::where('type', 'tindahan_listing')->findOrFail($id);

        if ($subscription->status !== 'active') {
            return response()->json(['message' => 'Only active subscriptions can be refunded.'], 422);
        }

        $subscription = $this->service->refund($subscription, $request->user());

        return response()->json(['message' => 'Subscription refunded and ended.', 'subscription' => $subscription]);
    }
}
