<?php
declare(strict_types=1);

function e($v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function bn_digits($n): string
{
    return strtr((string) $n, ['0'=>'০','1'=>'১','2'=>'২','3'=>'৩','4'=>'৪','5'=>'৫','6'=>'৬','7'=>'৭','8'=>'৮','9'=>'৯']);
}

function en_digits($n): string
{
    return strtr((string) $n, ['০'=>'0','১'=>'1','২'=>'2','৩'=>'3','৪'=>'4','৫'=>'5','৬'=>'6','৭'=>'7','৮'=>'8','৯'=>'9']);
}

function normalize_phone(string $phone): string
{
    $phone = preg_replace('/[^\d]/', '', en_digits($phone)) ?? '';
    if (str_starts_with($phone, '880') && strlen($phone) === 13) {
        $phone = '0' . substr($phone, 3);
    }
    if (strlen($phone) === 10 && str_starts_with($phone, '1')) {
        $phone = '0' . $phone;
    }
    return $phone;
}

function valid_bd_phone(string $phone): bool
{
    return (bool) preg_match('/^01[3-9]\d{8}$/', $phone);
}

function token(int $len = 16): string
{
    return bin2hex(random_bytes($len));
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function json_out($data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = token(12);
    }
    return $_SESSION['csrf'];
}

function csrf_check(): void
{
    $t = $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF'] ?? '';
    if (!$t || !hash_equals($_SESSION['csrf'] ?? '', (string) $t)) {
        http_response_code(419);
        exit('Invalid CSRF token');
    }
}

function flash(string $key, ?string $msg = null)
{
    if ($msg === null) {
        $v = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);
        return $v;
    }
    $_SESSION['flash'][$key] = $msg;
}

function bangla_day(int $w): string
{
    return ['রবিবার','সোমবার','মঙ্গলবার','বুধবার','বৃহস্পতিবার','শুক্রবার','শনিবার'][$w] ?? '';
}

function bangla_date(string $ymd): string
{
    $t = strtotime($ymd);
    $months = ['জানুয়ারি','ফেব্রুয়ারি','মার্চ','এপ্রিল','মে','জুন','জুলাই','আগস্ট','সেপ্টেম্বর','অক্টোবর','নভেম্বর','ডিসেম্বর'];
    return bn_digits(date('j', $t)) . ' ' . $months[(int) date('n', $t) - 1] . ' ' . bn_digits(date('Y', $t));
}

function format_time_12(string $hm): string
{
    $t = strtotime($hm);
    if ($t === false) {
        return $hm;
    }
    $h = (int) date('g', $t);
    $m = date('i', $t);
    $ampm = ((int) date('H', $t) < 12) ? 'সকাল' : (((int) date('H', $t) < 17) ? 'বিকাল' : 'সন্ধ্যা');
    if ((int) date('H', $t) >= 20) {
        $ampm = 'রাত';
    }
    if ((int) date('H', $t) < 6) {
        $ampm = 'রাত';
    }
    return $ampm . ' ' . bn_digits($h) . ':' . bn_digits($m);
}

function add_minutes(string $hm, int $mins): string
{
    return date('H:i', strtotime($hm . ' +' . $mins . ' minutes'));
}

function log_activity(string $actor, string $action, string $detail = ''): void
{
    try {
        db()->prepare('INSERT INTO activity_logs (actor, action, detail) VALUES (?,?,?)')->execute([$actor, $action, $detail]);
    } catch (Throwable $e) {
    }
}

function doctor_row(): array
{
    static $d = null;
    if ($d) {
        return $d;
    }
    $d = db()->query('SELECT * FROM doctors WHERE is_active = 1 ORDER BY id ASC LIMIT 1')->fetch() ?: [];
    return $d;
}

function current_delay(string $date, int $doctorId = 1): int
{
    $stmt = db()->prepare('SELECT delay_minutes FROM delays WHERE delay_date = ? AND doctor_id = ? ORDER BY id DESC LIMIT 1');
    $stmt->execute([$date, $doctorId]);
    $row = $stmt->fetch();
    return $row ? (int) $row['delay_minutes'] : 0;
}

function status_bn(string $s): string
{
    return [
        'booked' => 'বুকড',
        'waiting' => 'অপেক্ষমাণ',
        'in_chamber' => 'চেম্বারে',
        'done' => 'সম্পন্ন',
        'cancelled' => 'বাতিল',
        'no_show' => 'আসেননি',
    ][$s] ?? $s;
}

function base_url(): string
{
    $cfg = app_config();
    if (!empty($cfg['app_url'])) {
        return rtrim((string) $cfg['app_url'], '/');
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') == 443);
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    if (str_ends_with($script, '/doctor') || str_ends_with($script, '/api') || str_ends_with($script, '/patient')) {
        $script = dirname($script);
    }
    if ($script === '/' || $script === '\\' || $script === '.') {
        $script = '';
    }
    return rtrim($scheme . '://' . $host . $script, '/');
}

function require_install(): void
{
    $cfg = app_config();
    if (empty($cfg['installed'])) {
        redirect(base_url() . '/install.php');
    }
}
