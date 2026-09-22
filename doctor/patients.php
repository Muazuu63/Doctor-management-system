<?php
require __DIR__ . '/_auth.php';
require_doctor();
$q = trim($_GET['q'] ?? '');
if ($q !== '') {
    $phone = normalize_phone($q);
    $stmt = db()->prepare('SELECT * FROM patients WHERE phone LIKE ? OR name LIKE ? ORDER BY id DESC LIMIT 50');
    $like = '%' . $q . '%';
    $stmt->execute([$phone ? '%'.$phone.'%' : $like, $like]);
} else {
    $stmt = db()->query('SELECT * FROM patients ORDER BY id DESC LIMIT 50');
}
$rows = $stmt->fetchAll();
render_header('রোগী', 'patients', true);
?>
<h1>রোগী খুঁজুন</h1>
<form method="get" class="inline-form card">
  <label>নাম বা ফোন (পরের ভিজিটে শুধু নম্বর দিলেই হিস্ট্রি উঠবে)
    <input name="q" value="<?= e($q) ?>" placeholder="01XXXXXXXXX" autofocus>
  </label>
  <button class="btn" type="submit">খুঁজুন</button>
</form>
<div class="table-wrap">
<table class="table">
  <tr><th>নাম</th><th>ফোন</th><th>বয়স</th><th>লিঙ্গ</th><th></th></tr>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= e($r['name']) ?></td>
      <td><?= e($r['phone']) ?></td>
      <td><?= e(bn_digits((int)$r['age'])) ?></td>
      <td><?= e($r['gender']) ?></td>
      <td><a href="patient.php?id=<?= (int)$r['id'] ?>">ফাইল</a></td>
    </tr>
  <?php endforeach; ?>
</table>
</div>
<?php render_footer(); ?>
