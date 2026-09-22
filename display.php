<?php
require __DIR__ . '/includes/bootstrap.php';
$date = date('Y-m-d');
$stmt = db()->prepare("SELECT a.serial_no, a.scheduled_time, a.status, p.name FROM appointments a JOIN patients p ON p.id=a.patient_id WHERE a.appointment_date=? AND a.status!='cancelled' ORDER BY a.serial_no");
$stmt->execute([$date]);
$rows = $stmt->fetchAll();
$now = null; $next = [];
foreach ($rows as $r) {
    if ($r['status'] === 'in_chamber') {
        $now = $r;
    } elseif (in_array($r['status'], ['booked','waiting'], true) && count($next) < 4) {
        $next[] = $r;
    }
}
$delay = current_delay($date);
$doc = doctor_row();
?>
<!DOCTYPE html>
<html lang="bn">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="refresh" content="15">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ডিসপ্লে | <?= e(clinic_name()) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    body { background: #072821; color: #fff; }
    .disp { min-height: 100vh; padding: 32px; }
    .now { font-size: clamp(42px, 8vw, 96px); margin: 10px 0; color: #e8b86d; }
    .next { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-top: 28px; }
    .next div { background: #0b3d34; padding: 16px; border-radius: 16px; }
  </style>
</head>
<body>
  <div class="disp">
    <p><?= e(clinic_name()) ?> · <?= e($doc['name_bn'] ?: $doc['name']) ?></p>
    <h1>এখন চেম্বারে</h1>
    <?php if ($now): ?>
      <div class="now">সিরিয়াল <?= e(bn_digits($now['serial_no'])) ?></div>
      <p><?= e($now['name']) ?> · <?= e(format_time_12($now['scheduled_time'])) ?></p>
    <?php else: ?>
      <div class="now">—</div>
      <p>অপেক্ষা করুন</p>
    <?php endif; ?>
    <?php if ($delay): ?><p>ডাক্তার <?= e(bn_digits($delay)) ?> মিনিট দেরি করছেন</p><?php endif; ?>
    <h2>পরবর্তী</h2>
    <div class="next">
      <?php foreach ($next as $n): ?>
        <div>
          <b>সিরিয়াল <?= e(bn_digits($n['serial_no'])) ?></b>
          <div><?= e(format_time_12($n['scheduled_time'])) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</body>
</html>
