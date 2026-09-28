<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Services\AppointmentService;
use App\Services\SlotService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected SlotService $slots,
        protected AppointmentService $appointments,
    ) {}

    public function index(): View
    {
        $today = now()->toDateString();
        $sch = $this->slots->scheduleForDate($today);
        $apts = Appointment::query()
            ->with('patient')
            ->whereDate('appointment_date', $today)
            ->orderBy('serial_no')
            ->get();
        $counts = ['booked' => 0, 'waiting' => 0, 'in_chamber' => 0, 'done' => 0, 'cancelled' => 0, 'no_show' => 0];
        foreach ($apts as $a) {
            $counts[$a->status] = ($counts[$a->status] ?? 0) + 1;
        }
        $week = Appointment::query()
            ->selectRaw('appointment_date, COUNT(*) as c')
            ->where('status', '!=', 'cancelled')
            ->groupBy('appointment_date')
            ->orderByDesc('appointment_date')
            ->limit(7)
            ->get();

        return view('staff.dashboard', [
            'today' => $today,
            'sch' => $sch,
            'delay' => $this->slots->currentDelay($today),
            'apts' => $apts,
            'counts' => $counts,
            'week' => $week,
        ]);
    }

    public function delay(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'minutes' => ['required', 'integer', 'min:0', 'max:240'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);
        $n = $this->appointments->applyDelay($data['date'], (int) $data['minutes'], $data['reason'] ?? '');

        return redirect()->route('staff.dashboard')->with('ok', e_bn($n).' জন রোগীকে নতুন সময় পাঠানো হয়েছে।');
    }
}
