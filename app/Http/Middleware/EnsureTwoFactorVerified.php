<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->route('staff.login');
        }

        if (! $user->is_active) {
            auth()->logout();
            $request->session()->invalidate();

            return redirect()->route('staff.login')->with('err', 'অ্যাকাউন্ট নিষ্ক্রিয়।');
        }

        $needs = $user->two_factor_enabled || config('clinic.force_2fa');
        if ($needs && ! $request->session()->get('2fa_passed')) {
            return redirect()->route('staff.2fa.challenge');
        }

        return $next($request);
    }
}
