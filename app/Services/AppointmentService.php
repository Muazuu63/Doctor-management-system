<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\Delay;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class AppointmentService
{
    public function __construct(
        protected SlotService $slots,
        protected SmsService $sms,
    ) {}

    public function findOrCreatePatient(string $name, string $phone, array $extra = []): Patient
    {
        $phone = normalize_phone($phone);
        $patient = Patient::query()->where('phone', $phone)->first();

        if ($patient) {
            $updates = [];
            if ($name && $name !== $patient->name) {
                $updates['name'] = $name;
            }
            foreach (['gender', 'age', 'blood_group', 'address'] as $f) {
                if (! empty($extra[$f]) && (string) $extra[$f] !== (string) $patient->{$f}) {
                    $updates[$f] = $extra[$f];
                }
            }
            if ($updates) {
                $patient->update($updates);
            }

            return $patient->fresh();
        }

        return Patient::create([
            'name' => $name,
            'phone' => $phone,
            'pin' => (string) random_int(1000, 9999),
            'gender' => $extra['gender'] ?? '',
            'age' => (int) ($extra['age'] ?? 0),
            'blood_group' => $extra['blood_group'] ?? '',
            'address' => $extra['address'] ?? '',
        ]);
    }

    public function book(Patient $patient, string $date, ?int $serial = null, string $notes = ''): Appointment
    {
        $doctor = Doctor::primary();
        if (! $doctor) {
            throw new RuntimeException('ডাক্তার সেট করা নেই।');
        }
        $doctorId = (int) $doctor->id;
        $sch = $this->slots->scheduleForDate($date, $doctorId);
        if (! $sch) {
            throw new RuntimeException('এই তারিখে চেম্বার বন্ধ।');
        }
        if (strtotime($date) < strtotime(date('Y-m-d'))) {
            throw new RuntimeException('গত তারিখে সিরিয়াল নেওয়া যায় না।');
        }

        return DB::transaction(function () use ($patient, $date, $serial, $notes, $doctor, $doctorId, $sch) {
            $dup = Appointment::query()
                ->where('patient_id', $patient->id)
                ->whereDate('appointment_date', $date)
                ->where('doctor_id', $doctorId)
                ->where('status', '!=', 'cancelled')
                ->lockForUpdate()
                ->exists();
            if ($dup) {
                throw new RuntimeException('এই নম্বরে আজকের সিরিয়াল আগেই নেওয়া আছে।');
            }

            $slots = $this->slots->generateSlots($sch, $date, $doctorId);
            $chosen = null;
            if ($serial) {
                foreach ($slots as $s) {
                    if ($s['serial'] === $serial) {
                        $chosen = $s;
                        break;
                    }
                }
                if (! $chosen || ! $chosen['available']) {
                    throw new RuntimeException('এই সিরিয়াল ইতিমধ্যে নেওয়া হয়েছে।');
                }
            } else {
                foreach ($slots as $s) {
                    if ($s['available']) {
                        $chosen = $s;
                        break;
                    }
                }
                if (! $chosen) {
                    throw new RuntimeException('আজকের সব সিরিয়াল শেষ।');
                }
            }

            try {
                $apt = Appointment::create([
                    'patient_id' => $patient->id,
                    'doctor_id' => $doctorId,
                    'schedule_id' => $sch['id'] ?? 0,
                    'appointment_date' => $date,
                    'serial_no' => $chosen['serial'],
                    'scheduled_time' => $chosen['scheduled_time'],
                    'original_time' => $chosen['original_time'],
                    'slot_minutes' => $sch['slot_minutes'],
                    'delay_minutes' => $this->slots->currentDelay($date, $doctorId),
                    'status' => 'booked',
                    'token' => Str::random(20),
                    'notes' => $notes,
                ]);
            } catch (QueryException $e) {
                throw new RuntimeException('এই সিরিয়াল ইতিমধ্যে নেওয়া হয়েছে। আবার চেষ্টা করুন।');
            }

            $this->sms->booking($apt, $patient, $doctor);
            ActivityLog::record('patient', 'book', 'serial '.$chosen['serial'].' on '.$date);

            return $apt;
        });
    }

    public function applyDelay(string $date, int $minutes, string $reason = '', ?int $doctorId = null): int
    {
        $doctorId = $this->slots->doctorId($doctorId);
        Delay::create([
            'doctor_id' => $doctorId,
            'delay_date' => $date,
            'delay_minutes' => $minutes,
            'reason' => $reason,
        ]);

        $rows = Appointment::query()
            ->with('patient')
            ->whereDate('appointment_date', $date)
            ->where('doctor_id', $doctorId)
            ->whereIn('status', ['booked', 'waiting'])
            ->orderBy('serial_no')
            ->get();

        $count = 0;
        foreach ($rows as $r) {
            $newTime = add_minutes($r->original_time, $minutes);
            $r->update([
                'scheduled_time' => $newTime,
                'delay_minutes' => $minutes,
            ]);
            $r->scheduled_time = $newTime;
            $this->sms->reschedule($r, $r->patient, $minutes);
            $count++;
        }

        ActivityLog::record('staff', 'delay', $minutes.' min on '.$date);

        return $count;
    }
}
