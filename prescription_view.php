<?php
require __DIR__ . '/includes/bootstrap.php';
$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT pr.*, a.appointment_date, a.serial_no, p.name AS patient_name, p.phone, p.age, p.gender, p.address
  FROM prescriptions pr
  JOIN appointments a ON a.id = pr.appointment_id
  JOIN patients p ON p.id = pr.patient_id
  WHERE pr.id = ?');
$stmt->execute([$id]);
$rx = $stmt->fetch();
if (!$rx) {
    http_response_code(404);
    exit('Not found');
}
$ok = !empty($_SESSION['doctor_id']) || (!empty($_SESSION['patient_id']) && (int)$_SESSION['patient_id'] === (int)$rx['patient_id']);
if (!$ok) {
    http_response_code(403);
    exit('Unauthorized');
}
$doc = doctor_row();
$meds = json_decode($rx['medicines'] ?: '[]', true) ?: [];
?>
<!DOCTYPE html>
<html lang="bn">
<head>
  <meta charset="UTF-8">
  <title>প্রেসক্রিপশন</title>
  <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;600;700&family=Noto+Serif+Bengali:wght@700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(base_url()) ?>/assets/css/print.css">
</head>
<body>
  <div class="rx">
    <header>
      <div>
        <h1><?= e($doc['name_bn'] ?: $doc['name']) ?></h1>
        <p><?= e($doc['degree']) ?> · <?= e($doc['specialty']) ?></p>
        <p><?= e(clinic_name()) ?> · <?= e(setting('clinic_address')) ?></p>
      </div>
      <div class="right">
        <p>তারিখ: <?= e(bangla_date($rx['appointment_date'])) ?></p>
        <p>সিরিয়াল: <?= e(bn_digits($rx['serial_no'])) ?></p>
      </div>
    </header>
    <section class="patient">
      <span>নাম: <b><?= e($rx['patient_name']) ?></b></span>
      <span>বয়স: <?= e(bn_digits((int)$rx['age'])) ?></span>
      <span>লিঙ্গ: <?= e($rx['gender']) ?></span>
      <span>ফোন: <?= e($rx['phone']) ?></span>
    </section>
    <div class="rx-body">
      <div class="left-col">
        <h3>C/C</h3><p><?= nl2br(e($rx['complaints'])) ?></p>
        <h3>O/E</h3><p><?= nl2br(e($rx['findings'])) ?></p>
        <h3>Dx</h3><p><?= e($rx['diagnosis']) ?></p>
      </div>
      <div class="right-col">
        <div class="rx-symbol">℞</div>
        <table>
          <?php foreach ($meds as $i => $m): ?>
            <tr>
              <td><?= e(bn_digits($i+1)) ?>.</td>
              <td>
                <b><?= e($m['name']??'') ?></b><br>
                <?= e($m['dose']??'') ?> · <?= e($m['duration']??'') ?><br>
                <small><?= e($m['instruction']??'') ?></small>
              </td>
            </tr>
          <?php endforeach; ?>
        </table>
        <?php if ($rx['tests']): ?><p><b>Investigation:</b> <?= e($rx['tests']) ?></p><?php endif; ?>
        <?php if ($rx['advice']): ?><p><b>Advice:</b> <?= nl2br(e($rx['advice'])) ?></p><?php endif; ?>
        <?php if ($rx['follow_up_date']): ?><p><b>Follow up:</b> <?= e($rx['follow_up_date']) ?></p><?php endif; ?>
      </div>
    </div>
    <footer>
      <p>ফোন: <?= e(setting('clinic_phone')) ?></p>
      <p class="sign"><?= e($doc['name']) ?><br><small><?= e($doc['degree']) ?></small></p>
    </footer>
  </div>
  <p class="no-print" style="text-align:center"><button onclick="window.print()">প্রিন্ট</button></p>
</body>
</html>
