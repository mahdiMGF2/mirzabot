<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../function.php';
require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/../botapi.php';
header('Content-Type: application/json; charset=UTF-8');
date_default_timezone_set('Asia/Tehran');
ini_set('default_charset', 'UTF-8');
ini_set('error_log', 'error_log');

requireApiTokenOrAdminSession(getallheaders());

$textbotlang = languagechange();

$keyboardSetting = select("setting", "keyboardmain", null, null, "select");
$keyboardmain = json_decode($keyboardSetting['keyboardmain'] ?? '', true);
if (!is_array($keyboardmain) || !isset($keyboardmain['keyboard']) || !is_array($keyboardmain['keyboard'])) {
    $keyboardmain = ['keyboard' => []];
}

$list_keyboard = [
    'text_sell',
    'text_extend',
    'text_usertest',
    'text_wheel_luck',
    'text_Purchased_services',
    'accountwallet',
    'text_affiliates',
    'text_Tariff_list',
    'text_support',
    'text_help',
    'text_agentpanel',
    'text_requestagent',
];
$textbotlang['textbot'] = [
    'text_sell' => $textbotlang['textbot']['sell'],
    'text_extend' => $textbotlang['textbot']['extend'],
    'text_usertest' => $textbotlang['textbot']['userTest'],
    'text_wheel_luck' => $textbotlang['textbot']['wheelLuck'],
    'text_Purchased_services' => $textbotlang['textbot']['purchasedServices'],
    'accountwallet' => $textbotlang['textbot']['accountWallet'],
    'text_affiliates' => $textbotlang['textbot']['affiliates'],
    'text_Tariff_list' => $textbotlang['textbot']['tariffList'],
    'text_support' => $textbotlang['textbot']['support'],
    'text_help' => $textbotlang['textbot']['help'],
    'text_agentpanel' => $textbotlang['textbot']['agentPanel'],
    'text_requestagent' => $textbotlang['textbot']['requestAgent'],
];

foreach ($keyboardmain['keyboard'] as $keyboard) {
    if (!is_array($keyboard)) {
        continue;
    }
    foreach ($keyboard as $arrkey) {
        if (!is_array($arrkey) || !isset($arrkey['text'])) {
            continue;
        }
        $index_number = array_search($arrkey['text'], $list_keyboard, true);
        if ($index_number !== false) {
            unset($list_keyboard[$index_number]);
        }
    }
}
$list_keyboard = array_values($list_keyboard);

$keyboard = [];
foreach ($list_keyboard as $key) {
    $keyboard[] = [['text' => $key]];
}

echo json_encode([
    'keylist' => $keyboard,
    'userlist' => $keyboardmain['keyboard'],
    'text' => array_map('customEmojiLabelText', $textbotlang['textbot'])
], JSON_UNESCAPED_UNICODE);
