<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\Schedule;
use App\Models\ScheduleException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function index(Request $request): View
    {
        $edit = null;
        if ($request->filled('edit')) {
            $edit = Schedule::query()->find((int) $request->query('edit'));
        }

        return view('staff.schedule', [
            'rows' => Schedule::query()->orderBy('day_of_week')->get(),
            'ex' => ScheduleException::query()->orderByDesc('except_date')->limit(30)->get(),
            'edit' => $edit,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'id' => ['nullable', 'integer'],
            'title' => ['required', 'string', 'max:191'],
            'day_of_week' => ['required', 'integer', 'min:0', 'max:6'],
            'start_time' => ['required', 'regex:/^\d{2}:\d{2}/'],
            'end_time' => ['required', 'regex:/^\d{2}:\d{2}/'],
            'slot_minutes' => ['required', 'integer', 'min:10', 'max:60'],
            'max_serial' => ['required', 'integer', 'min:1', 'max:200'],
            'is_active' => ['nullable'],
        ]);
        $payload = [
            'doctor_id' => Doctor::primary()?->id ?? 1,
            'title' => $data['title'],
            'day_of_week' => $data['day_of_week'],
            'start_time' => substr($data['start_time'], 0, 5),
            'end_time' => substr($data['end_time'], 0, 5),
            'slot_minutes' => $data['slot_minutes'],
            'max_serial' => $data['max_serial'],
            'is_active' => $request->boolean('is_active'),
        ];
        if (! empty($data['id'])) {
            Schedule::query()->where('id', $data['id'])->update($payload);
        } else {
            Schedule::create($payload);
        }

        return redirect()->route('staff.schedule')->with('ok', 'সময়সূচি সেভ হয়েছে। নতুন বুকিং এই সময় অনুযায়ী হবে।');
    }

    public function storeException(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'except_date' => ['required', 'date_format:Y-m-d'],
            'title' => ['nullable', 'string', 'max:191'],
            'start_time' => ['nullable', 'regex:/^\d{2}:\d{2}/'],
            'end_time' => ['nullable', 'regex:/^\d{2}:\d{2}/'],
            'slot_minutes' => ['nullable', 'integer', 'min:10', 'max:60'],
            'max_serial' => ['nullable', 'integer', 'min:1', 'max:200'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
        ScheduleException::updateOrCreate(
            [
                'doctor_id' => Doctor::primary()?->id ?? 1,
                'except_date' => $data['except_date'],
            ],
            [
                'is_off' => $request->boolean('is_off'),
                'title' => $data['title'] ?? '',
                'start_time' => isset($data['start_time']) ? substr($data['start_time'], 0, 5) : null,
                'end_time' => isset($data['end_time']) ? substr($data['end_time'], 0, 5) : null,
                'slot_minutes' => $data['slot_minutes'] ?? 20,
                'max_serial' => $data['max_serial'] ?? 50,
                'note' => $data['note'] ?? '',
            ]
        );

        return redirect()->route('staff.schedule')->with('ok', 'বিশেষ দিন সেভ হয়েছে।');
    }

    public function destroyException(ScheduleException $exception): RedirectResponse
    {
        $exception->delete();

        return redirect()->route('staff.schedule')->with('ok', 'বিশেষ দিন মুছে ফেলা হয়েছে।');
    }
}
