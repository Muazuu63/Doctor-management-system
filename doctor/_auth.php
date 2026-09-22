<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/layout.php';

function require_doctor(): array
{
    if (empty($_SESSION['doctor_id'])) {
        redirect(base_url() . '/doctor/login.php');
    }
    $stmt = db()->prepare('SELECT * FROM doctors WHERE id=?');
    $stmt->execute([$_SESSION['doctor_id']]);
    $d = $stmt->fetch();
    if (!$d) {
        unset($_SESSION['doctor_id']);
        redirect(base_url() . '/doctor/login.php');
    }
    return $d;
}
