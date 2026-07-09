<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;

// Read-only — the payments table is a ledger; records are written by the
// PayMongo webhook and never edited or deleted from the admin.
class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::with('user:id,name,username,email');

        if ($request->filled('search')) {
            $q = $request->string('search');
            $query->whereHas('user', function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhere('username', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        if ($request->filled('plan_type')) {
            $query->where('plan_type', $request->string('plan_type'));
        }

        return response()->json(
            $query->orderByDesc('paid_at')->paginate($request->integer('per_page', 15))
        );
    }

    public function show(int $id)
    {
        $payment = Payment::with('user:id,name,username,email')->findOrFail($id);

        return response()->json(['payment' => $payment]);
    }
}
