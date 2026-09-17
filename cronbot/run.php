<?php

declare(strict_types=1);

$cronbotDir = __DIR__;
chdir($cronbotDir);
ini_set('error_log', 'error_log');
date_default_timezone_set('Asia/Tehran');

require_once $cronbotDir . '/jobs.php';

$lockFh = fopen($cronbotDir . '/.run.lock', 'c+');
if ($lockFh === false) {
    exit(1);
}
if (!flock($lockFh, LOCK_EX | LOCK_NB)) {
    fclose($lockFh);
    exit(0);
}

$slotFh = mirza_cron_try_host_slot(3, 2);
$scorestatus = null;

try {
    foreach (mirza_cron_jobs() as $job) {
        $script = $cronbotDir . '/' . $job['job'] . '.php';
        if (!is_file($script)) {
            continue;
        }

        if (!mirza_cron_is_due($job['schedule'])) {
            continue;
        }

        if ($job['job'] === 'lottery') {
            if ($scorestatus === null) {
                require_once dirname($cronbotDir) . '/config.php';
                require_once dirname($cronbotDir) . '/function.php';
                $setting = select('setting', '*');
                $scorestatus = intval($setting['scorestatus'] ?? 0);
            }
            if ($scorestatus !== 1) {
                continue;
            }
        }

        try {
            include $script;
        } catch (Throwable $e) {
            error_log('mirza cron: ' . $job['job'] . ': ' . $e->getMessage());
        }
    }
} finally {
    mirza_cron_release_host_slot($slotFh);
    flock($lockFh, LOCK_UN);
    fclose($lockFh);
}

/** @return resource|null */
function mirza_cron_try_host_slot(int $maxSlots, int $waitSeconds)
{
    $dir = '/tmp/mirza-cron-slots';
    if ((!is_dir($dir) && !@mkdir($dir, 0777, true) && !is_dir($dir)) || !is_writable($dir)) {
        return null;
    }

    $deadline = microtime(true) + $waitSeconds;
    do {
        for ($i = 0; $i < $maxSlots; $i++) {
            $path = $dir . '/slot-' . $i . '.lock';
            $fh = @fopen($path, 'c+');
            if ($fh === false) {
                continue;
            }
            if (flock($fh, LOCK_EX | LOCK_NB)) {
                return $fh;
            }
            fclose($fh);
        }
        usleep(200000);
    } while (microtime(true) < $deadline);

    return null;
}

/** @param resource|null $slotFh */
function mirza_cron_release_host_slot($slotFh): void
{
    if ($slotFh === null || !is_resource($slotFh)) {
        return;
    }
    flock($slotFh, LOCK_UN);
    fclose($slotFh);
}

function mirza_cron_is_due(string $expression, ?DateTimeInterface $now = null): bool
{
    $now = $now ?? new DateTimeImmutable('now');
    $parts = preg_split('/\s+/', trim($expression));
    if ($parts === false || count($parts) !== 5) {
        return false;
    }

    [$minute, $hour, $day, $month, $weekday] = $parts;
    $values = [
        (int) $now->format('i'),
        (int) $now->format('G'),
        (int) $now->format('j'),
        (int) $now->format('n'),
        (int) $now->format('w'),
    ];
    $fields = [$minute, $hour, $day, $month, $weekday];
    $mins = [0, 0, 1, 1, 0];

    for ($i = 0; $i < 5; $i++) {
        if (!mirza_cron_field_matches($fields[$i], $values[$i], $mins[$i])) {
            return false;
        }
    }

    return true;
}

function mirza_cron_field_matches(string $field, int $value, int $min): bool
{
    foreach (explode(',', $field) as $piece) {
        $piece = trim($piece);
        if ($piece === '') {
            continue;
        }

        $step = 1;
        if (strpos($piece, '/') !== false) {
            [$piece, $stepRaw] = explode('/', $piece, 2);
            $step = max(1, (int) $stepRaw);
        }

        if ($piece === '*' || $piece === '') {
            if ($step === 1 || ($value - $min) % $step === 0) {
                return true;
            }
            continue;
        }

        if (strpos($piece, '-') !== false) {
            [$start, $end] = array_map('intval', explode('-', $piece, 2));
            if ($value >= $start && $value <= $end && ($step === 1 || ($value - $start) % $step === 0)) {
                return true;
            }
            continue;
        }

        $exact = (int) $piece;
        if ($value === $exact && ($step === 1 || ($value - $min) % $step === 0)) {
            return true;
        }
    }

    return false;
}
