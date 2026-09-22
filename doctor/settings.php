<?php
require __DIR__ . '/_auth.php';
$doc = require_doctor();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    save_config([
        'clinic_name' => trim($_POST['clinic_name']),
        'clinic_tagline' => trim($_POST['clinic_tagline']),
        'clinic_address' => trim($_POST['clinic_address']),
        'clinic_phone' => trim($_POST['clinic_phone']),
        'clinic_email' => trim($_POST['clinic_email']),
        'slot_minutes' => (int) $_POST['slot_minutes'],
        'app_url' => rtrim(trim($_POST['app_url'] ?? ''), '/'),
    ]);
    $hashSql = '';
    $params = [
        trim($_POST['name']),
        trim($_POST['name_bn']),
        trim($_POST['degree']),
        trim($_POST['specialty']),
        normalize_phone($_POST['phone']),
        trim($_POST['email']),
        (int) $_POST['consultation_fee'],
        (int) $_POST['doc_slot'],
        trim($_POST['bio']),
        $doc['id'],
    ];
    $sql = 'UPDATE doctors SET name=?, name_bn=?, degree=?, specialty=?, phone=?, email=?, consultation_fee=?, slot_minutes=?, bio=? WHERE id=?';
    if (!empty($_POST['password'])) {
        $sql = 'UPDATE doctors SET name=?, name_bn=?, degree=?, specialty=?, phone=?, email=?, consultation_fee=?, slot_minutes=?, bio=?, password_hash=? WHERE id=?';
        array_splice($params, 9, 0, [password_hash($_POST['password'], PASSWORD_DEFAULT)]);
    }
    db()->prepare($sql)->execute($params);
    flash('ok', 'সেটিংস সেভ হয়েছে।');
    redirect('settings.php');
}
$cfg = app_config();
render_header('সেটিংস', 'settings', true);
?>
<h1>ক্লিনিক ও ডাক্তার সেটিংস</h1>
<form method="post" class="split">
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
  <div class="card form">
    <h2>ক্লিনিক</h2>
    <label>নাম<input name="clinic_name" value="<?= e($cfg['clinic_name']) ?>"></label>
    <label>ট্যাগলাইন<input name="clinic_tagline" value="<?= e($cfg['clinic_tagline']) ?>"></label>
    <label>ঠিকানা<input name="clinic_address" value="<?= e($cfg['clinic_address']) ?>"></label>
    <label>ফোন<input name="clinic_phone" value="<?= e($cfg['clinic_phone']) ?>"></label>
    <label>ইমেইল<input name="clinic_email" value="<?= e($cfg['clinic_email']) ?>"></label>
    <label>ডিফল্ট স্লট (মিনিট)<input type="number" name="slot_minutes" value="<?= e($cfg['slot_minutes']) ?>"></label>
    <label>সাইট URL<input name="app_url" value="<?= e($cfg['app_url']) ?>"></label>
  </div>
  <div class="card form">
    <h2>ডাক্তার প্রোফাইল</h2>
    <label>নাম<input name="name" value="<?= e($doc['name']) ?>"></label>
    <label>বাংলা নাম<input name="name_bn" value="<?= e($doc['name_bn']) ?>"></label>
    <label>ডিগ্রি<input name="degree" value="<?= e($doc['degree']) ?>"></label>
    <label>বিশেষত্ব<input name="specialty" value="<?= e($doc['specialty']) ?>"></label>
    <label>লগইন ফোন<input name="phone" value="<?= e($doc['phone']) ?>"></label>
    <label>ইমেইল<input name="email" value="<?= e($doc['email']) ?>"></label>
    <label>ফি<input type="number" name="consultation_fee" value="<?= e($doc['consultation_fee']) ?>"></label>
    <label>স্লট মিনিট<input type="number" name="doc_slot" value="<?= e($doc['slot_minutes']) ?>"></label>
    <label>বায়ো<textarea name="bio"><?= e($doc['bio']) ?></textarea></label>
    <label>নতুন পাসওয়ার্ড (খালি রাখলে বদলাবে না)<input type="password" name="password"></label>
    <button class="btn" type="submit">সেভ</button>
  </div>
</form>
<?php render_footer(); ?>
