<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/install_lib.php';

$config = app_config();
$done = false;
$error = '';

if (!empty($config['installed']) && empty($_GET['force'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $driver = $_POST['db_driver'] === 'mysql' ? 'mysql' : 'sqlite';
    $data = [
        'db_driver' => $driver,
        'mysql_host' => trim($_POST['mysql_host'] ?? 'localhost'),
        'mysql_name' => trim($_POST['mysql_name'] ?? 'clinic'),
        'mysql_user' => trim($_POST['mysql_user'] ?? ''),
        'mysql_pass' => (string) ($_POST['mysql_pass'] ?? ''),
        'clinic_name' => trim($_POST['clinic_name'] ?? 'মেডিসেবা ক্লিনিক'),
        'clinic_phone' => trim($_POST['clinic_phone'] ?? ''),
        'clinic_address' => trim($_POST['clinic_address'] ?? ''),
        'clinic_email' => trim($_POST['clinic_email'] ?? ''),
        'slot_minutes' => (int) ($_POST['slot_minutes'] ?? 20),
        'app_url' => rtrim(trim($_POST['app_url'] ?? ''), '/'),
        'installed' => true,
    ];
    try {
        if ($driver === 'mysql') {
            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $data['mysql_host'], $data['mysql_name']);
            $pdo = new PDO($dsn, $data['mysql_user'], $data['mysql_pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
        } else {
            if (!is_dir(DATA_PATH)) mkdir(DATA_PATH, 0755, true);
            $pdo = new PDO('sqlite:' . DATA_PATH . '/clinic.db');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        if (!defined('BASE_PATH')) {
            define('BASE_PATH', __DIR__);
        }
        run_schema($pdo, $driver);
        seed_defaults($pdo);
        $docPass = $_POST['doctor_password'] ?? 'admin123';
        $docPhone = trim($_POST['doctor_phone'] ?? '01700000000');
        $docName = trim($_POST['doctor_name'] ?? 'Dr. Ayesha Rahman');
        $docNameBn = trim($_POST['doctor_name_bn'] ?? 'ডা. আয়েশা রহমান');
        $hash = password_hash($docPass, PASSWORD_DEFAULT);
        $pdo->prepare('UPDATE doctors SET name=?, name_bn=?, phone=?, password_hash=? WHERE id=1')
            ->execute([$docName, $docNameBn, $docPhone, $hash]);
        file_put_contents(DATA_PATH . '/config.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $done = true;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ইনস্টল | ক্লিনিক সিস্টেম</title>
  <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<main class="wrap" style="max-width:720px;padding-top:40px">
  <div class="card">
    <h1>ক্লিনিক সিস্টেম ইনস্টল</h1>
    <?php if ($done): ?>
      <div class="alert ok">ইনস্টল সম্পন্ন। ডাক্তার লগইন: আপনার দেওয়া ফোন ও পাসওয়ার্ড।</div>
      <p><a class="btn" href="index.php">ওয়েবসাইটে যান</a> <a class="btn ghost" href="doctor/login.php">ডাক্তার প্যানেল</a></p>
    <?php else: ?>
      <?php if ($error): ?><div class="alert err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
      <form method="post" class="form">
        <label>ক্লিনিকের নাম<input name="clinic_name" value="মেডিসেবা ক্লিনিক" required></label>
        <label>ক্লিনিক ফোন<input name="clinic_phone" value="01700000000" required></label>
        <label>ঠিকানা<input name="clinic_address" value="ধানমন্ডি, ঢাকা"></label>
        <label>ইমেইল<input name="clinic_email" type="email" value="info@mediseba.local"></label>
        <label>প্রতি রোগী সময় (মিনিট)<input name="slot_minutes" type="number" value="20" min="10" max="60"></label>
        <label>সাইট URL (ঐচ্ছিক)<input name="app_url" placeholder="https://yourdomain.com"></label>
        <hr>
        <label>ডাটাবেস
          <select name="db_driver" id="dbDriver">
            <option value="sqlite">SQLite (সহজ, ফাইল ভিত্তিক)</option>
            <option value="mysql">MySQL / MariaDB (cPanel)</option>
          </select>
        </label>
        <div id="mysqlBox" class="hidden">
          <label>MySQL হোস্ট<input name="mysql_host" value="localhost"></label>
          <label>ডাটাবেস নাম<input name="mysql_name" value="clinic"></label>
          <label>ইউজার<input name="mysql_user" value=""></label>
          <label>পাসওয়ার্ড<input name="mysql_pass" type="password"></label>
        </div>
        <hr>
        <label>ডাক্তারের নাম (ইংরেজি)<input name="doctor_name" value="Dr. Ayesha Rahman"></label>
        <label>ডাক্তারের নাম (বাংলা)<input name="doctor_name_bn" value="ডা. আয়েশা রহমান"></label>
        <label>ডাক্তার লগইন ফোন<input name="doctor_phone" value="01700000000"></label>
        <label>ডাক্তার পাসওয়ার্ড<input name="doctor_password" type="password" value="admin123" required></label>
        <button class="btn" type="submit">ইনস্টল করুন</button>
      </form>
    <?php endif; ?>
  </div>
</main>
<script>
document.getElementById('dbDriver')?.addEventListener('change', e => {
  document.getElementById('mysqlBox').classList.toggle('hidden', e.target.value !== 'mysql');
});
</script>
</body>
</html>
