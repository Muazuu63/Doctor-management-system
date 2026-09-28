<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\Setting;
use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(protected TwoFactorService $twoFactor) {}

    public function index(): View
    {
        return view('staff.settings', [
            'doc' => Doctor::primary(),
            'user' => auth()->user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'clinic_name' => ['required', 'string', 'max:191'],
            'clinic_tagline' => ['nullable', 'string', 'max:191'],
            'clinic_address' => ['nullable', 'string', 'max:255'],
            'clinic_phone' => ['required', 'string', 'max:30'],
            'clinic_email' => ['nullable', 'email', 'max:191'],
            'slot_minutes' => ['required', 'integer', 'min:10', 'max:60'],
            'name' => ['required', 'string', 'max:191'],
            'name_bn' => ['nullable', 'string', 'max:191'],
            'degree' => ['nullable', 'string', 'max:255'],
            'specialty' => ['nullable', 'string', 'max:191'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:191'],
            'consultation_fee' => ['required', 'integer', 'min:0'],
            'doc_slot' => ['required', 'integer', 'min:10', 'max:60'],
            'bio' => ['nullable', 'string'],
            'password' => ['nullable', 'string', 'min:8', 'max:100'],
        ]);
        Setting::setMany([
            'clinic_name' => $data['clinic_name'],
            'clinic_tagline' => $data['clinic_tagline'] ?? '',
            'clinic_address' => $data['clinic_address'] ?? '',
            'clinic_phone' => $data['clinic_phone'],
            'clinic_email' => $data['clinic_email'] ?? '',
            'slot_minutes' => $data['slot_minutes'],
        ]);
        $doc = Doctor::primary();
        if ($doc) {
            $doc->update([
                'name' => $data['name'],
                'name_bn' => $data['name_bn'] ?? '',
                'degree' => $data['degree'] ?? '',
                'specialty' => $data['specialty'] ?? '',
                'phone' => normalize_phone($data['phone']),
                'email' => $data['email'] ?? '',
                'consultation_fee' => $data['consultation_fee'],
                'slot_minutes' => $data['doc_slot'],
                'bio' => $data['bio'] ?? '',
            ]);
        }
        $user = $request->user();
        $updates = [];
        if ($request->filled('password')) {
            $updates['password'] = Hash::make($data['password']);
        }
        if ($updates) {
            $user->update($updates);
        }

        return redirect()->route('staff.settings')->with('ok', 'সেটিংস সেভ হয়েছে।');
    }

    public function security(): View
    {
        $user = auth()->user();
        $pendingSecret = session('pending_totp_secret');

        return view('staff.security', [
            'user' => $user,
            'pendingSecret' => $pendingSecret,
            'otpauth' => $pendingSecret ? $this->twoFactor->totpUri($user, $pendingSecret) : null,
        ]);
    }

    public function updateSecurity(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'two_factor_method' => ['required', 'in:sms,totp'],
            'code' => ['nullable', 'string', 'max:12'],
        ]);
        $user = $request->user();
        if ($data['two_factor_method'] === 'sms') {
            $user->update([
                'two_factor_method' => 'sms',
                'two_factor_enabled' => true,
            ]);

            return redirect()->route('staff.security')->with('ok', 'দুই ধাপ যাচাই এখন এসএমএস OTP।');
        }

        if (! session('pending_totp_secret')) {
            $secret = $this->twoFactor->generateTotpSecret();
            session(['pending_totp_secret' => $secret]);

            return redirect()->route('staff.security')->with('ok', 'Google Authenticator-এ QR/কোড যোগ করে নিচের কোড দিন।');
        }

        $secret = session('pending_totp_secret');
        $this->twoFactor->storeTotpSecret($user, $secret);
        if (! $this->twoFactor->confirmTotp($user->fresh(), (string) ($data['code'] ?? ''))) {
            return back()->with('err', 'Authenticator কোড ভুল। আবার চেষ্টা করুন।');
        }
        session()->forget('pending_totp_secret');

        return redirect()->route('staff.security')->with('ok', 'Google Authenticator চালু হয়েছে।');
    }
}
