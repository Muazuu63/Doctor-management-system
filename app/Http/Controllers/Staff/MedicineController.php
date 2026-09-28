<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\MedicineTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MedicineController extends Controller
{
    public function index(): View
    {
        return view('staff.medicines', [
            'rows' => MedicineTemplate::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'dose' => ['nullable', 'string', 'max:100'],
            'duration' => ['nullable', 'string', 'max:100'],
            'instruction' => ['nullable', 'string', 'max:191'],
        ]);
        MedicineTemplate::create([
            'doctor_id' => Doctor::primary()?->id ?? 1,
            'name' => $data['name'],
            'dose' => $data['dose'] ?? '',
            'duration' => $data['duration'] ?? '',
            'instruction' => $data['instruction'] ?? '',
        ]);

        return redirect()->route('staff.medicines')->with('ok', 'ওষুধ টেমপ্লেট যোগ হয়েছে।');
    }
}
