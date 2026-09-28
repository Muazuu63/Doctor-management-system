<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Delay extends Model
{
    protected $fillable = ['doctor_id', 'delay_date', 'delay_minutes', 'reason'];

    protected function casts(): array
    {
        return [
            'delay_date' => 'date',
            'delay_minutes' => 'integer',
        ];
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }
}
