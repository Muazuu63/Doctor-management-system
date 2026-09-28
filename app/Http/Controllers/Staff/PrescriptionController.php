<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\MedicineTemplate;
use App\Models\Prescription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrescriptionController extends Controller
{
    public function edit(Appointment $appointment): View
    {
        $appointment->load('patient');
        $existing = Prescription::query()->where('appointment_id', $appointment->id)->latest('id')->first();
        $history = Appointment::query()
            ->with('prescription')
            ->where('patient_id', $appointment->patient_id)
            ->orderByDesc('appointment_date')
            ->get();

        return view('staff.prescribe', [
            'apt' => $appointment,
            'existing' => $existing,
            'templates' => MedicineTemplate::query()->orderBy('name')->get(),
            'history' => $history,
            'meds' => $existing?->medicines ?: [['name' => '', 'dose' => '', 'duration' => '', 'instruction' => '']],
        ]);
    }

    public function store(Request $request, Appointment $appointment): RedirectResponse
    {
        $data = $request->validate([
            'complaints' => ['nullable', 'string'],
            'findings' => ['nullable', 'string'],
            'diagnosis' => ['nullable', 'string', 'max:255'],
            'tests' => ['nullable', 'string'],
            'advice' => ['nullable', 'string'],
            'follow_up_date' => ['nullable', 'string', 'max:40'],
            'med_name' => ['array'],
            'med_name.*' => ['nullable', 'string', 'max:191'],
            'med_dose' => ['array'],
            'med_duration' => ['array'],
            'med_instruction' => ['array'],
        ]);
        $meds = [];
        foreach ($data['med_name'] ?? [] as $i => $n) {
            $n = trim((string) $n);
            if ($n === '') {
                continue;
            }
            $meds[] = [
                'name' => $n,
                'dose' => trim((string) ($request->input('med_dose.'.$i, ''))),
                'duration' => trim((string) ($request->input('med_duration.'.$i, ''))),
                'instruction' => trim((string) ($request->input('med_instruction.'.$i, ''))),
            ];
        }
        $doctor = Doctor::primary();
        $payload = [
            'appointment_id' => $appointment->id,
            'patient_id' => $appointment->patient_id,
            'doctor_id' => $doctor?->id ?? $appointment->doctor_id,
            'complaints' => $data['complaints'] ?? '',
            'findings' => $data['findings'] ?? '',
            'diagnosis' => $data['diagnosis'] ?? '',
            'medicines' => $meds,
            'tests' => $data['tests'] ?? '',
            'advice' => $data['advice'] ?? '',
            'follow_up_date' => $data['follow_up_date'] ?? '',
        ];
        $rx = Prescription::query()->where('appointment_id', $appointment->id)->latest('id')->first();
        if ($rx) {
            $rx->update($payload);
        } else {
            $rx = Prescription::create($payload);
            $appointment->update(['status' => 'done']);
        }

        return redirect()->route('prescription.show', $rx)->with('ok', 'প্রেসক্রিপশন সেভ হয়েছে।');
    }
}
