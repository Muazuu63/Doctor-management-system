<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicineTemplate extends Model
{
    protected $fillable = ['doctor_id', 'name', 'dose', 'duration', 'instruction'];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }
}
