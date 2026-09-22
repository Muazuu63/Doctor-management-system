<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = app_config();
    $driver = $config['db_driver'] ?? 'sqlite';

    if ($driver === 'mysql') {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            $config['mysql_host'],
            $config['mysql_name']
        );
        $pdo = new PDO($dsn, $config['mysql_user'], $config['mysql_pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } else {
        $path = $config['sqlite_path'];
        $pdo = new PDO('sqlite:' . $path, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA journal_mode = WAL');
    }

    return $pdo;
}

function setting(string $key, $default = null)
{
    $config = app_config();
    if (array_key_exists($key, $config)) {
        return $config[$key];
    }
    try {
        $stmt = db()->prepare('SELECT svalue FROM settings WHERE skey = ?');
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return $row ? $row['svalue'] : $default;
    } catch (Throwable $e) {
        return $default;
    }
}

function save_config(array $data): void
{
    $file = DATA_PATH . '/config.json';
    $current = is_file($file) ? json_decode((string) file_get_contents($file), true) : [];
    if (!is_array($current)) {
        $current = [];
    }
    file_put_contents($file, json_encode(array_merge($current, $data), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    $GLOBALS['_app_config'] = null;
}

function clinic_name(): string
{
    return (string) setting('clinic_name', 'মেডিসেবা ক্লিনিক');
}
