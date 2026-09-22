<?php
require __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';
$phone = normalize_phone($_GET['phone'] ?? $_POST['phone'] ?? '');
$rows = [];
$patient = null;
if ($phone && valid_bd_phone($phone)) {
    $stmt = db()->prepare('SELECT * FROM patients WHERE phone=?');
    $stmt->execute([$phone]);
    $patient = $stmt->fetch();
    if ($patient) {
        $stmt = db()->prepare("SELECT * FROM appointments WHERE patient_id=? AND appointment_date >= date('now','localtime') AND status != 'cancelled' ORDER BY appointment_date, serial_no");
        if (setting('db_driver') === 'mysql') {
            $stmt = db()->prepare("SELECT * FROM appointments WHERE patient_id=? AND appointment_date >= CURDATE() AND status != 'cancelled' ORDER BY appointment_date, serial_no");
        }
        $stmt->execute([$patient['id']]);
        $rows = $stmt->fetchAll();
    }
}
$nowServing = null;
$today = date('Y-m-d');
$q = db()->prepare("SELECT serial_no, scheduled_time FROM appointments WHERE appointment_date=? AND status='in_chamber' ORDER BY serial_no LIMIT 1");
$q->execute([$today]);
$nowServing = $q->fetch();
if (!$nowServing) {
    $q = db()->prepare("SELECT serial_no, scheduled_time FROM appointments WHERE appointment_date=? AND status='waiting' ORDER BY serial_no LIMIT 1");
    $q->execute([$today]);
    $nowServing = $q->fetch();
}
$delay = current_delay($today);
render_header('সিরিয়াল স্ট্যাটাস', 'status');
?>
<h1>আমার সিরিয়াল</h1>
<form method="get" class="inline-form card">
  <label>মোবাইল নম্বর<input name="phone" value="<?= e($phone) ?>" placeholder="01XXXXXXXXX" required></label>
  <button class="btn" type="submit">খুঁজুন</button>
</form>

<?php if ($delay): ?>
  <div class="alert warn">আজ ডাক্তার <?= e(bn_digits($delay)) ?> মিনিট দেরি করবেন। আপনার সময় আপডেট হয়েছে।</div>
<?php endif; ?>
<?php if ($nowServing): ?>
  <div class="now-serving">এখন চেম্বারে সিরিয়াল <?= e(bn_digits($nowServing['serial_no'])) ?> · <?= e(format_time_12($nowServing['scheduled_time'])) ?></div>
<?php endif; ?>

<?php if ($phone && !$patient): ?>
  <div class="alert err">এই নম্বরে কোনো রোগী পাওয়া যায়নি।</div>
<?php endif; ?>

<?php foreach ($rows as $r): ?>
  <div class="card ticket-mini">
    <div>
      <p class="serial-big">সিরিয়াল <?= e(bn_digits($r['serial_no'])) ?></p>
      <p><?= e(bangla_date($r['appointment_date'])) ?> · <?= e(format_time_12($r['scheduled_time'])) ?></p>
      <p>স্ট্যাটাস: <b><?= e(status_bn($r['status'])) ?></b></p>
    </div>
    <a class="btn ghost" href="ticket.php?token=<?= e($r['token']) ?>">টিকিট</a>
  </div>
<?php endforeach; ?>
<?php render_footer(); ?>
