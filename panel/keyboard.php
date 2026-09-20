<?php
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/icons.php';
require_auth();

$keyboard = json_decode(file_get_contents("php://input"), true);
$method = $_SERVER['REQUEST_METHOD'];
if ($method == "POST" && is_array($keyboard)) {
    $validKeyboard = count($keyboard) > 0;
    foreach ($keyboard as $row) {
        if (!is_array($row) || count($row) === 0) {
            $validKeyboard = false;
            break;
        }
        foreach ($row as $button) {
            if (!is_array($button) || !isset($button['text']) || !is_string($button['text']) || trim($button['text']) === '') {
                $validKeyboard = false;
                break 2;
            }
        }
    }
    header('Content-Type: application/json; charset=utf-8');
    if (!$validKeyboard) {
        http_response_code(422);
        echo json_encode(['ok' => false]);
        exit;
    }
    $keyboardmain = ['keyboard' => $keyboard];
    update("setting", "keyboardmain", json_encode($keyboardmain), null, null);
    echo json_encode(['ok' => true]);
    exit;
} else {
    $keyboardmain = '{"keyboard":[[{"text":"text_sell"},{"text":"text_extend"}],[{"text":"text_usertest"},{"text":"text_wheel_luck"}],[{"text":"text_Purchased_services"},{"text":"accountwallet"}],[{"text":"text_affiliates"},{"text":"text_Tariff_list"}],[{"text":"text_support"},{"text":"text_help"}],[{"text":"text_agentpanel"},{"text":"text_requestagent"}]]}';
    $action = filter_input(INPUT_GET, 'action');
    if ($action === "reaset") {
        csrf_check_get();
        update("setting", "keyboardmain", $keyboardmain, null, null);
        header('Location: keyboard.php');
        exit;
    }
}
?>

<!doctype html>
<html lang="FA">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= $textbotlang['panel']['keyboardManageTitle'] ?></title>

    <script type="module" crossorigin src="js/sort_keyboard.js"></script>
    <link rel="stylesheet" crossorigin href="css/sort_keyboard.css">
    <style>
        @import url(https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap);

        * {
            font-family: 'Vazirmatn' !important;
        }

        button {
            font-family: yekan;
        }

        .btnback {
            position: fixed;
            top: 10px;
            left: 10px;
            padding: 7px;
            background-color: #3d3d3d;
            color: #fff;
            border-radius: 6px;
            font-family: yekan;
            font-size: 13px;
            font-weight: bold;
        }

        .btndefult {
            position: fixed;
            top: 10px;
            left: 150px;
            padding: 7px;
            background-color: #fff;
            border: 2px solid #3d3d3d;
            color: #3d3d3d;
            border-radius: 6px;
            font-family: yekan;
            font-size: 13px;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <a class="btnback" href="index.php"><?= $textbotlang['panel']['keyboardSortHint'] ?></a>
    <a class="btndefult" href="keyboard.php?action=reaset&_csrf=<?= urlencode(csrf_token()) ?>"><?= $textbotlang['panel']['keyboardSaveBtn'] ?></a>
    <div id="root"></div>
</body>

</html>