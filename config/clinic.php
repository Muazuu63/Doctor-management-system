<?php

return [
    'force_2fa' => env('CLINIC_FORCE_2FA', true),
    'otp_expire_minutes' => (int) env('CLINIC_OTP_EXPIRE_MINUTES', 5),
    'login_max_attempts' => (int) env('CLINIC_LOGIN_MAX_ATTEMPTS', 5),
    'login_lock_minutes' => (int) env('CLINIC_LOGIN_LOCK_MINUTES', 15),
    'roles' => [
        'super_admin' => 'সুপার অ্যাডমিন',
        'admin' => 'অ্যাডমিন',
        'manager' => 'ম্যানেজার',
    ],
];
