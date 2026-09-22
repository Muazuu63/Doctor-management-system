<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/config.php';
$config = app_config();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/sms.php';
require_once __DIR__ . '/appointments.php';

$script = basename($_SERVER['SCRIPT_NAME'] ?? '');
if ($script !== 'install.php' && empty($config['installed']) && PHP_SAPI !== 'cli') {
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    if (str_ends_with($dir, '/doctor') || str_ends_with($dir, '/api')) {
        $dir = dirname($dir);
    }
    if ($dir === '/' || $dir === '\\' || $dir === '.') {
        $dir = '';
    }
    header('Location: ' . $dir . '/install.php');
    exit;
}
