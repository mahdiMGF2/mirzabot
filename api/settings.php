<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../function.php';
require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/../botapi.php';

header('Content-Type: application/json; charset=UTF-8');
date_default_timezone_set('Asia/Tehran');
ini_set('default_charset', 'UTF-8');
ini_set('error_log', 'error_log');

list($headers, $data, $action) = apiRequestContext();
$method = $_SERVER['REQUEST_METHOD'];

const DEFAULT_MAIN_KEYBOARD = '{"keyboard":[[{"text":"text_sell"},{"text":"text_extend"}],[{"text":"text_usertest"},{"text":"text_wheel_luck"}],[{"text":"text_Purchased_services"},{"text":"accountwallet"}],[{"text":"text_affiliates"},{"text":"text_Tariff_list"}],[{"text":"text_support"},{"text":"text_help"}],[{"text":"text_agentpanel"},{"text":"text_requestagent"}]]}';

function setting_keyboard_set(array $data, string $method): void
{
    validateMethod('POST', $method);

    try {
        if (!empty($data['keyboard_reset'])) {
            update("setting", "keyboardmain", DEFAULT_MAIN_KEYBOARD, null, null);
            sendJsonResponse(true, "Successful", [], 200);
        }

        if (!isset($data['keyboard']) || !is_array($data['keyboard'])) {
            sendJsonResponse(false, 'keyboard invalid', [], 200);
        }

        update("setting", "keyboardmain", json_encode(['keyboard' => $data['keyboard']]), null, null);
        sendJsonResponse(true, "Successful", [], 200);
    } catch (Exception $e) {
        error_log("Database error in keyboard: " . $e->getMessage());
        sendJsonResponse(false, "Database error occurred", [], 200);
    }
}

function setting_setting_info(array $data, string $method): void
{
    validateMethod('GET', $method);

    try {
        $shopsetting = select("shopSetting", "*", null, null, "fetchAll");
        $shop_setting = [];
        foreach (is_array($shopsetting) ? $shopsetting : [] as $row) {
            $shop_setting[$row['Namevalue']] = $row['value'];
        }

        sendJsonResponse(true, "Successful", [
            'setting_shop' => $shop_setting,
            'setting_General' => select("setting", "*", null, null, "select")
        ]);
    } catch (Exception $e) {
        error_log("Database error in setting: " . $e->getMessage());
        sendJsonResponse(false, "Database error ", [], 500);
    }
}

function setting_save_setting_shop(array $data, string $method): void
{
    validateMethod('POST', $method);

    if (empty($data['data']) || !is_array($data['data'])) {
        sendJsonResponse(false, "data empty ", [], 200);
    }

    try {
        foreach ($data['data'] as $row) {
            if (!is_array($row) || !isset($row['name_value'])) {
                sendJsonResponse(false, "invalid setting entry", [], 200);
            }

            $value = $row['value'] ?? null;
            if (!empty($row['json'])) {
                $value = json_encode($value);
            }

            if (($row['type'] ?? '') === "shop") {
                update("shopSetting", "value", $value, "Namevalue", $row['name_value']);
            } else {
                $settingField = (string) $row['name_value'];
                if (!preg_match('/^[A-Za-z0-9_]{1,64}$/', $settingField)) {
                    sendJsonResponse(false, "invalid setting name", [], 200);
                }
                $columnCheck = $GLOBALS['pdo']->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?');
                $columnCheck->execute(['setting', $settingField]);
                if ((int) $columnCheck->fetchColumn() === 0) {
                    sendJsonResponse(false, "invalid setting name", [], 200);
                }
                update("setting", $settingField, $value, null, null);
            }
        }

        sendJsonResponse(true, "setting updated successfully", [], 200);
    } catch (InvalidArgumentException $e) {
        error_log("Rejected setting name: " . $e->getMessage());
        sendJsonResponse(false, "invalid setting name", [], 200);
    } catch (Exception $e) {
        error_log("Database error in setting: " . $e->getMessage());
        sendJsonResponse(false, "Database error ", [], 500);
    }
}

match ($action) {
    'keyboard_set' => setting_keyboard_set($data, $method),
    'setting_info' => setting_setting_info($data, $method),
    'save_setting_shop' => setting_save_setting_shop($data, $method),
    default => sendJsonResponse(false, "Action Invalid"),
};
