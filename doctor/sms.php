<?php
require __DIR__ . '/_auth.php';
require_doctor();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = $_POST['act'] ?? '';
    if ($act === 'save') {
        save_config([
            'sms_provider' => $_POST['sms_provider'] === 'http' ? 'http' : 'log',
            'sms_api_url' => trim($_POST['sms_api_url'] ?? ''),
            'sms_api_key' => trim($_POST['sms_api_key'] ?? ''),
            'sms_sender' => trim($_POST['sms_sender'] ?? 'MediSeba'),
            'sms_http_method' => $_POST['sms_http_method'] === 'POST' ? 'POST' : 'GET',
            'reminder_minutes' => (int) ($_POST['reminder_minutes'] ?? 30),
        ]);
        flash('ok', 'এসএমএস সেটিংস সেভ হয়েছে।');
    }
    if ($act === 'test') {
        send_sms(normalize_phone($_POST['test_phone'] ?? ''), 'টেস্ট মেসেজ: ' . clinic_name() . ' সিরিয়াল সিস্টেম চালু আছে।', 'test');
        flash('ok', 'টেস্ট মেসেজ লগ/পাঠানো হয়েছে।');
    }
    redirect('sms.php');
}
$logs = db()->query('SELECT * FROM sms_logs ORDER BY id DESC LIMIT 40')->fetchAll();
$cfg = app_config();
render_header('এসএমএস', 'sms', true);
?>
<h1>এসএমএস গেটওয়ে</h1>
<p>ডিফল্টে মেসেজ <code>data/</code> ডাটাবেস লগে জমা হয়। cPanel-এ SSL Wireless / BulkSMSBD / MIMSMS এর API URL ও কি দিন।</p>
<form method="post" class="card form">
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
  <input type="hidden" name="act" value="save">
  <label>প্রোভাইডার
    <select name="sms_provider">
      <option value="log" <?= ($cfg['sms_provider']??'')==='log'?'selected':'' ?>>শুধু লগ (টেস্ট)</option>
      <option value="http" <?= ($cfg['sms_provider']??'')==='http'?'selected':'' ?>>HTTP API</option>
    </select>
  </label>
  <label>API URL<input name="sms_api_url" value="<?= e($cfg['sms_api_url']??'') ?>" placeholder="https://bulksmsbd.net/api/smsapi"></label>
  <label>API Key<input name="sms_api_key" value="<?= e($cfg['sms_api_key']??'') ?>"></label>
  <label>Sender ID<input name="sms_sender" value="<?= e($cfg['sms_sender']??'MediSeba') ?>"></label>
  <label>মেথড
    <select name="sms_http_method">
      <option>GET</option>
      <option <?= ($cfg['sms_http_method']??'')==='POST'?'selected':'' ?>>POST</option>
    </select>
  </label>
  <label>রিমাইন্ডার কত মিনিট আগে<input type="number" name="reminder_minutes" value="<?= e($cfg['reminder_minutes']??30) ?>"></label>
  <button class="btn" type="submit">সেভ</button>
</form>
<form method="post" class="inline-form card">
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
  <input type="hidden" name="act" value="test">
  <label>টেস্ট ফোন<input name="test_phone" placeholder="01XXXXXXXXX" required></label>
  <button class="btn ghost" type="submit">টেস্ট পাঠান</button>
</form>
<h2>লগ</h2>
<div class="table-wrap">
<table class="table">
  <tr><th>সময়</th><th>ফোন</th><th>টাইপ</th><th>স্ট্যাটাস</th><th>মেসেজ</th></tr>
  <?php foreach ($logs as $l): ?>
    <tr>
      <td><?= e($l['created_at']) ?></td>
      <td><?= e($l['phone']) ?></td>
      <td><?= e($l['type']) ?></td>
      <td><?= e($l['status']) ?></td>
      <td><?= e(mb_strimwidth($l['message'], 0, 80, '…')) ?></td>
    </tr>
  <?php endforeach; ?>
</table>
</div>
<?php render_footer(); ?>
