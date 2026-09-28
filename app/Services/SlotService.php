<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Delay;
use App\Models\Doctor;
use App\Models\Schedule;
use App\Models\ScheduleException;
use Carbon\Carbon;

class SlotService
{
    public function doctorId(?int $doctorId = null): int
    {
        if ($doctorId) {
            return $doctorId;
        }

        return (int) (Doctor::primary()?->id ?? 1);
    }

    public function currentDelay(string $date, ?int $doctorId = null): int
    {
        $doctorId = $this->doctorId($doctorId);
        $row = Delay::query()
            ->whereDate('delay_date', $date)
            ->where('doctor_id', $doctorId)
            ->orderByDesc('id')
            ->first();

        return $row ? (int) $row->delay_minutes : 0;
    }

    public function scheduleForDate(string $date, ?int $doctorId = null): ?array
    {
        $doctorId = $this->doctorId($doctorId);
        $ex = ScheduleException::query()
            ->whereDate('except_date', $date)
            ->where('doctor_id', $doctorId)
            ->first();

        if ($ex) {
            if ($ex->is_off) {
                return null;
            }

            return [
                'id' => 0,
                'title' => $ex->title ?: 'বিশেষ চেম্বার',
                'start_time' => $ex->start_time,
                'end_time' => $ex->end_time,
                'slot_minutes' => (int) $ex->slot_minutes,
                'max_serial' => (int) $ex->max_serial,
                'is_exception' => true,
            ];
        }

        $w = (int) Carbon::parse($date)->dayOfWeek;
        $row = Schedule::query()
            ->where('doctor_id', $doctorId)
            ->where('day_of_week', $w)
            ->where('is_active', true)
            ->first();

        if (! $row) {
            return null;
        }

        return [
            'id' => $row->id,
            'title' => $row->title,
            'start_time' => $row->start_time,
            'end_time' => $row->end_time,
            'slot_minutes' => (int) $row->slot_minutes,
            'max_serial' => (int) $row->max_serial,
            'is_exception' => false,
        ];
    }

    public function generateSlots(array $schedule, string $date, ?int $doctorId = null): array
    {
        $doctorId = $this->doctorId($doctorId);
        $slot = (int) ($schedule['slot_minutes'] ?: 20);
        $max = (int) ($schedule['max_serial'] ?: 50);
        $delay = $this->currentDelay($date, $doctorId);

        $taken = Appointment::query()
            ->whereDate('appointment_date', $date)
            ->where('doctor_id', $doctorId)
            ->where('status', '!=', 'cancelled')
            ->pluck('status', 'serial_no')
            ->all();

        $slots = [];
        $t = strtotime($schedule['start_time']);
        $endTs = strtotime($schedule['end_time']);
        $serial = 1;
        while ($t < $endTs && $serial <= $max) {
            $orig = date('H:i', $t);
            $adj = date('H:i', $t + ($delay * 60));
            $slots[] = [
                'serial' => $serial,
                'original_time' => $orig,
                'scheduled_time' => $adj,
                'available' => ! isset($taken[$serial]),
                'status' => $taken[$serial] ?? null,
            ];
            $t += $slot * 60;
            $serial++;
        }

        return $slots;
    }

    public function upcomingDates(int $days = 14, ?int $doctorId = null): array
    {
        $doctorId = $this->doctorId($doctorId);
        $out = [];
        for ($i = 0; $i < $days; $i++) {
            $d = Carbon::today()->addDays($i)->toDateString();
            $sch = $this->scheduleForDate($d, $doctorId);
            if (! $sch) {
                continue;
            }
            $slots = $this->generateSlots($sch, $d, $doctorId);
            $free = count(array_filter($slots, fn ($s) => $s['available']));
            $out[] = [
                'date' => $d,
                'label' => bangla_date($d),
                'day' => bangla_day((int) Carbon::parse($d)->dayOfWeek),
                'start' => $sch['start_time'],
                'end' => $sch['end_time'],
                'title' => $sch['title'],
                'total' => count($slots),
                'free' => $free,
            ];
        }

        return $out;
    }
}
