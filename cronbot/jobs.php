<?php

if (!function_exists('mirza_cron_jobs')) {
    function mirza_cron_jobs(): array
    {
        return [
            ['job' => 'croncard', 'schedule' => '*/1 * * * *', 'title' => 'تأیید خودکار رسید کارت به کارت'],
            ['job' => 'NoticationsService', 'schedule' => '*/1 * * * *', 'title' => 'ارسال اعلان‌های ربات'],
            ['job' => 'sendmessage', 'schedule' => '*/1 * * * *', 'title' => 'صف ارسال پیام همگانی'],
            ['job' => 'activeconfig', 'schedule' => '*/1 * * * *', 'title' => 'فعال‌سازی سرویس‌های خریداری‌شده'],
            ['job' => 'disableconfig', 'schedule' => '*/1 * * * *', 'title' => 'غیرفعال‌سازی سرویس‌های منقضی'],
            ['job' => 'iranpay1', 'schedule' => '*/1 * * * *', 'title' => 'پیگیری پرداخت‌های ایران‌پی'],
            ['job' => 'gift', 'schedule' => '*/2 * * * *', 'title' => 'پردازش کدهای هدیه'],
            ['job' => 'configtest', 'schedule' => '*/2 * * * *', 'title' => 'مدیریت سرویس‌های تست'],
            ['job' => 'plisio', 'schedule' => '*/3 * * * *', 'title' => 'پیگیری پرداخت‌های ارز دیجیتال'],
            ['job' => 'payment_expire', 'schedule' => '*/5 * * * *', 'title' => 'انقضای فاکتورهای پرداخت‌نشده'],
            ['job' => 'statusday', 'schedule' => '*/15 * * * *', 'title' => 'گزارش وضعیت روزانه'],
            ['job' => 'on_hold', 'schedule' => '*/15 * * * *', 'title' => 'سرویس‌های در حالت انتظار'],
            ['job' => 'uptime_node', 'schedule' => '*/15 * * * *', 'title' => 'پایش وضعیت نودها'],
            ['job' => 'uptime_panel', 'schedule' => '*/15 * * * *', 'title' => 'پایش وضعیت پنل‌ها'],
            ['job' => 'expireagent', 'schedule' => '*/30 * * * *', 'title' => 'انقضای اشتراک نمایندگان'],
            ['job' => 'backupbot', 'schedule' => '0 */5 * * *', 'title' => 'پشتیبان‌گیری ربات‌ساز'],
            ['job' => 'lottery', 'schedule' => '*/1 * * * *', 'title' => 'قرعه‌کشی و امتیازات'],
        ];
    }
}

if (!function_exists('mirza_cron_dispatcher_path')) {
    function mirza_cron_dispatcher_path(): string
    {
        return __DIR__ . '/run.php';
    }
}

if (!function_exists('mirza_cron_stagger_seconds')) {
    function mirza_cron_stagger_seconds(string $seed = ''): int
    {
        if ($seed === '') {
            $seed = dirname(__DIR__);
        }

        return (int) (sprintf('%u', crc32($seed)) % 20);
    }
}

if (!function_exists('mirza_cron_php_binary')) {
    function mirza_cron_php_binary(): string
    {
        $php = PHP_BINDIR . '/php';
        if (is_executable($php)) {
            return $php;
        }
        if (defined('PHP_BINARY') && PHP_BINARY !== '' && is_executable(PHP_BINARY)) {
            return PHP_BINARY;
        }
        if (is_executable('/usr/bin/php')) {
            return '/usr/bin/php';
        }

        return 'php';
    }
}

if (!function_exists('mirza_cron_dispatcher_command')) {
    function mirza_cron_dispatcher_command(string $seed = ''): string
    {
        $sleep = mirza_cron_stagger_seconds($seed);
        $php = mirza_cron_php_binary();
        $path = mirza_cron_dispatcher_path();

        return '* * * * * sleep ' . $sleep . '; ' . $php . ' ' . $path . ' >/dev/null 2>&1';
    }
}

if (!function_exists('mirza_cron_dispatcher_curl_command')) {
    function mirza_cron_dispatcher_curl_command(string $baseUrl): string
    {
        return '* * * * * curl -s ' . rtrim($baseUrl, '/') . '/cronbot/run.php > /dev/null 2>&1';
    }
}
