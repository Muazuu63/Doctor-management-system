<?php
require __DIR__ . '/_auth.php';
$doc = require_doctor();
$today = date('Y-m-d');
$sch = schedule_for_date($today);
$slots = $sch ? generate_slots($sch, $today) : [];
$delay = current_delay($today);
$stmt = db()->prepare("SELECT a.*, p.name, p.phone FROM appointments a JOIN patients p ON p.id=a.patient_id WHERE a.appointment_date=? ORDER BY a.serial_no");
$stmt->execute([$today]);
$apts = $stmt->fetchAll();
$counts = ['booked'=>0,'waiting'=>0,'in_chamber'=>0,'done'=>0,'cancelled'=>0,'no_show'=>0];
foreach ($apts as $a) {
    $counts[$a['status']] = ($counts[$a['status']] ?? 0) + 1;
}
$week = db()->query("SELECT appointment_date, COUNT(*) AS c FROM appointments WHERE status!='cancelled' GROUP BY appointment_date ORDER BY appointment_date DESC LIMIT 7")->fetchAll();
render_header('ড্যাশবোর্ড', 'dash', true);
?>
<h1>স্বাগতম, <?= e($doc['name_bn'] ?: $doc['name']) ?></h1>
<div class="stat-row cards">
  <div><b><?= e(bn_digits(count($apts))) ?></b><span>আজকের সিরিয়াল</span></div>
  <div><b><?= e(bn_digits($counts['done'])) ?></b><span>দেখা হয়েছে</span></div>
  <div><b><?= e(bn_digits($counts['booked']+$counts['waiting'])) ?></b><span>বাকি</span></div>
  <div><b><?= $delay ? e(bn_digits($delay)).' মি' : '০' ?></b><span>বর্তমান দেরি</span></div>
</div>

<div class="split">
  <div class="card">
    <h2>দেরি / রি-শিডিউল</h2>
    <p>চেম্বার দেরিতে শুরু হলে মিনিট দিন। সব রোগীর সময় সরে যাবে এবং এসএমএস যাবে।</p>
    <form method="post" action="delay.php" class="form">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="date" value="<?= e($today) ?>">
      <label>দেরি (মিনিট)<input name="minutes" type="number" min="0" max="240" value="<?= (int)$delay ?>" required></label>
      <label>কারণ<input name="reason" placeholder="যেমন: হাসপাতালে জরুরি"></label>
      <button class="btn" type="submit">সবাইকে নতুন সময় পাঠান</button>
    </form>
  </div>
  <div class="card">
    <h2>আজকের চেম্বার</h2>
    <?php if (!$sch): ?>
      <p>আজ চেম্বার সেট নেই। <a href="schedule.php">সময়সূচি</a> আপডেট করুন।</p>
    <?php else: ?>
      <p><?= e($sch['title']) ?><br><?= e(format_time_12($sch['start_time'])) ?> – <?= e(format_time_12($sch['end_time'])) ?></p>
      <a class="btn" href="queue.php">কিউ খুলুন</a>
    <?php endif; ?>
    <h3 style="margin-top:24px">সাম্প্রতিক দিন</h3>
    <ul class="plain">
      <?php foreach ($week as $w): ?>
        <li><?= e(bangla_date($w['appointment_date'])) ?> — <?= e(bn_digits((int)$w['c'])) ?> জন</li>
      <?php endforeach; ?>
    </ul>
  </div>
</div>
<?php render_footer(); ?>
