<?php
declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}
if (!defined('DATA_PATH')) {
    define('DATA_PATH', BASE_PATH . '/data');
}
if (!defined('UPLOAD_PATH')) {
    define('UPLOAD_PATH', BASE_PATH . '/uploads');
}

date_default_timezone_set('Asia/Dhaka');

if (!is_dir(DATA_PATH)) {
    mkdir(DATA_PATH, 0755, true);
}

function app_config(): array
{
    if (!empty($GLOBALS['_app_config']) && is_array($GLOBALS['_app_config'])) {
        return $GLOBALS['_app_config'];
    }
    $defaults = [
        'installed' => false,
        'db_driver' => 'sqlite',
        'sqlite_path' => DATA_PATH . '/clinic.db',
        'mysql_host' => 'localhost',
        'mysql_name' => 'clinic',
        'mysql_user' => 'clinic',
        'mysql_pass' => '',
        'app_url' => '',
        'clinic_name' => 'মেডিসেবা ক্লিনিক',
        'clinic_name_en' => 'MediSeba Clinic',
        'clinic_tagline' => 'সময়মতো সেবা, অপেক্ষা নয়',
        'clinic_address' => 'হাউস ১২, রোড ৭, ধানমন্ডি, ঢাকা',
        'clinic_phone' => '01700000000',
        'clinic_email' => 'info@mediseba.local',
        'slot_minutes' => 20,
        'sms_provider' => 'log',
        'sms_api_url' => '',
        'sms_api_key' => '',
        'sms_sender' => 'MediSeba',
        'sms_http_method' => 'GET',
        'reminder_minutes' => 30,
    ];
    $configFile = DATA_PATH . '/config.json';
    if (is_file($configFile)) {
        $saved = json_decode((string) file_get_contents($configFile), true);
        if (is_array($saved)) {
            $defaults = array_merge($defaults, $saved);
        }
    }
    $GLOBALS['_app_config'] = $defaults;
    return $defaults;
}

return app_config();
