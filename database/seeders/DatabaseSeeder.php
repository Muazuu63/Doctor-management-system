<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\MedicineTemplate;
use App\Models\Schedule;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $super = User::query()->updateOrCreate(
            ['email' => 'super@mediseba.local'],
            [
                'name' => 'Super Admin',
                'name_bn' => 'সুপার অ্যাডমিন',
                'phone' => '01700000001',
                'password' => Hash::make('ChangeMe#Super1'),
                'role' => 'super_admin',
                'is_active' => true,
                'two_factor_enabled' => true,
                'two_factor_method' => 'sms',
            ]
        );

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@mediseba.local'],
            [
                'name' => 'Clinic Admin',
                'name_bn' => 'ক্লিনিক অ্যাডমিন',
                'phone' => '01700000002',
                'password' => Hash::make('ChangeMe#Admin1'),
                'role' => 'admin',
                'is_active' => true,
                'two_factor_enabled' => true,
                'two_factor_method' => 'sms',
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'manager@mediseba.local'],
            [
                'name' => 'Chamber Manager',
                'name_bn' => 'চেম্বার ম্যানেজার',
                'phone' => '01700000003',
                'password' => Hash::make('ChangeMe#Manager1'),
                'role' => 'manager',
                'is_active' => true,
                'two_factor_enabled' => true,
                'two_factor_method' => 'sms',
            ]
        );

        $doctor = Doctor::query()->updateOrCreate(
            ['phone' => '01700000000'],
            [
                'user_id' => $admin->id,
                'name' => 'Dr. Ayesha Rahman',
                'name_bn' => 'ডা. আয়েশা রহমান',
                'degree' => 'MBBS, FCPS (Medicine)',
                'specialty' => 'মেডিসিন বিশেষজ্ঞ',
                'email' => 'doctor@mediseba.local',
                'consultation_fee' => 600,
                'slot_minutes' => 20,
                'bio' => '১২ বছরের অভিজ্ঞতাসম্পন্ন মেডিসিন বিশেষজ্ঞ। রোগীকে সময়মতো দেখা এবং স্পষ্ট প্রেসক্রিপশন দেওয়ার নীতিতে বিশ্বাসী।',
                'is_active' => true,
            ]
        );

        if (Schedule::query()->count() === 0) {
            $days = [
                [6, 'শনিবার চেম্বার', '14:00', '18:00'],
                [0, 'রবিবার চেম্বার', '16:00', '20:00'],
                [1, 'সোমবার চেম্বার', '16:00', '20:00'],
                [2, 'মঙ্গলবার চেম্বার', '16:00', '20:00'],
                [3, 'বুধবার চেম্বার', '16:00', '20:00'],
                [4, 'বৃহস্পতিবার চেম্বার', '16:00', '20:00'],
            ];
            foreach ($days as $d) {
                Schedule::create([
                    'doctor_id' => $doctor->id,
                    'title' => $d[1],
                    'day_of_week' => $d[0],
                    'start_time' => $d[2],
                    'end_time' => $d[3],
                    'slot_minutes' => 20,
                    'max_serial' => 50,
                    'is_active' => true,
                ]);
            }
        }

        if (MedicineTemplate::query()->count() === 0) {
            $meds = [
                ['Napa 500mg', '১ + ১ + ১', '৫ দিন', 'খাওয়ার পর'],
                ['Seclo 20mg', '১ + ০ + ১', '১৪ দিন', 'খাওয়ার আগে'],
                ['Fexo 120mg', '০ + ০ + ১', '৭ দিন', 'রাত্রে'],
                ['Monas 10mg', '০ + ০ + ১', '৩০ দিন', 'রাত্রে'],
                ['ORSaline-N', 'প্রয়োজনমতো', '৩ দিন', 'পানির সাথে'],
                ['Azithro 500mg', '১ + ০ + ০', '৩ দিন', 'খাওয়ার পর'],
            ];
            foreach ($meds as $row) {
                MedicineTemplate::create([
                    'doctor_id' => $doctor->id,
                    'name' => $row[0],
                    'dose' => $row[1],
                    'duration' => $row[2],
                    'instruction' => $row[3],
                ]);
            }
        }

        $defaults = [
            'clinic_name' => 'মেডিসেবা ক্লিনিক',
            'clinic_name_en' => 'MediSeba Clinic',
            'clinic_tagline' => 'সময়মতো সেবা, অপেক্ষা নয়',
            'clinic_address' => 'হাউস ১২, রোড ৭, ধানমন্ডি, ঢাকা',
            'clinic_phone' => '01700000000',
            'clinic_email' => 'info@mediseba.local',
            'slot_minutes' => '20',
            'sms_provider' => 'log',
            'sms_api_url' => '',
            'sms_api_key' => '',
            'sms_sender' => 'MediSeba',
            'sms_http_method' => 'GET',
            'reminder_minutes' => '30',
            'cron_key' => Str::random(24),
        ];
        foreach ($defaults as $k => $v) {
            Setting::query()->firstOrCreate(['skey' => $k], ['svalue' => $v]);
        }
    }
}
