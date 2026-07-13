<?php

namespace App\Services;

use Anthropic\Client as AnthropicClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Screens uploaded images for explicit content.
 *
 * Primary:  Claude Haiku vision (uses the existing Anthropic key) — gated by a
 *           monthly usage cap so spend can never surprise us.
 * Fallback: self-hosted NSFWJS service (moderation-service/) when the cap is
 *           reached or the Claude call fails.
 *
 * Returns: ['verdict' => 'safe'|'flagged'|'unknown', 'provider' => ..., 'scores' => ...]
 * 'unknown' fails open (image stays up) — the in-app Report system is the human net.
 */
class ImageModerationService
{
    public function moderate(string $absolutePath): array
    {
        if (! config('services.moderation.enabled')) {
            return ['verdict' => 'unknown', 'provider' => 'none', 'scores' => null];
        }
        if (! is_file($absolutePath)) {
            return ['verdict' => 'unknown', 'provider' => 'none', 'scores' => ['error' => 'file missing']];
        }

        if ($this->underMonthlyCap()) {
            $result = $this->viaClaude($absolutePath);
            if ($result !== null) {
                return $result;
            }
        }

        $result = $this->viaNsfwjs($absolutePath);
        if ($result !== null) {
            return $result;
        }

        return ['verdict' => 'unknown', 'provider' => 'none', 'scores' => null];
    }

    private function capKey(): string
    {
        return 'moderation:claude:' . now()->format('Y-m');
    }

    private function underMonthlyCap(): bool
    {
        $cap = (int) config('services.moderation.monthly_cap', 2000);
        return (int) Cache::get($this->capKey(), 0) < $cap;
    }

    private function viaClaude(string $path): ?array
    {
        try {
            $client = new AnthropicClient(apiKey: config('services.anthropic.key'));

            $mime = mime_content_type($path) ?: 'image/jpeg';
            $data = base64_encode(file_get_contents($path));

            $response = $client->messages->create(
                maxTokens: 60,
                model: config('services.moderation.model', 'claude-haiku-4-5-20251001'),
                system: 'You are an image moderator for a Filipino food & grocery community app. '
                    . 'Food photos of raw meat, fish, offal, and butchered products are NORMAL and SAFE here. '
                    . 'Respond with ONLY a JSON object: {"verdict":"safe"} or {"verdict":"flagged","reason":"<short>"}. '
                    . 'Flag ONLY: nudity/sexual content, real graphic violence against people or animals (not butchered food), or hateful imagery.',
                messages: [[
                    'role' => 'user',
                    'content' => [
                        ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => $mime, 'data' => $data]],
                        ['type' => 'text', 'text' => 'Moderate this image.'],
                    ],
                ]],
            );

            Cache::increment($this->capKey());

            $text = trim($response->content[0]->text ?? '');
            // The model sometimes wraps JSON in a markdown fence; extract the object.
            $json = null;
            if (preg_match('/\{.*\}/s', $text, $m)) {
                $json = json_decode($m[0], true);
            }
            if (! is_array($json)) {
                // Unparseable response: treat as unknown rather than silently safe.
                return ['verdict' => 'unknown', 'provider' => 'claude', 'scores' => ['raw' => $text]];
            }
            $verdict = ($json['verdict'] ?? null) === 'flagged' ? 'flagged' : 'safe';

            return ['verdict' => $verdict, 'provider' => 'claude', 'scores' => $json ?? ['raw' => $text]];
        } catch (\Throwable $e) {
            Log::warning('Claude moderation failed: ' . $e->getMessage());
            return null;
        }
    }

    private function viaNsfwjs(string $path): ?array
    {
        $url = config('services.moderation.fallback_url');
        if (! $url) {
            return null;
        }

        try {
            $response = Http::timeout(6)->post(rtrim($url, '/') . '/classify', ['path' => $path]);
            if (! $response->ok()) {
                return null;
            }

            $p = collect($response->json('predictions', []))
                ->mapWithKeys(fn ($row) => [$row['className'] => $row['probability']]);

            $flagged = ($p['Porn'] ?? 0) > 0.6
                || ($p['Hentai'] ?? 0) > 0.6
                || ($p['Sexy'] ?? 0) > 0.9;

            return [
                'verdict' => $flagged ? 'flagged' : 'safe',
                'provider' => 'nsfwjs',
                'scores' => $p->all(),
            ];
        } catch (\Throwable $e) {
            Log::warning('NSFWJS moderation failed: ' . $e->getMessage());
            return null;
        }
    }
}
