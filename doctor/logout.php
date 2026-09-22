<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
unset($_SESSION['doctor_id']);
redirect(base_url() . '/doctor/login.php');
