<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = ['actor', 'action', 'detail', 'ip_address'];

    public static function record(string $actor, string $action, string $detail = ''): void
    {
        try {
            static::create([
                'actor' => $actor,
                'action' => $action,
                'detail' => $detail,
                'ip_address' => request()->ip(),
            ]);
        } catch (\Throwable) {
        }
    }
}
