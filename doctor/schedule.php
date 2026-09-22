<?php
require __DIR__ . '/_auth.php';
$doc = require_doctor();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = $_POST['act'] ?? '';
    if ($act === 'save_week') {
        $id = (int) ($_POST['id'] ?? 0);
        $data = [
            trim($_POST['title'] ?? ''),
            (int) $_POST['day_of_week'],
            $_POST['start_time'],
            $_POST['end_time'],
            (int) ($_POST['slot_minutes'] ?? 20),
            (int) ($_POST['max_serial'] ?? 50),
            isset($_POST['is_active']) ? 1 : 0,
        ];
        if ($id) {
            db()->prepare('UPDATE schedules SET title=?, day_of_week=?, start_time=?, end_time=?, slot_minutes=?, max_serial=?, is_active=? WHERE id=?')
                ->execute([...$data, $id]);
        } else {
            db()->prepare('INSERT INTO schedules (doctor_id,title,day_of_week,start_time,end_time,slot_minutes,max_serial,is_active) VALUES (1,?,?,?,?,?,?,?)')
                ->execute($data);
        }
        flash('ok', 'সময়সূচি সেভ হয়েছে। নতুন বুকিং এই সময় অনুযায়ী হবে।');
    }
    if ($act === 'exception') {
        db()->prepare('INSERT INTO schedule_exceptions (doctor_id, except_date, is_off, title, start_time, end_time, slot_minutes, max_serial, note) VALUES (1,?,?,?,?,?,?,?,?)')
            ->execute([
                $_POST['except_date'],
                isset($_POST['is_off']) ? 1 : 0,
                trim($_POST['title'] ?? ''),
                $_POST['start_time'] ?: null,
                $_POST['end_time'] ?: null,
                (int) ($_POST['slot_minutes'] ?? 20),
                (int) ($_POST['max_serial'] ?? 50),
                trim($_POST['note'] ?? ''),
            ]);
        flash('ok', 'বিশেষ দিন সেভ হয়েছে।');
    }
    if ($act === 'del_ex') {
        db()->prepare('DELETE FROM schedule_exceptions WHERE id=?')->execute([(int)$_POST['id']]);
        flash('ok', 'বিশেষ দিন মুছে ফেলা হয়েছে।');
    }
    redirect('schedule.php');
}

$rows = db()->query('SELECT * FROM schedules ORDER BY day_of_week')->fetchAll();
$ex = db()->query("SELECT * FROM schedule_exceptions ORDER BY except_date DESC LIMIT 30")->fetchAll();
$edit = null;
if (!empty($_GET['edit'])) {
    $s = db()->prepare('SELECT * FROM schedules WHERE id=?');
    $s->execute([(int)$_GET['edit']]);
    $edit = $s->fetch();
}
render_header('সময়সূচি', 'schedule', true);
?>
<h1>চেম্বার সময়সূচি</h1>
<p>এখান থেকে আপনি নিজে সময় বদলালে পরবর্তী সিরিয়াল নতুন সময় থেকে শুরু হবে। প্রতি রোগী ১৫–২০ মিনিট।</p>

<div class="table-wrap card">
<table class="table">
  <tr><th>দিন</th><th>শিরোনাম</th><th>শুরু</th><th>শেষ</th><th>স্লট</th><th>সর্বোচ্চ</th><th>স্ট্যাটাস</th><th></th></tr>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= e(bangla_day((int)$r['day_of_week'])) ?></td>
      <td><?= e($r['title']) ?></td>
      <td><?= e($r['start_time']) ?></td>
      <td><?= e($r['end_time']) ?></td>
      <td><?= e(bn_digits($r['slot_minutes'])) ?> মি</td>
      <td><?= e(bn_digits($r['max_serial'])) ?></td>
      <td><?= (int)$r['is_active'] ? 'চলছে' : 'বন্ধ' ?></td>
      <td><a href="schedule.php?edit=<?= (int)$r['id'] ?>">এডিট</a></td>
    </tr>
  <?php endforeach; ?>
</table>
</div>

<div class="split">
  <form method="post" class="card form">
    <h2><?= $edit ? 'সময়সূচি এডিট' : 'নতুন দিন যোগ' ?></h2>
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="act" value="save_week">
    <input type="hidden" name="id" value="<?= (int)($edit['id']??0) ?>">
    <label>শিরোনাম<input name="title" required value="<?= e($edit['title']??'চেম্বার') ?>"></label>
    <label>দিন
      <select name="day_of_week">
        <?php for ($i=0;$i<7;$i++): ?>
          <option value="<?= $i ?>" <?= ((int)($edit['day_of_week']??-1)===$i)?'selected':'' ?>><?= e(bangla_day($i)) ?></option>
        <?php endfor; ?>
      </select>
    </label>
    <div class="row-2">
      <label>শুরু<input type="time" name="start_time" required value="<?= e($edit['start_time']??'16:00') ?>"></label>
      <label>শেষ<input type="time" name="end_time" required value="<?= e($edit['end_time']??'20:00') ?>"></label>
    </div>
    <div class="row-2">
      <label>স্লট মিনিট<input type="number" name="slot_minutes" value="<?= e($edit['slot_minutes']??20) ?>" min="10" max="60"></label>
      <label>সর্বোচ্চ সিরিয়াল<input type="number" name="max_serial" value="<?= e($edit['max_serial']??50) ?>" min="1" max="200"></label>
    </div>
    <label class="check"><input type="checkbox" name="is_active" <?= !isset($edit['is_active']) || $edit['is_active'] ? 'checked':'' ?>> সক্রিয়</label>
    <button class="btn" type="submit">সেভ</button>
  </form>

  <form method="post" class="card form">
    <h2>বিশেষ দিন / ছুটি</h2>
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="act" value="exception">
    <label>তারিখ<input type="date" name="except_date" required></label>
    <label class="check"><input type="checkbox" name="is_off"> পুরো দিন বন্ধ</label>
    <label>শিরোনাম<input name="title" placeholder="অতিরিক্ত চেম্বার"></label>
    <div class="row-2">
      <label>শুরু<input type="time" name="start_time"></label>
      <label>শেষ<input type="time" name="end_time"></label>
    </div>
    <div class="row-2">
      <label>স্লট<input type="number" name="slot_minutes" value="20"></label>
      <label>সর্বোচ্চ<input type="number" name="max_serial" value="50"></label>
    </div>
    <label>নোট<input name="note"></label>
    <button class="btn" type="submit">যোগ করুন</button>
  </form>
</div>

<h2>আসন্ন বিশেষ দিন</h2>
<ul class="plain">
  <?php foreach ($ex as $x): ?>
    <li>
      <?= e(bangla_date($x['except_date'])) ?> —
      <?= (int)$x['is_off'] ? 'বন্ধ' : e(($x['start_time']??'').'–'.($x['end_time']??'')) ?>
      <form method="post" style="display:inline">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="act" value="del_ex">
        <input type="hidden" name="id" value="<?= (int)$x['id'] ?>">
        <button class="linkish" type="submit">মুছুন</button>
      </form>
    </li>
  <?php endforeach; ?>
</ul>
<?php render_footer(); ?>
