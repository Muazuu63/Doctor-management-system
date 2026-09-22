<?php
require __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';
$records = [];
$patient = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $phone = normalize_phone($_POST['phone'] ?? '');
    $pin = trim($_POST['pin'] ?? '');
    $stmt = db()->prepare('SELECT * FROM patients WHERE phone=? AND pin=?');
    $stmt->execute([$phone, $pin]);
    $patient = $stmt->fetch();
    if ($patient) {
        $_SESSION['patient_id'] = (int) $patient['id'];
    } else {
        flash('err', 'ফোন বা পিন ভুল।');
    }
}
if (!empty($_SESSION['patient_id'])) {
    $stmt = db()->prepare('SELECT * FROM patients WHERE id=?');
    $stmt->execute([$_SESSION['patient_id']]);
    $patient = $stmt->fetch();
    $stmt = db()->prepare('SELECT pr.*, a.appointment_date, a.serial_no FROM prescriptions pr JOIN appointments a ON a.id=pr.appointment_id WHERE pr.patient_id=? ORDER BY pr.id DESC');
    $stmt->execute([$patient['id']]);
    $records = $stmt->fetchAll();
}
if (isset($_GET['logout'])) {
    unset($_SESSION['patient_id']);
    redirect('portal.php');
}
$doc = doctor_row();
render_header('রোগী পোর্টাল', 'portal');
?>
<h1>প্রেসক্রিপশন ও হিস্ট্রি</h1>
<?php if (!$patient): ?>
  <form method="post" class="card form" style="max-width:420px">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <label>মোবাইল<input name="phone" required placeholder="01XXXXXXXXX"></label>
    <label>পিন (৪ অঙ্ক)<input name="pin" required></label>
    <button class="btn" type="submit">ঢুকুন</button>
    <p class="muted">পিন সিরিয়াল কনফার্ম পেজে দেওয়া হয়। হারিয়ে গেলে ক্লিনিকে ফোন করুন।</p>
  </form>
<?php else: ?>
  <p><?= e($patient['name']) ?> · <?= e($patient['phone']) ?> <a href="portal.php?logout=1">লগআউট</a></p>
  <?php if (!$records): ?><p>এখনো কোনো প্রেসক্রিপশন নেই।</p><?php endif; ?>
  <?php foreach ($records as $r):
    $meds = json_decode($r['medicines'] ?: '[]', true) ?: [];
  ?>
    <article class="card rx-card">
      <h3><?= e(bangla_date($r['appointment_date'])) ?> · সিরিয়াল <?= e(bn_digits($r['serial_no'])) ?></h3>
      <p><b>রোগ নির্ণয়:</b> <?= e($r['diagnosis']) ?></p>
      <p><b>অভিযোগ:</b> <?= e($r['complaints']) ?></p>
      <?php if ($meds): ?>
        <table class="table">
          <tr><th>ওষুধ</th><th>মাত্রা</th><th>সময়</th><th>নির্দেশ</th></tr>
          <?php foreach ($meds as $m): ?>
            <tr><td><?= e($m['name']??'') ?></td><td><?= e($m['dose']??'') ?></td><td><?= e($m['duration']??'') ?></td><td><?= e($m['instruction']??'') ?></td></tr>
          <?php endforeach; ?>
        </table>
      <?php endif; ?>
      <?php if ($r['tests']): ?><p><b>পরীক্ষা:</b> <?= e($r['tests']) ?></p><?php endif; ?>
      <?php if ($r['advice']): ?><p><b>পরামর্শ:</b> <?= e($r['advice']) ?></p><?php endif; ?>
      <a class="btn ghost" href="prescription_view.php?id=<?= (int)$r['id'] ?>">প্রিন্ট</a>
    </article>
  <?php endforeach; ?>
<?php endif; ?>
<?php render_footer(); ?>
