<?php
declare(strict_types=1);

function schedule_for_date(string $date, int $doctorId = 1): ?array
{
    $stmt = db()->prepare('SELECT * FROM schedule_exceptions WHERE except_date = ? AND doctor_id = ? LIMIT 1');
    $stmt->execute([$date, $doctorId]);
    $ex = $stmt->fetch();
    if ($ex) {
        if ((int) $ex['is_off'] === 1) {
            return null;
        }
        return [
            'id' => 0,
            'title' => $ex['title'] ?: 'বিশেষ চেম্বার',
            'start_time' => $ex['start_time'],
            'end_time' => $ex['end_time'],
            'slot_minutes' => (int) $ex['slot_minutes'],
            'max_serial' => (int) $ex['max_serial'],
            'is_exception' => true,
        ];
    }
    $w = (int) date('w', strtotime($date));
    $stmt = db()->prepare('SELECT * FROM schedules WHERE doctor_id = ? AND day_of_week = ? AND is_active = 1 LIMIT 1');
    $stmt->execute([$doctorId, $w]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function generate_slots(array $schedule, string $date, int $doctorId = 1): array
{
    $slot = (int) ($schedule['slot_minutes'] ?: 20);
    $max = (int) ($schedule['max_serial'] ?: 50);
    $start = $schedule['start_time'];
    $end = $schedule['end_time'];
    $delay = current_delay($date, $doctorId);

    $taken = [];
    $stmt = db()->prepare("SELECT serial_no, status FROM appointments WHERE appointment_date = ? AND doctor_id = ? AND status != 'cancelled'");
    $stmt->execute([$date, $doctorId]);
    foreach ($stmt->fetchAll() as $r) {
        $taken[(int) $r['serial_no']] = $r['status'];
    }

    $slots = [];
    $t = strtotime($start);
    $endTs = strtotime($end);
    $serial = 1;
    while ($t < $endTs && $serial <= $max) {
        $orig = date('H:i', $t);
        $adj = date('H:i', $t + ($delay * 60));
        $slots[] = [
            'serial' => $serial,
            'original_time' => $orig,
            'scheduled_time' => $adj,
            'available' => !isset($taken[$serial]),
            'status' => $taken[$serial] ?? null,
        ];
        $t += $slot * 60;
        $serial++;
    }
    return $slots;
}

function next_available_slot(string $date, int $doctorId = 1): ?array
{
    $sch = schedule_for_date($date, $doctorId);
    if (!$sch) {
        return null;
    }
    foreach (generate_slots($sch, $date, $doctorId) as $s) {
        if ($s['available']) {
            return $s + ['schedule' => $sch];
        }
    }
    return null;
}

function find_or_create_patient(string $name, string $phone, array $extra = []): array
{
    $phone = normalize_phone($phone);
    $stmt = db()->prepare('SELECT * FROM patients WHERE phone = ?');
    $stmt->execute([$phone]);
    $p = $stmt->fetch();
    if ($p) {
        $fields = [];
        $vals = [];
        if ($name && $name !== $p['name']) {
            $fields[] = 'name = ?';
            $vals[] = $name;
        }
        foreach (['gender', 'age', 'blood_group', 'address'] as $f) {
            if (!empty($extra[$f]) && (string) $extra[$f] !== (string) $p[$f]) {
                $fields[] = "$f = ?";
                $vals[] = $extra[$f];
            }
        }
        if ($fields) {
            $vals[] = $p['id'];
            db()->prepare('UPDATE patients SET ' . implode(',', $fields) . ' WHERE id = ?')->execute($vals);
            $stmt->execute([$phone]);
            $p = $stmt->fetch();
        }
        return $p;
    }
    $pin = (string) random_int(1000, 9999);
    db()->prepare('INSERT INTO patients (name, phone, pin, gender, age, blood_group, address) VALUES (?,?,?,?,?,?,?)')
        ->execute([
            $name,
            $phone,
            $pin,
            $extra['gender'] ?? '',
            (int) ($extra['age'] ?? 0),
            $extra['blood_group'] ?? '',
            $extra['address'] ?? '',
        ]);
    $id = (int) db()->lastInsertId();
    $stmt = db()->prepare('SELECT * FROM patients WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function book_appointment(array $patient, string $date, ?int $serial = null, string $notes = ''): array
{
    $doctor = doctor_row();
    $doctorId = (int) $doctor['id'];
    $sch = schedule_for_date($date, $doctorId);
    if (!$sch) {
        throw new RuntimeException('এই তারিখে চেম্বার বন্ধ।');
    }
    if (strtotime($date) < strtotime(date('Y-m-d'))) {
        throw new RuntimeException('গত তারিখে সিরিয়াল নেওয়া যায় না।');
    }

    $slots = generate_slots($sch, $date, $doctorId);
    $chosen = null;
    if ($serial) {
        foreach ($slots as $s) {
            if ($s['serial'] === $serial) {
                $chosen = $s;
                break;
            }
        }
        if (!$chosen || !$chosen['available']) {
            throw new RuntimeException('এই সিরিয়াল ইতিমধ্যে নেওয়া হয়েছে।');
        }
    } else {
        foreach ($slots as $s) {
            if ($s['available']) {
                $chosen = $s;
                break;
            }
        }
        if (!$chosen) {
            throw new RuntimeException('আজকের সব সিরিয়াল শেষ।');
        }
    }

    $dup = db()->prepare("SELECT id FROM appointments WHERE patient_id = ? AND appointment_date = ? AND doctor_id = ? AND status != 'cancelled'");
    $dup->execute([$patient['id'], $date, $doctorId]);
    if ($dup->fetch()) {
        throw new RuntimeException('এই নম্বরে আজকের সিরিয়াল আগেই নেওয়া আছে।');
    }

    $tok = token(10);
    db()->prepare('INSERT INTO appointments (patient_id, doctor_id, schedule_id, appointment_date, serial_no, scheduled_time, original_time, slot_minutes, delay_minutes, status, token, notes)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?)')->execute([
        $patient['id'],
        $doctorId,
        $sch['id'] ?? 0,
        $date,
        $chosen['serial'],
        $chosen['scheduled_time'],
        $chosen['original_time'],
        $sch['slot_minutes'],
        current_delay($date, $doctorId),
        'booked',
        $tok,
        $notes,
    ]);
    $id = (int) db()->lastInsertId();
    $stmt = db()->prepare('SELECT * FROM appointments WHERE id = ?');
    $stmt->execute([$id]);
    $apt = $stmt->fetch();
    sms_booking($apt, $patient, $doctor);
    log_activity('patient', 'book', 'serial ' . $chosen['serial'] . ' on ' . $date);
    return $apt;
}

function apply_delay(string $date, int $minutes, string $reason = '', int $doctorId = 1): int
{
    db()->prepare('INSERT INTO delays (doctor_id, delay_date, delay_minutes, reason) VALUES (?,?,?,?)')
        ->execute([$doctorId, $date, $minutes, $reason]);

    $stmt = db()->prepare("SELECT a.*, p.name AS patient_name, p.phone FROM appointments a JOIN patients p ON p.id = a.patient_id WHERE a.appointment_date = ? AND a.doctor_id = ? AND a.status IN ('booked','waiting') ORDER BY a.serial_no");
    $stmt->execute([$date, $doctorId]);
    $rows = $stmt->fetchAll();
    $upd = db()->prepare('UPDATE appointments SET scheduled_time = ?, delay_minutes = ? WHERE id = ?');
    $count = 0;
    foreach ($rows as $r) {
        $newTime = add_minutes($r['original_time'], $minutes);
        $upd->execute([$newTime, $minutes, $r['id']]);
        $r['scheduled_time'] = $newTime;
        sms_reschedule($r, ['name' => $r['patient_name'], 'phone' => $r['phone']], $minutes);
        $count++;
    }
    log_activity('doctor', 'delay', $minutes . ' min on ' . $date);
    return $count;
}

function upcoming_dates(int $days = 14, int $doctorId = 1): array
{
    $out = [];
    for ($i = 0; $i < $days; $i++) {
        $d = date('Y-m-d', strtotime('+' . $i . ' days'));
        $sch = schedule_for_date($d, $doctorId);
        if (!$sch) {
            continue;
        }
        $slots = generate_slots($sch, $d, $doctorId);
        $free = count(array_filter($slots, fn($s) => $s['available']));
        $out[] = [
            'date' => $d,
            'label' => bangla_date($d),
            'day' => bangla_day((int) date('w', strtotime($d))),
            'start' => $sch['start_time'],
            'end' => $sch['end_time'],
            'title' => $sch['title'],
            'total' => count($slots),
            'free' => $free,
        ];
    }
    return $out;
}

function patient_history(int $patientId): array
{
    $stmt = db()->prepare('SELECT a.*, pr.id AS prescription_id, pr.diagnosis
        FROM appointments a
        LEFT JOIN prescriptions pr ON pr.appointment_id = a.id
        WHERE a.patient_id = ?
        ORDER BY a.appointment_date DESC, a.serial_no DESC');
    $stmt->execute([$patientId]);
    return $stmt->fetchAll();
}
