<?php
require __DIR__ . '/_auth.php';
$doc = require_doctor();
$aid = (int) ($_GET['aid'] ?? $_POST['aid'] ?? 0);
$stmt = db()->prepare('SELECT a.*, p.* FROM appointments a JOIN patients p ON p.id=a.patient_id WHERE a.id=?');
$stmt->execute([$aid]);
$apt = $stmt->fetch();
if (!$apt) {
    flash('err', 'অ্যাপয়েন্টমেন্ট পাওয়া যায়নি।');
    redirect('queue.php');
}
$rxStmt = db()->prepare('SELECT * FROM prescriptions WHERE appointment_id=? ORDER BY id DESC LIMIT 1');
$rxStmt->execute([$aid]);
$existing = $rxStmt->fetch();
$templates = db()->query('SELECT * FROM medicine_templates ORDER BY name')->fetchAll();
$history = patient_history((int)$apt['patient_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $meds = [];
    $names = $_POST['med_name'] ?? [];
    foreach ($names as $i => $n) {
        $n = trim((string)$n);
        if ($n === '') continue;
        $meds[] = [
            'name' => $n,
            'dose' => trim((string)($_POST['med_dose'][$i] ?? '')),
            'duration' => trim((string)($_POST['med_duration'][$i] ?? '')),
            'instruction' => trim((string)($_POST['med_instruction'][$i] ?? '')),
        ];
    }
    $data = [
        $aid,
        $apt['patient_id'],
        $doc['id'],
        trim($_POST['complaints'] ?? ''),
        trim($_POST['findings'] ?? ''),
        trim($_POST['diagnosis'] ?? ''),
        json_encode($meds, JSON_UNESCAPED_UNICODE),
        trim($_POST['tests'] ?? ''),
        trim($_POST['advice'] ?? ''),
        trim($_POST['follow_up_date'] ?? ''),
    ];
    if ($existing) {
        db()->prepare('UPDATE prescriptions SET complaints=?, findings=?, diagnosis=?, medicines=?, tests=?, advice=?, follow_up_date=? WHERE id=?')
            ->execute([$data[3],$data[4],$data[5],$data[6],$data[7],$data[8],$data[9], $existing['id']]);
        $rid = (int)$existing['id'];
    } else {
        db()->prepare('INSERT INTO prescriptions (appointment_id, patient_id, doctor_id, complaints, findings, diagnosis, medicines, tests, advice, follow_up_date) VALUES (?,?,?,?,?,?,?,?,?,?)')
            ->execute($data);
        $rid = (int) db()->lastInsertId();
        db()->prepare("UPDATE appointments SET status='done' WHERE id=?")->execute([$aid]);
    }
    flash('ok', 'প্রেসক্রিপশন সেভ হয়েছে।');
    redirect('../prescription_view.php?id=' . $rid);
}

$meds = $existing ? (json_decode($existing['medicines'] ?: '[]', true) ?: []) : [['name'=>'','dose'=>'','duration'=>'','instruction'=>'']];
render_header('প্রেসক্রিপশন', 'queue', true);
?>
<h1>প্রেসক্রিপশন · <?= e($apt['name']) ?></h1>
<p><?= e($apt['phone']) ?> · <?= e($apt['gender']) ?> · <?= e(bn_digits((int)$apt['age'])) ?> বছর · সিরিয়াল <?= e(bn_digits($apt['serial_no'])) ?></p>
<p><a href="patient.php?id=<?= (int)$apt['patient_id'] ?>">পূর্ণ হিস্ট্রি</a></p>

<form method="post" class="rx-form">
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
  <input type="hidden" name="aid" value="<?= (int)$aid ?>">
  <div class="split">
    <div class="card">
      <label>অভিযোগ (C/C)<textarea name="complaints"><?= e($existing['complaints'] ?? $apt['notes']) ?></textarea></label>
      <label>পরীক্ষা (O/E)<textarea name="findings"><?= e($existing['findings'] ?? '') ?></textarea></label>
      <label>রোগ নির্ণয় (Dx)<input name="diagnosis" value="<?= e($existing['diagnosis'] ?? '') ?>"></label>
      <label>পরীক্ষা-নিরীক্ষা<input name="tests" value="<?= e($existing['tests'] ?? '') ?>" placeholder="CBC, RBS..."></label>
      <label>পরামর্শ<textarea name="advice"><?= e($existing['advice'] ?? '') ?></textarea></label>
      <label>ফলো-আপ তারিখ<input name="follow_up_date" value="<?= e($existing['follow_up_date'] ?? '') ?>" placeholder="৭ দিন পর"></label>
    </div>
    <div class="card">
      <h3>ওষুধ</h3>
      <p class="muted">টেমপ্লেট ক্লিক করে যোগ করুন</p>
      <div class="tpl">
        <?php foreach ($templates as $t): ?>
          <button type="button" class="chip" data-med='<?= e(json_encode($t, JSON_UNESCAPED_UNICODE)) ?>'><?= e($t['name']) ?></button>
        <?php endforeach; ?>
      </div>
      <div id="meds">
        <?php foreach ($meds as $m): ?>
          <div class="med-row">
            <input name="med_name[]" placeholder="ওষুধ" value="<?= e($m['name']??'') ?>">
            <input name="med_dose[]" placeholder="১+০+১" value="<?= e($m['dose']??'') ?>">
            <input name="med_duration[]" placeholder="৭ দিন" value="<?= e($m['duration']??'') ?>">
            <input name="med_instruction[]" placeholder="খাওয়ার পর" value="<?= e($m['instruction']??'') ?>">
          </div>
        <?php endforeach; ?>
      </div>
      <button type="button" class="btn ghost" id="addMed">আরও ওষুধ</button>
      <button class="btn" type="submit">সেভ ও প্রিন্ট</button>
    </div>
  </div>
</form>

<?php if (count($history) > 1): ?>
  <h2>আগের ভিজিট</h2>
  <ul class="plain">
    <?php foreach ($history as $h): if ((int)$h['id']===$aid) continue; ?>
      <li><?= e(bangla_date($h['appointment_date'])) ?> · <?= e(status_bn($h['status'])) ?> <?= $h['diagnosis']? '· '.e($h['diagnosis']):'' ?></li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>
<script>
document.getElementById('addMed').onclick = () => {
  const d = document.createElement('div');
  d.className = 'med-row';
  d.innerHTML = '<input name="med_name[]" placeholder="ওষুধ"><input name="med_dose[]" placeholder="১+০+১"><input name="med_duration[]" placeholder="৭ দিন"><input name="med_instruction[]" placeholder="খাওয়ার পর">';
  document.getElementById('meds').appendChild(d);
};
document.querySelectorAll('[data-med]').forEach(btn => {
  btn.onclick = () => {
    const t = JSON.parse(btn.dataset.med);
    const empty = [...document.querySelectorAll('#meds .med-row')].find(r => !r.querySelector('[name="med_name[]"]').value);
    const row = empty || (() => { document.getElementById('addMed').click(); return document.querySelector('#meds .med-row:last-child'); })();
    row.children[0].value = t.name;
    row.children[1].value = t.dose;
    row.children[2].value = t.duration;
    row.children[3].value = t.instruction;
  };
});
</script>
<?php render_footer(); ?>
