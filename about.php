<?php
require __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';
$doc = doctor_row();
$schedules = db()->query('SELECT * FROM schedules WHERE is_active=1 ORDER BY day_of_week')->fetchAll();
render_header('ডাক্তার পরিচিতি', 'about');
?>
<section class="about">
  <div class="card">
    <p class="eyebrow"><?= e($doc['specialty']) ?></p>
    <h1><?= e($doc['name_bn'] ?: $doc['name']) ?></h1>
    <p><?= e($doc['degree']) ?></p>
    <p><?= e($doc['bio']) ?></p>
    <p>ফি: <?= e(bn_digits((int)$doc['consultation_fee'])) ?> টাকা · স্লট <?= e(bn_digits((int)$doc['slot_minutes'])) ?> মিনিট</p>
    <a class="btn" href="book.php">সিরিয়াল নিন</a>
  </div>
  <div class="card">
    <h2>সাপ্তাহিক চেম্বার</h2>
    <table class="table">
      <tr><th>দিন</th><th>সময়</th><th>সিরিয়াল</th></tr>
      <?php foreach ($schedules as $s): ?>
        <tr>
          <td><?= e(bangla_day((int)$s['day_of_week'])) ?></td>
          <td><?= e(format_time_12($s['start_time'])) ?> – <?= e(format_time_12($s['end_time'])) ?></td>
          <td><?= e(bn_digits((int)$s['max_serial'])) ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
    <p class="muted"><?= e(setting('clinic_address')) ?><br>ফোন: <?= e(setting('clinic_phone')) ?></p>
  </div>
</section>
<?php render_footer(); ?>
