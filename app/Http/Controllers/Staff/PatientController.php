<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Prescription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PatientController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $query = Patient::query()->orderByDesc('id');
        if ($q !== '') {
            $phone = normalize_phone($q);
            $query->where(function ($w) use ($q, $phone) {
                $w->where('name', 'like', '%'.$q.'%');
                if ($phone) {
                    $w->orWhere('phone', 'like', '%'.$phone.'%');
                }
            });
        }

        return view('staff.patients', [
            'q' => $q,
            'rows' => $query->limit(50)->get(),
        ]);
    }

    public function show(Patient $patient): View
    {
        $hist = Appointment::query()
            ->with('prescription')
            ->where('patient_id', $patient->id)
            ->orderByDesc('appointment_date')
            ->orderByDesc('serial_no')
            ->get();
        $rxs = Prescription::query()
            ->with('appointment')
            ->where('patient_id', $patient->id)
            ->orderByDesc('id')
            ->get();

        return view('staff.patient', compact('patient', 'hist', 'rxs'));
    }

    public function update(Request $request, Patient $patient): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'gender' => ['nullable', 'string', 'max:20'],
            'age' => ['nullable', 'integer', 'min:0', 'max:120'],
            'blood_group' => ['nullable', 'string', 'max:10'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);
        $patient->update($data);

        return redirect()->route('staff.patients.show', $patient)->with('ok', 'রোগীর তথ্য আপডেট হয়েছে।');
    }
}
