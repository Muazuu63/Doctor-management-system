<?php
require __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';

$date = $_GET['date'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $date = date('Y-m-d');
}
$dates = upcoming_dates(14);
$sch = schedule_for_date($date);
$slots = $sch ? generate_slots($sch, $date) : [];
$delay = current_delay($date);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    try {
        $name = trim($_POST['name'] ?? '');
        $phone = normalize_phone($_POST['phone'] ?? '');
        $age = (int) ($_POST['age'] ?? 0);
        $gender = $_POST['gender'] ?? '';
        $notes = trim($_POST['notes'] ?? '');
        $serial = (int) ($_POST['serial'] ?? 0);
        $bdate = $_POST['date'] ?? $date;
        if ($name === '' || !valid_bd_phone($phone)) {
            throw new RuntimeException('সঠিক নাম ও বাংলাদেশি মোবাইল নম্বর দিন (01XXXXXXXXX)।');
        }
        $patient = find_or_create_patient($name, $phone, ['age' => $age, 'gender' => $gender]);
        $apt = book_appointment($patient, $bdate, $serial ?: null, $notes);
        redirect('ticket.php?token=' . urlencode($apt['token']));
    } catch (Throwable $e) {
        flash('err', $e->getMessage());
        redirect('book.php?date=' . urlencode($_POST['date'] ?? $date));
    }
}

render_header('সিরিয়াল নিন', 'book');
?>
<h1>অনলাইন সিরিয়াল</h1>
<p class="lead">তারিখ বাছুন, ফাঁকা সিরিয়াল সিলেক্ট করুন। বুকিংয়ের সাথেই আপনার সময় এসএমএস যাবে।</p>

<div class="chip-row">
  <?php foreach ($dates as $d): ?>
    <a class="chip <?= $d['date']===$date?'on':'' ?>" href="book.php?date=<?= e($d['date']) ?>">
      <?= e($d['day']) ?><br><small><?= e($d['label']) ?></small>
    </a>
  <?php endforeach; ?>
</div>

<?php if (!$sch): ?>
  <div class="alert err">এই তারিখে চেম্বার নেই। অন্য দিন বাছুন।</div>
<?php else: ?>
  <div class="info-bar">
    <span><?= e($sch['title']) ?></span>
    <span><?= e(format_time_12($sch['start_time'])) ?> – <?= e(format_time_12($sch['end_time'])) ?></span>
    <span>স্লট <?= e(bn_digits((int)$sch['slot_minutes'])) ?> মিনিট</span>
    <?php if ($delay): ?><span class="warn-inline">দেরি <?= e(bn_digits($delay)) ?> মিনিট — সময় আপডেটেড</span><?php endif; ?>
  </div>

  <form method="post" class="book-grid">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="date" value="<?= e($date) ?>">
    <div class="card">
      <h3>রোগীর তথ্য</h3>
      <label>নাম *<input name="name" required placeholder="পূর্ণ নাম"></label>
      <label>মোবাইল *<input name="phone" required placeholder="01XXXXXXXXX"></label>
      <div class="row-2">
        <label>বয়স<input name="age" type="number" min="0" max="120"></label>
        <label>লিঙ্গ
          <select name="gender">
            <option value="">নির্বাচন</option>
            <option>পুরুষ</option>
            <option>নারী</option>
            <option>অন্যান্য</option>
          </select>
        </label>
      </div>
      <label>সংক্ষিপ্ত সমস্যা (ঐচ্ছিক)<input name="notes" placeholder="যেমন: জ্বর, কাশি"></label>
      <p class="muted">আগের রোগী হলে একই নম্বরে হিস্ট্রি ও প্রেসক্রিপশন জুড়ে যাবে।</p>
    </div>
    <div class="card">
      <h3>সিরিয়াল বাছুন</h3>
      <div class="slots">
        <?php foreach ($slots as $s): ?>
          <label class="slot <?= $s['available']?'':'taken' ?>">
            <input type="radio" name="serial" value="<?= (int)$s['serial'] ?>" <?= $s['available']?'':'disabled' ?> required>
            <b><?= e(bn_digits($s['serial'])) ?></b>
            <span><?= e(format_time_12($s['scheduled_time'])) ?></span>
            <?php if ($s['scheduled_time'] !== $s['original_time']): ?>
              <small>আসল <?= e(format_time_12($s['original_time'])) ?></small>
            <?php endif; ?>
          </label>
        <?php endforeach; ?>
      </div>
      <button class="btn" type="submit">সিরিয়াল নিশ্চিত করুন</button>
    </div>
  </form>
<?php endif; ?>
<?php render_footer(); ?>
