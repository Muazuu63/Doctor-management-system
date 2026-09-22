<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
redirect(base_url() . '/doctor/' . (empty($_SESSION['doctor_id']) ? 'login.php' : 'dashboard.php'));
