<?php
require __DIR__ . '/_auth.php';
require_doctor();
$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM patients WHERE id=?');
$stmt->execute([$id]);
$p = $stmt->fetch();
if (!$p) {
    flash('err', 'রোগী পাওয়া যায়নি।');
    redirect('patients.php');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    db()->prepare('UPDATE patients SET name=?, gender=?, age=?, blood_group=?, address=? WHERE id=?')
        ->execute([
            trim($_POST['name']),
            $_POST['gender'] ?? '',
            (int) $_POST['age'],
            trim($_POST['blood_group'] ?? ''),
            trim($_POST['address'] ?? ''),
            $id,
        ]);
    flash('ok', 'রোগীর তথ্য আপডেট হয়েছে।');
    redirect('patient.php?id=' . $id);
}
$hist = patient_history($id);
$rxs = db()->prepare('SELECT pr.*, a.appointment_date FROM prescriptions pr JOIN appointments a ON a.id=pr.appointment_id WHERE pr.patient_id=? ORDER BY pr.id DESC');
$rxs->execute([$id]);
$rxs = $rxs->fetchAll();
render_header('রোগীর ফাইল', 'patients', true);
?>
<h1><?= e($p['name']) ?></h1>
<p><?= e($p['phone']) ?> · পিন <?= e($p['pin']) ?></p>
<div class="split">
  <form method="post" class="card form">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <label>নাম<input name="name" value="<?= e($p['name']) ?>"></label>
    <div class="row-2">
      <label>বয়স<input type="number" name="age" value="<?= (int)$p['age'] ?>"></label>
      <label>লিঙ্গ<input name="gender" value="<?= e($p['gender']) ?>"></label>
    </div>
    <label>রক্তের গ্রুপ<input name="blood_group" value="<?= e($p['blood_group']) ?>"></label>
    <label>ঠিকানা<input name="address" value="<?= e($p['address']) ?>"></label>
    <button class="btn" type="submit">সেভ</button>
  </form>
  <div class="card">
    <h2>ভিজিট হিস্ট্রি</h2>
    <ul class="plain">
      <?php foreach ($hist as $h): ?>
        <li>
          <?= e(bangla_date($h['appointment_date'])) ?> · সিরিয়াল <?= e(bn_digits($h['serial_no'])) ?> · <?= e(status_bn($h['status'])) ?>
          <?php if ($h['prescription_id']): ?>
            · <a href="../prescription_view.php?id=<?= (int)$h['prescription_id'] ?>">℞</a>
          <?php elseif ($h['status'] !== 'cancelled'): ?>
            · <a href="prescribe.php?aid=<?= (int)$h['id'] ?>">লিখুন</a>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</div>
<h2>প্রেসক্রিপশন</h2>
<?php foreach ($rxs as $r): $meds = json_decode($r['medicines']?:'[]', true) ?: []; ?>
  <article class="card">
    <h3><?= e(bangla_date($r['appointment_date'])) ?> · <?= e($r['diagnosis']) ?></h3>
    <p><?= e($r['complaints']) ?></p>
    <ul><?php foreach ($meds as $m): ?><li><?= e($m['name']) ?> — <?= e($m['dose']) ?></li><?php endforeach; ?></ul>
    <a class="btn ghost sm" href="../prescription_view.php?id=<?= (int)$r['id'] ?>">প্রিন্ট</a>
  </article>
<?php endforeach; ?>
<?php render_footer(); ?>
