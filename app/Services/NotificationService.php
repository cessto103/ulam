<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function send(
        User    $user,
        string  $type,
        string  $title,
        string  $body,
        array   $data = [],
        ?string $actionUrl = null,
    ): UserNotification {
        $notif = UserNotification::create([
            'user_id'    => $user->id,
            'type'       => $type,
            'title'      => $title,
            'body'       => $body,
            'data'       => $data,
            'action_url' => $actionUrl,
        ]);

        if ($user->push_token) {
            $this->sendExpoPush($user->push_token, $title, $body, array_merge($data, [
                'notification_id' => $notif->id,
                'action_url'      => $actionUrl,
            ]));
        }

        return $notif;
    }

    public function sendBulk(array $tokens, string $title, string $body, array $data = []): void
    {
        if (empty($tokens)) return;

        $messages = array_map(fn ($token) => [
            'to'    => $token,
            'title' => $title,
            'body'  => $body,
            'data'  => $data,
            'sound' => 'default',
        ], $tokens);

        try {
            Http::timeout(10)->post('https://exp.host/push/send', $messages);
        } catch (\Throwable $e) {
            Log::warning('Expo bulk push failed', ['error' => $e->getMessage()]);
        }
    }

    private function sendExpoPush(string $token, string $title, string $body, array $data = []): void
    {
        try {
            Http::timeout(5)->post('https://exp.host/push/send', [
                'to'    => $token,
                'title' => $title,
                'body'  => $body,
                'data'  => $data,
                'sound' => 'default',
            ]);
        } catch (\Throwable $e) {
            Log::warning('Expo push failed', ['token' => $token, 'error' => $e->getMessage()]);
        }
    }
}
