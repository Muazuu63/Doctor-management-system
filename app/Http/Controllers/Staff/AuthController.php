<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(protected TwoFactorService $twoFactor) {}

    public function showLogin(): View
    {
        return view('staff.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'login' => ['required', 'string', 'max:80'],
            'password' => ['required', 'string', 'max:200'],
        ]);

        $key = 'login:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, (int) config('clinic.login_max_attempts', 5))) {
            $sec = RateLimiter::availableIn($key);

            return back()->with('err', 'অনেকবার ভুল হয়েছে। '.e_bn($sec).' সেকেন্ড পর চেষ্টা করুন।')->onlyInput('login');
        }

        $login = trim($data['login']);
        $phone = normalize_phone($login);
        $user = valid_bd_phone($phone)
            ? User::query()->where('phone', $phone)->first()
            : User::query()->where('email', $login)->first();

        if (! $user || ! $user->is_active || $user->isLocked() || ! Auth::getProvider()->validateCredentials($user, ['password' => $data['password']])) {
            RateLimiter::hit($key, (int) config('clinic.login_lock_minutes', 15) * 60);
            if ($user) {
                $user->increment('failed_login_attempts');
                if ($user->failed_login_attempts >= (int) config('clinic.login_max_attempts', 5)) {
                    $user->update(['locked_until' => now()->addMinutes((int) config('clinic.login_lock_minutes', 15))]);
                }
            }
            ActivityLog::record('guest', 'login_failed', $login);

            return back()->with('err', 'ফোন/ইমেইল বা পাসওয়ার্ড ভুল।')->onlyInput('login');
        }

        RateLimiter::clear($key);
        $user->update(['failed_login_attempts' => 0, 'locked_until' => null]);

        Auth::login($user, (bool) $request->boolean('remember'));
        $request->session()->regenerate();
        $request->session()->forget('2fa_passed');

        $needs = $user->two_factor_enabled || config('clinic.force_2fa');
        if ($needs) {
            if ($user->two_factor_method === 'sms' || ! $user->two_factor_confirmed_at) {
                $code = $this->twoFactor->issueSmsOtp($user, $request->ip());
                if (config('app.debug') && clinic_setting('sms_provider', 'log') === 'log') {
                    $request->session()->flash('ok', 'টেস্ট OTP: '.$code);
                }
            }

            return redirect()->route('staff.2fa.challenge');
        }

        $this->markLogin($request, $user);

        return redirect()->intended(route('staff.dashboard'));
    }

    public function showTwoFactor(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->route('staff.login');
        }
        if ($request->session()->get('2fa_passed')) {
            return redirect()->route('staff.dashboard');
        }

        return view('staff.auth.two-factor', [
            'method' => $user->two_factor_method === 'totp' && $user->two_factor_confirmed_at ? 'totp' : 'sms',
        ]);
    }

    public function verifyTwoFactor(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'max:12'],
        ]);
        $user = $request->user();
        $code = preg_replace('/\s+/', '', (string) $request->input('code')) ?? '';

        $ok = false;
        if ($user->two_factor_method === 'totp' && $user->two_factor_confirmed_at) {
            $ok = $this->twoFactor->verifyTotp($user, $code);
        } else {
            $ok = $this->twoFactor->verifySmsOtp($user, $code);
        }

        if (! $ok) {
            return back()->with('err', 'কোড ভুল বা মেয়াদ শেষ।');
        }

        $request->session()->put('2fa_passed', true);
        $this->markLogin($request, $user);

        return redirect()->intended(route('staff.dashboard'));
    }

    public function resendOtp(Request $request): RedirectResponse
    {
        $key = 'otp-resend:'.$request->user()->id;
        if (RateLimiter::tooManyAttempts($key, 3)) {
            return back()->with('err', 'অপেক্ষা করুন, পরে আবার কোড চান।');
        }
        RateLimiter::hit($key, 60);
        $code = $this->twoFactor->issueSmsOtp($request->user(), $request->ip());
        $msg = 'নতুন কোড পাঠানো হয়েছে।';
        if (config('app.debug') && clinic_setting('sms_provider', 'log') === 'log') {
            $msg .= ' টেস্ট OTP: '.$code;
        }

        return back()->with('ok', $msg);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('staff.login');
    }

    protected function markLogin(Request $request, User $user): void
    {
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);
        ActivityLog::record($user->role, 'login', $user->phone);
    }
}
