<?php
require __DIR__ . '/_auth.php';
require_doctor();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['act'] ?? '') === 'add') {
        db()->prepare('INSERT INTO medicine_templates (doctor_id, name, dose, duration, instruction) VALUES (1,?,?,?,?)')
            ->execute([trim($_POST['name']), trim($_POST['dose']), trim($_POST['duration']), trim($_POST['instruction'])]);
        flash('ok', 'ওষুধ টেমপ্লেট যোগ হয়েছে।');
    }
    redirect('medicines.php');
}
$rows = db()->query('SELECT * FROM medicine_templates ORDER BY name')->fetchAll();
render_header('ওষুধ টেমপ্লেট', 'queue', true);
?>
<h1>প্রেসক্রিপশন টেমপ্লেট</h1>
<form method="post" class="card form">
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
  <input type="hidden" name="act" value="add">
  <div class="med-row">
    <input name="name" placeholder="ওষুধের নাম" required>
    <input name="dose" placeholder="১+০+১">
    <input name="duration" placeholder="৭ দিন">
    <input name="instruction" placeholder="খাওয়ার পর">
  </div>
  <button class="btn" type="submit">যোগ</button>
</form>
<table class="table">
  <tr><th>নাম</th><th>মাত্রা</th><th>মেয়াদ</th><th>নির্দেশ</th></tr>
  <?php foreach ($rows as $r): ?>
    <tr><td><?= e($r['name']) ?></td><td><?= e($r['dose']) ?></td><td><?= e($r['duration']) ?></td><td><?= e($r['instruction']) ?></td></tr>
  <?php endforeach; ?>
</table>
<?php render_footer(); ?>
