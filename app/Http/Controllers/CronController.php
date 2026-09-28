<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Services\SmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CronController extends Controller
{
    public function remind(Request $request, SmsService $sms): JsonResponse
    {
        $expected = (string) clinic_setting('cron_key', '');
        $key = (string) $request->query('key', $request->input('key', ''));
        if ($expected === '' || ! hash_equals($expected, $key)) {
            abort(403, 'forbidden');
        }
        $minutes = (int) clinic_setting('reminder_minutes', 30);
        $today = now()->toDateString();
        $from = now()->format('H:i');
        $to = now()->addMinutes($minutes)->format('H:i');

        $rows = Appointment::query()
            ->with('patient')
            ->whereDate('appointment_date', $today)
            ->whereIn('status', ['booked', 'waiting'])
            ->where('reminder_sent', false)
            ->where('scheduled_time', '>=', $from)
            ->where('scheduled_time', '<=', $to)
            ->get();

        $n = 0;
        foreach ($rows as $r) {
            $sms->reminder($r, $r->patient);
            $n++;
        }

        return response()->json(['ok' => true, 'sent' => $n, 'window' => [$from, $to]]);
    }
}
