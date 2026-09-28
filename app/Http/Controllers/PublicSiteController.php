<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Schedule;
use App\Services\AppointmentService;
use App\Services\SlotService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicSiteController extends Controller
{
    public function __construct(
        protected SlotService $slots,
        protected AppointmentService $appointments,
    ) {}

    public function home(): View
    {
        $doc = Doctor::primary();
        $today = now()->toDateString();
        $todaySch = $this->slots->scheduleForDate($today);
        $todaySlots = $todaySch ? $this->slots->generateSlots($todaySch, $today) : [];
        $free = count(array_filter($todaySlots, fn ($s) => $s['available']));
        $booked = count($todaySlots) - $free;

        return view('public.home', [
            'doc' => $doc,
            'dates' => $this->slots->upcomingDates(10),
            'todaySch' => $todaySch,
            'free' => $free,
            'booked' => $booked,
            'delay' => $this->slots->currentDelay($today),
        ]);
    }

    public function about(): View
    {
        return view('public.about', [
            'doc' => Doctor::primary(),
            'schedules' => Schedule::query()->where('is_active', true)->orderBy('day_of_week')->get(),
        ]);
    }

    public function book(Request $request): View
    {
        $date = $request->query('date', now()->toDateString());
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = now()->toDateString();
        }
        $sch = $this->slots->scheduleForDate($date);

        return view('public.book', [
            'date' => $date,
            'dates' => $this->slots->upcomingDates(14),
            'sch' => $sch,
            'slots' => $sch ? $this->slots->generateSlots($sch, $date) : [],
            'delay' => $this->slots->currentDelay($date),
        ]);
    }

    public function storeBooking(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:20'],
            'age' => ['nullable', 'integer', 'min:0', 'max:120'],
            'gender' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:255'],
            'serial' => ['required', 'integer', 'min:1'],
            'date' => ['required', 'date_format:Y-m-d'],
        ]);
        $phone = normalize_phone($data['phone']);
        if (! valid_bd_phone($phone)) {
            return back()->with('err', 'সঠিক নাম ও বাংলাদেশি মোবাইল নম্বর দিন (01XXXXXXXXX)।')->withInput();
        }

        try {
            $patient = $this->appointments->findOrCreatePatient($data['name'], $phone, [
                'age' => $data['age'] ?? 0,
                'gender' => $data['gender'] ?? '',
            ]);
            $apt = $this->appointments->book($patient, $data['date'], (int) $data['serial'], $data['notes'] ?? '');

            return redirect()->route('ticket', ['token' => $apt->token]);
        } catch (\Throwable $e) {
            return redirect()->route('book', ['date' => $data['date']])->with('err', $e->getMessage());
        }
    }

    public function ticket(string $token): View|RedirectResponse
    {
        $apt = Appointment::query()->with('patient')->where('token', $token)->first();
        if (! $apt) {
            return redirect()->route('status')->with('err', 'সিরিয়াল পাওয়া যায়নি।');
        }

        return view('public.ticket', [
            'apt' => $apt,
            'doc' => Doctor::primary(),
        ]);
    }

    public function status(Request $request): View
    {
        $phone = normalize_phone((string) $request->input('phone', ''));
        $rows = collect();
        $patient = null;
        if ($phone && valid_bd_phone($phone)) {
            $patient = Patient::query()->where('phone', $phone)->first();
            if ($patient) {
                $rows = Appointment::query()
                    ->where('patient_id', $patient->id)
                    ->whereDate('appointment_date', '>=', now()->toDateString())
                    ->where('status', '!=', 'cancelled')
                    ->orderBy('appointment_date')
                    ->orderBy('serial_no')
                    ->get();
            }
        }
        $today = now()->toDateString();
        $nowServing = Appointment::query()
            ->whereDate('appointment_date', $today)
            ->where('status', 'in_chamber')
            ->orderBy('serial_no')
            ->first();
        if (! $nowServing) {
            $nowServing = Appointment::query()
                ->whereDate('appointment_date', $today)
                ->where('status', 'waiting')
                ->orderBy('serial_no')
                ->first();
        }

        return view('public.status', [
            'phone' => $phone,
            'patient' => $patient,
            'rows' => $rows,
            'nowServing' => $nowServing,
            'delay' => $this->slots->currentDelay($today),
        ]);
    }

    public function portal(Request $request): View
    {
        $patient = null;
        $records = collect();
        if ($request->session()->get('patient_id')) {
            $patient = Patient::query()->find($request->session()->get('patient_id'));
            if ($patient) {
                $records = Prescription::query()
                    ->with('appointment')
                    ->where('patient_id', $patient->id)
                    ->orderByDesc('id')
                    ->get();
            }
        }

        return view('public.portal', compact('patient', 'records'));
    }

    public function portalLogin(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string'],
            'pin' => ['required', 'string', 'max:10'],
        ]);
        $phone = normalize_phone($data['phone']);
        $patient = Patient::query()->where('phone', $phone)->where('pin', $data['pin'])->first();
        if (! $patient) {
            return back()->with('err', 'ফোন বা পিন ভুল।');
        }
        $request->session()->put('patient_id', $patient->id);

        return redirect()->route('portal');
    }

    public function portalLogout(Request $request): RedirectResponse
    {
        $request->session()->forget('patient_id');

        return redirect()->route('portal');
    }

    public function display(): View
    {
        $date = now()->toDateString();
        $rows = Appointment::query()
            ->with('patient')
            ->whereDate('appointment_date', $date)
            ->where('status', '!=', 'cancelled')
            ->orderBy('serial_no')
            ->get();
        $now = $rows->firstWhere('status', 'in_chamber');
        $next = $rows->whereIn('status', ['booked', 'waiting'])->take(4);

        return view('public.display', [
            'now' => $now,
            'next' => $next,
            'delay' => $this->slots->currentDelay($date),
            'doc' => Doctor::primary(),
        ]);
    }

    public function prescription(Request $request, Prescription $prescription): View
    {
        $ok = $request->user()
            || ((int) $request->session()->get('patient_id') === (int) $prescription->patient_id);
        abort_unless($ok, 403, 'Unauthorized');

        $prescription->load(['patient', 'appointment', 'doctor']);

        return view('public.prescription', [
            'rx' => $prescription,
            'doc' => $prescription->doctor ?: Doctor::primary(),
            'meds' => $prescription->medicines ?: [],
        ]);
    }
}
