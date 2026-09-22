<?php
require __DIR__ . '/_auth.php';
require_doctor();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('dashboard.php');
}
csrf_check();
$date = $_POST['date'] ?? date('Y-m-d');
$minutes = (int) ($_POST['minutes'] ?? 0);
$reason = trim($_POST['reason'] ?? '');
try {
    $n = apply_delay($date, $minutes, $reason);
    flash('ok', bn_digits($n) . ' জন রোগীকে নতুন সময় পাঠানো হয়েছে।');
} catch (Throwable $e) {
    flash('err', $e->getMessage());
}
redirect('dashboard.php');
