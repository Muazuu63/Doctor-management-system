<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleException extends Model
{
    protected $fillable = [
        'doctor_id', 'except_date', 'is_off', 'title', 'start_time', 'end_time',
        'slot_minutes', 'max_serial', 'note',
    ];

    protected function casts(): array
    {
        return [
            'except_date' => 'date',
            'is_off' => 'boolean',
            'slot_minutes' => 'integer',
            'max_serial' => 'integer',
        ];
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }
}
