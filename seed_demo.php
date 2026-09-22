<?php
declare(strict_types=1);
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/install_lib.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/sms.php';
require __DIR__ . '/includes/appointments.php';

if (PHP_SAPI !== 'cli' && empty($_GET['ok'])) {
    exit('Add ?ok=1');
}

$cfg = app_config();
if (empty($cfg['installed'])) {
    $pdo = new PDO('sqlite:' . DATA_PATH . '/clinic.db');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    run_schema($pdo, 'sqlite');
    seed_defaults($pdo);
    save_config([
        'installed' => true,
        'db_driver' => 'sqlite',
        'sqlite_path' => DATA_PATH . '/clinic.db',
        'clinic_name' => 'মেডিসেবা ক্লিনিক',
        'clinic_phone' => '01700000000',
        'clinic_address' => 'হাউস ১২, রোড ৭, ধানমন্ডি, ঢাকা',
        'slot_minutes' => 20,
        'sms_provider' => 'log',
    ]);
    $GLOBALS['_app_config'] = null;
}

$names = [
    ['রহিম উদ্দিন', '01711000001', 45, 'পুরুষ'],
    ['সালমা খাতুন', '01711000002', 32, 'নারী'],
    ['করিম মিয়া', '01711000003', 58, 'পুরুষ'],
    ['নুসরাত জাহান', '01711000004', 27, 'নারী'],
    ['আব্দুল মালেক', '01711000005', 61, 'পুরুষ'],
];
$date = date('Y-m-d');
$sch = schedule_for_date($date);
if (!$sch) {
    db()->prepare('INSERT INTO schedule_exceptions (doctor_id, except_date, is_off, title, start_time, end_time, slot_minutes, max_serial) VALUES (1,?,0,?,?,?,?,?)')
        ->execute([$date, 'ডেমো চেম্বার', '14:00', '18:00', 20, 50]);
}
foreach ($names as $n) {
    try {
        $p = find_or_create_patient($n[0], $n[1], ['age' => $n[2], 'gender' => $n[3]]);
        book_appointment($p, $date, null, 'ডেমো');
    } catch (Throwable $e) {
    }
}
echo "ok\n";
