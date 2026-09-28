<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Doctor extends Model
{
    protected $fillable = [
        'user_id', 'name', 'name_bn', 'degree', 'specialty', 'phone', 'email',
        'consultation_fee', 'slot_minutes', 'bio', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'consultation_fee' => 'integer',
            'slot_minutes' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function displayName(): string
    {
        return $this->name_bn ?: $this->name;
    }

    public static function primary(): ?self
    {
        return static::query()->where('is_active', true)->orderBy('id')->first();
    }
}
