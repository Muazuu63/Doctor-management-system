<?php
declare(strict_types=1);

function render_header(string $title, string $active = 'home', bool $doctor = false): void
{
    $clinic = clinic_name();
    $doc = [];
    try { $doc = doctor_row(); } catch (Throwable $e) {}
    ?>
<!DOCTYPE html>
<html lang="bn">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title) ?> | <?= e($clinic) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Noto+Serif+Bengali:wght@500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(base_url()) ?>/assets/css/style.css">
</head>
<body>
<header class="topbar">
  <div class="wrap nav">
    <a class="brand" href="<?= e(base_url()) ?>/index.php">
      <span class="logo">+</span>
      <span>
        <strong><?= e($clinic) ?></strong>
        <small><?= e(setting('clinic_tagline')) ?></small>
      </span>
    </a>
    <button class="menu-btn" id="menuBtn" type="button" aria-label="মেনু">☰</button>
    <nav id="navLinks">
      <?php if ($doctor): ?>
        <a class="<?= $active==='dash'?'on':'' ?>" href="dashboard.php">ড্যাশবোর্ড</a>
        <a class="<?= $active==='queue'?'on':'' ?>" href="queue.php">আজকের কিউ</a>
        <a href="walkin.php">ওয়াক-ইন</a>
        <a class="<?= $active==='schedule'?'on':'' ?>" href="schedule.php">সময়সূচি</a>
        <a class="<?= $active==='patients'?'on':'' ?>" href="patients.php">রোগী</a>
        <a class="<?= $active==='sms'?'on':'' ?>" href="sms.php">এসএমএস</a>
        <a class="<?= $active==='settings'?'on':'' ?>" href="settings.php">সেটিংস</a>
        <a href="logout.php">লগআউট</a>
      <?php else: ?>
        <a class="<?= $active==='home'?'on':'' ?>" href="<?= e(base_url()) ?>/index.php">হোম</a>
        <a class="<?= $active==='book'?'on':'' ?>" href="<?= e(base_url()) ?>/book.php">সিরিয়াল নিন</a>
        <a class="<?= $active==='status'?'on':'' ?>" href="<?= e(base_url()) ?>/status.php">সিরিয়াল দেখুন</a>
        <a class="<?= $active==='portal'?'on':'' ?>" href="<?= e(base_url()) ?>/portal.php">প্রেসক্রিপশন</a>
        <a class="<?= $active==='about'?'on':'' ?>" href="<?= e(base_url()) ?>/about.php">ডাক্তার</a>
        <a class="btn-nav" href="<?= e(base_url()) ?>/doctor/login.php">ডাক্তার লগইন</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<main class="wrap">
<?php
    $ok = flash('ok'); $err = flash('err');
    if ($ok) echo '<div class="alert ok">'.e($ok).'</div>';
    if ($err) echo '<div class="alert err">'.e($err).'</div>';
}

function render_footer(): void
{
    $clinic = clinic_name();
    ?>
</main>
<footer class="footer">
  <div class="wrap footer-grid">
    <div>
      <strong><?= e($clinic) ?></strong>
      <p><?= e(setting('clinic_address')) ?></p>
      <p>ফোন: <?= e(setting('clinic_phone')) ?></p>
    </div>
    <div>
      <p>সিরিয়াল ওয়েবসাইট থেকে নিন। সময়মতো আসুন, অপেক্ষা কমান।</p>
      <p class="muted">প্রতি রোগী <?= e(bn_digits(setting('slot_minutes', 20))) ?> মিনিট</p>
    </div>
  </div>
</footer>
<script src="<?= e(base_url()) ?>/assets/js/app.js"></script>
</body>
</html>
<?php
}
