<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Schedule extends Model
{
    protected $fillable = [
        'doctor_id', 'title', 'day_of_week', 'start_time', 'end_time',
        'slot_minutes', 'max_serial', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'slot_minutes' => 'integer',
            'max_serial' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }
}
