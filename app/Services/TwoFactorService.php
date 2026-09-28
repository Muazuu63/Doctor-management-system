<?php

namespace App\Services;

use App\Models\LoginOtp;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;

class TwoFactorService
{
    public function __construct(protected SmsService $sms) {}

    public function issueSmsOtp(User $user, ?string $ip = null): string
    {
        LoginOtp::query()
            ->where('user_id', $user->id)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        LoginOtp::create([
            'user_id' => $user->id,
            'code_hash' => Hash::make($code),
            'channel' => 'sms',
            'expires_at' => now()->addMinutes((int) config('clinic.otp_expire_minutes', 5)),
            'ip_address' => $ip,
        ]);

        $this->sms->otp($user->phone, $code);

        return $code;
    }

    public function verifySmsOtp(User $user, string $code): bool
    {
        $otp = LoginOtp::query()
            ->where('user_id', $user->id)
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();

        if (! $otp || ! $otp->isValid()) {
            return false;
        }

        $otp->increment('attempts');
        if (! Hash::check($code, $otp->code_hash)) {
            return false;
        }

        $otp->update(['consumed_at' => now()]);

        return true;
    }

    public function generateTotpSecret(): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < 32; $i++) {
            $secret .= $chars[random_int(0, 31)];
        }

        return $secret;
    }

    public function storeTotpSecret(User $user, string $secret): void
    {
        $user->update([
            'two_factor_secret' => Crypt::encryptString($secret),
            'two_factor_method' => 'totp',
            'two_factor_enabled' => true,
        ]);
    }

    public function totpSecret(User $user): ?string
    {
        if (! $user->two_factor_secret) {
            return null;
        }

        try {
            return Crypt::decryptString($user->two_factor_secret);
        } catch (\Throwable) {
            return null;
        }
    }

    public function totpUri(User $user, string $secret): string
    {
        $issuer = rawurlencode(clinic_name());
        $account = rawurlencode($user->email ?: $user->phone);

        return "otpauth://totp/{$issuer}:{$account}?secret={$secret}&issuer={$issuer}&digits=6&period=30";
    }

    public function verifyTotp(User $user, string $code): bool
    {
        $secret = $this->totpSecret($user);
        if (! $secret) {
            return false;
        }
        $code = preg_replace('/\s+/', '', $code) ?? '';
        if (! preg_match('/^\d{6}$/', $code)) {
            return false;
        }
        $now = time();
        for ($i = -1; $i <= 1; $i++) {
            if (hash_equals($this->hotp($secret, intdiv($now, 30) + $i), $code)) {
                return true;
            }
        }

        return false;
    }

    public function confirmTotp(User $user, string $code): bool
    {
        if (! $this->verifyTotp($user, $code)) {
            return false;
        }
        $user->update(['two_factor_confirmed_at' => now()]);

        return true;
    }

    protected function hotp(string $secret, int $counter): string
    {
        $key = $this->base32Decode($secret);
        $bin = pack('N*', 0).pack('N*', $counter);
        $hash = hash_hmac('sha1', $bin, $key, true);
        $offset = ord($hash[19]) & 0x0F;
        $truncated = (
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        ) % 1000000;

        return str_pad((string) $truncated, 6, '0', STR_PAD_LEFT);
    }

    protected function base32Decode(string $secret): string
    {
        $secret = strtoupper($secret);
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $buffer = 0;
        $bits = 0;
        $out = '';
        $len = strlen($secret);
        for ($i = 0; $i < $len; $i++) {
            $val = strpos($alphabet, $secret[$i]);
            if ($val === false) {
                continue;
            }
            $buffer = ($buffer << 5) | $val;
            $bits += 5;
            if ($bits >= 8) {
                $bits -= 8;
                $out .= chr(($buffer >> $bits) & 0xFF);
            }
        }

        return $out;
    }
}
