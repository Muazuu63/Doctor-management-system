<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['skey', 'svalue'];

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $all = static::cached();

        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    public static function setValue(string $key, mixed $value): void
    {
        static::updateOrCreate(['skey' => $key], ['svalue' => is_scalar($value) || $value === null ? (string) $value : json_encode($value)]);
        Cache::forget('clinic.settings');
    }

    public static function setMany(array $pairs): void
    {
        foreach ($pairs as $key => $value) {
            static::updateOrCreate(['skey' => $key], ['svalue' => (string) $value]);
        }
        Cache::forget('clinic.settings');
    }

    public static function cached(): array
    {
        return Cache::remember('clinic.settings', 60, function () {
            try {
                return static::query()->pluck('svalue', 'skey')->all();
            } catch (\Throwable) {
                return [];
            }
        });
    }
}
