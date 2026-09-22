<?php
require __DIR__ . '/includes/bootstrap.php';
$_GET['key'] = $_GET['key'] ?? substr(hash('sha256', (setting('clinic_phone') ?: 'clinic') . 'cron'), 0, 16);
require __DIR__ . '/api/remind.php';
