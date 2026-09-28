<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Appointment extends Model
{
    protected $fillable = [
        'patient_id', 'doctor_id', 'schedule_id', 'appointment_date', 'serial_no',
        'scheduled_time', 'original_time', 'slot_minutes', 'delay_minutes',
        'status', 'token', 'notes', 'sms_sent', 'reminder_sent',
    ];

    protected function casts(): array
    {
        return [
            'appointment_date' => 'date',
            'serial_no' => 'integer',
            'slot_minutes' => 'integer',
            'delay_minutes' => 'integer',
            'sms_sent' => 'boolean',
            'reminder_sent' => 'boolean',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function prescription(): HasOne
    {
        return $this->hasOne(Prescription::class);
    }

    public function isActive(): bool
    {
        return $this->status !== 'cancelled';
    }
}
