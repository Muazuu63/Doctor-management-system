<?php
declare(strict_types=1);

function send_sms(string $phone, string $message, string $type = 'info', int $appointmentId = 0): bool
{
    $phone = normalize_phone($phone);
    $provider = (string) setting('sms_provider', 'log');
    $status = 'logged';

    if ($provider === 'http') {
        $url = (string) setting('sms_api_url', '');
        $key = (string) setting('sms_api_key', '');
        $sender = (string) setting('sms_sender', 'MediSeba');
        $method = strtoupper((string) setting('sms_http_method', 'GET'));
        if ($url) {
            $payload = [
                'api_key' => $key,
                'senderid' => $sender,
                'number' => $phone,
                'message' => $message,
                'type' => 'text',
            ];
            try {
                if ($method === 'POST') {
                    $ch = curl_init($url);
                    curl_setopt_array($ch, [
                        CURLOPT_POST => true,
                        CURLOPT_POSTFIELDS => http_build_query($payload),
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_TIMEOUT => 12,
                    ]);
                    $res = curl_exec($ch);
                    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);
                } else {
                    $full = $url . (str_contains($url, '?') ? '&' : '?') . http_build_query($payload);
                    $res = @file_get_contents($full);
                    $code = $res !== false ? 200 : 0;
                }
                $status = ($code >= 200 && $code < 300) ? 'sent' : 'failed';
            } catch (Throwable $e) {
                $status = 'failed';
            }
        }
    }

    db()->prepare('INSERT INTO sms_logs (phone, message, type, status, appointment_id) VALUES (?,?,?,?,?)')
        ->execute([$phone, $message, $type, $status, $appointmentId]);
    return $status !== 'failed';
}

function sms_booking(array $apt, array $patient, array $doctor): void
{
    $msg = sprintf(
        "%s\nপ্রিয় %s, আপনার সিরিয়াল %s। তারিখ: %s, সময়: %s। ডা. %s\nসময়মতো আসুন। বেশি আগে এসে অপেক্ষা করবেন না। হটলাইন: %s",
        clinic_name(),
        $patient['name'],
        bn_digits($apt['serial_no']),
        bangla_date($apt['appointment_date']),
        format_time_12($apt['scheduled_time']),
        $doctor['name_bn'] ?: $doctor['name'],
        setting('clinic_phone')
    );
    send_sms($patient['phone'], $msg, 'booking', (int) $apt['id']);
    db()->prepare('UPDATE appointments SET sms_sent = 1 WHERE id = ?')->execute([$apt['id']]);
}

function sms_reschedule(array $apt, array $patient, int $delay): void
{
    $msg = sprintf(
        "%s\nপ্রিয় %s, ডাক্তার %s মিনিট দেরি করবেন। আপনার নতুন সময়: %s (সিরিয়াল %s, %s)। আগের সময়ে আসবেন না।",
        clinic_name(),
        $patient['name'],
        bn_digits($delay),
        format_time_12($apt['scheduled_time']),
        bn_digits($apt['serial_no']),
        bangla_date($apt['appointment_date'])
    );
    send_sms($patient['phone'], $msg, 'reschedule', (int) $apt['id']);
}

function sms_reminder(array $apt, array $patient): void
{
    $msg = sprintf(
        "%s\nরিমাইন্ডার: %s, আপনার পালা প্রায়। সিরিয়াল %s, সময় %s। এখন রওনা দিন।",
        clinic_name(),
        $patient['name'],
        bn_digits($apt['serial_no']),
        format_time_12($apt['scheduled_time'])
    );
    send_sms($patient['phone'], $msg, 'reminder', (int) $apt['id']);
    db()->prepare('UPDATE appointments SET reminder_sent = 1 WHERE id = ?')->execute([$apt['id']]);
}

function sms_cancel(array $apt, array $patient): void
{
    $msg = sprintf(
        "%s\nপ্রিয় %s, আপনার %s তারিখের সিরিয়াল %s বাতিল হয়েছে। নতুন সিরিয়াল নিতে ওয়েবসাইটে আসুন।",
        clinic_name(),
        $patient['name'],
        bangla_date($apt['appointment_date']),
        bn_digits($apt['serial_no'])
    );
    send_sms($patient['phone'], $msg, 'cancel', (int) $apt['id']);
}
