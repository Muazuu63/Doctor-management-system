<?php
require __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';
$token = $_GET['token'] ?? '';
$stmt = db()->prepare('SELECT a.*, p.name, p.phone, p.pin FROM appointments a JOIN patients p ON p.id=a.patient_id WHERE a.token=?');
$stmt->execute([$token]);
$apt = $stmt->fetch();
if (!$apt) {
    flash('err', 'সিরিয়াল পাওয়া যায়নি।');
    redirect('status.php');
}
$doc = doctor_row();
render_header('সিরিয়াল কনফার্ম', 'book');
?>
<div class="ticket card">
  <p class="eyebrow"><?= e(clinic_name()) ?></p>
  <h1>সিরিয়াল নিশ্চিত</h1>
  <p class="serial-big">সিরিয়াল <?= e(bn_digits($apt['serial_no'])) ?></p>
  <ul class="meta">
    <li><span>রোগী</span><b><?= e($apt['name']) ?></b></li>
    <li><span>ফোন</span><b><?= e($apt['phone']) ?></b></li>
    <li><span>তারিখ</span><b><?= e(bangla_date($apt['appointment_date'])) ?></b></li>
    <li><span>সময়</span><b><?= e(format_time_12($apt['scheduled_time'])) ?></b></li>
    <li><span>ডাক্তার</span><b><?= e($doc['name_bn'] ?: $doc['name']) ?></b></li>
    <li><span>পোর্টাল পিন</span><b><?= e($apt['pin']) ?></b></li>
  </ul>
  <div class="alert ok">এই সময়ে আসুন। অনেক আগে এসে বসে থাকবেন না। দেরি হলে নতুন সময় এসএমএস যাবে।</div>
  <p class="muted">প্রেসক্রিপশন দেখতে পোর্টালে ফোন ও পিন দিন। পিন সংরক্ষণ করুন।</p>
  <div class="cta-row">
    <a class="btn" href="status.php?phone=<?= e(urlencode($apt['phone'])) ?>">স্ট্যাটাস দেখুন</a>
    <button class="btn ghost" type="button" onclick="window.print()">প্রিন্ট</button>
  </div>
</div>
<?php render_footer(); ?>
