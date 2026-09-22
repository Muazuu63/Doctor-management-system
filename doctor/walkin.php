<?php
require __DIR__ . '/_auth.php';
require_doctor();
$date = $_GET['date'] ?? date('Y-m-d');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    try {
        $phone = normalize_phone($_POST['phone'] ?? '');
        $name = trim($_POST['name'] ?? '');
        if (!valid_bd_phone($phone) || $name === '') {
            throw new RuntimeException('সঠিক নাম ও মোবাইল দিন।');
        }
        $patient = find_or_create_patient($name, $phone, [
            'age' => (int) ($_POST['age'] ?? 0),
            'gender' => $_POST['gender'] ?? '',
        ]);
        $apt = book_appointment($patient, $_POST['date'] ?? $date, (int) ($_POST['serial'] ?? 0) ?: null, 'walk-in');
        flash('ok', 'সিরিয়াল ' . bn_digits($apt['serial_no']) . ' · সময় ' . format_time_12($apt['scheduled_time']));
        redirect('queue.php?date=' . urlencode($apt['appointment_date']));
    } catch (Throwable $e) {
        flash('err', $e->getMessage());
        redirect('walkin.php?date=' . urlencode($_POST['date'] ?? $date));
    }
}
$sch = schedule_for_date($date);
$slots = $sch ? generate_slots($sch, $date) : [];
render_header('ওয়াক-ইন সিরিয়াল', 'queue', true);
?>
<h1>চেম্বারে এসে সিরিয়াল</h1>
<form method="get" class="inline-form"><label>তারিখ<input type="date" name="date" value="<?= e($date) ?>" onchange="this.form.submit()"></label></form>
<?php if (!$sch): ?>
  <div class="alert err">এই দিন চেম্বার নেই।</div>
<?php else: ?>
<form method="post" class="split">
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
  <input type="hidden" name="date" value="<?= e($date) ?>">
  <div class="card form">
    <label>নাম<input name="name" required></label>
    <label>মোবাইল<input name="phone" required placeholder="আগের রোগী হলে শুধু নম্বর"></label>
    <div class="row-2">
      <label>বয়স<input type="number" name="age"></label>
      <label>লিঙ্গ<select name="gender"><option value="">—</option><option>পুরুষ</option><option>নারী</option></select></label>
    </div>
    <button class="btn" type="submit">পরের ফাঁকা সিরিয়াল দিন</button>
  </div>
  <div class="card">
    <h3>নির্দিষ্ট সিরিয়াল (ঐচ্ছিক)</h3>
    <div class="slots">
      <?php foreach ($slots as $s): ?>
        <label class="slot <?= $s['available']?'':'taken' ?>">
          <input type="radio" name="serial" value="<?= (int)$s['serial'] ?>" <?= $s['available']?'':'disabled' ?>>
          <b><?= e(bn_digits($s['serial'])) ?></b>
          <span><?= e(format_time_12($s['scheduled_time'])) ?></span>
        </label>
      <?php endforeach; ?>
    </div>
  </div>
</form>
<?php endif; ?>
<?php render_footer(); ?>
