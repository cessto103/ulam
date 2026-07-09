<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifs = UserNotification::where('user_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return response()->json($notifs);
    }

    public function unreadCount(Request $request)
    {
        $count = UserNotification::where('user_id', $request->user()->id)
            ->unread()
            ->count();

        return response()->json(['count' => $count]);
    }

    public function markRead(Request $request, int $id)
    {
        UserNotification::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->whereNull('read_at')
            ->update(['read_at' => Carbon::now()]);

        return response()->json(['ok' => true]);
    }

    public function markAllRead(Request $request)
    {
        UserNotification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => Carbon::now()]);

        return response()->json(['ok' => true]);
    }

    public function registerToken(Request $request)
    {
        $data = $request->validate([
            'push_token' => ['required', 'string', 'max:200'],
        ]);

        $request->user()->update(['push_token' => $data['push_token']]);

        return response()->json(['ok' => true]);
    }
}
