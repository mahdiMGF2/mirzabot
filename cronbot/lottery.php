<?php
chdir(__DIR__);
ini_set('error_log', 'error_log');
date_default_timezone_set('Asia/Tehran');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../botapi.php';
require_once __DIR__ . '/../function.php';
require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../jdf.php';

$setting = select("setting", "*");

if (!$setting || !isset($setting['scorestatus'])) {
    error_log("Setting data is missing or incomplete.");
    return;
}

$midnight_time = date("H:i");

if (intval($setting['scorestatus']) == 1) {
    $otherreport = select("topicid", "idreport", "report", "otherreport", "select")['idreport'];

    if ($midnight_time == "00:00") {
        $temp = [];
        $Lottery_prize = json_decode($setting['Lottery_prize'], true);

        if (!is_array($Lottery_prize)) {
            error_log("Lottery_prize is not a valid JSON array.");
            return;
        }

        foreach ($Lottery_prize as $lottery) {
            $temp[] = $lottery;
        }
        $Lottery_prize = $temp;

        if ($setting['Lotteryagent'] == "1") {
            $stmt = $pdo->prepare("SELECT * FROM user WHERE User_Status = 'Active' AND score != '0' ORDER BY score DESC LIMIT 3");
        } else {
            $stmt = $pdo->prepare("SELECT * FROM user WHERE User_Status = 'Active' AND score != '0' AND agent = 'f' ORDER BY score DESC LIMIT 3");
        }
        $stmt->execute();
        $winners = $stmt->fetchAll(PDO::FETCH_ASSOC);
        update("user", "score", "0", null, null);

        $count = 0;

        $textJson = languagechange();
        if (!is_array($textJson)) {
            error_log("Language file (lang/fa.php) could not be loaded.");
            return;
        }
        $textlotterygroup = $textJson['Admin']['report']['lotteryTitle'];

        foreach ($winners as $result) {
            if (!isset($Lottery_prize[$count])) {
                error_log("No prize defined for rank " . ($count + 1));
                break;
            }

            $prizeAmount = intval($Lottery_prize[$count]);
            $creditStmt = $pdo->prepare("UPDATE user SET Balance = Balance + :prize WHERE id = :id");
            $creditStmt->execute([':prize' => $prizeAmount, ':id' => $result['id']]);
            clearSelectCache('user');

            $balanceFormatted = number_format($prizeAmount);
            $rank = $count + 1;

            $textlottery = sprintf($textJson['users']['lottery']['winnerNotice'], $rank, $balanceFormatted);
            sendmessage($result['id'], $textlottery, null, 'html');

            $textlotterygroup .= sprintf($textJson['Admin']['report']['lotteryWinnerRow'], $result['username'], $result['id'], $balanceFormatted, $rank);

            $count++;
        }

        if ($count > 0 && !isTelegramChatIdEmpty($setting['Channel_Report'] ?? '')) {
            telegram('sendmessage', [
                'chat_id' => $setting['Channel_Report'],
                'message_thread_id' => $otherreport,
                'text' => $textlotterygroup,
                'parse_mode' => "HTML"
            ]);
        }
    }
}
