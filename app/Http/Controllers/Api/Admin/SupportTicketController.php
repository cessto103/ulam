<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class SupportTicketController extends Controller
{
    public function __construct(private NotificationService $notifications)
    {
    }

    /** GET /admin/support-tickets — inbox, open tickets first. */
    public function index(Request $request)
    {
        $query = SupportTicket::with([
            'user:id,name,username,email',
            'latestMessage:id,support_ticket_id,is_from_admin,body,created_at',
        ]);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->string('category'));
        }

        if ($request->filled('search')) {
            $q = $request->string('search');
            $query->where(function ($w) use ($q) {
                $w->where('subject', 'like', "%{$q}%")
                    ->orWhereHas('user', function ($u) use ($q) {
                        $u->where('name', 'like', "%{$q}%")
                            ->orWhere('email', 'like', "%{$q}%");
                    });
            });
        }

        $page = $query->orderByRaw("FIELD(status, 'open') DESC")
            ->orderByDesc('last_reply_at')
            ->paginate($request->integer('per_page', 15));

        return response()->json(array_merge($page->toArray(), [
            'counts' => [
                'open' => SupportTicket::where('status', 'open')->count(),
            ],
        ]));
    }

    public function show(int $id)
    {
        $ticket = SupportTicket::with([
            'user:id,name,username,email,avatar',
            'messages.sender:id,name,avatar',
        ])->findOrFail($id);

        return response()->json(['ticket' => $ticket]);
    }

    /** POST /admin/support-tickets/{id}/reply */
    public function reply(Request $request, int $id)
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:3000'],
        ]);

        $ticket = SupportTicket::with('user')->findOrFail($id);

        $ticket->messages()->create([
            'sender_id' => $request->user()->id,
            'is_from_admin' => true,
            'body' => $validated['body'],
        ]);

        $ticket->update(['status' => 'answered', 'last_reply_at' => now()]);

        if ($ticket->user) {
            $this->notifications->send(
                $ticket->user,
                'support',
                'Support replied to your ticket',
                mb_strimwidth($validated['body'], 0, 120, '…'),
                ['ticket_id' => $ticket->id],
                "/help/ticket/{$ticket->id}",
            );
        }

        return response()->json(['ticket' => $ticket->fresh(['messages.sender:id,name,avatar'])]);
    }

    /** POST /admin/support-tickets/{id}/close */
    public function close(int $id)
    {
        $ticket = SupportTicket::findOrFail($id);
        $ticket->update(['status' => 'closed']);

        return response()->json(['ticket' => $ticket->fresh()]);
    }
}
