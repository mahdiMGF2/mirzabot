<?php

declare(strict_types=1);

function mirza_install_root(): string
{
    return dirname(__DIR__);
}

function mirza_install_lock_file(): string
{
    return __DIR__ . '/.installed';
}

function mirza_install_state_dir(): string
{
    $directory = __DIR__ . '/state';
    if (!is_dir($directory)) {
        @mkdir($directory, 0775, true);
    }

    return $directory;
}

function mirza_install_result(array $items): array
{
    $failed = 0;
    $warned = 0;
    foreach ($items as $item) {
        if ($item['status'] === 'fail') {
            $failed++;
        } elseif ($item['status'] === 'warn') {
            $warned++;
        }
    }

    return [
        'ok' => $failed === 0,
        'failed' => $failed,
        'warned' => $warned,
        'items' => $items,
    ];
}

function mirza_install_item(string $status, string $label, string $value = '', string $hint = ''): array
{
    return [
        'status' => $status,
        'label' => $label,
        'value' => $value,
        'hint' => $hint,
    ];
}

function mirza_install_host(): string
{
    $host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? '');
    $host = strtolower(trim((string) $host));
    $host = preg_replace('/:\d+$/', '', $host);

    return (string) $host;
}

function mirza_install_base_path(): string
{
    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/install/index.php'));
    $directory = rtrim(dirname($scriptName), '/');
    $base = preg_replace('#/install$#', '', $directory);

    return $base === '/' ? '' : (string) $base;
}

function mirza_install_base_url(): string
{
    return 'https://' . mirza_install_host() . mirza_install_base_path();
}

function mirza_install_is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
        return true;
    }
    if ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443') {
        return true;
    }
    if (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https') {
        return true;
    }

    return false;
}

function mirza_install_php_check(): array
{
    $items = [];

    $items[] = version_compare(PHP_VERSION, '8.2.0', '>=')
        ? mirza_install_item('ok', 'نسخه PHP', PHP_VERSION, 'حداقل نسخه موردنیاز 8.2.0 است.')
        : mirza_install_item('fail', 'نسخه PHP', PHP_VERSION, 'نسخه PHP باید 8.2.0 یا بالاتر باشد. از بخش Select PHP Version در کنترل پنل هاست نسخه را تغییر دهید.');

    if (version_compare(PHP_VERSION, '9.0.0', '>=')) {
        $items[] = mirza_install_item('warn', 'سازگاری نسخه', PHP_VERSION, 'این نسخه هنوز روی ربات تست نشده است؛ نسخه 8.2 تا 8.4 پیشنهاد می‌شود.');
    }

    $items[] = mirza_install_item('ok', 'SAPI اجرایی', PHP_SAPI);

    return $items;
}

function mirza_install_webserver_check(): array
{
    $items = [];
    $software = trim((string) ($_SERVER['SERVER_SOFTWARE'] ?? ''));
    $isApache = stripos($software, 'apache') !== false;
    $isLiteSpeed = stripos($software, 'litespeed') !== false || stripos($software, 'lsws') !== false;

    if ($isApache) {
        $items[] = mirza_install_item('ok', 'وب‌سرور', $software !== '' ? $software : 'Apache');
    } elseif ($isLiteSpeed) {
        $items[] = mirza_install_item('warn', 'وب‌سرور', $software, 'LiteSpeed شناسایی شد. با فایل‌های .htaccess آپاچی سازگار است و ربات کار می‌کند، اما وب‌سرور رسمی پشتیبانی‌شده آپاچی است.');
    } elseif ($software === '') {
        $items[] = mirza_install_item('fail', 'وب‌سرور', 'نامشخص', 'نام وب‌سرور قابل تشخیص نیست. ربات به آپاچی و پشتیبانی از .htaccess نیاز دارد.');
    } else {
        $items[] = mirza_install_item('fail', 'وب‌سرور', $software, 'وب‌سرور آپاچی نیست. مسیرهای بازنویسی (.htaccess) در پوشه api کار نخواهد کرد.');
    }

    if (function_exists('apache_get_modules')) {
        $modules = apache_get_modules();
        $items[] = in_array('mod_rewrite', $modules, true)
            ? mirza_install_item('ok', 'ماژول mod_rewrite', 'فعال')
            : mirza_install_item('fail', 'ماژول mod_rewrite', 'غیرفعال', 'برای مسیرهای api باید mod_rewrite فعال باشد.');
    }

    return $items;
}

function mirza_install_required_extensions(): array
{
    return [
        'curl' => 'ارتباط با تلگرام و پنل‌ها',
        'pdo' => 'لایه اتصال دیتابیس',
        'pdo_mysql' => 'اتصال به MySQL',
        'mbstring' => 'پردازش متن فارسی',
        'json' => 'پردازش پاسخ‌های API',
        'openssl' => 'ارتباط امن HTTPS',
        'gd' => 'ساخت تصویر QR Code',
        'zip' => 'خروجی اکسل و پشتیبان‌گیری',
        'dom' => 'موردنیاز PhpSpreadsheet',
        'simplexml' => 'موردنیاز PhpSpreadsheet',
        'xml' => 'موردنیاز PhpSpreadsheet',
        'xmlwriter' => 'موردنیاز PhpSpreadsheet',
        'xmlreader' => 'موردنیاز PhpSpreadsheet',
        'fileinfo' => 'تشخیص نوع فایل‌های ارسالی',
        'iconv' => 'تبدیل انکودینگ',
        'ctype' => 'اعتبارسنجی ورودی',
        'filter' => 'اعتبارسنجی ورودی',
        'zlib' => 'فشرده‌سازی',
        'session' => 'نشست پنل مدیریت وب',
    ];
}

function mirza_install_optional_extensions(): array
{
    return [
        'intl' => 'قالب‌بندی عدد و تاریخ در خروجی اکسل',
        'bcmath' => 'محاسبات دقیق مالی',
        'mysqli' => 'سازگاری با نصب‌های قدیمی‌تر',
        'sodium' => 'رمزنگاری مدرن',
    ];
}

function mirza_install_extensions_check(): array
{
    $items = [];

    foreach (mirza_install_required_extensions() as $extension => $reason) {
        $items[] = extension_loaded($extension)
            ? mirza_install_item('ok', 'اکستنشن ' . $extension, 'نصب است', $reason)
            : mirza_install_item('fail', 'اکستنشن ' . $extension, 'نصب نیست', $reason . ' — از بخش PHP Extensions کنترل پنل هاست فعالش کنید.');
    }

    foreach (mirza_install_optional_extensions() as $extension => $reason) {
        if (!extension_loaded($extension)) {
            $items[] = mirza_install_item('warn', 'اکستنشن ' . $extension, 'نصب نیست', $reason . ' — اختیاری است.');
        }
    }

    return $items;
}

function mirza_install_disabled_functions(): array
{
    $raw = (string) ini_get('disable_functions');
    if (trim($raw) === '') {
        return [];
    }

    return array_filter(array_map('trim', explode(',', $raw)));
}

function mirza_install_shell_exec_available(): bool
{
    return function_exists('shell_exec') && !in_array('shell_exec', mirza_install_disabled_functions(), true);
}

function mirza_install_ini_check(): array
{
    $items = [];
    $disabled = mirza_install_disabled_functions();
    $criticalFunctions = ['curl_init', 'curl_exec', 'file_get_contents', 'file_put_contents', 'fopen', 'json_encode'];

    foreach ($criticalFunctions as $criticalFunction) {
        if (!function_exists($criticalFunction) || in_array($criticalFunction, $disabled, true)) {
            $items[] = mirza_install_item('fail', 'تابع ' . $criticalFunction, 'غیرفعال', 'این تابع برای کارکرد ربات ضروری است و روی هاست غیرفعال شده است.');
        }
    }

    $items[] = mirza_install_shell_exec_available()
        ? mirza_install_item('ok', 'تابع shell_exec', 'فعال', 'کرون‌ها خودکار ثبت می‌شوند و مرحله کرون از نصب حذف می‌شود.')
        : mirza_install_item('warn', 'تابع shell_exec', 'غیرفعال', 'ثبت خودکار کرون ممکن نیست و باید کرون‌ها را دستی ست کنید (در گام کرون آموزش داده می‌شود). همچنین پشتیبان‌گیری خودکار ربات‌ساز غیرفعال خواهد بود.');

    $memoryLimit = (string) ini_get('memory_limit');
    $memoryBytes = mirza_install_bytes($memoryLimit);
    $items[] = ($memoryBytes === -1 || $memoryBytes >= 134217728)
        ? mirza_install_item('ok', 'memory_limit', $memoryLimit)
        : mirza_install_item('warn', 'memory_limit', $memoryLimit, 'حداقل 128M پیشنهاد می‌شود.');

    $executionTime = (int) ini_get('max_execution_time');
    $items[] = ($executionTime === 0 || $executionTime >= 60)
        ? mirza_install_item('ok', 'max_execution_time', $executionTime === 0 ? 'نامحدود' : $executionTime . ' ثانیه')
        : mirza_install_item('warn', 'max_execution_time', $executionTime . ' ثانیه', 'حداقل 60 ثانیه پیشنهاد می‌شود؛ ساخت جداول و پشتیبان‌گیری زمان‌بر است.');

    $uploadSize = (string) ini_get('upload_max_filesize');
    $items[] = mirza_install_bytes($uploadSize) >= 8388608
        ? mirza_install_item('ok', 'upload_max_filesize', $uploadSize)
        : mirza_install_item('warn', 'upload_max_filesize', $uploadSize, 'حداقل 8M پیشنهاد می‌شود.');

    $items[] = ini_get('allow_url_fopen')
        ? mirza_install_item('ok', 'allow_url_fopen', 'فعال')
        : mirza_install_item('fail', 'allow_url_fopen', 'غیرفعال', 'برای دریافت فایل‌ها و تصاویر از آدرس‌های خارجی ضروری است. از بخش تنظیمات PHP کنترل پنل هاست (MultiPHP INI Editor یا PHP Settings) گزینه allow_url_fopen را روی On بگذارید.');

    return $items;
}

function mirza_install_bytes(string $value): int
{
    $value = trim($value);
    if ($value === '') {
        return 0;
    }
    if ($value === '-1') {
        return -1;
    }

    $unit = strtolower(substr($value, -1));
    $number = (int) $value;

    if ($unit === 'g') {
        return $number * 1024 * 1024 * 1024;
    }
    if ($unit === 'm') {
        return $number * 1024 * 1024;
    }
    if ($unit === 'k') {
        return $number * 1024;
    }

    return $number;
}

function mirza_install_ssl_check(): array
{
    $items = [];
    $host = mirza_install_host();

    if ($host === '') {
        return [mirza_install_item('fail', 'دامنه', 'نامشخص', 'دامنه از روی درخواست قابل تشخیص نیست.')];
    }

    $items[] = mirza_install_item('ok', 'دامنه نصب', $host);

    if (filter_var($host, FILTER_VALIDATE_IP)) {
        $items[] = mirza_install_item('fail', 'نوع آدرس', 'آی‌پی', 'تلگرام برای وبهوک به دامنه با گواهی معتبر نیاز دارد؛ نصب روی آی‌پی خام ممکن نیست.');

        return $items;
    }

    $items[] = mirza_install_is_https()
        ? mirza_install_item('ok', 'اتصال فعلی', 'HTTPS')
        : mirza_install_item('warn', 'اتصال فعلی', 'HTTP', 'این صفحه را با https باز کنید تا اطلاعات ورودی رمزنگاری شود.');

    $certificate = mirza_install_read_certificate($host);

    if ($certificate === null) {
        $items[] = mirza_install_item('fail', 'گواهی SSL', 'در دسترس نیست', 'اتصال امن به https://' . $host . ' برقرار نشد. از بخش SSL هاست (یا Let\'s Encrypt) گواهی نصب کنید.');

        return $items;
    }

    if (!empty($certificate['error'])) {
        $items[] = mirza_install_item('fail', 'گواهی SSL', 'نامعتبر', (string) $certificate['error']);

        return $items;
    }

    $items[] = mirza_install_item('ok', 'گواهی SSL', 'معتبر', 'صادرکننده: ' . $certificate['issuer']);

    $daysLeft = (int) $certificate['days_left'];
    if ($daysLeft < 0) {
        $items[] = mirza_install_item('fail', 'اعتبار گواهی', 'منقضی شده', 'تاریخ انقضا: ' . $certificate['valid_to']);
    } elseif ($daysLeft < 15) {
        $items[] = mirza_install_item('warn', 'اعتبار گواهی', $daysLeft . ' روز', 'تاریخ انقضا: ' . $certificate['valid_to'] . ' — به‌زودی باید تمدید شود.');
    } else {
        $items[] = mirza_install_item('ok', 'اعتبار گواهی', $daysLeft . ' روز مانده', 'تاریخ انقضا: ' . $certificate['valid_to']);
    }

    if (!$certificate['host_match']) {
        $items[] = mirza_install_item('fail', 'تطابق دامنه گواهی', 'ناسازگار', 'گواهی برای دامنه ' . $host . ' صادر نشده است.');
    } else {
        $items[] = mirza_install_item('ok', 'تطابق دامنه گواهی', 'سازگار');
    }

    $reachable = mirza_install_http_probe(mirza_install_base_url() . '/install/probe.php');
    if ($reachable['ok'] && strpos((string) $reachable['body'], 'MIRZA_REWRITE_OK') !== false) {
        $items[] = mirza_install_item('ok', 'دسترسی خارجی به دامنه', 'برقرار', 'تلگرام می‌تواند به وبهوک متصل شود.');
    } else {
        $items[] = mirza_install_item('warn', 'دسترسی خارجی به دامنه', 'تأیید نشد', 'درخواست HTTPS از خود سرور به دامنه پاسخ نداد: ' . $reachable['error'] . ' — اگر هاست اتصال حلقه‌ای را مسدود کرده باشد طبیعی است.');
    }

    return $items;
}

function mirza_install_read_certificate(string $host): ?array
{
    if (!function_exists('stream_socket_client') || !function_exists('openssl_x509_parse')) {
        return null;
    }

    $context = stream_context_create([
        'ssl' => [
            'capture_peer_cert' => true,
            'verify_peer' => true,
            'verify_peer_name' => true,
            'SNI_enabled' => true,
            'peer_name' => $host,
        ],
    ]);

    $errorNumber = 0;
    $errorMessage = '';
    $client = @stream_socket_client(
        'ssl://' . $host . ':443',
        $errorNumber,
        $errorMessage,
        10,
        STREAM_CLIENT_CONNECT,
        $context
    );

    if ($client === false) {
        return ['error' => 'اتصال TLS ناموفق بود: ' . ($errorMessage !== '' ? $errorMessage : 'خطای نامشخص')];
    }

    $params = stream_context_get_params($client);
    fclose($client);

    if (empty($params['options']['ssl']['peer_certificate'])) {
        return ['error' => 'گواهی از سرور دریافت نشد.'];
    }

    $parsed = openssl_x509_parse($params['options']['ssl']['peer_certificate']);
    if (!is_array($parsed)) {
        return ['error' => 'گواهی دریافت‌شده قابل خواندن نیست.'];
    }

    $validTo = isset($parsed['validTo_time_t']) ? (int) $parsed['validTo_time_t'] : 0;
    $names = [];
    if (isset($parsed['subject']['CN'])) {
        $names[] = (string) $parsed['subject']['CN'];
    }
    if (isset($parsed['extensions']['subjectAltName'])) {
        foreach (explode(',', (string) $parsed['extensions']['subjectAltName']) as $entry) {
            $entry = trim($entry);
            if (stripos($entry, 'DNS:') === 0) {
                $names[] = substr($entry, 4);
            }
        }
    }

    return [
        'error' => '',
        'issuer' => (string) ($parsed['issuer']['O'] ?? ($parsed['issuer']['CN'] ?? 'نامشخص')),
        'valid_to' => $validTo > 0 ? date('Y-m-d H:i', $validTo) : 'نامشخص',
        'days_left' => $validTo > 0 ? (int) floor(($validTo - time()) / 86400) : 0,
        'host_match' => mirza_install_host_matches_certificate($host, $names),
    ];
}

function mirza_install_host_matches_certificate(string $host, array $names): bool
{
    foreach ($names as $name) {
        $name = strtolower(trim($name));
        if ($name === '') {
            continue;
        }
        if ($name === $host) {
            return true;
        }
        if (strpos($name, '*.') === 0 && substr($host, -strlen(substr($name, 1))) === substr($name, 1)) {
            return true;
        }
    }

    return false;
}

function mirza_install_http_probe(string $url, int $timeout = 12): array
{
    if (!function_exists('curl_init')) {
        return ['ok' => false, 'body' => '', 'code' => 0, 'error' => 'اکستنشن curl در دسترس نیست'];
    }

    $handle = curl_init();
    curl_setopt_array($handle, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_CONNECTTIMEOUT => $timeout,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_USERAGENT => 'MirzaInstaller',
    ]);

    $body = curl_exec($handle);
    $code = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
    $error = curl_error($handle);
    unset($handle);

    return [
        'ok' => $body !== false && $code >= 200 && $code < 400,
        'body' => is_string($body) ? $body : '',
        'code' => $code,
        'error' => $error !== '' ? $error : ('کد پاسخ ' . $code),
    ];
}

function mirza_install_required_directories(): array
{
    return [
        'api' => 'وب‌سرویس مینی‌اپ',
        'app' => 'فایل‌های مینی‌اپ',
        'cronbot' => 'اسکریپت‌های کرون',
        'db' => 'ساختار دیتابیس',
        'db/tables' => 'تعریف جداول',
        'db/migrations' => 'مهاجرت‌های دیتابیس',
        'ibsng' => 'ماژول IBSng',
        'lang' => 'فایل‌های زبان',
        'panel' => 'پنل تحت وب',
        'payment' => 'درگاه‌های پرداخت',
        'sub' => 'لینک اشتراک',
        'vendor' => 'کتابخانه‌های کامپوزر',
        'vpnbot' => 'ربات‌ساز',
    ];
}

function mirza_install_required_files(): array
{
    return [
        'index.php' => 'ورودی وبهوک تلگرام',
        'admin.php' => 'پنل مدیریت ربات',
        'function.php' => 'توابع اصلی',
        'botapi.php' => 'ارتباط با API تلگرام',
        'keyboard.php' => 'کیبوردهای ربات',
        'panels.php' => 'مدیریت پنل‌ها',
        'config.php' => 'تنظیمات اتصال',
        'table.php' => 'ساخت جداول',
        'jdf.php' => 'تاریخ شمسی',
        'request.php' => 'درخواست‌های HTTP',
        'composer.json' => 'تعریف وابستگی‌ها',
        'vendor/autoload.php' => 'بارگذار کامپوزر (همراه پوشه vendor می‌آید)',
        'db/bootstrap.php' => 'راه‌اندازی دیتابیس',
        'db/Schema.php' => 'موتور ساخت جدول',
        'db/tables.php' => 'فهرست جداول',
        'db/indexes.php' => 'فهرست ایندکس‌ها',
        'lang/fa.php' => 'زبان فارسی',
        'lang/en.php' => 'زبان انگلیسی',
        'app/index.php' => 'مینی‌اپ',
        'api/index.php' => 'ورودی وب‌سرویس',
        'sub/index.php' => 'صفحه اشتراک',
        'panel/index.php' => 'ورودی پنل وب',
        'payment/index.php' => 'ورودی درگاه پرداخت',
        'Marzban.php' => 'پنل مرزبان',
        'marzneshin.php' => 'پنل مرزنشین',
        'hiddify.php' => 'پنل هیدیفای',
        's_ui.php' => 'پنل S-UI',
        'alireza_single.php' => 'پنل علیرضا',
        'x-ui_single.php' => 'پنل X-UI',
        'mikrotik.php' => 'پنل میکروتیک',
        'mirza_agent.php' => 'ایجنت میرزا',
        'Rebecca.php' => 'پنل ربکا',
        'WGDashboard.php' => 'پنل WGDashboard',
        'ibsng.php' => 'پنل IBSng',
    ];
}

function mirza_install_required_htaccess(): array
{
    return ['.htaccess', 'api/.htaccess', 'app/.htaccess', 'cronbot/.htaccess', 'sub/.htaccess'];
}

function mirza_install_writable_paths(): array
{
    return [
        '.' => 'ذخیره لاگ و فایل‌های موقت ربات',
        'cronbot' => 'ذخیره users.json و info',
        'api' => 'ذخیره hash.txt',
        'install/state' => 'ثبت وضعیت اجرای کرون‌ها',
    ];
}

function mirza_install_paths_check(): array
{
    $items = [];
    $root = mirza_install_root();

    foreach (mirza_install_required_directories() as $directory => $reason) {
        $items[] = is_dir($root . '/' . $directory)
            ? mirza_install_item('ok', 'پوشه ' . $directory, 'موجود است', $reason)
            : mirza_install_item('fail', 'پوشه ' . $directory, 'یافت نشد', $reason . ($directory === 'vendor' ? ' — به‌جای زیپ سورس گیت‌هاب، فایل mirzabot-hosting-<نسخه>.zip را از بخش Releases دانلود و در ریشه هاست اکسترکت کنید؛ آن نسخه شامل پوشه vendor است.' : ' — فایل‌های سورس ناقص آپلود شده‌اند.'));
    }

    $missingFiles = [];
    foreach (mirza_install_required_files() as $file => $reason) {
        if (!is_file($root . '/' . $file)) {
            $missingFiles[] = $file;
            $items[] = mirza_install_item('fail', 'فایل ' . $file, 'یافت نشد', $reason);
        }
    }
    if ($missingFiles === []) {
        $items[] = mirza_install_item('ok', 'فایل‌های اصلی', count(mirza_install_required_files()) . ' فایل بررسی شد', 'همه فایل‌های ضروری سورس موجود هستند.');
    }

    foreach (mirza_install_cron_jobs() as $job) {
        if (!is_file($root . '/cronbot/' . $job['job'] . '.php')) {
            $items[] = mirza_install_item('fail', 'فایل cronbot/' . $job['job'] . '.php', 'یافت نشد', $job['title']);
        }
    }

    $missingHtaccess = [];
    foreach (mirza_install_required_htaccess() as $htaccess) {
        if (!is_file($root . '/' . $htaccess)) {
            $missingHtaccess[] = $htaccess;
        }
    }
    $items[] = $missingHtaccess === []
        ? mirza_install_item('ok', 'فایل‌های .htaccess', 'کامل است', 'محافظت از فایل‌های حساس فعال است.')
        : mirza_install_item('fail', 'فایل‌های .htaccess', 'ناقص: ' . implode('، ', $missingHtaccess), 'بدون این فایل‌ها فایل‌های json و پشتیبان از اینترنت قابل دانلود می‌شوند.');

    foreach (mirza_install_writable_paths() as $path => $reason) {
        $absolute = $path === '.' ? $root : $root . '/' . $path;
        if (!is_dir($absolute)) {
            @mkdir($absolute, 0775, true);
        }
        $items[] = is_writable($absolute)
            ? mirza_install_item('ok', 'دسترسی نوشتن ' . $path, 'دارد', $reason)
            : mirza_install_item('fail', 'دسترسی نوشتن ' . $path, 'ندارد', $reason . ' — سطح دسترسی این مسیر را روی 755 (یا 775) تنظیم کنید.');
    }

    foreach (mirza_install_document_root_check() as $rootItem) {
        $items[] = $rootItem;
    }

    $items[] = mirza_install_rewrite_check();
    $items[] = mirza_install_guard_files_check();
    $items[] = mirza_install_guard_active_check();

    $composerLock = $root . '/composer.lock';
    $autoload = $root . '/vendor/autoload.php';
    if (is_file($composerLock) && is_file($autoload)) {
        $items[] = mirza_install_item('ok', 'وابستگی‌های کامپوزر', 'نصب شده است');
    }

    return $items;
}

function mirza_install_document_root_check(): array
{
    $items = [];
    $root = mirza_install_root();
    $realRoot = realpath($root);
    $documentRoot = (string) ($_SERVER['DOCUMENT_ROOT'] ?? '');
    $realDocumentRoot = $documentRoot !== '' ? realpath($documentRoot) : false;

    $items[] = mirza_install_item('ok', 'مسیر سورس روی هاست', $realRoot === false ? $root : $realRoot);

    if ($realDocumentRoot === false) {
        $items[] = mirza_install_item('warn', 'ریشه هاست (DOCUMENT_ROOT)', 'قابل تشخیص نیست', 'مطمئن شوید فایل‌ها مستقیماً داخل public_html (یا ریشه ساب‌دامنه) آپلود شده‌اند و داخل زیرپوشه نیستند.');
    } elseif ($realRoot !== false && rtrim($realRoot, '/') === rtrim($realDocumentRoot, '/')) {
        $items[] = mirza_install_item('ok', 'آپلود در ریشه هاست', 'تأیید شد', 'سورس ربات مستقیماً در ' . $realDocumentRoot . ' قرار دارد.');
    } else {
        $items[] = mirza_install_item('fail', 'آپلود در ریشه هاست', 'در زیرپوشه است', 'ریشه هاست ' . $realDocumentRoot . ' است ولی سورس در ' . $realRoot . ' آپلود شده. همه فایل‌ها و پوشه‌های ربات را مستقیماً داخل ریشه هاست (public_html یا ریشه ساب‌دامنه) منتقل کنید؛ آدرس وبهوک و کرون‌ها فقط با نصب در ریشه کار می‌کنند.');
    }

    $items[] = mirza_install_base_path() === ''
        ? mirza_install_item('ok', 'آدرس وب ربات', mirza_install_base_url() . '/index.php')
        : mirza_install_item('fail', 'آدرس وب ربات', mirza_install_base_url() . '/index.php', 'ربات از زیرمسیر ' . mirza_install_base_path() . ' سرو می‌شود. آدرس وبهوک و کرون‌ها بر اساس ریشه دامنه ساخته می‌شوند و در زیرپوشه کار نمی‌کنند.');

    $installerPath = __DIR__;
    $expectedInstaller = ($realRoot === false ? $root : $realRoot) . '/install';
    if (rtrim(str_replace('\\', '/', $installerPath), '/') !== rtrim(str_replace('\\', '/', $expectedInstaller), '/')) {
        $items[] = mirza_install_item('fail', 'محل پوشه install', $installerPath, 'پوشه install باید دقیقاً کنار فایل index.php ربات باشد.');
    }

    return $items;
}

function mirza_install_guard_files_check(): array
{
    $root = mirza_install_root();
    $missing = [];
    foreach (['.htaccess', 'api/.htaccess', 'app/.htaccess', 'sub/.htaccess'] as $file) {
        $contents = @file_get_contents($root . '/' . $file);
        if (!is_string($contents) || strpos($contents, 'DOCUMENT_ROOT}/install/index.php') === false) {
            $missing[] = $file;
        }
    }

    if ($missing === []) {
        return mirza_install_item('ok', 'قفل امنیتی زمان نصب', 'فعال است', 'تا وقتی پوشه install روی هاست باشد، ربات از دسترس خارج است.');
    }

    return mirza_install_item('fail', 'قفل امنیتی زمان نصب', 'ناقص: ' . implode('، ', $missing), 'این فایل‌ها باید قانون مسدودسازی ربات در زمان وجود پوشه install را داشته باشند. فایل‌های .htaccess را کامل (همراه فایل‌های مخفی) آپلود کنید.');
}

function mirza_install_guard_active_check(): array
{
    $probe = mirza_install_http_probe(mirza_install_base_url() . '/mirza-install-guard-probe');

    if ($probe['code'] === 403) {
        return mirza_install_item('ok', 'مسدود بودن ربات در حین نصب', 'تأیید شد', 'ربات تا حذف پوشه install پاسخ نمی‌دهد و در پایان نصب خودکار آزاد می‌شود.');
    }
    if ($probe['code'] === 0) {
        return mirza_install_item('warn', 'مسدود بودن ربات در حین نصب', 'تأیید نشد', 'درخواست تست به دامنه پاسخ نداد (' . $probe['error'] . ').');
    }

    return mirza_install_item('warn', 'مسدود بودن ربات در حین نصب', 'کد پاسخ ' . $probe['code'], 'قانون .htaccess اجرا نشد؛ احتمالاً mod_rewrite یا AllowOverride روی هاست فعال نیست. لایه دوم محافظت داخل index.php همچنان فعال است و ربات تا حذف پوشه install بالا نمی‌آید.');
}

function mirza_install_rewrite_check(): array
{
    if (function_exists('apache_get_modules')) {
        return in_array('mod_rewrite', apache_get_modules(), true)
            ? mirza_install_item('ok', 'بازنویسی مسیر (.htaccess)', 'فعال')
            : mirza_install_item('fail', 'بازنویسی مسیر (.htaccess)', 'غیرفعال', 'ماژول mod_rewrite باید فعال باشد تا مسیرهای پوشه api کار کنند.');
    }

    $probe = mirza_install_http_probe(mirza_install_base_url() . '/install/rewrite-check');
    if ($probe['ok'] && strpos((string) $probe['body'], 'MIRZA_REWRITE_OK') !== false) {
        return mirza_install_item('ok', 'بازنویسی مسیر (.htaccess)', 'فعال', 'قانون بازنویسی روی همین هاست تست و تأیید شد.');
    }

    return mirza_install_item('warn', 'بازنویسی مسیر (.htaccess)', 'تأیید نشد', 'تست بازنویسی پاسخ نداد (' . $probe['error'] . '). اگر AllowOverride روی هاست فعال نباشد، مسیرهای api کار نمی‌کنند.');
}

function mirza_install_config_path(): string
{
    return mirza_install_root() . '/config.php';
}

function mirza_install_config_values(): array
{
    $values = [
        'dbhost' => '',
        'dbname' => '',
        'usernamedb' => '',
        'passworddb' => '',
        'APIKEY' => '',
        'adminnumber' => '',
        'domainhosts' => '',
        'usernamebot' => '',
    ];

    $contents = @file_get_contents(mirza_install_config_path());
    if (!is_string($contents)) {
        return $values;
    }

    foreach (array_keys($values) as $key) {
        if (preg_match('/\$' . preg_quote($key, '/') . '\s*=\s*\'((?:[^\'\\\\]|\\\\.)*)\'\s*;/', $contents, $matches)) {
            $values[$key] = stripslashes($matches[1]);
        }
    }

    return $values;
}

function mirza_install_is_placeholder(string $value): bool
{
    return $value === '' || (strpos($value, '{') === 0 && substr($value, -1) === '}');
}

function mirza_install_is_configured(): bool
{
    $values = mirza_install_config_values();

    return !mirza_install_is_placeholder($values['APIKEY'])
        && !mirza_install_is_placeholder($values['dbname'])
        && !mirza_install_is_placeholder($values['adminnumber']);
}

function mirza_install_webhook_secret(array $values): string
{
    try {
        $dsn = 'mysql:host=' . $values['dbhost'] . ';dbname=' . $values['dbname'] . ';charset=utf8mb4';
        $pdo = new PDO($dsn, $values['usernamedb'], $values['passworddb'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 10,
        ]);

        $statement = $pdo->prepare("UPDATE setting SET webhook_secret = ? WHERE webhook_secret IS NULL OR webhook_secret = ''");
        $statement->execute([bin2hex(random_bytes(24))]);

        $secret = $pdo->query('SELECT webhook_secret FROM setting LIMIT 1')->fetchColumn();
    } catch (Throwable $exception) {
        error_log('Unable to read the webhook secret from the database: ' . $exception->getMessage());

        return '';
    }

    return is_string($secret) ? $secret : '';
}

function mirza_install_write_config(array $values): array
{
    $path = mirza_install_config_path();

    if (is_file($path) && !is_writable($path)) {
        return ['ok' => false, 'error' => 'فایل config.php قابل نوشتن نیست؛ سطح دسترسی آن را روی 644 تنظیم کنید.'];
    }
    if (!is_file($path) && !is_writable(dirname($path))) {
        return ['ok' => false, 'error' => 'پوشه ریشه قابل نوشتن نیست و config.php ساخته نمی‌شود.'];
    }

    $escape = static fn(string $value): string => str_replace(["\\", "'"], ["\\\\", "\\'"], $value);

    $contents = "<?php\n\n"
        . "\$request_exec_timeout = null;\n"
        . "\$dbhost = '" . $escape($values['dbhost']) . "';\n"
        . "\$dbname = '" . $escape($values['dbname']) . "';\n"
        . "\$usernamedb = '" . $escape($values['usernamedb']) . "';\n"
        . "\$passworddb = '" . $escape($values['passworddb']) . "';\n"
        . "\$options = [\n"
        . "    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,\n"
        . "    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,\n"
        . "    PDO::ATTR_EMULATE_PREPARES => false,\n"
        . "    PDO::MYSQL_ATTR_INIT_COMMAND => \"SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci\",\n"
        . "];\n"
        . "\$dsn = \"mysql:host=\$dbhost;dbname=\$dbname;charset=utf8mb4\";\n"
        . "try {\n"
        . "    \$pdo = new PDO(\$dsn, \$usernamedb, \$passworddb, \$options);\n"
        . "} catch (\\PDOException \$e) {\n"
        . "    error_log(\"Database connection failed: \" . \$e->getMessage());\n"
        . "    die(\"error: database connection failed\");\n"
        . "}\n"
        . "\$APIKEY = '" . $escape($values['APIKEY']) . "';\n"
        . "\$adminnumber = '" . $escape($values['adminnumber']) . "';\n"
        . "\$domainhosts = '" . $escape($values['domainhosts']) . "';\n"
        . "\$usernamebot = '" . $escape($values['usernamebot']) . "';\n";

    if (is_file($path)) {
        @copy($path, __DIR__ . '/state/config.backup.php');
    }

    $temporary = $path . '.tmp';
    if (@file_put_contents($temporary, $contents, LOCK_EX) === false) {
        return ['ok' => false, 'error' => 'نوشتن فایل موقت config.php ناموفق بود.'];
    }
    if (!@rename($temporary, $path)) {
        @unlink($temporary);

        return ['ok' => false, 'error' => 'جایگزینی فایل config.php ناموفق بود.'];
    }

    @chmod($path, 0644);

    return ['ok' => true, 'error' => ''];
}

function mirza_install_database_version_check(string $version): array
{
    $isMariaDb = stripos($version, 'mariadb') !== false;
    preg_match('/(\d+)\.(\d+)\.(\d+)/', $version, $matches);
    $numeric = isset($matches[0]) ? $matches[0] : '0.0.0';

    if ($isMariaDb) {
        return version_compare($numeric, '10.2.0', '>=')
            ? mirza_install_item('ok', 'نسخه دیتابیس', 'MariaDB ' . $numeric)
            : mirza_install_item('fail', 'نسخه دیتابیس', 'MariaDB ' . $numeric, 'حداقل MariaDB 10.2 لازم است؛ جداول ربات از ستون JSON و utf8mb4 استفاده می‌کنند.');
    }

    return version_compare($numeric, '5.7.8', '>=')
        ? mirza_install_item('ok', 'نسخه دیتابیس', 'MySQL ' . $numeric)
        : mirza_install_item('fail', 'نسخه دیتابیس', 'MySQL ' . $numeric, 'حداقل MySQL 5.7.8 لازم است؛ جداول ربات از ستون JSON و utf8mb4 استفاده می‌کنند.');
}

function mirza_install_test_database(array $values): array
{
    $items = [];

    try {
        $dsn = 'mysql:host=' . $values['dbhost'] . ';dbname=' . $values['dbname'] . ';charset=utf8mb4';
        $pdo = new PDO($dsn, $values['usernamedb'], $values['passworddb'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 10,
        ]);
    } catch (Throwable $exception) {
        return [
            'ok' => false,
            'error' => $exception->getMessage(),
            'items' => [mirza_install_item('fail', 'اتصال به دیتابیس', 'ناموفق', mirza_install_database_hint($exception->getMessage()))],
        ];
    }

    $version = (string) $pdo->query('SELECT VERSION()')->fetchColumn();
    $items[] = mirza_install_item('ok', 'اتصال به دیتابیس', 'برقرار شد', 'میزبان: ' . $values['dbhost']);
    $items[] = mirza_install_database_version_check($version);

    $activeDatabase = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();
    $items[] = $activeDatabase === $values['dbname']
        ? mirza_install_item('ok', 'دیتابیس فعال', $activeDatabase)
        : mirza_install_item('fail', 'دیتابیس فعال', $activeDatabase === '' ? 'انتخاب نشده' : $activeDatabase, 'دیتابیس متصل با نام واردشده یکی نیست.');

    $collation = $pdo->query("SHOW COLLATION LIKE 'utf8mb4_unicode_ci'")->fetch();
    $items[] = $collation
        ? mirza_install_item('ok', 'پشتیبانی utf8mb4', 'utf8mb4_unicode_ci موجود است', 'برای ذخیره متن فارسی و ایموجی لازم است.')
        : mirza_install_item('fail', 'پشتیبانی utf8mb4', 'موجود نیست', 'سرور دیتابیس از utf8mb4_unicode_ci پشتیبانی نمی‌کند و جداول ساخته نمی‌شوند.');

    $innodb = false;
    foreach ($pdo->query('SHOW ENGINES')->fetchAll() as $engine) {
        if (strcasecmp((string) ($engine['Engine'] ?? ''), 'InnoDB') === 0
            && in_array(strtoupper((string) ($engine['Support'] ?? '')), ['YES', 'DEFAULT'], true)) {
            $innodb = true;
        }
    }
    $items[] = $innodb
        ? mirza_install_item('ok', 'موتور InnoDB', 'فعال است')
        : mirza_install_item('fail', 'موتور InnoDB', 'فعال نیست', 'همه جداول ربات با ENGINE=InnoDB ساخته می‌شوند.');

    $privilege = mirza_install_database_privilege_check($pdo);
    $items[] = $privilege;

    try {
        $tableCount = (int) $pdo->query('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()')->fetchColumn();
        if ($tableCount === 0) {
            $items[] = mirza_install_item('ok', 'وضعیت دیتابیس', 'خالی است', 'نصب تازه انجام می‌شود.');
        } else {
            $items[] = mirza_install_item('warn', 'وضعیت دیتابیس', $tableCount . ' جدول موجود است', 'جداول موجود پاک نمی‌شوند؛ اگر این دیتابیس قبلاً برای ربات استفاده شده، اطلاعات حفظ و ساختار به‌روزرسانی می‌شود.');
        }
    } catch (Throwable $exception) {
        $items[] = mirza_install_item('warn', 'وضعیت دیتابیس', 'قابل خواندن نیست', $exception->getMessage());
    }

    try {
        $packet = (int) $pdo->query("SELECT @@max_allowed_packet")->fetchColumn();
        $items[] = $packet >= 4194304
            ? mirza_install_item('ok', 'max_allowed_packet', round($packet / 1048576, 1) . ' مگابایت')
            : mirza_install_item('warn', 'max_allowed_packet', round($packet / 1048576, 1) . ' مگابایت', 'مقدار کم است و ارسال پیام‌ها یا پشتیبان‌های بزرگ ممکن است خطا بدهد.');
    } catch (Throwable $exception) {
        $items[] = mirza_install_item('warn', 'max_allowed_packet', 'قابل خواندن نیست', $exception->getMessage());
    }

    $failed = 0;
    foreach ($items as $item) {
        if ($item['status'] === 'fail') {
            $failed++;
        }
    }

    return ['ok' => $failed === 0, 'error' => '', 'items' => $items, 'version' => $version];
}

function mirza_install_database_privilege_check(PDO $pdo): array
{
    $table = 'mirza_install_probe';

    try {
        $pdo->exec('DROP TABLE IF EXISTS `' . $table . '`');
        $pdo->exec('CREATE TABLE `' . $table . '` (id INT AUTO_INCREMENT PRIMARY KEY, payload JSON NOT NULL, note VARCHAR(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci');
        $pdo->exec('INSERT INTO `' . $table . '` (payload, note) VALUES (\'{"ok":true}\', \'آزمایش فارسی\')');
        $stored = (string) $pdo->query('SELECT note FROM `' . $table . '` LIMIT 1')->fetchColumn();
        $pdo->exec('ALTER TABLE `' . $table . '` ADD COLUMN extra VARCHAR(50) NULL');
        $pdo->exec('CREATE INDEX mirza_probe_idx ON `' . $table . '` (note)');
        $pdo->exec('UPDATE `' . $table . '` SET extra = \'1\'');
        $pdo->exec('DELETE FROM `' . $table . '`');
        $pdo->exec('DROP TABLE `' . $table . '`');

        if ($stored !== 'آزمایش فارسی') {
            return mirza_install_item('fail', 'ذخیره متن فارسی', 'ناموفق', 'متن فارسی درست بازخوانی نشد؛ کدگذاری دیتابیس را روی utf8mb4 تنظیم کنید.');
        }

        return mirza_install_item('ok', 'دسترسی‌های کاربر دیتابیس', 'کامل است', 'CREATE، ALTER، INDEX، INSERT، UPDATE، DELETE و DROP تست و تأیید شد.');
    } catch (Throwable $exception) {
        @$pdo->exec('DROP TABLE IF EXISTS `' . $table . '`');

        return mirza_install_item('fail', 'دسترسی‌های کاربر دیتابیس', 'ناکافی', 'اجرای تست ساخت جدول شکست خورد: ' . $exception->getMessage() . ' — در کنترل پنل هاست به این کاربر ALL PRIVILEGES بدهید.');
    }
}

function mirza_install_database_hint(string $message): string
{
    if (stripos($message, 'access denied') !== false) {
        return 'نام کاربری یا رمز دیتابیس اشتباه است، یا این کاربر به دیتابیس دسترسی ندارد: ' . $message;
    }
    if (stripos($message, 'unknown database') !== false) {
        return 'دیتابیس با این نام وجود ندارد. روی هاست‌های اشتراکی معمولاً نام دیتابیس با پیشوند اکانت شماست: ' . $message;
    }
    if (stripos($message, 'connection refused') !== false || stripos($message, 'no such host') !== false || stripos($message, 'getaddrinfo') !== false) {
        return 'میزبان دیتابیس در دسترس نیست؛ معمولاً باید localhost باشد: ' . $message;
    }

    return $message;
}

function mirza_install_telegram(string $token, string $method, array $parameters = []): array
{
    $probe = mirza_install_telegram_request($token, $method, $parameters);
    $decoded = json_decode($probe['body'], true);

    if (!is_array($decoded)) {
        return ['ok' => false, 'error' => 'پاسخ تلگرام قابل خواندن نیست: ' . $probe['error'], 'result' => []];
    }
    if (empty($decoded['ok'])) {
        return ['ok' => false, 'error' => (string) ($decoded['description'] ?? 'خطای نامشخص تلگرام'), 'result' => []];
    }

    return ['ok' => true, 'error' => '', 'result' => is_array($decoded['result'] ?? null) ? $decoded['result'] : []];
}

function mirza_install_telegram_request(string $token, string $method, array $parameters): array
{
    $handle = curl_init();
    curl_setopt_array($handle, [
        CURLOPT_URL => 'https://api.telegram.org/bot' . $token . '/' . $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $parameters,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_CONNECTTIMEOUT => 15,
    ]);
    $body = curl_exec($handle);
    $error = curl_error($handle);
    unset($handle);

    return ['body' => is_string($body) ? $body : '', 'error' => $error];
}

function mirza_install_cron_jobs(): array
{
    return [
        ['job' => 'croncard', 'schedule' => '*/1 * * * *', 'minutes' => 1, 'optional' => false, 'title' => 'تأیید خودکار رسید کارت به کارت'],
        ['job' => 'NoticationsService', 'schedule' => '*/1 * * * *', 'minutes' => 1, 'optional' => false, 'title' => 'ارسال اعلان‌های ربات'],
        ['job' => 'sendmessage', 'schedule' => '*/1 * * * *', 'minutes' => 1, 'optional' => false, 'title' => 'صف ارسال پیام همگانی'],
        ['job' => 'activeconfig', 'schedule' => '*/1 * * * *', 'minutes' => 1, 'optional' => false, 'title' => 'فعال‌سازی سرویس‌های خریداری‌شده'],
        ['job' => 'disableconfig', 'schedule' => '*/1 * * * *', 'minutes' => 1, 'optional' => false, 'title' => 'غیرفعال‌سازی سرویس‌های منقضی'],
        ['job' => 'iranpay1', 'schedule' => '*/1 * * * *', 'minutes' => 1, 'optional' => false, 'title' => 'پیگیری پرداخت‌های ایران‌پی'],
        ['job' => 'gift', 'schedule' => '*/2 * * * *', 'minutes' => 2, 'optional' => false, 'title' => 'پردازش کدهای هدیه'],
        ['job' => 'configtest', 'schedule' => '*/2 * * * *', 'minutes' => 2, 'optional' => false, 'title' => 'مدیریت سرویس‌های تست'],
        ['job' => 'plisio', 'schedule' => '*/3 * * * *', 'minutes' => 3, 'optional' => false, 'title' => 'پیگیری پرداخت‌های ارز دیجیتال'],
        ['job' => 'payment_expire', 'schedule' => '*/5 * * * *', 'minutes' => 5, 'optional' => false, 'title' => 'انقضای فاکتورهای پرداخت‌نشده'],
        ['job' => 'statusday', 'schedule' => '*/15 * * * *', 'minutes' => 15, 'optional' => false, 'title' => 'گزارش وضعیت روزانه'],
        ['job' => 'on_hold', 'schedule' => '*/15 * * * *', 'minutes' => 15, 'optional' => false, 'title' => 'سرویس‌های در حالت انتظار'],
        ['job' => 'uptime_node', 'schedule' => '*/15 * * * *', 'minutes' => 15, 'optional' => false, 'title' => 'پایش وضعیت نودها'],
        ['job' => 'uptime_panel', 'schedule' => '*/15 * * * *', 'minutes' => 15, 'optional' => false, 'title' => 'پایش وضعیت پنل‌ها'],
        ['job' => 'expireagent', 'schedule' => '*/30 * * * *', 'minutes' => 30, 'optional' => false, 'title' => 'انقضای اشتراک نمایندگان'],
        ['job' => 'backupbot', 'schedule' => '0 */5 * * *', 'minutes' => 300, 'optional' => false, 'title' => 'پشتیبان‌گیری ربات‌ساز'],
        ['job' => 'lottery', 'schedule' => '*/1 * * * *', 'minutes' => 1, 'optional' => true, 'title' => 'قرعه‌کشی و امتیازات (فقط اگر امتیازدهی را فعال کنید)'],
    ];
}

function mirza_install_cron_command(array $job): string
{
    return $job['schedule'] . ' curl -s ' . mirza_install_base_url() . '/cronbot/' . $job['job'] . '.php > /dev/null 2>&1';
}

function mirza_install_cron_command_php(array $job): string
{
    return $job['schedule'] . ' /usr/bin/php ' . mirza_install_root() . '/cronbot/' . $job['job'] . '.php > /dev/null 2>&1';
}

function mirza_install_probe_command(): string
{
    return '*/1 * * * * curl -s ' . mirza_install_base_url() . '/install/cron-check.php > /dev/null 2>&1';
}

function mirza_install_probe_command_php(): string
{
    return '*/1 * * * * /usr/bin/php ' . __DIR__ . '/cron-check.php > /dev/null 2>&1';
}

function mirza_install_probe_file(): string
{
    return mirza_install_state_dir() . '/cron-probe.json';
}

function mirza_install_probe_read(): array
{
    $raw = @file_get_contents(mirza_install_probe_file());
    if (!is_string($raw) || $raw === '') {
        return ['started_at' => 0, 'hits' => []];
    }
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return ['started_at' => 0, 'hits' => []];
    }

    return [
        'started_at' => (int) ($decoded['started_at'] ?? 0),
        'hits' => is_array($decoded['hits'] ?? null) ? array_map('intval', $decoded['hits']) : [],
    ];
}

function mirza_install_probe_reset(): void
{
    @file_put_contents(mirza_install_probe_file(), json_encode(['started_at' => time(), 'hits' => []]), LOCK_EX);
}

function mirza_install_probe_record(): void
{
    $state = mirza_install_probe_read();
    if ($state['started_at'] === 0) {
        $state['started_at'] = time();
    }
    $state['hits'][] = time();
    if (count($state['hits']) > 30) {
        $state['hits'] = array_slice($state['hits'], -30);
    }
    @file_put_contents(mirza_install_probe_file(), json_encode($state), LOCK_EX);
}

function mirza_install_relative_time(int $timestamp): string
{
    if ($timestamp <= 0) {
        return 'هرگز';
    }

    $seconds = time() - $timestamp;
    if ($seconds < 60) {
        return $seconds . ' ثانیه پیش';
    }
    if ($seconds < 3600) {
        return (int) floor($seconds / 60) . ' دقیقه پیش';
    }
    if ($seconds < 86400) {
        return (int) floor($seconds / 3600) . ' ساعت پیش';
    }

    return (int) floor($seconds / 86400) . ' روز پیش';
}

function mirza_install_probe_status(): array
{
    $state = mirza_install_probe_read();
    $hits = $state['hits'];
    sort($hits);
    $count = count($hits);
    $first = $count > 0 ? $hits[0] : 0;
    $last = $count > 0 ? $hits[$count - 1] : 0;
    $span = $count > 1 ? $last - $first : 0;
    $verified = $count >= 2 && $span >= 45;

    if ($verified) {
        $message = 'کرون هاست فعال است و هر دقیقه اجرا می‌شود.';
    } elseif ($count === 1) {
        $message = 'یک اجرا ثبت شد؛ برای اطمینان از تکرار شدن، تا اجرای بعدی صبر کنید.';
    } elseif ($count > 1) {
        $message = 'چند اجرا ثبت شد ولی فاصله زمانی آن‌ها کافی نیست؛ کمی صبر کنید.';
    } else {
        $message = 'هنوز هیچ اجرایی ثبت نشده است. کرون تست را در کنترل پنل هاست ثبت کنید و یک تا دو دقیقه صبر کنید. اگر با curl ثبت کردید و هنوز SSL دامنه آماده نیست، از تب «اجرای مستقیم PHP» استفاده کنید.';
    }

    return [
        'verified' => $verified,
        'count' => $count,
        'first_run' => $first > 0 ? date('H:i:s', $first) : '',
        'last_run' => $last > 0 ? date('Y-m-d H:i:s', $last) : '',
        'last_run_human' => mirza_install_relative_time($last),
        'span' => $span,
        'started_at_human' => $state['started_at'] > 0 ? date('Y-m-d H:i:s', $state['started_at']) : '',
        'waited_seconds' => $state['started_at'] > 0 ? time() - $state['started_at'] : 0,
        'message' => $message,
        'command_curl' => mirza_install_probe_command(),
        'command_php' => mirza_install_probe_command_php(),
    ];
}

function mirza_install_cron_plan(): array
{
    $jobs = [];
    foreach (mirza_install_cron_jobs() as $job) {
        $jobs[] = [
            'job' => $job['job'],
            'title' => $job['title'],
            'schedule' => $job['schedule'],
            'optional' => $job['optional'],
            'command_curl' => mirza_install_cron_command($job),
            'command_php' => mirza_install_cron_command_php($job),
        ];
    }

    return $jobs;
}

function mirza_install_required_jobs(): array
{
    $required = [];
    foreach (mirza_install_cron_jobs() as $job) {
        if (!$job['optional']) {
            $required[] = $job['job'];
        }
    }

    return $required;
}

function mirza_install_delete_tree(string $path): bool
{
    if (is_link($path) || is_file($path)) {
        return @unlink($path);
    }
    if (!is_dir($path)) {
        return true;
    }

    $entries = @scandir($path);
    if ($entries === false) {
        return false;
    }

    $removed = true;
    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $removed = mirza_install_delete_tree($path . '/' . $entry) && $removed;
    }

    return @rmdir($path) && $removed;
}
