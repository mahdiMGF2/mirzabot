<?php

declare(strict_types=1);

date_default_timezone_set('Asia/Tehran');
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);
@set_time_limit(300);

require_once __DIR__ . '/checks.php';

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

function mirza_install_json(array $payload, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function mirza_install_locked(): bool
{
    return is_file(mirza_install_lock_file());
}

function mirza_install_authorized(): bool
{
    if (!mirza_install_is_configured()) {
        return true;
    }

    return !empty($_SESSION['mirza_install_authorized']);
}

function mirza_install_group(string $group, array $items): array
{
    foreach ($items as $index => $item) {
        $items[$index]['group'] = $group;
    }

    return $items;
}

$action = (string) ($_POST['action'] ?? ($_GET['action'] ?? ''));

$mirza_install_mutating_actions = ['auth', 'config_write', 'bootstrap', 'probe_begin', 'finish'];
if (in_array($action, $mirza_install_mutating_actions, true) && ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    mirza_install_json(['error' => 'POST required'], 405);
}

if ($action !== '') {
    if (mirza_install_locked() && $action !== 'state') {
        mirza_install_json(['error' => 'نصب قبلاً انجام شده است. برای اجرای مجدد فایل install/.installed را حذف کنید.'], 423);
    }

    if ($action === 'state') {
        mirza_install_json([
            'locked' => mirza_install_locked(),
            'configured' => mirza_install_is_configured(),
            'authorized' => mirza_install_authorized(),
            'shell_exec' => mirza_install_shell_exec_available(),
            'host' => mirza_install_host(),
            'base_url' => mirza_install_base_url(),
            'root' => mirza_install_root(),
        ]);
    }

    if ($action === 'auth') {
        $secret = trim((string) ($_POST['secret'] ?? ''));
        $values = mirza_install_config_values();
        $matches = $secret !== '' && hash_equals($values['APIKEY'], $secret);

        if (!$matches) {
            usleep(700000);
            mirza_install_json(['ok' => false, 'error' => 'توکن ربات نادرست است.'], 403);
        }

        $_SESSION['mirza_install_authorized'] = true;
        mirza_install_json(['ok' => true]);
    }

    if (!mirza_install_authorized()) {
        mirza_install_json(['error' => 'برای ادامه ابتدا هویت خود را تأیید کنید.'], 403);
    }

    if ($action === 'requirements') {
        $items = array_merge(
            mirza_install_group('نسخه PHP', mirza_install_php_check()),
            mirza_install_group('وب‌سرور', mirza_install_webserver_check()),
            mirza_install_group('اکستنشن‌ها', mirza_install_extensions_check()),
            mirza_install_group('تنظیمات PHP', mirza_install_ini_check())
        );
        mirza_install_json(mirza_install_result($items));
    }

    if ($action === 'ssl') {
        mirza_install_json(mirza_install_result(mirza_install_group('دامنه و گواهی', mirza_install_ssl_check())));
    }

    if ($action === 'paths') {
        mirza_install_json(mirza_install_result(mirza_install_group('ساختار فایل‌ها', mirza_install_paths_check())));
    }

    if ($action === 'config_load') {
        $values = mirza_install_config_values();
        foreach ($values as $key => $value) {
            if (mirza_install_is_placeholder($value)) {
                $values[$key] = '';
            }
        }
        $values['passworddb'] = '';
        if ($values['dbhost'] === '') {
            $values['dbhost'] = 'localhost';
        }
        $values['domainhosts'] = mirza_install_host();
        mirza_install_json(['ok' => true, 'values' => $values]);
    }

    if ($action === 'config_check') {
        $values = [
            'dbhost' => trim((string) ($_POST['dbhost'] ?? '')),
            'dbname' => trim((string) ($_POST['dbname'] ?? '')),
            'usernamedb' => trim((string) ($_POST['usernamedb'] ?? '')),
            'passworddb' => (string) ($_POST['passworddb'] ?? ''),
            'APIKEY' => trim((string) ($_POST['APIKEY'] ?? '')),
            'adminnumber' => trim((string) ($_POST['adminnumber'] ?? '')),
            'domainhosts' => mirza_install_host(),
            'usernamebot' => '',
        ];

        if ($values['dbname'] === '' || $values['usernamedb'] === '' || $values['APIKEY'] === '' || $values['adminnumber'] === '') {
            mirza_install_json(['ok' => false, 'error' => 'نام دیتابیس، کاربر دیتابیس، توکن ربات و آیدی عددی مدیر الزامی هستند.', 'items' => []], 400);
        }
        if ($values['dbhost'] === '') {
            $values['dbhost'] = 'localhost';
        }
        if (!preg_match('/^\d{5,15}$/', $values['adminnumber'])) {
            mirza_install_json(['ok' => false, 'error' => 'آیدی عددی مدیر باید فقط عدد باشد.', 'items' => []], 400);
        }
        if (!preg_match('/^\d{6,}:[A-Za-z0-9_-]{30,}$/', $values['APIKEY'])) {
            mirza_install_json(['ok' => false, 'error' => 'قالب توکن ربات نادرست است.', 'items' => []], 400);
        }

        $database = mirza_install_test_database($values);
        $_SESSION['mirza_install_values'] = $values;

        mirza_install_json([
            'ok' => $database['ok'],
            'error' => $database['ok'] ? '' : 'بررسی دیتابیس با خطا مواجه شد؛ موارد قرمز را برطرف کنید.',
            'items' => $database['items'],
        ], $database['ok'] ? 200 : 400);
    }

    if ($action === 'config_token') {
        $values = $_SESSION['mirza_install_values'] ?? null;
        if (!is_array($values)) {
            mirza_install_json(['ok' => false, 'error' => 'اطلاعات فرم یافت نشد؛ صفحه را دوباره باز کنید.', 'items' => []], 400);
        }

        $bot = mirza_install_telegram($values['APIKEY'], 'getMe');
        if (!$bot['ok']) {
            mirza_install_json([
                'ok' => false,
                'error' => 'توکن ربات معتبر نیست: ' . $bot['error'],
                'items' => [mirza_install_item('fail', 'اعتبارسنجی توکن', 'ناموفق', $bot['error'])],
            ], 400);
        }

        $values['usernamebot'] = ltrim(trim((string) ($bot['result']['username'] ?? '')), '@');
        if ($values['usernamebot'] === '') {
            mirza_install_json([
                'ok' => false,
                'error' => 'یوزرنیم ربات از تلگرام دریافت نشد.',
                'items' => [mirza_install_item('fail', 'یوزرنیم ربات', 'دریافت نشد', 'پاسخ تلگرام یوزرنیم نداشت؛ توکن را بررسی کنید.')],
            ], 400);
        }
        $_SESSION['mirza_install_values'] = $values;

        $items = [
            mirza_install_item('ok', 'ارتباط با API تلگرام', 'برقرار است'),
            mirza_install_item('ok', 'ربات شناسایی شد', '@' . $values['usernamebot'], (string) ($bot['result']['first_name'] ?? '')),
        ];

        mirza_install_json(['ok' => true, 'error' => '', 'items' => $items]);
    }

    if ($action === 'config_write') {
        $values = $_SESSION['mirza_install_values'] ?? null;
        if (!is_array($values) || $values['usernamebot'] === '') {
            mirza_install_json(['ok' => false, 'error' => 'ابتدا باید توکن ربات تأیید شود.', 'items' => []], 400);
        }

        $written = mirza_install_write_config($values);
        if (!$written['ok']) {
            mirza_install_json([
                'ok' => false,
                'error' => $written['error'],
                'items' => [mirza_install_item('fail', 'نوشتن config.php', 'ناموفق', $written['error'])],
            ], 500);
        }

        $_SESSION['mirza_install_authorized'] = true;

        mirza_install_json([
            'ok' => true,
            'error' => '',
            'items' => [
                mirza_install_item('ok', 'فایل config.php ساخته شد', $values['domainhosts'], 'نسخه قبلی در install/state بکاپ گرفته شد.'),
            ],
        ]);
    }

    if ($action === 'bootstrap') {
        $mirzaRoot = mirza_install_root();
        $mirzaPreviousDirectory = getcwd();
        @chdir($mirzaRoot);
        @set_time_limit(300);
        $mirzaBootstrapError = '';
        $mirzaBootstrapCompleted = false;

        register_shutdown_function(static function () use (&$mirzaBootstrapCompleted) {
            if ($mirzaBootstrapCompleted) {
                return;
            }
            $output = ob_get_level() > 0 ? trim((string) ob_get_clean()) : '';
            if (!headers_sent()) {
                http_response_code(500);
                header('Content-Type: application/json; charset=utf-8');
            }
            echo json_encode([
                'ok' => false,
                'error' => 'ساخت جداول نیمه‌کاره متوقف شد: ' . ($output !== '' ? $output : 'خطای نامشخص سرور'),
                'items' => [mirza_install_item('fail', 'ساخت جداول', 'متوقف شد', $output !== '' ? $output : 'پاسخی از سرور دریافت نشد.')],
            ], JSON_UNESCAPED_UNICODE);
        });

        ob_start();
        try {
            require_once $mirzaRoot . '/db/bootstrap.php';
        } catch (Throwable $mirzaBootstrapException) {
            $mirzaBootstrapError = $mirzaBootstrapException->getMessage();
        }
        ob_end_clean();
        $mirzaBootstrapCompleted = true;

        if ($mirzaPreviousDirectory !== false) {
            @chdir($mirzaPreviousDirectory);
        }

        if ($mirzaBootstrapError !== '') {
            mirza_install_json([
                'ok' => false,
                'error' => 'ساخت جداول ناموفق بود: ' . $mirzaBootstrapError,
                'items' => [mirza_install_item('fail', 'ساخت جداول', 'ناموفق', $mirzaBootstrapError)],
            ], 500);
        }

        mirza_install_json([
            'ok' => true,
            'error' => '',
            'items' => [
                mirza_install_item('ok', 'جداول دیتابیس', 'ساخته و به‌روزرسانی شد', 'جداول، ایندکس‌ها و مهاجرت‌ها اعمال شدند.'),
                mirza_install_item('ok', 'وبهوک تلگرام', 'در مرحله پایانی ست می‌شود'),
            ],
        ]);
    }

    if ($action === 'cron_plan') {
        mirza_install_json([
            'ok' => true,
            'jobs' => mirza_install_cron_plan(),
            'required' => mirza_install_required_jobs(),
            'probe' => mirza_install_probe_status(),
        ]);
    }

    if ($action === 'probe_begin') {
        mirza_install_probe_reset();
        mirza_install_json(mirza_install_probe_status());
    }

    if ($action === 'probe_status') {
        mirza_install_json(mirza_install_probe_status());
    }

    if ($action === 'finish') {
        if (!mirza_install_shell_exec_available()) {
            $probe = mirza_install_probe_status();
            if (!$probe['verified']) {
                mirza_install_json(['ok' => false, 'error' => 'اجرای کرون هاست هنوز تأیید نشده است.'], 400);
            }

            $confirmed = json_decode((string) ($_POST['confirmed'] ?? '[]'), true);
            $confirmed = is_array($confirmed) ? array_map('strval', $confirmed) : [];
            $missing = array_diff(mirza_install_required_jobs(), $confirmed);
            if ($missing !== []) {
                mirza_install_json(['ok' => false, 'error' => 'این کرون‌ها هنوز تأیید نشده‌اند: ' . implode('، ', $missing)], 400);
            }
        }

        if (!mirza_install_is_configured()) {
            mirza_install_json(['ok' => false, 'error' => 'ابتدا باید مرحله تنظیمات ربات کامل شود.'], 400);
        }

        $values = mirza_install_config_values();
        $webhookUrl = 'https://' . $values['domainhosts'] . '/index.php';
        $webhookSecret = mirza_install_webhook_secret($values);
        $webhookTarget = $webhookSecret === ''
            ? $webhookUrl
            : $webhookUrl . '?secret=' . rawurlencode($webhookSecret);
        $reactivateUrl = 'https://' . $values['domainhosts'] . '/table.php';

        @file_put_contents(mirza_install_lock_file(), (string) time());
        $deleted = mirza_install_delete_tree(__DIR__);

        if (!$deleted) {
            mirza_install_telegram($values['APIKEY'], 'deleteWebhook', []);

            mirza_install_json([
                'ok' => false,
                'deleted' => false,
                'disabled' => true,
                'steps' => [
                    ['status' => 'fail', 'label' => 'حذف پوشه install', 'detail' => 'حذف خودکار انجام نشد'],
                    ['status' => 'fail', 'label' => 'وضعیت ربات', 'detail' => 'وبهوک حذف شد و ربات غیرفعال است'],
                ],
                'error' => 'پوشه install حذف نشد، بنابراین ربات غیرفعال شد. پوشه install را با فایل‌منیجر یا FTP دستی حذف کنید، سپس یک بار آدرس ' . $reactivateUrl . ' را در مرورگر باز کنید تا ربات دوباره فعال شود.',
                'reactivate_url' => $reactivateUrl,
            ], 500);
        }

        $steps = [['status' => 'ok', 'label' => 'حذف پوشه install', 'detail' => 'نصب‌کننده از روی هاست پاک شد و مسدودسازی ربات برداشته شد']];

        $webhook = mirza_install_telegram($values['APIKEY'], 'setWebhook', [
            'url' => $webhookTarget,
            'max_connections' => 40,
        ]);
        if (!$webhook['ok']) {
            mirza_install_json([
                'ok' => false,
                'deleted' => true,
                'steps' => $steps,
                'error' => 'پوشه install حذف شد ولی تنظیم وبهوک ناموفق بود: ' . $webhook['error'] . ' — یک بار آدرس ' . $reactivateUrl . ' را در مرورگر باز کنید تا وبهوک ست شود.',
                'reactivate_url' => $reactivateUrl,
            ], 500);
        }

        $steps[] = ['status' => 'ok', 'label' => 'تنظیم وبهوک تلگرام', 'detail' => $webhookUrl];

        $info = mirza_install_telegram($values['APIKEY'], 'getWebhookInfo');
        if ($info['ok']) {
            $lastError = (string) ($info['result']['last_error_message'] ?? '');
            $steps[] = [
                'status' => $lastError === '' ? 'ok' : 'warn',
                'label' => 'وضعیت وبهوک',
                'detail' => $lastError === ''
                    ? 'بدون خطا، ' . (int) ($info['result']['pending_update_count'] ?? 0) . ' آپدیت در صف'
                    : 'آخرین خطای تلگرام: ' . $lastError,
            ];
        }

        mirza_install_telegram($values['APIKEY'], 'sendMessage', [
            'chat_id' => $values['adminnumber'],
            'text' => 'ربات میرزا روی هاست نصب شد. برای شروع دستور /start را بفرستید.',
        ]);

        mirza_install_json([
            'ok' => true,
            'deleted' => true,
            'steps' => $steps,
            'bot_url' => $values['usernamebot'] !== '' ? 'https://t.me/' . $values['usernamebot'] : '',
        ]);
    }

    mirza_install_json(['error' => 'درخواست نامعتبر است.'], 400);
}

$locked = mirza_install_locked();
$configured = mirza_install_is_configured();
$authorized = mirza_install_authorized();
$host = mirza_install_host();

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>نصب ربات میرزا</title>
    <style>
        @font-face {
            font-family: Vazirmatn;
            src: url("fonts/Vazirmatn-Regular.woff2") format("woff2");
            font-weight: 400;
            font-display: swap;
        }

        @font-face {
            font-family: Vazirmatn;
            src: url("fonts/Vazirmatn-Medium.woff2") format("woff2");
            font-weight: 500;
            font-display: swap;
        }

        @font-face {
            font-family: Vazirmatn;
            src: url("fonts/Vazirmatn-ExtraBold.woff2") format("woff2");
            font-weight: 800;
            font-display: swap;
        }

        :root {
            --void: #0d1014;
            --surface: #141920;
            --surface-2: #1a2028;
            --hairline: #242c37;
            --hairline-soft: #1d242d;
            --text: #e7ecf3;
            --dim: #8b96a6;
            --faint: #616c7c;
            --pass: #4cc38a;
            --warn: #ffb454;
            --fail: #ff6b6b;
            --live: #6ea8fe;
            --mono: ui-monospace, "SF Mono", SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace;
            --sans: Vazirmatn, "Segoe UI", system-ui, sans-serif;
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            background: var(--void);
            color: var(--text);
            font-family: var(--sans);
            font-size: 14.5px;
            line-height: 1.85;
            -webkit-font-smoothing: antialiased;
        }

        .wrap {
            max-width: 860px;
            margin: 0 auto;
            padding: 0 20px 96px;
        }

        header.top {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 16px;
            padding: 26px 0 18px;
            margin-bottom: 26px;
            border-bottom: 1px solid var(--hairline-soft);
            animation: rise .45s ease both;
        }

        header.top h1 {
            margin: 0;
            font-size: 19px;
            font-weight: 800;
            letter-spacing: -.015em;
        }

        header.top .rule {
            display: none;
        }

        header.top p {
            margin: 0;
            font-size: 11.5px;
            color: var(--faint);
            letter-spacing: .02em;
        }

        header.top b {
            font-family: var(--mono);
            font-weight: 400;
            font-size: 12px;
            color: var(--dim);
        }

        .steps {
            display: flex;
            gap: 3px;
            margin-bottom: 28px;
        }

        .steps span {
            flex: 1;
            min-width: 0;
            font-size: 11.5px;
            font-weight: 500;
            letter-spacing: .01em;
            padding: 10px 6px 0;
            border-top: 2px solid var(--hairline);
            color: var(--faint);
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            transition: color .25s ease, border-color .25s ease;
        }

        .steps span.done {
            border-top-color: var(--pass);
            color: var(--dim);
        }

        .steps span.active {
            border-top-color: var(--live);
            color: var(--text);
        }

        .progress {
            display: none;
        }

        .card {
            background: var(--surface);
            border: 1px solid var(--hairline-soft);
            border-radius: 10px;
            padding: 0;
        }

        .card > .inner {
            padding: 26px 24px 22px;
            animation: rise .4s ease both;
        }

        .card h2 {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            letter-spacing: -.01em;
        }

        .card .lead {
            margin: 8px 0 22px;
            color: var(--dim);
            font-size: 13px;
            line-height: 1.8;
        }

        .group-title {
            margin: 26px 0 10px;
            font-size: 11.5px;
            font-weight: 500;
            letter-spacing: .02em;
            color: var(--faint);
        }

        .group-title:first-child {
            margin-top: 0;
        }

        .meter {
            display: flex;
            gap: 2px;
            margin-bottom: 12px;
            height: 8px;
        }

        .meter i {
            flex: 1;
            border-radius: 2px;
            background: var(--hairline);
            transform: scaleY(.28);
            transform-origin: bottom;
            opacity: 0;
            animation: seg .34s cubic-bezier(.2, .8, .3, 1) forwards;
        }

        .meter i.ok {
            background: var(--pass);
        }

        .meter i.warn {
            background: var(--warn);
        }

        .meter i.fail {
            background: var(--fail);
        }

        .tally {
            display: flex;
            gap: 18px;
            font-size: 11.5px;
            color: var(--faint);
            margin-bottom: 22px;
            letter-spacing: .02em;
        }

        .tally b {
            font-weight: 400;
        }

        .tally .n {
            font-family: var(--mono);
            font-size: 12px;
            color: var(--dim);
        }

        .tally .ok .n {
            color: var(--pass);
        }

        .tally .warn .n {
            color: var(--warn);
        }

        .tally .fail .n {
            color: var(--fail);
        }

        .row {
            display: flex;
            gap: 14px;
            padding: 11px 14px 11px 14px;
            background: var(--surface-2);
            border-radius: 7px;
            border-right: 2px solid var(--hairline);
            margin-bottom: 4px;
            animation: rowIn .3s ease both;
        }

        .row.ok {
            border-right-color: rgba(76, 195, 138, .4);
        }

        .row.warn {
            border-right-color: var(--warn);
        }

        .row.fail {
            border-right-color: var(--fail);
        }

        .row .body {
            flex: 1;
            min-width: 0;
        }

        .row .line {
            display: flex;
            align-items: baseline;
            gap: 14px;
            justify-content: space-between;
        }

        .row .label {
            font-size: 13.5px;
            font-weight: 500;
        }

        .row .value {
            font-size: 12.5px;
            color: var(--dim);
            direction: ltr;
            text-align: left;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 46%;
            flex: 0 1 auto;
        }

        .row.warn .value {
            color: var(--warn);
        }

        .row.fail .value {
            color: var(--fail);
        }

        .row .hint {
            font-size: 12.5px;
            color: var(--dim);
            margin-top: 3px;
            line-height: 1.75;
        }

        .summary {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }

        .pill {
            font-family: var(--mono);
            font-size: 11.5px;
            padding: 3px 11px;
            border: 1px solid var(--hairline);
            border-radius: 20px;
            color: var(--dim);
        }

        .pill.ok {
            color: var(--pass);
            border-color: rgba(76, 195, 138, .3);
        }

        .pill.warn {
            color: var(--warn);
            border-color: rgba(255, 180, 84, .3);
        }

        .pill.fail {
            color: var(--fail);
            border-color: rgba(255, 107, 107, .3);
        }

        button {
            font-family: var(--sans);
            font-size: 13.5px;
            font-weight: 500;
            padding: 9px 20px;
            border: 1px solid var(--hairline);
            border-radius: 7px;
            background: transparent;
            color: var(--dim);
            cursor: pointer;
            transition: color .18s ease, border-color .18s ease, background .18s ease;
        }

        button:hover:not(:disabled) {
            color: var(--text);
            border-color: var(--faint);
        }

        button:focus-visible {
            outline: 2px solid var(--live);
            outline-offset: 2px;
        }

        button.primary {
            background: var(--text);
            border-color: var(--text);
            color: var(--void);
            font-weight: 700;
        }

        button.primary:hover:not(:disabled) {
            background: #fff;
            border-color: #fff;
            color: var(--void);
        }

        button:disabled {
            opacity: .35;
            cursor: not-allowed;
        }

        .actions {
            display: flex;
            gap: 10px;
            justify-content: space-between;
            margin-top: 26px;
            padding-top: 18px;
            border-top: 1px solid var(--hairline-soft);
            flex-wrap: wrap;
        }

        .actions .left,
        .actions .right {
            display: flex;
            gap: 10px;
        }

        label.field {
            display: block;
            margin-bottom: 16px;
        }

        label.field span {
            display: block;
            font-size: 12px;
            font-weight: 500;
            margin-bottom: 6px;
            color: var(--dim);
        }

        input[type=text],
        input[type=password] {
            width: 100%;
            padding: 10px 13px;
            border: 1px solid var(--hairline);
            border-radius: 7px;
            background: var(--surface-2);
            color: var(--text);
            font-family: var(--mono);
            font-size: 13px;
            direction: ltr;
            text-align: left;
            transition: border-color .18s ease, box-shadow .18s ease;
        }

        input[type=text]:focus,
        input[type=password]:focus {
            outline: none;
            border-color: var(--live);
            box-shadow: 0 0 0 3px rgba(110, 168, 254, .12);
        }

        .grid2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0 16px;
        }

        .notice {
            padding: 11px 15px;
            font-size: 12.5px;
            line-height: 1.8;
            margin-bottom: 14px;
            border-radius: 7px;
            border-right: 2px solid var(--hairline);
            background: var(--surface-2);
            color: var(--dim);
            animation: rowIn .3s ease both;
        }

        .notice.bad {
            border-right-color: var(--fail);
            color: #ffb3b3;
        }

        .notice.good {
            border-right-color: var(--pass);
            color: #9fe0bf;
        }

        .notice.info {
            border-right-color: var(--live);
        }

        .notice code,
        .notice b {
            font-family: var(--mono);
            font-weight: 400;
            font-size: 12px;
            color: var(--text);
        }

        .notice a {
            color: var(--live);
        }

        pre.cmd {
            background: var(--void);
            border: 1px solid var(--hairline);
            border-radius: 7px;
            color: #c8d3e2;
            padding: 14px 16px;
            overflow-x: auto;
            direction: ltr;
            text-align: left;
            font-family: var(--mono);
            font-size: 12px;
            line-height: 1.9;
            margin: 0 0 12px;
        }

        table.jobs {
            width: 100%;
            border-collapse: collapse;
            font-size: 12.5px;
        }

        table.jobs th,
        table.jobs td {
            padding: 9px 10px;
            border-bottom: 1px solid var(--hairline-soft);
            text-align: right;
        }

        table.jobs th {
            color: var(--faint);
            font-weight: 500;
            font-size: 11.5px;
        }

        table.jobs td.mono {
            direction: ltr;
            text-align: left;
            font-family: var(--mono);
            font-size: 11.5px;
            color: var(--dim);
        }

        .badge {
            font-family: var(--mono);
            font-size: 11px;
            padding: 2px 9px;
            border-radius: 20px;
            border: 1px solid var(--hairline);
            color: var(--dim);
            white-space: nowrap;
        }

        .badge.ok {
            color: var(--pass);
            border-color: rgba(76, 195, 138, .3);
        }

        .badge.warn {
            color: var(--warn);
            border-color: rgba(255, 180, 84, .3);
        }

        .badge.fail {
            color: var(--fail);
            border-color: rgba(255, 107, 107, .3);
        }

        .tabs {
            display: flex;
            gap: 6px;
            margin-bottom: 14px;
        }

        .tabs button {
            font-size: 12.5px;
            padding: 7px 15px;
        }

        .tabs button.active {
            color: var(--text);
            border-color: var(--live);
        }

        .checkbox {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            font-size: 13px;
            color: var(--dim);
            margin-top: 16px;
        }

        input[type=checkbox] {
            accent-color: var(--live);
            width: 15px;
            height: 15px;
            margin-top: 4px;
        }

        ol.guide {
            margin: 0 0 16px;
            padding-right: 18px;
            font-size: 12.5px;
            color: var(--dim);
            line-height: 1.9;
        }

        ol.stages {
            list-style: none;
            margin: 22px 0 0;
            padding: 0;
        }

        ol.stages > li {
            position: relative;
            padding: 0 40px 10px 0;
        }

        ol.stages > li::before {
            content: "";
            position: absolute;
            top: 30px;
            bottom: 0;
            right: 12px;
            width: 1px;
            background: var(--hairline);
        }

        ol.stages > li:last-child::before {
            display: none;
        }

        .stage-head {
            display: flex;
            align-items: center;
            gap: 12px;
            min-height: 30px;
        }

        .stage-mark {
            position: absolute;
            right: 0;
            top: 2px;
            width: 25px;
            height: 25px;
            border: 1px solid var(--hairline);
            border-radius: 6px;
            background: var(--surface);
            color: var(--faint);
            font-family: var(--mono);
            font-size: 11.5px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all .25s ease;
        }

        .stage-label {
            font-size: 13.5px;
            font-weight: 500;
            color: var(--faint);
            transition: color .25s ease;
        }

        .stage-note {
            font-size: 11.5px;
            color: var(--faint);
            margin-right: auto;
            letter-spacing: .02em;
        }

        li[data-state=running] .stage-mark {
            border-color: var(--live);
            color: var(--live);
        }

        li[data-state=running] .stage-label,
        li[data-state=running] .stage-note {
            color: var(--live);
        }

        li[data-state=done] .stage-mark {
            border-color: rgba(76, 195, 138, .45);
            color: var(--pass);
        }

        li[data-state=done] .stage-label {
            color: var(--text);
        }

        li[data-state=fail] .stage-mark {
            border-color: rgba(255, 107, 107, .5);
            color: var(--fail);
        }

        li[data-state=fail] .stage-label,
        li[data-state=fail] .stage-note {
            color: var(--fail);
        }

        .stage-detail {
            margin-top: 10px;
        }

        .stage-detail:empty {
            margin: 0;
        }

        .spinner {
            display: inline-block;
            width: 12px;
            height: 12px;
            border: 1.5px solid rgba(110, 168, 254, .25);
            border-top-color: var(--live);
            border-radius: 50%;
            animation: spin .65s linear infinite;
            vertical-align: -1px;
            margin-left: 7px;
        }

        button.primary .spinner {
            border-color: rgba(13, 16, 20, .25);
            border-top-color: var(--void);
        }

        .stage-mark .spinner {
            margin: 0;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        @keyframes rise {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: none;
            }
        }

        @keyframes rowIn {
            from {
                opacity: 0;
                transform: translateY(6px);
            }

            to {
                opacity: 1;
                transform: none;
            }
        }

        @keyframes seg {
            from {
                opacity: 0;
                transform: scaleY(.28);
            }

            to {
                opacity: 1;
                transform: scaleY(1);
            }
        }

        @media (max-width: 700px) {
            .grid2 {
                grid-template-columns: 1fr;
            }

            .steps span {
                font-size: 0;
                padding-top: 8px;
            }

            .row .line {
                display: block;
            }

            .row .value {
                max-width: 100%;
                display: block;
                text-align: right;
                direction: ltr;
            }

            header.top {
                flex-direction: column;
                gap: 4px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            * {
                animation: none !important;
                transition: none !important;
            }

            .meter i {
                opacity: 1;
                transform: none;
            }
        }
    </style>
</head>

<body>
    <div class="wrap">
        <header class="top">
            <h1>نصب ربات میرزا</h1>
            <p>دامنه <b><?php echo htmlspecialchars($host, ENT_QUOTES, 'UTF-8'); ?></b></p>
        </header>

        <?php if ($locked): ?>
            <div class="card"><div class="inner">
                <h2>نصب قبلاً انجام شده است</h2>
                <p class="lead">برای امنیت، نصب‌کننده قفل شده است.</p>
                <div class="notice good">پوشه <code>install</code> را از هاست حذف کنید. اگر می‌خواهید دوباره نصب کنید، فایل <code>install/.installed</code> را پاک کنید.</div>
            </div></div>
        <?php elseif (!$authorized): ?>
            <div class="card"><div class="inner">
                <h2>تأیید هویت</h2>
                <p class="lead">ربات قبلاً روی این هاست پیکربندی شده است. برای اجرای دوباره نصب‌کننده، توکن ربات یا آیدی عددی مدیر را وارد کنید.</p>
                <div id="authError" class="notice bad" style="display:none"></div>
                <label class="field">
                    <span>توکن ربات یا آیدی عددی مدیر</span>
                    <input type="password" id="authSecret" autocomplete="off">
                </label>
                <div class="actions">
                    <span></span>
                    <button class="primary" id="authButton">ورود</button>
                </div>
            </div></div>
            <script>
                const authButton = document.getElementById('authButton');
                authButton.addEventListener('click', async () => {
                    const box = document.getElementById('authError');
                    box.style.display = 'none';
                    authButton.disabled = true;
                    const body = new FormData();
                    body.append('action', 'auth');
                    body.append('secret', document.getElementById('authSecret').value);
                    const response = await fetch('index.php', { method: 'POST', body });
                    const data = await response.json();
                    if (data.ok) {
                        location.reload();
                        return;
                    }
                    box.textContent = data.error || 'ورود ناموفق بود.';
                    box.style.display = 'block';
                    authButton.disabled = false;
                });
            </script>
        <?php else: ?>
            <div class="steps" id="steps"></div>
            <div class="card"><div class="inner" id="card"></div></div>
        <?php endif; ?>
    </div>
    <?php if (!$locked && $authorized): ?>
        <script>
            const SHELL_EXEC_AVAILABLE = <?php echo mirza_install_shell_exec_available() ? 'true' : 'false'; ?>;
            const STEPS = [
                { key: 'requirements', title: 'پیش‌نیازهای سرور' },
                { key: 'cron', title: 'کرون‌ها' },
                { key: 'ssl', title: 'دامنه و SSL' },
                { key: 'paths', title: 'فایل‌ها و مسیرها' },
                { key: 'config', title: 'تنظیمات ربات' },
                { key: 'done', title: 'پایان' }
            ].filter(step => step.key !== 'cron' || !SHELL_EXEC_AVAILABLE);

            let current = 0;
            let cronTimer = null;

            const card = document.getElementById('card');
            const stepsBar = document.getElementById('steps');

            async function api(action, payload = {}) {
                const body = new FormData();
                body.append('action', action);
                Object.keys(payload).forEach(key => body.append(key, payload[key]));
                const response = await fetch('index.php', { method: 'POST', body });
                try {
                    return await response.json();
                } catch (error) {
                    return { error: 'پاسخ سرور نامعتبر بود (کد ' + response.status + ').' };
                }
            }

            function renderSteps() {
                stepsBar.innerHTML = STEPS.map((step, index) => {
                    const cls = index === current ? 'active' : (index < current ? 'done' : '');
                    return '<span class="' + cls + '">' + (index + 1) + '. ' + step.title + '</span>';
                }).join('');
                const bar = document.getElementById('progressBar');
                if (bar) {
                    bar.style.width = Math.round((current / (STEPS.length - 1)) * 100) + '%';
                }
                card.style.animation = 'none';
                void card.offsetWidth;
                card.style.animation = '';
            }

            function escapeHtml(value) {
                return String(value == null ? '' : value)
                    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
            }

            function rowHtml(item, index) {
                return '<div class="row ' + item.status + '" style="animation-delay:' + Math.min(index * 32, 480) + 'ms">'
                    + '<div class="body">'
                    + '<div class="line"><span class="label">' + escapeHtml(item.label) + '</span>'
                    + (item.value ? '<span class="value' + (/^[\x20-\x7E]+$/.test(item.value) ? ' mono' : '') + '">' + escapeHtml(item.value) + '</span>' : '')
                    + '</div>'
                    + (item.hint ? '<div class="hint">' + escapeHtml(item.hint) + '</div>' : '')
                    + '</div></div>';
            }

            function meterHtml(result) {
                const segments = result.items.map((item, index) =>
                    '<i class="' + item.status + '" style="animation-delay:' + Math.min(index * 24, 800) + 'ms"></i>').join('');
                const passed = result.items.length - result.failed - result.warned;
                return '<div class="meter">' + segments + '</div>'
                    + '<div class="tally">'
                    + '<span class="ok">سالم <span class="n">' + passed + '</span></span>'
                    + '<span class="warn">هشدار <span class="n">' + result.warned + '</span></span>'
                    + '<span class="fail">خطا <span class="n">' + result.failed + '</span></span>'
                    + '</div>';
            }

            function renderItems(result) {
                let html = '';
                let lastGroup = null;
                result.items.forEach((item, index) => {
                    if (item.group && item.group !== lastGroup) {
                        lastGroup = item.group;
                        html += '<div class="group-title">' + escapeHtml(item.group) + '</div>';
                    }
                    html += rowHtml(item, index);
                });
                return meterHtml(result) + html;
            }

            function actionsHtml(nextLabel, nextEnabled, extra = '') {
                return '<div class="actions">'
                    + '<div class="left">'
                    + (current > 0 ? '<button id="backBtn">مرحله قبل</button>' : '')
                    + extra
                    + '</div>'
                    + '<div class="right">'
                    + '<button id="recheckBtn">بررسی مجدد</button>'
                    + '<button class="primary" id="nextBtn"' + (nextEnabled ? '' : ' disabled') + '>' + nextLabel + '</button>'
                    + '</div></div>';
            }

            function bindNav(onNext, onRecheck) {
                const backBtn = document.getElementById('backBtn');
                if (backBtn) {
                    backBtn.addEventListener('click', () => { current--; render(); });
                }
                const nextBtn = document.getElementById('nextBtn');
                if (nextBtn) {
                    nextBtn.addEventListener('click', onNext || (() => { current++; render(); }));
                }
                const recheckBtn = document.getElementById('recheckBtn');
                if (recheckBtn && onRecheck) {
                    recheckBtn.addEventListener('click', onRecheck);
                }
            }

            function loading(text) {
                card.innerHTML = '<h2>' + text + '<span class="spinner"></span></h2>';
            }

            async function renderCheckStep(action, title, lead, blockingNote) {
                loading('در حال بررسی');
                const result = await api(action);
                if (result.error) {
                    card.innerHTML = '<h2>' + title + '</h2><div class="notice bad">' + escapeHtml(result.error) + '</div>';
                    return;
                }
                card.innerHTML = '<h2>' + title + '</h2><p class="lead">' + lead + '</p>'
                    + (result.ok ? '' : '<div class="notice bad">' + blockingNote + '</div>')
                    + renderItems(result)
                    + actionsHtml('مرحله بعد', result.ok);
                bindNav(null, () => renderCheckStep(action, title, lead, blockingNote));
            }

            const INSTALL_STAGES = [
                { action: 'config_check', label: 'بررسی اتصال و دسترسی‌های دیتابیس' },
                { action: 'config_token', label: 'اعتبارسنجی توکن ربات' },
                { action: 'config_write', label: 'ساخت فایل config.php' },
                { action: 'bootstrap', label: 'ساخت جداول دیتابیس' }
            ];

            function delay(ms) {
                return new Promise(resolve => setTimeout(resolve, ms));
            }

            async function renderConfigStep() {
                loading('در حال خواندن تنظیمات');
                const data = await api('config_load');
                const values = data.values || {};
                card.innerHTML = '<h2>تنظیمات ربات</h2>'
                    + '<p class="lead">اطلاعات دیتابیس و ربات را وارد کنید. با زدن دکمه شروع نصب، مراحل زیر یکی‌یکی اجرا و نتیجه هرکدام نمایش داده می‌شود.</p>'
                    + '<div id="configMsg"></div>'
                    + '<div class="grid2">'
                    + field('dbhost', 'هاست دیتابیس', values.dbhost || 'localhost')
                    + field('dbname', 'نام دیتابیس', values.dbname || '')
                    + field('usernamedb', 'کاربر دیتابیس', values.usernamedb || '')
                    + field('passworddb', 'رمز دیتابیس', '', 'password')
                    + '</div>'
                    + field('APIKEY', 'توکن ربات تلگرام', values.APIKEY || '', 'password')
                    + field('adminnumber', 'آیدی عددی مدیر', values.adminnumber || '')
                    + '<div class="group-title">مراحل نصب</div>'
                    + '<ol class="stages" id="stageList">' + stagesHtml() + '</ol>'
                    + actionsHtml('شروع نصب', true);
                document.getElementById('recheckBtn').style.display = 'none';
                bindNav(runInstall, null);
            }

            function field(name, label, value, type = 'text') {
                return '<label class="field"><span>' + label + '</span>'
                    + '<input type="' + type + '" id="f_' + name + '" value="' + escapeHtml(value) + '" autocomplete="off"></label>';
            }

            function stagesHtml() {
                return INSTALL_STAGES.map((stage, index) =>
                    '<li data-state="pending" id="stg_' + index + '">'
                    + '<span class="stage-mark">' + (index + 1) + '</span>'
                    + '<div class="stage-head"><span class="stage-label">' + escapeHtml(stage.label) + '</span>'
                    + '<span class="stage-note" id="stgnote_' + index + '">در انتظار</span></div>'
                    + '<div class="stage-detail" id="stgdetail_' + index + '"></div></li>'
                ).join('');
            }

            function setStage(index, state, note) {
                const item = document.getElementById('stg_' + index);
                if (!item) {
                    return;
                }
                item.dataset.state = state;
                const marks = { done: '✓', fail: '✕', running: '<span class="spinner" style="margin:0"></span>' };
                item.querySelector('.stage-mark').innerHTML = marks[state] || String(index + 1);
                document.getElementById('stgnote_' + index).textContent = note;
            }

            async function runInstall() {
                const message = document.getElementById('configMsg');
                const nextBtn = document.getElementById('nextBtn');
                message.innerHTML = '';
                nextBtn.disabled = true;
                nextBtn.innerHTML = 'در حال نصب<span class="spinner"></span>';
                document.getElementById('stageList').innerHTML = stagesHtml();

                const payload = {};
                ['dbhost', 'dbname', 'usernamedb', 'passworddb', 'APIKEY', 'adminnumber'].forEach(name => {
                    payload[name] = document.getElementById('f_' + name).value;
                });

                for (let index = 0; index < INSTALL_STAGES.length; index++) {
                    setStage(index, 'running', 'در حال اجرا…');
                    await delay(320);
                    const result = await api(INSTALL_STAGES[index].action, index === 0 ? payload : {});
                    renderItemsInto('stgdetail_' + index, result.items || []);
                    if (!result.ok) {
                        setStage(index, 'fail', 'ناموفق');
                        for (let rest = index + 1; rest < INSTALL_STAGES.length; rest++) {
                            setStage(rest, 'pending', 'اجرا نشد');
                        }
                        message.innerHTML = '<div class="notice bad">' + escapeHtml(result.error || 'اجرای این مرحله ناموفق بود.') + '</div>';
                        nextBtn.disabled = false;
                        nextBtn.textContent = 'تلاش مجدد';
                        return;
                    }
                    setStage(index, 'done', 'انجام شد');
                    await delay(220);
                }

                message.innerHTML = '<div class="notice good">پیکربندی و پایگاه داده با موفقیت آماده شد.</div>';
                nextBtn.disabled = false;
                nextBtn.textContent = 'مرحله بعد';
                nextBtn.replaceWith(nextBtn.cloneNode(true));
                document.getElementById('nextBtn').addEventListener('click', () => { current++; render(); });
            }

            function renderItemsInto(containerId, items) {
                const container = document.getElementById(containerId);
                if (!container) {
                    return;
                }
                container.innerHTML = items.map((item, index) => rowHtml(item, index)).join('');
            }

            let cronPlan = null;
            const confirmedJobs = new Set();
            let cronTab = 'curl';

            async function renderCronStep() {
                if (!cronPlan) {
                    loading('در حال آماده‌سازی مرحله کرون');
                    cronPlan = await api('cron_plan');
                    if (cronPlan.probe && cronPlan.probe.count === 0) {
                        cronPlan.probe = await api('probe_begin');
                    }
                }
                drawCron();
                if (cronTimer) {
                    clearInterval(cronTimer);
                }
                cronTimer = setInterval(async () => {
                    if (STEPS[current].key !== 'cron') {
                        clearInterval(cronTimer);
                        return;
                    }
                    cronPlan.probe = await api('probe_status');
                    drawCron();
                }, 15000);
            }

            function commandOf(job) {
                return cronTab === 'curl' ? job.command_curl : job.command_php;
            }

            function drawCron() {
                const probe = cronPlan.probe || {};
                const jobs = cronPlan.jobs || [];
                const required = cronPlan.required || [];
                const allCommands = jobs.map(commandOf).join('\n');
                const missing = required.filter(job => !confirmedJobs.has(job));
                const canContinue = probe.verified && missing.length === 0;

                card.innerHTML = '<h2>ثبت دستی کرون‌ها</h2>'
                    + '<p class="lead">روی هاست اشتراکی دسترسی shell_exec وجود ندارد، پس کرون‌ها باید از بخش Cron Jobs کنترل پنل هاست (cPanel / DirectAdmin / Plesk / DirectSlave) دستی ثبت شوند. بدون این کرون‌ها فعال‌سازی سرویس، ارسال پیام و پیگیری پرداخت‌ها کار نمی‌کند.</p>'
                    + '<div class="tabs"><button class="' + (cronTab === 'curl' ? 'active' : '') + '" id="tabCurl">فراخوانی با curl</button>'
                    + '<button class="' + (cronTab === 'php' ? 'active' : '') + '" id="tabPhp">اجرای مستقیم PHP</button></div>'
                    + '<div class="group-title">۱. تست اجرای کرون روی هاست</div>'
                    + '<p class="lead">این یک خط <b>موقت</b> است و فقط برای اثبات فعال بودن کرون هاست استفاده می‌شود. بعد از پایان نصب آن را از کنترل پنل حذف کنید.</p>'
                    + '<pre class="cmd" id="probeBox">' + escapeHtml(cronTab === 'curl' ? probe.command_curl : probe.command_php) + '</pre>'
                    + rowHtml({
                        status: probe.verified ? 'ok' : 'fail',
                        label: 'وضعیت کرون هاست',
                        value: probe.count + ' اجرا / ' + probe.last_run_human,
                        hint: probe.message || ''
                    }, 0)
                    + '<div class="actions" style="margin:10px 0 0"><div class="left">'
                    + '<button id="copyProbe">کپی دستور تست</button><button id="resetProbe">شروع دوباره تست</button>'
                    + '</div><div class="right"></div></div>'
                    + '<div class="group-title">۲. کرون‌های اصلی ربات</div>'
                    + '<p class="lead">هر خط را در کنترل پنل ثبت کنید و بعد تیک کنارش را بزنید. تا وقتی همه کرون‌های اجباری تیک نخورند، ادامه ممکن نیست. این کرون‌ها تا پایان نصب و حذف شدن پوشه install پاسخی نمی‌گیرند و از همان لحظه به بعد شروع به کار می‌کنند.</p>'
                    + '<div class="actions" style="margin:0 0 12px"><div class="left">'
                    + '<button id="copyAll">کپی همه دستورها</button><button id="checkAll">تیک همه</button>'
                    + '</div><div class="right"></div></div>'
                    + jobs.map(jobRow).join('')
                    + '<pre class="cmd" id="allBox" style="display:none">' + escapeHtml(allCommands) + '</pre>'
                    + '<div class="summary" style="margin-top:14px"><span class="pill ' + (missing.length === 0 ? 'ok' : 'fail') + '">'
                    + (required.length - missing.length) + ' از ' + required.length + ' کرون اجباری تأیید شد</span>'
                    + '<span class="pill ' + (probe.verified ? 'ok' : 'fail') + '">تست کرون هاست: ' + (probe.verified ? 'تأیید شد' : 'در انتظار') + '</span></div>'
                    + actionsHtml('مرحله بعد', canContinue);

                document.getElementById('recheckBtn').textContent = 'بررسی وضعیت';
                document.getElementById('tabCurl').addEventListener('click', () => { cronTab = 'curl'; drawCron(); });
                document.getElementById('tabPhp').addEventListener('click', () => { cronTab = 'php'; drawCron(); });
                document.getElementById('copyProbe').addEventListener('click', () => copyText(document.getElementById('probeBox').textContent, 'copyProbe'));
                document.getElementById('copyAll').addEventListener('click', () => copyText(document.getElementById('allBox').textContent, 'copyAll'));
                document.getElementById('resetProbe').addEventListener('click', async () => {
                    cronPlan.probe = await api('probe_begin');
                    drawCron();
                });
                document.getElementById('checkAll').addEventListener('click', () => {
                    jobs.forEach(job => confirmedJobs.add(job.job));
                    drawCron();
                });
                jobs.forEach(job => {
                    document.getElementById('chk_' + job.job).addEventListener('change', event => {
                        if (event.target.checked) {
                            confirmedJobs.add(job.job);
                        } else {
                            confirmedJobs.delete(job.job);
                        }
                        drawCron();
                    });
                    document.getElementById('cpy_' + job.job).addEventListener('click', () => copyText(commandOf(job), 'cpy_' + job.job));
                });
                bindNav(null, async () => {
                    cronPlan.probe = await api('probe_status');
                    drawCron();
                });
            }

            function jobRow(job) {
                const checked = confirmedJobs.has(job.job);
                return '<div class="row ' + (checked ? 'ok' : (job.optional ? 'warn' : 'fail')) + '">'
                    + '<input type="checkbox" id="chk_' + job.job + '"' + (checked ? ' checked' : '') + '>'
                    + '<div class="body">'
                    + '<div class="line"><span class="label">' + escapeHtml(job.title) + '</span>'
                    + (job.optional ? '<span class="value">اختیاری</span>' : '') + '</div>'
                    + '<div class="hint" style="direction:ltr;text-align:left;font-family:var(--mono);font-size:11.5px">'
                    + escapeHtml(commandOf(job)) + '</div></div>'
                    + '<button id="cpy_' + job.job + '" style="padding:5px 11px;font-size:11.5px">کپی</button></div>';
            }

            function copyText(text, buttonId) {
                navigator.clipboard.writeText(text).then(() => {
                    const button = document.getElementById(buttonId);
                    const original = button.textContent;
                    button.textContent = 'کپی شد';
                    setTimeout(() => { button.textContent = original; }, 1500);
                });
            }

            async function renderDoneStep() {
                card.innerHTML = '<h2>پایان نصب</h2>'
                    + '<p class="lead">با تأیید این مرحله ابتدا پوشه install به‌صورت خودکار حذف می‌شود، سپس وبهوک تلگرام ست شده و پیام تست برای مدیر ارسال می‌گردد.</p>'
                    + '<div class="notice info">تا زمانی که پوشه install روی هاست باشد، ربات مسدود است. اگر حذف خودکار ناموفق باشد، وبهوک هم حذف می‌شود و ربات غیرفعال می‌ماند تا پوشه را دستی پاک کنید.</div>'
                    + (SHELL_EXEC_AVAILABLE ? '' : '<div class="notice info">فراموش نکنید کرون <b>موقت</b> تست (install/cron-check.php) را هم از کنترل پنل هاست حذف کنید.</div>')
                    + '<div id="doneMsg"></div><div id="doneSteps"></div>'
                    + actionsHtml('پایان نصب و حذف نصب‌کننده', true);
                document.getElementById('recheckBtn').style.display = 'none';
                bindNav(async () => {
                    const nextBtn = document.getElementById('nextBtn');
                    nextBtn.disabled = true;
                    nextBtn.innerHTML = 'در حال اتمام<span class="spinner"></span>';
                    const result = await api('finish', { confirmed: JSON.stringify(Array.from(confirmedJobs)) });
                    renderConfigStepsInto('doneSteps', result.steps || []);
                    const box = document.getElementById('doneMsg');
                    if (result.ok) {
                        box.className = 'notice good';
                        box.innerHTML = 'نصب کامل شد، پوشه install حذف گردید و ربات از حالت مسدود خارج شد. حالا در تلگرام دستور /start را برای ربات بفرستید.'
                            + (result.bot_url ? ' <a href="' + escapeHtml(result.bot_url) + '" style="color:#93c5fd">' + escapeHtml(result.bot_url) + '</a>' : '');
                        nextBtn.style.display = 'none';
                        return;
                    }
                    box.className = 'notice bad';
                    box.textContent = result.error || 'اتمام نصب ناموفق بود.';
                    nextBtn.disabled = false;
                    nextBtn.textContent = 'تلاش مجدد';
                }, null);
            }

            function renderConfigStepsInto(containerId, steps) {
                const container = document.getElementById(containerId);
                if (!container) {
                    return;
                }
                container.innerHTML = steps.map((step, index) =>
                    rowHtml({ status: step.status, label: step.label, value: '', hint: step.detail }, index)
                ).join('');
            }

            function render() {
                renderSteps();
                const key = STEPS[current].key;
                if (key === 'requirements') {
                    renderCheckStep('requirements', 'پیش‌نیازهای سرور',
                        'نسخه PHP، نوع وب‌سرور، اکستنشن‌های موردنیاز و تنظیمات php.ini بررسی می‌شوند.',
                        'تا وقتی موارد قرمز برطرف نشوند نمی‌توان ادامه داد.');
                } else if (key === 'ssl') {
                    renderCheckStep('ssl', 'دامنه و گواهی SSL',
                        'دامنه از روی همین صفحه خوانده می‌شود و اعتبار گواهی SSL آن مستقیماً تست می‌شود.',
                        'تلگرام بدون SSL معتبر وبهوک را قبول نمی‌کند.');
                } else if (key === 'paths') {
                    renderCheckStep('paths', 'فایل‌ها و مسیرها',
                        'کامل بودن پوشه‌ها، فایل‌های سورس، فایل‌های .htaccess و دسترسی نوشتن بررسی می‌شود.',
                        'فایل‌های ناقص یا دسترسی نوشتن نداشتن باعث خطای ربات می‌شود.');
                } else if (key === 'config') {
                    renderConfigStep();
                } else if (key === 'cron') {
                    renderCronStep();
                } else {
                    renderDoneStep();
                }
            }

            render();
        </script>
    <?php endif; ?>
</body>

</html>
