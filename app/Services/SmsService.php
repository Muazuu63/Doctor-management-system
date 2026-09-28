<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\SmsLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    public function send(string $phone, string $message, string $type = 'info', int $appointmentId = 0): bool
    {
        $phone = normalize_phone($phone);
        $provider = (string) clinic_setting('sms_provider', 'log');
        $status = 'logged';

        if ($provider === 'http') {
            $status = $this->sendHttp($phone, $message);
        }

        SmsLog::create([
            'phone' => $phone,
            'message' => $message,
            'type' => $type,
            'status' => $status,
            'appointment_id' => $appointmentId,
        ]);

        return $status !== 'failed';
    }

    public function booking(Appointment $apt, Patient $patient, Doctor $doctor): void
    {
        $msg = sprintf(
            "%s\nপ্রিয় %s, আপনার সিরিয়াল %s। তারিখ: %s, সময়: %s। %s\nসময়মতো আসুন। বেশি আগে এসে অপেক্ষা করবেন না। হটলাইন: %s",
            clinic_name(),
            $patient->name,
            e_bn($apt->serial_no),
            bangla_date($apt->appointment_date->format('Y-m-d')),
            format_time_12($apt->scheduled_time),
            $doctor->displayName(),
            clinic_setting('clinic_phone')
        );
        $this->send($patient->phone, $msg, 'booking', $apt->id);
        $apt->update(['sms_sent' => true]);
    }

    public function reschedule(Appointment $apt, Patient $patient, int $delay): void
    {
        $msg = sprintf(
            "%s\nপ্রিয় %s, ডাক্তার %s মিনিট দেরি করবেন। আপনার নতুন সময়: %s (সিরিয়াল %s, %s)। আগের সময়ে আসবেন না।",
            clinic_name(),
            $patient->name,
            e_bn($delay),
            format_time_12($apt->scheduled_time),
            e_bn($apt->serial_no),
            bangla_date($apt->appointment_date->format('Y-m-d'))
        );
        $this->send($patient->phone, $msg, 'reschedule', $apt->id);
    }

    public function reminder(Appointment $apt, Patient $patient): void
    {
        $msg = sprintf(
            "%s\nরিমাইন্ডার: %s, আপনার পালা প্রায়। সিরিয়াল %s, সময় %s। এখন রওনা দিন।",
            clinic_name(),
            $patient->name,
            e_bn($apt->serial_no),
            format_time_12($apt->scheduled_time)
        );
        $this->send($patient->phone, $msg, 'reminder', $apt->id);
        $apt->update(['reminder_sent' => true]);
    }

    public function cancel(Appointment $apt, Patient $patient): void
    {
        $msg = sprintf(
            "%s\nপ্রিয় %s, আপনার %s তারিখের সিরিয়াল %s বাতিল হয়েছে। নতুন সিরিয়াল নিতে ওয়েবসাইটে আসুন।",
            clinic_name(),
            $patient->name,
            bangla_date($apt->appointment_date->format('Y-m-d')),
            e_bn($apt->serial_no)
        );
        $this->send($patient->phone, $msg, 'cancel', $apt->id);
    }

    public function otp(string $phone, string $code): void
    {
        $msg = sprintf(
            "%s\nআপনার লগইন কোড: %s। %s মিনিটের মধ্যে ব্যবহার করুন। কাউকে এই কোড দেবেন না।",
            clinic_name(),
            $code,
            e_bn((int) config('clinic.otp_expire_minutes', 5))
        );
        $this->send($phone, $msg, 'otp');
    }

    protected function sendHttp(string $phone, string $message): string
    {
        $url = (string) clinic_setting('sms_api_url', '');
        $key = (string) clinic_setting('sms_api_key', '');
        $sender = (string) clinic_setting('sms_sender', 'MediSeba');
        $method = strtoupper((string) clinic_setting('sms_http_method', 'GET'));

        if ($url === '') {
            return 'failed';
        }

        $payload = [
            'api_key' => $key,
            'senderid' => $sender,
            'number' => $phone,
            'message' => $message,
            'type' => 'text',
        ];

        try {
            $request = Http::timeout(12)->withHeaders(['Accept' => 'application/json']);
            $response = $method === 'POST'
                ? $request->asForm()->post($url, $payload)
                : $request->get($url, $payload);

            return $response->successful() ? 'sent' : 'failed';
        } catch (\Throwable $e) {
            Log::warning('SMS send failed', ['error' => $e->getMessage()]);

            return 'failed';
        }
    }
}
