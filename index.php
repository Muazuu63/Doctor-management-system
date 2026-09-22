<?php
require __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';
$doc = doctor_row();
$dates = upcoming_dates(10);
$today = date('Y-m-d');
$todaySch = schedule_for_date($today);
$todaySlots = $todaySch ? generate_slots($todaySch, $today) : [];
$free = count(array_filter($todaySlots, fn($s) => $s['available']));
$booked = count($todaySlots) - $free;
$delay = current_delay($today);
render_header('হোম', 'home');
?>
<section class="hero">
  <div>
    <p class="eyebrow">অনলাইন সিরিয়াল · সময়মতো সেবা</p>
    <h1><?= e($doc['name_bn'] ?: $doc['name']) ?></h1>
    <p class="lead"><?= e($doc['degree']) ?> · <?= e($doc['specialty']) ?></p>
    <p><?= e($doc['bio']) ?></p>
    <div class="cta-row">
      <a class="btn" href="book.php">এখনই সিরিয়াল নিন</a>
      <a class="btn ghost" href="status.php">আমার সিরিয়াল</a>
    </div>
  </div>
  <div class="hero-card">
    <h3>আজকের চেম্বার</h3>
    <?php if (!$todaySch): ?>
      <p>আজ চেম্বার বন্ধ। পরবর্তী দিন থেকে সিরিয়াল নিন।</p>
    <?php else: ?>
      <p class="big-time"><?= e(format_time_12($todaySch['start_time'])) ?> – <?= e(format_time_12($todaySch['end_time'])) ?></p>
      <p>প্রতি রোগী <?= e(bn_digits((int)$todaySch['slot_minutes'])) ?> মিনিট</p>
      <div class="stat-row">
        <div><b><?= e(bn_digits($booked)) ?></b><span>বুকড</span></div>
        <div><b><?= e(bn_digits($free)) ?></b><span>ফাঁকা</span></div>
        <div><b><?= $delay ? e(bn_digits($delay)).' মি' : 'না' ?></b><span>দেরি</span></div>
      </div>
      <?php if ($delay): ?>
        <p class="warn">ডাক্তার <?= e(bn_digits($delay)) ?> মিনিট দেরি করবেন। সব সিরিয়াল সেই অনুযায়ী সরেছে।</p>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>

<section class="how">
  <h2>কীভাবে কাজ করে</h2>
  <div class="grid-3">
    <article>
      <span class="step">১</span>
      <h3>ওয়েবসাইট থেকে সিরিয়াল</h3>
      <p>ফোন নম্বর দিয়ে রোগী চিনে নেওয়া হয়। তারিখ বেছে সিরিয়াল নিন। প্রথম সিরিয়াল চেম্বার শুরুর সময়, পরেরটি ১৫–২০ মিনিট পর।</p>
    </article>
    <article>
      <span class="step">২</span>
      <h3>এসএমএস-এ সঠিক সময়</h3>
      <p>বুকিংয়ের সাথেই মেসেজ যায়। সিরিয়াল ৫০ হলেও আপনি আপনার নির্দিষ্ট সময়ে আসবেন—আগে এসে বসে থাকতে হবে না।</p>
    </article>
    <article>
      <span class="step">৩</span>
      <h3>দেরি হলে নতুন সময়</h3>
      <p>ডাক্তার দেরি করলে সব সিরিয়াল অটো রি-শিডিউল হয় এবং প্রত্যেক রোগীকে নতুন সময় এসএমএস যায়।</p>
    </article>
  </div>
</section>

<section>
  <h2>আসন্ন চেম্বার</h2>
  <div class="date-grid">
    <?php foreach ($dates as $d): ?>
      <a class="date-card" href="book.php?date=<?= e($d['date']) ?>">
        <strong><?= e($d['day']) ?></strong>
        <span><?= e($d['label']) ?></span>
        <em><?= e(format_time_12($d['start'])) ?> – <?= e(format_time_12($d['end'])) ?></em>
        <small><?= e(bn_digits($d['free'])) ?> ফাঁকা / <?= e(bn_digits($d['total'])) ?></small>
      </a>
    <?php endforeach; ?>
    <?php if (!$dates): ?><p>পরবর্তী ১৪ দিনে কোনো চেম্বার নেই।</p><?php endif; ?>
  </div>
</section>
<?php render_footer(); ?>
