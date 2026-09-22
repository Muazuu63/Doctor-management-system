<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';

$key = $_GET['key'] ?? $_POST['key'] ?? '';
$expected = (string) setting('cron_key', '');
if ($expected === '') {
    $expected = substr(hash('sha256', (setting('clinic_phone') ?: 'clinic') . 'cron'), 0, 16);
}
if (!hash_equals($expected, (string) $key)) {
    json_out(['ok' => false, 'error' => 'forbidden'], 403);
}

$minutes = (int) setting('reminder_minutes', 30);
$today = date('Y-m-d');
$from = date('H:i');
$to = date('H:i', time() + $minutes * 60);

$stmt = db()->prepare("SELECT a.*, p.name, p.phone FROM appointments a JOIN patients p ON p.id=a.patient_id
  WHERE a.appointment_date=? AND a.status IN ('booked','waiting') AND a.reminder_sent=0
  AND a.scheduled_time >= ? AND a.scheduled_time <= ?");
$stmt->execute([$today, $from, $to]);
$n = 0;
foreach ($stmt->fetchAll() as $r) {
    sms_reminder($r, ['name' => $r['name'], 'phone' => $r['phone']]);
    $n++;
}
json_out(['ok' => true, 'sent' => $n, 'window' => [$from, $to]]);
