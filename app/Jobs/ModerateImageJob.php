<?php

namespace App\Jobs;

use App\Services\ImageModerationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Screens one uploaded image and, when flagged, strips it from its record and
 * quarantines the file. Dispatched with ::dispatchAfterResponse() so it runs
 * right after the HTTP response without needing a queue worker.
 */
class ModerateImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param string $publicPath  '/storage/...' path as stored on the record
     * @param string $contextType e.g. 'user.avatar', 'tindahan.photo', 'post.images'
     * @param int    $contextId   id of the owning record
     */
    public function __construct(
        public string $publicPath,
        public string $contextType,
        public int $contextId,
    ) {}

    public function handle(ImageModerationService $moderation): void
    {
        $absolute = public_path($this->storageRelative());
        $result = $moderation->moderate($absolute);

        $actionTaken = false;
        $mustRemove = $result['verdict'] === 'flagged'
            || ($result['verdict'] === 'unknown' && ! config('services.moderation.fail_open', false));
        if ($mustRemove) {
            $actionTaken = $this->removeFromRecord();
            $this->quarantineFile($absolute);
        }

        DB::table('image_moderations')->insert([
            'path'         => $this->publicPath,
            'context_type' => $this->contextType,
            'context_id'   => $this->contextId,
            'verdict'      => $result['verdict'],
            'provider'     => $result['provider'],
            'scores'       => json_encode($result['scores']),
            'action_taken' => $actionTaken,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }

    /** Null the photo column (or drop the array entry) on the owning record. */
    private function removeFromRecord(): bool
    {
        try {
            switch ($this->contextType) {
                case 'user.avatar':
                    DB::table('users')->where('id', $this->contextId)
                        ->where('avatar', $this->publicPath)->update(['avatar' => null]);
                    return true;
                case 'tindahan.photo':
                    DB::table('tindahan')->where('id', $this->contextId)
                        ->where('photo', $this->publicPath)->update(['photo' => null]);
                    return true;
                case 'tindahan.cover_photo':
                    DB::table('tindahan')->where('id', $this->contextId)
                        ->where('cover_photo', $this->publicPath)->update(['cover_photo' => null]);
                    return true;
                case 'market_price.photo':
                    DB::table('market_prices')->where('id', $this->contextId)
                        ->where('photo', $this->publicPath)->update(['photo' => null]);
                    return true;
                case 'price_report.photo':
                    DB::table('community_price_reports')->where('id', $this->contextId)
                        ->where('photo', $this->publicPath)->update(['photo' => null]);
                    return true;
                case 'post.images':
                    return $this->removeFromJsonArray('posts', 'images');
                case 'recipe.images':
                    return $this->removeFromJsonArray('recipes', 'image_urls');
            }
        } catch (\Throwable $e) {
            Log::warning("Moderation removal failed ({$this->contextType}#{$this->contextId}): " . $e->getMessage());
        }
        return false;
    }

    private function removeFromJsonArray(string $table, string $column): bool
    {
        $row = DB::table($table)->where('id', $this->contextId)->first([$column]);
        if (! $row) return false;
        $images = json_decode($row->{$column} ?? '[]', true) ?: [];
        $filtered = array_values(array_filter($images, fn ($img) => $img !== $this->publicPath));
        if (count($filtered) === count($images)) return false;
        DB::table($table)->where('id', $this->contextId)->update([$column => json_encode($filtered)]);
        return true;
    }

    /** Accepts either '/storage/...' or a full URL and returns the '/storage/...' part. */
    private function storageRelative(): string
    {
        $pos = strpos($this->publicPath, '/storage/');
        return $pos === false ? $this->publicPath : substr($this->publicPath, $pos);
    }

    private function quarantineFile(string $absolute): void
    {
        try {
            if (is_file($absolute)) {
                $dir = storage_path('app/quarantine');
                if (! is_dir($dir)) mkdir($dir, 0755, true);
                rename($absolute, $dir . '/' . basename($absolute));
            }
        } catch (\Throwable $e) {
            Log::warning('Quarantine move failed: ' . $e->getMessage());
        }
    }
}
