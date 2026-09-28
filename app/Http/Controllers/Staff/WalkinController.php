<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Services\AppointmentService;
use App\Services\SlotService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WalkinController extends Controller
{
    public function __construct(
        protected SlotService $slots,
        protected AppointmentService $appointments,
    ) {}

    public function create(Request $request): View
    {
        $date = $request->query('date', now()->toDateString());
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = now()->toDateString();
        }
        $sch = $this->slots->scheduleForDate($date);

        return view('staff.walkin', [
            'date' => $date,
            'sch' => $sch,
            'slots' => $sch ? $this->slots->generateSlots($sch, $date) : [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:20'],
            'age' => ['nullable', 'integer', 'min:0', 'max:120'],
            'gender' => ['nullable', 'string', 'max:20'],
            'serial' => ['nullable', 'integer', 'min:1'],
            'date' => ['required', 'date_format:Y-m-d'],
        ]);
        $phone = normalize_phone($data['phone']);
        if (! valid_bd_phone($phone)) {
            return back()->with('err', 'সঠিক নাম ও মোবাইল দিন।')->withInput();
        }
        try {
            $patient = $this->appointments->findOrCreatePatient($data['name'], $phone, [
                'age' => $data['age'] ?? 0,
                'gender' => $data['gender'] ?? '',
            ]);
            $apt = $this->appointments->book(
                $patient,
                $data['date'],
                ! empty($data['serial']) ? (int) $data['serial'] : null,
                'walk-in'
            );

            return redirect()
                ->route('staff.queue', ['date' => $apt->appointment_date->format('Y-m-d')])
                ->with('ok', 'সিরিয়াল '.e_bn($apt->serial_no).' · সময় '.format_time_12($apt->scheduled_time));
        } catch (\Throwable $e) {
            return redirect()->route('staff.walkin', ['date' => $data['date']])->with('err', $e->getMessage());
        }
    }
}
