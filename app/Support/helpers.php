<?php

use App\Models\Setting;

function e_bn($n): string
{
    return strtr((string) $n, ['0' => '০', '1' => '১', '2' => '২', '3' => '৩', '4' => '৪', '5' => '৫', '6' => '৬', '7' => '৭', '8' => '৮', '9' => '৯']);
}

function en_digits($n): string
{
    return strtr((string) $n, ['০' => '0', '১' => '1', '২' => '2', '৩' => '3', '৪' => '4', '৫' => '5', '৬' => '6', '৭' => '7', '৮' => '8', '৯' => '9']);
}

function normalize_phone(string $phone): string
{
    $phone = preg_replace('/[^\d]/', '', en_digits($phone)) ?? '';
    if (str_starts_with($phone, '880') && strlen($phone) === 13) {
        $phone = '0'.substr($phone, 3);
    }
    if (strlen($phone) === 10 && str_starts_with($phone, '1')) {
        $phone = '0'.$phone;
    }

    return $phone;
}

function valid_bd_phone(string $phone): bool
{
    return (bool) preg_match('/^01[3-9]\d{8}$/', $phone);
}

function bangla_day(int $w): string
{
    return ['রবিবার', 'সোমবার', 'মঙ্গলবার', 'বুধবার', 'বৃহস্পতিবার', 'শুক্রবার', 'শনিবার'][$w] ?? '';
}

function bangla_date(string $ymd): string
{
    $t = strtotime($ymd);
    if ($t === false) {
        return $ymd;
    }
    $months = ['জানুয়ারি', 'ফেব্রুয়ারি', 'মার্চ', 'এপ্রিল', 'মে', 'জুন', 'জুলাই', 'আগস্ট', 'সেপ্টেম্বর', 'অক্টোবর', 'নভেম্বর', 'ডিসেম্বর'];

    return e_bn(date('j', $t)).' '.$months[(int) date('n', $t) - 1].' '.e_bn(date('Y', $t));
}

function format_time_12(string $hm): string
{
    $t = strtotime($hm);
    if ($t === false) {
        return $hm;
    }
    $h = (int) date('g', $t);
    $hour24 = (int) date('H', $t);
    $m = date('i', $t);
    $ampm = 'সকাল';
    if ($hour24 >= 20 || $hour24 < 6) {
        $ampm = 'রাত';
    } elseif ($hour24 >= 17) {
        $ampm = 'সন্ধ্যা';
    } elseif ($hour24 >= 12) {
        $ampm = 'বিকাল';
    }

    return $ampm.' '.e_bn($h).':'.e_bn($m);
}

function add_minutes(string $hm, int $mins): string
{
    return date('H:i', strtotime($hm.' +'.$mins.' minutes'));
}

function status_bn(string $s): string
{
    return [
        'booked' => 'বুকড',
        'waiting' => 'অপেক্ষমাণ',
        'in_chamber' => 'চেম্বারে',
        'done' => 'সম্পন্ন',
        'cancelled' => 'বাতিল',
        'no_show' => 'আসেননি',
    ][$s] ?? $s;
}

function clinic_setting(string $key, mixed $default = null): mixed
{
    return Setting::getValue($key, $default);
}

function clinic_name(): string
{
    return (string) clinic_setting('clinic_name', config('app.name', 'মেডিসেবা ক্লিনিক'));
}
