<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/layout.php';
if (!empty($_SESSION['doctor_id'])) {
    redirect('dashboard.php');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $phone = normalize_phone($_POST['phone'] ?? '');
    $pass = $_POST['password'] ?? '';
    $stmt = db()->prepare('SELECT * FROM doctors WHERE phone=? AND is_active=1');
    $stmt->execute([$phone]);
    $d = $stmt->fetch();
    if ($d && password_verify($pass, $d['password_hash'])) {
        $_SESSION['doctor_id'] = (int) $d['id'];
        log_activity('doctor', 'login', $phone);
        redirect('dashboard.php');
    }
    flash('err', 'ফোন বা পাসওয়ার্ড ভুল।');
    redirect('login.php');
}
render_header('ডাক্তার লগইন', 'home');
?>
<form method="post" class="card form" style="max-width:420px;margin:40px auto">
  <h1>ডাক্তার লগইন</h1>
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
  <label>মোবাইল<input name="phone" required placeholder="01XXXXXXXXX"></label>
  <label>পাসওয়ার্ড<input name="password" type="password" required></label>
  <button class="btn" type="submit">ঢুকুন</button>
</form>
<?php render_footer(); ?>
