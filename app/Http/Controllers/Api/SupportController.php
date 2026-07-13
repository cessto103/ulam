<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupportController extends Controller
{
    /** GET /faqs — published Q&A, both languages; the app picks per its setting. */
    public function faqs()
    {
        $faqs = Faq::where('is_published', true)
            ->orderBy('sort')
            ->orderBy('id')
            ->get(['id', 'question', 'question_tl', 'answer', 'answer_tl', 'category']);

        return response()->json(['faqs' => $faqs]);
    }

    /** GET /support-tickets — the caller's tickets, newest activity first. */
    public function index(Request $request)
    {
        $tickets = SupportTicket::where('user_id', $request->user()->id)
            ->with('latestMessage:id,support_ticket_id,is_from_admin,body,created_at')
            ->orderByRaw('COALESCE(last_reply_at, created_at) DESC')
            ->get(['id', 'subject', 'category', 'status', 'last_reply_at', 'created_at']);

        return response()->json(['tickets' => $tickets]);
    }

    /** POST /support-tickets — open a ticket with its first message. */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:150'],
            'category' => ['required', Rule::in(SupportTicket::CATEGORIES)],
            'body' => ['required', 'string', 'max:3000'],
        ]);

        $ticket = SupportTicket::create([
            'user_id' => $request->user()->id,
            'subject' => $validated['subject'],
            'category' => $validated['category'],
            'status' => 'open',
            'last_reply_at' => now(),
        ]);

        $ticket->messages()->create([
            'sender_id' => $request->user()->id,
            'is_from_admin' => false,
            'body' => $validated['body'],
        ]);

        return response()->json([
            'message' => 'Ticket submitted. We will get back to you soon!',
            'ticket' => $ticket->load('messages'),
        ], 201);
    }

    /** GET /support-tickets/{id} — one of the caller's tickets, full thread. */
    public function show(Request $request, int $id)
    {
        $ticket = SupportTicket::where('user_id', $request->user()->id)
            ->with(['messages.sender:id,name,avatar'])
            ->findOrFail($id);

        return response()->json(['ticket' => $ticket]);
    }

    /** POST /support-tickets/{id}/reply — user follows up; reopens an answered ticket. */
    public function reply(Request $request, int $id)
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:3000'],
        ]);

        $ticket = SupportTicket::where('user_id', $request->user()->id)->findOrFail($id);

        if ($ticket->status === 'closed') {
            return response()->json(['message' => 'This ticket is closed. Please open a new one.'], 422);
        }

        $message = $ticket->messages()->create([
            'sender_id' => $request->user()->id,
            'is_from_admin' => false,
            'body' => $validated['body'],
        ]);

        $ticket->update(['status' => 'open', 'last_reply_at' => now()]);

        return response()->json(['message' => 'Reply sent.', 'ticket_message' => $message], 201);
    }
}
