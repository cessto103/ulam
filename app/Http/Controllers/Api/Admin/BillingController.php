<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\WebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\BillingService;

class BillingController extends Controller
{
    public function __construct(private BillingService $billing) {}
    public function summary()
    {
        $monthStart = now()->startOfMonth();
        $yearStart = now()->startOfYear();
        $started = Subscription::where('created_at', '>=', $monthStart)->count();
        $churned = Subscription::whereIn('status', ['cancelled', 'expired', 'suspended'])
            ->where('updated_at', '>=', $monthStart)->count();

        return response()->json([
            'active_subscribers' => Subscription::entitled()->distinct('user_id')->count('user_id'),
            'monthly_revenue' => Payment::where('status', 'paid')->where('paid_at', '>=', $monthStart)->sum('amount') / 100,
            'annual_revenue' => Payment::where('status', 'paid')->where('paid_at', '>=', $yearStart)->sum('amount') / 100,
            'failed_payments' => Payment::where('status', 'failed')->where('created_at', '>=', $monthStart)->count(),
            'churn_rate' => $started > 0 ? round($churned / $started * 100, 2) : 0,
            'expiring_soon' => Subscription::where('status', 'active')->whereBetween('current_period_end', [now(), now()->addDays(7)])->count(),
            'webhook_failures' => WebhookEvent::where('status', 'failed')->count(),
            'revenue_by_day' => Payment::where('status', 'paid')->where('paid_at', '>=', now()->subDays(29)->startOfDay())
                ->selectRaw('DATE(paid_at) as date, SUM(amount) as amount')->groupByRaw('DATE(paid_at)')->orderBy('date')->get()
                ->map(fn ($row) => ['date' => $row->date, 'amount' => $row->amount / 100]),
        ]);
    }

    public function subscriptions(Request $request)
    {
        return Subscription::with(['user:id,name,email,username', 'plan:id,slug,name', 'price:id,duration,price'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()->paginate(min($request->integer('per_page', 20), 100));
    }

    public function webhooks(Request $request)
    {
        return WebhookEvent::query()->select(['id', 'provider_event_id', 'event_type', 'livemode', 'status', 'processed_at', 'error', 'created_at'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = $request->string('search');
                $q->where(function ($w) use ($term) {
                    $w->where('event_type', 'like', "%{$term}%")
                        ->orWhere('provider_event_id', 'like', "%{$term}%")
                        ->orWhere('error', 'like', "%{$term}%");
                });
            })
            ->latest()->paginate(min($request->integer('per_page', 20), 100));
    }

    public function logs(Request $request)
    {
        return DB::table('billing_logs')->latest()->paginate(min($request->integer('per_page', 20), 100));
    }

    public function refund(Request $request, int $paymentId)
    {
        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'in:duplicate,fraudulent,requested_by_customer,others'],
        ]);
        $payment = Payment::findOrFail($paymentId);
        $refund = $this->billing->refund($payment, $request->user(), $validated['amount'], $validated['reason']);
        return response()->json(['refund' => $refund], 201);
    }
}
