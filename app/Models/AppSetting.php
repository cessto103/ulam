<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AppSetting extends Model
{
    public $incrementing = false;

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    public static function get(string $key, ?string $default = null): ?string
    {
        $all = static::allCached();

        return $all[$key] ?? $default;
    }

    public static function set(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget('app_settings');
    }

    /** @return array<string, string|null> */
    public static function allCached(): array
    {
        return Cache::remember('app_settings', 300, fn () => static::query()
            ->pluck('value', 'key')
            ->all());
    }
}
