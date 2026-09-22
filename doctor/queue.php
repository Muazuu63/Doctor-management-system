<?php
require __DIR__ . '/_auth.php';
$doc = require_doctor();
$date = $_GET['date'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $date = date('Y-m-d');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int) ($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $map = [
        'waiting' => 'waiting',
        'in_chamber' => 'in_chamber',
        'done' => 'done',
        'no_show' => 'no_show',
        'cancel' => 'cancelled',
    ];
    if (isset($map[$action]) && $id) {
        db()->prepare('UPDATE appointments SET status=? WHERE id=?')->execute([$map[$action], $id]);
        if ($action === 'cancel') {
            $stmt = db()->prepare('SELECT a.*, p.name, p.phone FROM appointments a JOIN patients p ON p.id=a.patient_id WHERE a.id=?');
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if ($row) {
                sms_cancel($row, ['name'=>$row['name'],'phone'=>$row['phone']]);
            }
        }
    }
    redirect('queue.php?date=' . urlencode($date));
}
$stmt = db()->prepare("SELECT a.*, p.name, p.phone, p.age, p.gender, p.id AS pid,
  (SELECT COUNT(*) FROM prescriptions WHERE appointment_id=a.id) AS has_rx
  FROM appointments a JOIN patients p ON p.id=a.patient_id
  WHERE a.appointment_date=? ORDER BY a.serial_no");
$stmt->execute([$date]);
$rows = $stmt->fetchAll();
$delay = current_delay($date);
render_header('আজকের কিউ', 'queue', true);
?>
<div class="toolbar">
  <h1>কিউ · <?= e(bangla_date($date)) ?></h1>
  <form method="get"><input type="date" name="date" value="<?= e($date) ?>" onchange="this.form.submit()"></form>
  <a class="btn ghost" href="walkin.php?date=<?= e($date) ?>">ওয়াক-ইন</a>
  <a class="btn ghost" href="../display.php" target="_blank">ওয়েটিং ডিসপ্লে</a>
</div>
<?php if ($delay): ?><div class="alert warn">দেরি <?= e(bn_digits($delay)) ?> মিনিট প্রয়োগ করা আছে।</div><?php endif; ?>

<div class="table-wrap">
<table class="table">
  <tr>
    <th>#</th><th>সময়</th><th>রোগী</th><th>ফোন</th><th>স্ট্যাটাস</th><th>অ্যাকশন</th>
  </tr>
  <?php foreach ($rows as $r): ?>
    <tr class="st-<?= e($r['status']) ?>">
      <td><?= e(bn_digits($r['serial_no'])) ?></td>
      <td><?= e(format_time_12($r['scheduled_time'])) ?></td>
      <td>
        <a href="patient.php?id=<?= (int)$r['pid'] ?>"><?= e($r['name']) ?></a>
        <small><?= e($r['gender']) ?> <?= $r['age']? e(bn_digits($r['age'])).' বছর':'' ?></small>
      </td>
      <td><?= e($r['phone']) ?></td>
      <td><?= e(status_bn($r['status'])) ?></td>
      <td class="acts">
        <form method="post">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
          <?php if ($r['status']==='booked'): ?>
            <button name="action" value="waiting">চেক-ইন</button>
          <?php endif; ?>
          <?php if (in_array($r['status'], ['booked','waiting'], true)): ?>
            <button name="action" value="in_chamber">চেম্বারে</button>
          <?php endif; ?>
          <?php if ($r['status']==='in_chamber'): ?>
            <a class="btn sm" href="prescribe.php?aid=<?= (int)$r['id'] ?>">প্রেসক্রিপশন</a>
            <button name="action" value="done">সম্পন্ন</button>
          <?php endif; ?>
          <?php if ((int)$r['has_rx']): ?>
            <a class="btn sm ghost" href="prescribe.php?aid=<?= (int)$r['id'] ?>">℞</a>
          <?php elseif ($r['status']==='done'): ?>
            <a class="btn sm" href="prescribe.php?aid=<?= (int)$r['id'] ?>">প্রেসক্রিপশন</a>
          <?php endif; ?>
          <?php if (!in_array($r['status'], ['done','cancelled'], true)): ?>
            <button name="action" value="no_show">আসেননি</button>
            <button name="action" value="cancel" class="danger">বাতিল</button>
          <?php endif; ?>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$rows): ?><tr><td colspan="6">এই তারিখে সিরিয়াল নেই।</td></tr><?php endif; ?>
</table>
</div>
<p class="muted">নতুন ট্যাবে কিউ অটো-রিফ্রেশ হয় প্রতি ৪০ সেকেন্ডে।</p>
<script>setTimeout(()=>location.reload(), 40000);</script>
<?php render_footer(); ?>
