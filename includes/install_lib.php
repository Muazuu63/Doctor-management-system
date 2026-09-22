<?php
declare(strict_types=1);

function mysqlify(string $sql): string
{
    $sql = str_replace('INTEGER PRIMARY KEY AUTOINCREMENT', 'INT AUTO_INCREMENT PRIMARY KEY', $sql);
    $sql = str_replace('INTEGER', 'INT', $sql);
    $sql = str_replace('TEXT', 'VARCHAR(500)', $sql);
    $sql = preg_replace('/CREATE TABLE IF NOT EXISTS (\w+)/', 'CREATE TABLE IF NOT EXISTS `$1`', $sql);
    return $sql;
}

function run_schema(PDO $pdo, string $driver): void
{
    $sql = file_get_contents(BASE_PATH . '/sql/schema.sql');
    if ($driver === 'mysql') {
        $sql = file_get_contents(BASE_PATH . '/sql/schema.mysql.sql');
    }
    foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
        if ($stmt !== '') {
            $pdo->exec($stmt);
        }
    }
}

function seed_defaults(PDO $pdo): void
{
    $exists = $pdo->query('SELECT COUNT(*) AS c FROM doctors')->fetch();
    if ((int) $exists['c'] > 0) {
        return;
    }

    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    $pdo->prepare('INSERT INTO doctors (name, name_bn, degree, specialty, phone, email, password_hash, consultation_fee, slot_minutes, bio)
        VALUES (?,?,?,?,?,?,?,?,?,?)')->execute([
        'Dr. Ayesha Rahman',
        'ডা. আয়েশা রহমান',
        'MBBS, FCPS (Medicine)',
        'মেডিসিন বিশেষজ্ঞ',
        '01700000000',
        'doctor@mediseba.local',
        $hash,
        600,
        20,
        '১২ বছরের অভিজ্ঞতাসম্পন্ন মেডিসিন বিশেষজ্ঞ। রোগীকে সময়মতো দেখা এবং স্পষ্ট প্রেসক্রিপশন দেওয়ার নীতিতে বিশ্বাসী।',
    ]);

    $days = [
        [6, 'শনিবার চেম্বার', '14:00', '18:00'],
        [0, 'রবিবার চেম্বার', '16:00', '20:00'],
        [1, 'সোমবার চেম্বার', '16:00', '20:00'],
        [2, 'মঙ্গলবার চেম্বার', '16:00', '20:00'],
        [3, 'বুধবার চেম্বার', '16:00', '20:00'],
        [4, 'বৃহস্পতিবার চেম্বার', '16:00', '20:00'],
    ];
    $ins = $pdo->prepare('INSERT INTO schedules (doctor_id, title, day_of_week, start_time, end_time, slot_minutes, max_serial, is_active) VALUES (1,?,?,?,?,20,50,1)');
    foreach ($days as $d) {
        $ins->execute([$d[1], $d[0], $d[2], $d[3]]);
    }

    $meds = [
        ['Napa 500mg', '১ + ১ + ১', '৫ দিন', 'খাওয়ার পর'],
        ['Seclo 20mg', '১ + ০ + ১', '১৪ দিন', 'খাওয়ার আগে'],
        ['Fexo 120mg', '০ + ০ + ১', '৭ দিন', 'রাত্রে'],
        ['Monas 10mg', '০ + ০ + ১', '৩০ দিন', 'রাত্রে'],
        ['ORSaline-N', 'প্রয়োজনমতো', '৩ দিন', 'পানির সাথে'],
        ['Azithro 500mg', '১ + ০ + ০', '৩ দিন', 'খাওয়ার পর'],
    ];
    $m = $pdo->prepare('INSERT INTO medicine_templates (doctor_id, name, dose, duration, instruction) VALUES (1,?,?,?,?)');
    foreach ($meds as $row) {
        $m->execute($row);
    }
}
