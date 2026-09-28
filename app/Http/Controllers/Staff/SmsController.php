<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\SmsLog;
use App\Services\SmsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SmsController extends Controller
{
    public function __construct(protected SmsService $sms) {}

    public function index(): View
    {
        return view('staff.sms', [
            'logs' => SmsLog::query()->orderByDesc('id')->limit(40)->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'sms_provider' => ['required', 'in:log,http'],
            'sms_api_url' => ['nullable', 'string', 'max:255'],
            'sms_api_key' => ['nullable', 'string', 'max:255'],
            'sms_sender' => ['nullable', 'string', 'max:40'],
            'sms_http_method' => ['required', 'in:GET,POST'],
            'reminder_minutes' => ['required', 'integer', 'min:5', 'max:180'],
        ]);
        Setting::setMany($data);

        return redirect()->route('staff.sms')->with('ok', 'এসএমএস সেটিংস সেভ হয়েছে।');
    }

    public function test(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'test_phone' => ['required', 'string', 'max:20'],
        ]);
        $phone = normalize_phone($data['test_phone']);
        if (! valid_bd_phone($phone)) {
            return back()->with('err', 'সঠিক মোবাইল নম্বর দিন।');
        }
        $this->sms->send($phone, 'টেস্ট মেসেজ: '.clinic_name().' সিরিয়াল সিস্টেম চালু আছে।', 'test');

        return redirect()->route('staff.sms')->with('ok', 'টেস্ট মেসেজ লগ/পাঠানো হয়েছে।');
    }
}
