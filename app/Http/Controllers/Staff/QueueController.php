<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Services\SmsService;
use App\Services\SlotService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QueueController extends Controller
{
    public function __construct(
        protected SlotService $slots,
        protected SmsService $sms,
    ) {}

    public function index(Request $request): View
    {
        $date = $request->query('date', now()->toDateString());
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = now()->toDateString();
        }
        $rows = Appointment::query()
            ->with(['patient', 'prescription'])
            ->whereDate('appointment_date', $date)
            ->orderBy('serial_no')
            ->get();

        return view('staff.queue', [
            'date' => $date,
            'rows' => $rows,
            'delay' => $this->slots->currentDelay($date),
        ]);
    }

    public function update(Request $request, Appointment $appointment): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', 'in:waiting,in_chamber,done,no_show,cancel'],
        ]);
        $map = [
            'waiting' => 'waiting',
            'in_chamber' => 'in_chamber',
            'done' => 'done',
            'no_show' => 'no_show',
            'cancel' => 'cancelled',
        ];
        $appointment->update(['status' => $map[$data['action']]]);
        if ($data['action'] === 'cancel') {
            $appointment->load('patient');
            $this->sms->cancel($appointment, $appointment->patient);
        }

        return redirect()->route('staff.queue', ['date' => $appointment->appointment_date->format('Y-m-d')]);
    }
}
