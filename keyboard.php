<?php
require_once 'config.php';
$setting = select("setting", "*", null, null, "select");
$textbotlang = languagechange();
//-----------------------------[  text panel  ]-------------------------------
$adminrulecheck = select("admin", "*", "id_admin", $from_id, "select");
if (!$adminrulecheck) {
    $adminrulecheck = array(
        'rule' => '',
    );
}
$users = select("user", "*", "id", $from_id, "select");
if ($users == false) {
    $users = array();
    $users = array(
        'step' => '',
        'agent' => '',
        'limit_usertest' => '',
        'Processing_value' => '',
        'Processing_value_four' => '',
        'cardpayment' => ""
    );
}
$replacements = [
    'text_usertest' => $textbotlang['textbot']['userTest'],
    'text_Purchased_services' => $textbotlang['textbot']['purchasedServices'],
    'text_support' => $textbotlang['textbot']['support'],
    'text_help' => $textbotlang['textbot']['help'],
    'accountwallet' => $textbotlang['textbot']['accountWallet'],
    'text_sell' => $textbotlang['textbot']['sell'],
    'text_Tariff_list' => $textbotlang['textbot']['tariffList'],
    'text_affiliates' => $textbotlang['textbot']['affiliates'],
    'text_wheel_luck' => $textbotlang['textbot']['wheelLuck'],
    'text_extend' => $textbotlang['textbot']['extend']
];
$admin_idss = select("admin", "*", "id_admin", $from_id, "count");
$temp_addtional_key = [];
$keyboardLayout = json_decode($setting['keyboardmain'], true);
$keyboardRows = [];
if (is_array($keyboardLayout) && isset($keyboardLayout['keyboard']) && is_array($keyboardLayout['keyboard'])) {
    $keyboardRows = $keyboardLayout['keyboard'];
}

if (!empty($keyboardRows)) {
    $allowed_btn_styles = ['primary', 'success', 'danger'];
    foreach ($keyboardRows as $kb_r => $kb_row) {
        if (!is_array($kb_row)) {
            continue;
        }
        foreach ($kb_row as $kb_c => $kb_btn) {
            if (is_array($kb_btn) && isset($kb_btn['style']) && !in_array($kb_btn['style'], $allowed_btn_styles, true)) {
                unset($keyboardRows[$kb_r][$kb_c]['style']);
            }
        }
    }
}

if ($setting['inlinebtnmain'] == "oninline" && !empty($keyboardRows)) {
    $trace_keyboard = $keyboardRows;
    foreach ($trace_keyboard as $key => $callback_set) {
        foreach ($callback_set as $keyboard_key => $keyboard) {
            if ($keyboard['text'] == "text_sell") {
                $trace_keyboard[$key][$keyboard_key]['callback_data'] = "buy";
            }
            if ($keyboard['text'] == "accountwallet") {
                $trace_keyboard[$key][$keyboard_key]['callback_data'] = "account";
            }
            if ($keyboard['text'] == "text_Tariff_list") {
                $trace_keyboard[$key][$keyboard_key]['callback_data'] = "Tariff_list";
            }
            if ($keyboard['text'] == "text_wheel_luck") {
                $trace_keyboard[$key][$keyboard_key]['callback_data'] = "wheel_luck";
            }
            if ($keyboard['text'] == "text_affiliates") {
                $trace_keyboard[$key][$keyboard_key]['callback_data'] = "affiliatesbtn";
            }
            if ($keyboard['text'] == "text_extend") {
                $trace_keyboard[$key][$keyboard_key]['callback_data'] = "extendbtn";
            }
            if ($keyboard['text'] == "text_support") {
                $trace_keyboard[$key][$keyboard_key]['callback_data'] = "supportbtns";
            }
            if ($keyboard['text'] == "text_Purchased_services") {
                $trace_keyboard[$key][$keyboard_key]['callback_data'] = "backorder";
            }
            if ($keyboard['text'] == "text_help") {
                $trace_keyboard[$key][$keyboard_key]['callback_data'] = "helpbtns";
            }
            if ($keyboard['text'] == "text_usertest") {
                $trace_keyboard[$key][$keyboard_key]['callback_data'] = "usertestbtn";
            }
        }
    }
    if ($admin_idss != 0) {
        $temp_addtional_key[] = ['text' => $textbotlang['Admin']['panelAdmin'], 'callback_data' => "admin"];
    }
    if ($users['agent'] != "f") {
        $temp_addtional_key[] = ['text' => $textbotlang['textbot']['agentPanel'], 'callback_data' => "agentpanel"];
    }
    if ($users['agent'] == "f" && $setting['statusagentrequest'] == "onrequestagent") {
        $temp_addtional_key[] = ['text' => $textbotlang['textbot']['requestAgent'], 'callback_data' => "requestagent"];
    }
    $keyboard = ['inline_keyboard' => []];
    $keyboardcustom = $trace_keyboard;
    $keyboardcustom = applyKeyboardLabels($keyboardcustom, $replacements);
    $keyboardcustom[] = $temp_addtional_key;
    $keyboard['inline_keyboard'] = $keyboardcustom;
    $keyboard = json_encode($keyboard);
} else {
    if ($admin_idss != 0) {
        $temp_addtional_key[] = ['text' => $textbotlang['Admin']['panelAdmin']];
    }
    if ($users['agent'] != "f") {
        $temp_addtional_key[] = ['text' => $textbotlang['textbot']['agentPanel']];
    }
    if ($users['agent'] == "f" && $setting['statusagentrequest'] == "onrequestagent") {
        $temp_addtional_key[] = ['text' => $textbotlang['textbot']['requestAgent']];
    }
    $keyboard = ['keyboard' => [], 'resize_keyboard' => true];
    $keyboardcustom = $keyboardRows;
    $keyboardcustom = applyKeyboardLabels($keyboardcustom, $replacements);
    $keyboardcustom[] = $temp_addtional_key;
    $keyboard['keyboard'] = $keyboardcustom;
    $keyboard = json_encode($keyboard);
}

$keyboardPanel = json_encode([
    'inline_keyboard' => [
        [
            ['text' => $textbotlang['textbot']['discount'], 'callback_data' => "Discount"],
            ['text' => $textbotlang['textbot']['addBalance'], 'callback_data' => "Add_Balance"]
        ],
        [
            ['text' => $textbotlang['language']['changeButton'], 'callback_data' => "ّchange_language"],
        ],
        [['text' => $textbotlang['users']['backbtn'], 'callback_data' => "backuser"]],
    ],
    'resize_keyboard' => true
]);
if ($adminrulecheck['rule'] == "administrator") {
    $keyboardadmin = json_encode([
        'keyboard' => [
            [['text' => $textbotlang['Admin']['Status']['btn']]],
            [['text' => $textbotlang['Admin']['btnKeyboard']['managementPanel']], ['text' => $textbotlang['Admin']['btnKeyboard']['addPanel']]],
            [['text' => $textbotlang['keyboard']['quickSetTimePrice']], ['text' => $textbotlang['keyboard']['quickSetVolumePrice']]],
            [['text' => $textbotlang['Admin']['btnKeyboard']['manageUser']], ['text' => $textbotlang['keyboard']['shopSettings']]],
            [['text' => $textbotlang['keyboard']['financial']]],
            [['text' => $textbotlang['keyboard']['supportSection']], ['text' => $textbotlang['keyboard']['educationSection']]],
            [['text' => $textbotlang['keyboard']['botReport']], ['text' => $textbotlang['keyboard']['panelFeatures']]],
            [['text' => $textbotlang['keyboard']['generalSettings']], ['text' => $textbotlang['keyboard']['pendingReceipts']]],
            [['text' => $textbotlang['bottext']['open_button']]],
            [['text' => $textbotlang['users']['backbtn']]]
        ],
        'resize_keyboard' => true
    ]);
}
if ($adminrulecheck['rule'] == "Seller") {
    $keyboardadmin = json_encode([
        'keyboard' => [
            [['text' => $textbotlang['Admin']['Status']['btn']]],
            [['text' => $textbotlang['keyboard']['manageUser']]],
            [['text' => $textbotlang['users']['backbtn']]]
        ],
        'resize_keyboard' => true
    ]);
}
if ($adminrulecheck['rule'] == "support") {
    $keyboardadmin = json_encode([
        'keyboard' => [
            [['text' => $textbotlang['keyboard']['manageUser']]],
            [['text' => $textbotlang['users']['backbtn']]]
        ],
        'resize_keyboard' => true
    ]);
}
$CartManage = json_encode([
    'keyboard' => [
        [['text' => $textbotlang['keyboard']['setCardNumber']], ['text' => $textbotlang['keyboard']['deleteCardNumber']]],
        [['text' => $textbotlang['keyboard']['supportId'],], ['text' => $textbotlang['keyboard']['offlineGatewayPv']]],
        [['text' => $textbotlang['keyboard']['disableShowCard']], ['text' => $textbotlang['keyboard']['enableShowCard']]],
        [['text' => $textbotlang['keyboard']['groupShowCard']]],
        [['text' => $textbotlang['keyboard']['exportActiveCardUsers']]],
        [['text' => $textbotlang['keyboard']['cashbackCartToCart']]],
        [['text' => $textbotlang['keyboard']['showCartAfterFirstPay']]],
        [['text' => $textbotlang['keyboard']['minAmountCartToCart']], ['text' => $textbotlang['keyboard']['maxAmountCartToCart']]],
        [['text' => $textbotlang['keyboard']['setEducationCartToCart']]],
        [['text' => $textbotlang['keyboard']['autoConfirmNoCheck']]],
        [['text' => $textbotlang['keyboard']['excludeUserAutoConfirm']]],
        [['text' => $textbotlang['keyboard']['autoConfirmNoCheckTime']]],
        [['text' => $textbotlang['Admin']['backAdminBtn']], ['text' => $textbotlang['Admin']['backMenuBtn']]]
    ],
    'resize_keyboard' => true
]);
$trnado = json_encode([
    'keyboard' => [
        [['text' => $textbotlang['keyboard']['apiT']]],
        [['text' => $textbotlang['keyboard']['cashbackIranPay2']]],
        [['text' => $textbotlang['keyboard']['feeStatusIranPay2']], ['text' => $textbotlang['keyboard']['feeAmountIranPay2']]],
        [['text' => $textbotlang['keyboard']['minAmountIranPay2']], ['text' => $textbotlang['keyboard']['maxAmountIranPay2']]],
        [['text' => $textbotlang['keyboard']['setEducationIranPay2']]],
        [['text' => $textbotlang['Admin']['backAdminBtn']], ['text' => $textbotlang['Admin']['backMenuBtn']]]
    ],
    'resize_keyboard' => true
]);
$keyboardzarinpal = json_encode([
    'keyboard' => [
        [['text' => $textbotlang['keyboard']['zarinPalMerchant']]],
        [['text' => $textbotlang['keyboard']['cashbackZarinPal']]],
        [['text' => $textbotlang['keyboard']['minAmountZarinPal']], ['text' => $textbotlang['keyboard']['maxAmountZarinPal']]],
        [['text' => $textbotlang['keyboard']['setEducationZarinPal']]],
        [['text' => $textbotlang['Admin']['backAdminBtn']], ['text' => $textbotlang['Admin']['backMenuBtn']]]
    ],
    'resize_keyboard' => true
]);
$aqayepardakht = json_encode([
    'keyboard' => [
        [['text' => $textbotlang['keyboard']['setAqayePardakhtMerchant']], ['text' => $textbotlang['keyboard']['cashbackAqayePardakht']]],
        [['text' => $textbotlang['keyboard']['minAmountAqayePardakht']], ['text' => $textbotlang['keyboard']['maxAmountAqayePardakht']]],
        [['text' => $textbotlang['keyboard']['setEducationAqayePardakht']]],
        [['text' => $textbotlang['Admin']['backAdminBtn']], ['text' => $textbotlang['Admin']['backMenuBtn']]]
    ],
    'resize_keyboard' => true
]);
$NowPaymentsManage = json_encode([
    'keyboard' => [
        [['text' => $textbotlang['keyboard']['apiPlisio']], ['text' => $textbotlang['keyboard']['cashbackPlisio']]],
        [['text' => $textbotlang['keyboard']['minAmountPlisio']], ['text' => $textbotlang['keyboard']['maxAmountPlisio']]],
        [['text' => $textbotlang['keyboard']['setEducationPlisio']]],
        [['text' => $textbotlang['Admin']['backAdminBtn']], ['text' => $textbotlang['Admin']['backMenuBtn']]]
    ],
    'resize_keyboard' => true
]);
$setting_panel = json_encode([
    'keyboard' => [
        [['text' => $textbotlang['keyboard']['featureStatus']]],
        [['text' => $textbotlang['keyboard']['botReports']], ['text' => $textbotlang['keyboard']['channelSettings']]],
        [['text' => $textbotlang['keyboard']['activateWebPanel']]],
        [['text' => $textbotlang['keyboard']['optimizeBot']]],
        [['text' => $textbotlang['keyboard']['adminSection']]],
        [['text' => $textbotlang['keyboard']['setTestAccountLimitAll']]],
        [['text' => $textbotlang['keyboard']['agentMembershipFee']], ['text' => $textbotlang['keyboard']['qrBackground']]],
        [['text' => $textbotlang['keyboard']['reWebhookAgentBots']]],
        [['text' => $textbotlang['Admin']['backAdminBtn']], ['text' => $textbotlang['Admin']['backMenuBtn']]]
    ],
    'resize_keyboard' => true
]);
$PaySettingcard = getPaySettingValue("Cartstatus");
$PaySettingnow = getPaySettingValue("nowpaymentstatus");
$PaySettingaqayepardakht = getPaySettingValue("statusaqayepardakht");
$PaySettingpv = getPaySettingValue("Cartstatuspv");
$usernamecart = getPaySettingValue("CartDirect");
$Swapino = getPaySettingValue("statusSwapWallet");
$trnadoo = getPaySettingValue("statustarnado");
$paymentverify = getPaySettingValue("checkpaycartfirst");
$stmt = $pdo->prepare("SELECT COUNT(*) FROM Payment_report WHERE id_user = :user_id AND payment_Status = 'paid'");
$stmt->bindValue(':user_id', $from_id);
$stmt->execute();
$paymentexits = (int) $stmt->fetchColumn();
$zarinpal = getPaySettingValue("zarinpalstatus");
$affilnecurrency = getPaySettingValue("digistatus");
$arzireyali3 = getPaySettingValue("statusiranpay3");
$abangateway4 = getPaySettingValue("statusiranpay4", "offiranpay4");
$paymentstatussnotverify = getPaySettingValue("paymentstatussnotverify");
$paymentsstartelegram = getPaySettingValue("statusstar");
$payment_status_nowpayment = getPaySettingValue("statusnowpayment");
$step_payment = [
    'inline_keyboard' => []
];
if ($PaySettingcard == "oncard" && intval($users['cardpayment']) == 1) {
    if ($PaySettingpv == "oncardpv") {
        $step_payment['inline_keyboard'][] = [
            ['text' => $textbotlang['textbot']['cartToCart'], 'url' => "https://t.me/$usernamecart"],
        ];
    } else {
        $step_payment['inline_keyboard'][] = [
            ['text' => $textbotlang['textbot']['cartToCart'], 'callback_data' => "cart_to_offline"],
        ];
    }
}
if (($paymentexits == 0 && $paymentverify == "onpayverify"))
    unset($step_payment['inline_keyboard']);
if ($PaySettingnow == "onnowpayment") {
    $step_payment['inline_keyboard'][] = [
        ['text' => $textbotlang['textbot']['nowPayment'], 'callback_data' => "plisio"]
    ];
}
if ($payment_status_nowpayment == "1") {
    $step_payment['inline_keyboard'][] = [
        ['text' => $textbotlang['textbot']['cryptoPayment'], 'callback_data' => "nowpayment"]
    ];
}
if ($affilnecurrency == "ondigi") {
    $step_payment['inline_keyboard'][] = [
        ['text' => $textbotlang['textbot']['nowPaymentTron'], 'callback_data' => "digitaltron"]
    ];
}
if ($Swapino == "onSwapinoBot") {
    $step_payment['inline_keyboard'][] = [
        ['text' => $textbotlang['textbot']['iranPay2'], 'callback_data' => "iranpay1"]
    ];
}
if ($trnadoo == "onternado") {
    $step_payment['inline_keyboard'][] = [
        ['text' => $textbotlang['textbot']['iranPay3'], 'callback_data' => "iranpay2"]
    ];
}
// Both halves matter: the admin has switched it on, *and* the key and endpoint
// exist. A gateway shown without them takes the buyer to a page that cannot be
// created — the button is the last place to find that out.
if (
    $abangateway4 == "oniranpay4"
    && trim((string) getPaySettingValue("apiiranpay4", "")) !== ""
    && trim((string) getPaySettingValue("apiiranpay4", "")) !== "0"
    && function_exists('abangatewayEndpoint') && abangatewayEndpoint() !== null
) {
    $step_payment['inline_keyboard'][] = [
        ['text' => $textbotlang['textbot']['iranPay4'], 'callback_data' => "iranpay4"]
    ];
}
if ($arzireyali3 == "oniranpay3" && $paymentexits >= 2) {
    $step_payment['inline_keyboard'][] = [
        ['text' => $textbotlang['textbot']['iranPay1'], 'callback_data' => "iranpay3"]
    ];
}
if ($PaySettingaqayepardakht == "onaqayepardakht") {
    $step_payment['inline_keyboard'][] = [
        ['text' => $textbotlang['textbot']['aqayePardakht'], 'callback_data' => "aqayepardakht"]
    ];
}
if ($zarinpal == "onzarinpal") {
    $step_payment['inline_keyboard'][] = [
        ['text' => $textbotlang['textbot']['zarinPal'], 'callback_data' => "zarinpal"]
    ];
}
if ($paymentstatussnotverify == "onverifypay") {
    $step_payment['inline_keyboard'][] = [
        ['text' => $textbotlang['textbot']['paymentNotVerify'], 'callback_data' => "paymentnotverify"]
    ];
}
if (intval($paymentsstartelegram) == 1) {
    $step_payment['inline_keyboard'][] = [
        ['text' => $textbotlang['textbot']['starTelegram'], 'callback_data' => "startelegrams"]
    ];
}
$step_payment['inline_keyboard'][] = [
    ['text' => $textbotlang['keyboard']['closeList'], 'callback_data' => "colselist"]
];
$step_payment = json_encode($step_payment);
$keyboardhelpadmin = json_encode([
    'keyboard' => [
        [['text' => $textbotlang['keyboard']['addEducation']], ['text' => $textbotlang['keyboard']['deleteEducation']]],
        [['text' => $textbotlang['keyboard']['editEducation']]],
        [['text' => $textbotlang['Admin']['backAdminBtn']], ['text' => $textbotlang['Admin']['backMenuBtn']]]
    ],
    'resize_keyboard' => true
]);
$shopkeyboard = json_encode([
    'keyboard' => [
        [['text' => $textbotlang['keyboard']['shopFeatureStatus']]],
        [['text' => $textbotlang['keyboard']['manageCategory']], ['text' => $textbotlang['keyboard']['manageProducts']]],
        [['text' => $textbotlang['keyboard']['createGiftCode']], ['text' => $textbotlang['keyboard']['deleteGiftCode']]],
        [['text' => $textbotlang['keyboard']['createDiscountCode']], ['text' => $textbotlang['keyboard']['deleteDiscountCode']]],
        [['text' => $textbotlang['keyboard']['minBulkBalance']], ['text' => $textbotlang['keyboard']['renewalCashback']]],
        [['text' => $textbotlang['Admin']['backAdminBtn']], ['text' => $textbotlang['Admin']['backMenuBtn']]]
    ],
    'resize_keyboard' => true
]);
$keyboard_Category_manage = json_encode([
    'keyboard' => [
        [['text' => $textbotlang['keyboard']['addCategory']], ['text' => $textbotlang['keyboard']['deleteCategory']]],
        [['text' => $textbotlang['keyboard']['editCategoryMenu']]],
        [['text' => $textbotlang['keyboard']['backToShopMenu']]]
    ],
    'resize_keyboard' => true
]);
$keyboard_shop_manage = json_encode([
    'keyboard' => [
        [['text' => $textbotlang['keyboard']['addProduct']], ['text' => $textbotlang['keyboard']['deleteProduct']]],
        [['text' => $textbotlang['keyboard']['editProduct']]],
        [['text' => $textbotlang['keyboard']['increaseGroupPrice']], ['text' => $textbotlang['keyboard']['decreaseGroupPrice']]],
        [['text' => $textbotlang['keyboard']['backToShopMenu']]]
    ],
    'resize_keyboard' => true
]);
if ($setting['inlinebtnmain'] == "oninline") {
    $confrimrolls = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['keyboard']['acceptRules'], 'callback_data' => "acceptrule"],
            ],
        ]
    ]);
} else {
    $confrimrolls = json_encode([
        'keyboard' => [
            [['text' => $textbotlang['keyboard']['acceptRules']]],
        ],
        'resize_keyboard' => true
    ]);
}
$request_contact = json_encode([
    'keyboard' => [
        [['text' => $textbotlang['keyboard']['sendPhoneNumber'], 'request_contact' => true]],
        [['text' => $textbotlang['users']['backbtn']]]
    ],
    'resize_keyboard' => true
]);
$channelkeyboard = json_encode([
    'keyboard' => [
        [['text' => $textbotlang['keyboard']['addChannel']], ['text' => $textbotlang['keyboard']['deleteChannel']]],
        [['text' => $textbotlang['Admin']['backAdminBtn']], ['text' => $textbotlang['Admin']['backMenuBtn']]]
    ],
    'resize_keyboard' => true
]);
if ($setting['inlinebtnmain'] == "oninline") {
    $backuser = json_encode([
        'inline_keyboard' => [
            [['text' => $textbotlang['users']['backbtn'], 'callback_data' => "backuser"]]
        ],
    ]);
} else {
    $backuser = json_encode([
        'keyboard' => [
            [['text' => $textbotlang['users']['backbtn']]]
        ],
        'resize_keyboard' => true,
    ]);
}
$backadmin = json_encode([
    'keyboard' => [
        [['text' => $textbotlang['Admin']['backAdminBtn']], ['text' => $textbotlang['Admin']['backMenuBtn']]]
    ],
    'resize_keyboard' => true,
]);
//------------------  [ list panel ]----------------//
$namepanel = [];
    $allPanelRows = $pdo->query("SELECT * FROM marzban_panel")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($allPanelRows as $row) {
        $namepanel[] = [$row['name_panel']];
    }
    $list_marzban_panel = [
        'keyboard' => [],
        'resize_keyboard' => true,
    ];
    foreach ($namepanel as $button) {
        $list_marzban_panel['keyboard'][] = [
            ['text' => $button[0]]
        ];
    }
    $list_marzban_panel['keyboard'][] = [
        ['text' => $textbotlang['Admin']['backAdminBtn']],
        ['text' => $textbotlang['Admin']['backMenuBtn']]
    ];
    $json_list_marzban_panel = json_encode($list_marzban_panel);
    //------------------  [ list panel inline ]----------------//
    $list_marzban_panel_edit_product = ['inline_keyboard' => []];
    foreach ($allPanelRows as $row) {
        $list_marzban_panel_edit_product['inline_keyboard'][] = [['text' => $row['name_panel'], 'callback_data' => 'locationedit_' . $row['code_panel']]];
    }
    $list_marzban_panel_edit_product['inline_keyboard'][] = [['text' => $textbotlang['keyboard']['allPanels'], 'callback_data' => 'locationedit_all']];
    $list_marzban_panel_edit_product['inline_keyboard'][] = [['text' => $textbotlang['keyboard']['backToPreviousMenu'], 'callback_data' => 'backproductadmin']];
    $list_marzban_panel_edit_product = json_encode($list_marzban_panel_edit_product);
//------------------  [ list channel ]----------------//
$list_channels = [];
    $stmt = $pdo->prepare("SELECT * FROM channels");
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $list_channels[] = [$row['link']];
    }
    $list_channels_join = [
        'keyboard' => [],
        'resize_keyboard' => true,
    ];
    foreach ($list_channels as $button) {
        $list_channels_join['keyboard'][] = [
            ['text' => $button[0]]
        ];
    }
    $list_channels_join['keyboard'][] = [
        ['text' => $textbotlang['Admin']['backAdminBtn']],
        ['text' => $textbotlang['Admin']['backMenuBtn']]
    ];
    $list_channels_joins = json_encode($list_channels_join);
//------------------  [ list card ]----------------//
$list_card = [];
    $stmt = $pdo->prepare("SELECT * FROM card_number");
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $list_card[] = [$row['cardnumber']];
    }
    $list_card_remove = [
        'keyboard' => [],
        'resize_keyboard' => true,
    ];
    foreach ($list_card as $button) {
        $list_card_remove['keyboard'][] = [
            ['text' => $button[0]]
        ];
    }
    $list_card_remove['keyboard'][] = [
        ['text' => $textbotlang['Admin']['backAdminBtn']],
        ['text' => $textbotlang['Admin']['backMenuBtn']]
    ];
    $list_card_remove = json_encode($list_card_remove);
//------------------  [ help list ]----------------//
    $helpRows = $pdo->query("SELECT * FROM help")->fetchAll(PDO::FETCH_ASSOC);
    $helpkey = [];
    foreach ($helpRows as $row) {
        $helpkey[] = [$row['name_os']];
    }
    $help_arrke = [
        'keyboard' => [],
        'resize_keyboard' => true,
    ];
    foreach ($helpkey as $button) {
        $help_arrke['keyboard'][] = [
            ['text' => $button[0]]
        ];
    }
    $help_arrke['keyboard'][] = [
        ['text' => $textbotlang['users']['backbtn']],
    ];
    $json_list_helpkey = json_encode($help_arrke);
//------------------  [ help list ]----------------//
$helpcwtgory = ['inline_keyboard' => []];
$datahelp = [];
$helpCategoryMap = [];
foreach ($helpRows as $result) {
    if (in_array($result['category'], $datahelp))
        continue;
    if ($result['category'] == null)
        continue;
    $datahelp[] = $result['category'];
    $catId = dechex(crc32($result['category']));
    $helpCategoryMap[$catId] = $result['category'];
    $helpcwtgory['inline_keyboard'][] = [
        ['text' => $result['category'], 'callback_data' => "helpctg_{$catId}"]
    ];
}
if ($setting['linkappstatus'] == "1") {
    $helpcwtgory['inline_keyboard'][] = [
        ['text' => $textbotlang['keyboard']['appDownloadLink'], 'callback_data' => "linkappdownlod"],
    ];
}
$helpcwtgory['inline_keyboard'][] = [
    ['text' => $textbotlang['users']['backbtn'], 'callback_data' => "backuser"],
];
$json_list_helpـcategory = json_encode($helpcwtgory);


//------------------  [ help app ]----------------//
$appRows = $pdo->query("SELECT * FROM app")->fetchAll(PDO::FETCH_ASSOC);
$helpapp = ['inline_keyboard' => []];
foreach ($appRows as $result) {
    $helpapp['inline_keyboard'][] = [
        ['text' => $result['name'], 'url' => $result['link']]
    ];
}
$helpapp['inline_keyboard'][] = [
    ['text' => $textbotlang['users']['backbtn'], 'callback_data' => "backuser"],
];
$json_list_helpـlink = json_encode($helpapp);
//------------------  [ help app admin ]----------------//
$helpappremove = ['keyboard' => [], 'resize_keyboard' => true];
foreach ($appRows as $result) {
    $helpappremove['keyboard'][] = [
        ['text' => $result['name']],
    ];
}
$helpappremove['keyboard'][] = [
    ['text' => $textbotlang['Admin']['backAdminBtn']],
];
$json_list_remove_helpـlink = json_encode($helpappremove);
//------------------  [ listpanelusers ]----------------//
$stmt = $pdo->prepare("SELECT * FROM marzban_panel WHERE status = 'active' AND (agent = :agent OR agent = 'all')");
$stmt->bindParam(':agent', $users['agent']);
$stmt->execute();
$activePanelRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$manualsellCounts = [];
if (in_array('Manualsale', array_column($activePanelRows, 'type'))) {
    foreach ($pdo->query("SELECT codepanel, COUNT(*) AS c FROM manualsell WHERE status = 'active' GROUP BY codepanel")->fetchAll(PDO::FETCH_ASSOC) as $msRow) {
        $manualsellCounts[$msRow['codepanel']] = (int) $msRow['c'];
    }
}
$list_marzban_panel_users = ['inline_keyboard' => []];
$panelcount = select("marzban_panel", "*", "status", "active", "count");
if ($panelcount > 10) {
    $temp_row = [];
    foreach ($activePanelRows as $result) {
        if ($result['hide_user'] != null && in_array($from_id, json_decode($result['hide_user'], true)))
            continue;
        if ($result['type'] == "Manualsale" && empty($manualsellCounts[$result['code_panel']]))
            continue;
        if ($users['step'] == "getusernameinfo") {
            $temp_row[] = ['text' => $result['name_panel'], 'callback_data' => "locationnotuser_{$result['code_panel']}"];
        } else {
            $temp_row[] = ['text' => $result['name_panel'], 'callback_data' => "location_{$result['code_panel']}"];
        }
        if (count($temp_row) == 2) {
            $list_marzban_panel_users['inline_keyboard'][] = $temp_row;
            $temp_row = [];
        }
    }
    if (!empty($temp_row)) {
        $list_marzban_panel_users['inline_keyboard'][] = $temp_row;
    }
} else {
    foreach ($activePanelRows as $result) {
        if ($result['type'] == "Manualsale" && empty($manualsellCounts[$result['code_panel']]))
            continue;
        if ($result['hide_user'] != null and in_array($from_id, json_decode($result['hide_user'], true)))
            continue;
        if ($users['step'] == "getusernameinfo") {
            $list_marzban_panel_users['inline_keyboard'][] = [
                ['text' => $result['name_panel'], 'callback_data' => "locationnotuser_{$result['code_panel']}"]
            ];
        } else {
            $list_marzban_panel_users['inline_keyboard'][] = [
                ['text' => $result['name_panel'], 'callback_data' => "location_{$result['code_panel']}"]
            ];
        }
    }
}
$statusnote = false;
if ($setting['statusnamecustom'] == 'onnamecustom')
    $statusnote = true;
if ($setting['statusnoteforf'] == "0" && $users['agent'] == "f")
    $statusnote = false;
if ($statusnote) {
    $list_marzban_panel_users['inline_keyboard'][] = [
        ['text' => $textbotlang['users']['backbtn'], 'callback_data' => "buyback"],
    ];
} else {
    $list_marzban_panel_users['inline_keyboard'][] = [
        ['text' => $textbotlang['users']['backbtn'], 'callback_data' => "backuser"],
    ];
}
$list_marzban_panel_user = json_encode($list_marzban_panel_users);


//------------------  [ listpanelusers omdhe ]----------------//
$list_marzban_panel_users_om = ['inline_keyboard' => []];
foreach ($activePanelRows as $result) {
    if ($result['hide_user'] != null and in_array($from_id, json_decode($result['hide_user'], true)))
        continue;
    $list_marzban_panel_users_om['inline_keyboard'][] = [
        ['text' => $result['name_panel'], 'callback_data' => "locationom_{$result['code_panel']}"]
    ];
}
$list_marzban_panel_users_om['inline_keyboard'][] = [
    ['text' => $textbotlang['users']['backbtn'], 'callback_data' => "backuser"],
];
$list_marzban_panel_userom = json_encode($list_marzban_panel_users_om);

//------------------  [ change location ]----------------//
$list_marzban_panel_users_change = ['inline_keyboard' => []];
if ($panelcount > 10) {
    $temp_row = [];
    foreach ($activePanelRows as $result) {
        if ($result['name_panel'] == $users['Processing_value_four'])
            continue;
        if ($result['hide_user'] != null && in_array($from_id, json_decode($result['hide_user'], true)))
            continue;

        $temp_row[] = ['text' => $result['name_panel'], 'callback_data' => "changelocselectlo-{$result['code_panel']}"];
        if (count($temp_row) == 2) {
            $list_marzban_panel_users_change['inline_keyboard'][] = $temp_row;
            $temp_row = [];
        }
    }
    if (!empty($temp_row)) {
        $list_marzban_panel_users_change['inline_keyboard'][] = $temp_row;
    }
} else {
    foreach ($activePanelRows as $result) {
        if ($result['name_panel'] == $users['Processing_value_four'])
            continue;
        if ($result['hide_user'] != null and in_array($from_id, json_decode($result['hide_user'], true)))
            continue;
        $list_marzban_panel_users_change['inline_keyboard'][] = [
            ['text' => $result['name_panel'], 'callback_data' => "changelocselectlo-{$result['code_panel']}"]
        ];
    }
}
$list_marzban_panel_users_change['inline_keyboard'][] = [
    ['text' => $textbotlang['users']['backbtn'], 'callback_data' => "backorder"],
];
$list_marzban_panel_userschange = json_encode($list_marzban_panel_users_change);


//------------------  [ listpanelusers test ]----------------//
$stmt = $pdo->prepare("SELECT * FROM marzban_panel WHERE TestAccount = 'ONTestAccount' AND (agent = :agent OR agent = 'all')");
$stmt->bindValue(':agent', $users['agent'], PDO::PARAM_STR);
$stmt->execute();
$list_marzban_panel_usertest = ['inline_keyboard' => []];
while ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if ($result['hide_user'] != null and in_array($from_id, json_decode($result['hide_user'], true)))
        continue;
    $list_marzban_panel_usertest['inline_keyboard'][] = [
        ['text' => $result['name_panel'], 'callback_data' => "locationtest_{$result['code_panel']}"]
    ];
}
$list_marzban_panel_usertest['inline_keyboard'][] = [
    ['text' => $textbotlang['users']['backbtn'], 'callback_data' => "backuser"],
];
$list_marzban_usertest = json_encode($list_marzban_panel_usertest);

//--------------------------------------------------
    $product = [];
    $stmt = $pdo->prepare("SELECT * FROM product WHERE Location = :text or Location = '/all' ");
    $stmt->bindParam(':text', $text, PDO::PARAM_STR);
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $product[] = [$row['name_product']];
    }
    $list_product = [
        'keyboard' => [],
        'resize_keyboard' => true,
    ];
    $list_product['keyboard'][] = [
        ['text' => $textbotlang['Admin']['backAdminBtn']],
    ];
    foreach ($product as $button) {
        $list_product['keyboard'][] = [
            ['text' => $button[0]]
        ];
    }
    $json_list_product_list_admin = json_encode($list_product);
//--------------------------------------------------
    $Discount = [];
    $stmt = $pdo->prepare("SELECT * FROM Discount");
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $Discount[] = [$row['code']];
    }
    $list_Discount = [
        'keyboard' => [],
        'resize_keyboard' => true,
    ];
    $list_Discount['keyboard'][] = [
        ['text' => $textbotlang['Admin']['backAdminBtn']],
    ];
    foreach ($Discount as $button) {
        $list_Discount['keyboard'][] = [
            ['text' => $button[0]]
        ];
    }
    $json_list_Discount_list_admin = json_encode($list_Discount);
//--------------------------------------------------
    $DiscountSell = [];
    $stmt = $pdo->prepare("SELECT * FROM DiscountSell");
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $DiscountSell[] = [$row['codeDiscount']];
    }
    $list_Discountsell = [
        'keyboard' => [],
        'resize_keyboard' => true,
    ];
    $list_Discountsell['keyboard'][] = [
        ['text' => $textbotlang['Admin']['backAdminBtn']],
    ];
    foreach ($DiscountSell as $button) {
        $list_Discountsell['keyboard'][] = [
            ['text' => $button[0]]
        ];
    }
    $json_list_Discount_list_admin_sell = json_encode($list_Discountsell);
$payment = json_encode([
    'inline_keyboard' => [
        [['text' => $textbotlang['keyboard']['payAndGetService'], 'callback_data' => "confirmandgetservice"]],
        [['text' => $textbotlang['keyboard']['registerDiscountCode'], 'callback_data' => "aptdc"]],
        [['text' => $textbotlang['users']['backbtn'], 'callback_data' => "backuser"]]
    ]
]);
$paymentom = json_encode([
    'inline_keyboard' => [
        [['text' => $textbotlang['keyboard']['payAndGetService'], 'callback_data' => "confirmandgetservice"]],
        [['text' => $textbotlang['users']['backbtn'], 'callback_data' => "backuser"]]
    ]
]);
$change_product = json_encode([
    'keyboard' => [
        [['text' => $textbotlang['keyboard']['price']], ['text' => $textbotlang['keyboard']['volume']], ['text' => $textbotlang['keyboard']['time']]],
        [['text' => $textbotlang['keyboard']['productName']], ['text' => $textbotlang['keyboard']['userType']]],
        [['text' => $textbotlang['keyboard']['volumeResetType']], ['text' => $textbotlang['keyboard']['note']]],
        [['text' => $textbotlang['keyboard']['productLocation']], ['text' => $textbotlang['keyboard']['category']]],
        [['text' => $textbotlang['keyboard']['setInbound']], ['text' => $textbotlang['keyboard']['showFirstPurchase']]],
        [['text' => $textbotlang['keyboard']['hidePanel']], ['text' => $textbotlang['keyboard']['deleteAllHiddenPanels']]],
        [['text' => $textbotlang['Admin']['backAdminBtn']], ['text' => $textbotlang['Admin']['backMenuBtn']]]
    ],
    'resize_keyboard' => true
]);

$keyboardprotocol = json_encode([
    'keyboard' => [
        [['text' => "vless"], ['text' => "vmess"], ['text' => "trojan"]],
        [['text' => "shadowsocks"]],
        [['text' => $textbotlang['Admin']['backAdminBtn']], ['text' => $textbotlang['Admin']['backMenuBtn']]]
    ],
    'resize_keyboard' => true
]);
$MethodUsername = json_encode([
    'keyboard' => [
        [['text' => $textbotlang['keyboard']['usernameSequential']]],
        [['text' => $textbotlang['keyboard']['numericIdRandom']]],
        [['text' => $textbotlang['keyboard']['customUsername']]],
        [['text' => $textbotlang['keyboard']['customUsernameRandom']]],
        [['text' => $textbotlang['keyboard']['customTextRandom']]],
        [['text' => $textbotlang['keyboard']['customTextSequential']]],
        [['text' => $textbotlang['keyboard']['numericIdSequential']]],
        [['text' => $textbotlang['keyboard']['agentCustomTextSequential']]],
        [['text' => $textbotlang['Admin']['backAdminBtn']], ['text' => $textbotlang['Admin']['backMenuBtn']]]
    ],
    'resize_keyboard' => true
]);
$optionMarzban = json_encode([
    'keyboard' => [
        [['text' => $textbotlang['keyboard']['panelFeatureStatus']]],
        [['text' => $textbotlang['keyboard']['panelName']], ['text' => $textbotlang['keyboard']['deletePanel']]],
        [['text' => $textbotlang['keyboard']['editPassword']], ['text' => $textbotlang['keyboard']['editUsername']]],
        [['text' => $textbotlang['keyboard']['editPanelUrl']], ['text' => $textbotlang['keyboard']['setProtocolInbound']]],
        [['text' => $textbotlang['keyboard']['renewalMethod']], ['text' => $textbotlang['keyboard']['usernameMethod']]],
        [['text' => $textbotlang['keyboard']['accountCreateLimit']], ['text' => $textbotlang['keyboard']['changeUserGroup']]],
        [['text' => $textbotlang['keyboard']['testServiceTime']], ['text' => $textbotlang['keyboard']['testAccountVolume']]],
        [['text' => $textbotlang['keyboard']['customVolumePrice']], ['text' => $textbotlang['keyboard']['extraVolumePrice']]],
        [['text' => $textbotlang['keyboard']['extraTimePrice']], ['text' => $textbotlang['keyboard']['customTimePrice']]],
        [['text' => $textbotlang['keyboard']['changeLocationPrice']]],
        [['text' => $textbotlang['keyboard']['minCustomVolume']], ['text' => $textbotlang['keyboard']['maxCustomVolume']]],
        [['text' => $textbotlang['keyboard']['minCustomTime']], ['text' => $textbotlang['keyboard']['maxCustomTime']]],
        [['text' => $textbotlang['keyboard']['inboundDeactivate']]],
        [['text' => $textbotlang['keyboard']['hidePanelForUser']]],
        [['text' => $textbotlang['keyboard']['removeFromHiddenList']]],
        [['text' => $textbotlang['Admin']['backAdminBtn']], ['text' => $textbotlang['Admin']['backMenuBtn']]]
    ],
    'resize_keyboard' => true
]);
$optionrebecca = json_encode([
    'keyboard' => [
        [['text' => $textbotlang['keyboard']['panelFeatureStatus']]],
        [['text' => $textbotlang['keyboard']['panelName']], ['text' => $textbotlang['keyboard']['deletePanel']]],
        [['text' => $textbotlang['keyboard']['editPassword']]],
        [['text' => $textbotlang['keyboard']['editPanelUrl']], ['text' => $textbotlang['keyboard']['setProtocolInbound']]],
        [['text' => $textbotlang['keyboard']['renewalMethod']], ['text' => $textbotlang['keyboard']['usernameMethod']]],
        [['text' => $textbotlang['keyboard']['accountCreateLimit']], ['text' => $textbotlang['keyboard']['changeUserGroup']]],
        [['text' => $textbotlang['keyboard']['testServiceTime']], ['text' => $textbotlang['keyboard']['testAccountVolume']]],
        [['text' => $textbotlang['keyboard']['customVolumePrice']], ['text' => $textbotlang['keyboard']['extraVolumePrice']]],
        [['text' => $textbotlang['keyboard']['extraTimePrice']], ['text' => $textbotlang['keyboard']['customTimePrice']]],
        [['text' => $textbotlang['keyboard']['changeLocationPrice']]],
        [['text' => $textbotlang['keyboard']['minCustomVolume']], ['text' => $textbotlang['keyboard']['maxCustomVolume']]],
        [['text' => $textbotlang['keyboard']['minCustomTime']], ['text' => $textbotlang['keyboard']['maxCustomTime']]],
        [['text' => $textbotlang['keyboard']['inboundDeactivate']]],
        [['text' => $textbotlang['keyboard']['hidePanelForUser']]],
        [['text' => $textbotlang['keyboard']['removeFromHiddenList']]],
        [['text' => $textbotlang['Admin']['backAdminBtn']], ['text' => $textbotlang['Admin']['backMenuBtn']]]
    ],
    'resize_keyboard' => true
]);
$optionibsng = json_encode([
    'keyboard' => [
        [['text' => $textbotlang['keyboard']['panelFeatureStatus']]],
        [['text' => $textbotlang['keyboard']['panelName']], ['text' => $textbotlang['keyboard']['deletePanel']]],
        [['text' => $textbotlang['keyboard']['editPassword']], ['text' => $textbotlang['keyboard']['editUsername']]],
        [['text' => $textbotlang['keyboard']['editPanelUrl']], ['text' => $textbotlang['keyboard']['setGroupName']]],
        [['text' => $textbotlang['keyboard']['renewalMethod']], ['text' => $textbotlang['keyboard']['usernameMethod']]],
        [['text' => $textbotlang['keyboard']['accountCreateLimit']], ['text' => $textbotlang['keyboard']['changeUserGroup']]],
        [['text' => $textbotlang['keyboard']['customVolumePrice']], ['text' => $textbotlang['keyboard']['extraVolumePrice']]],
        [['text' => $textbotlang['keyboard']['extraTimePrice']], ['text' => $textbotlang['keyboard']['customTimePrice']]],
        [['text' => $textbotlang['keyboard']['minCustomVolume']], ['text' => $textbotlang['keyboard']['maxCustomVolume']]],
        [['text' => $textbotlang['keyboard']['minCustomTime']], ['text' => $textbotlang['keyboard']['maxCustomTime']]],
        [['text' => $textbotlang['keyboard']['hidePanelForUser']]],
        [['text' => $textbotlang['keyboard']['removeFromHiddenList']]],
        [['text' => $textbotlang['Admin']['backAdminBtn']], ['text' => $textbotlang['Admin']['backMenuBtn']]]
    ],
    'resize_keyboard' => true
]);
$option_mikrotik = json_encode([
    'keyboard' => [
        [['text' => $textbotlang['keyboard']['panelFeatureStatus']]],
        [['text' => $textbotlang['keyboard']['panelName']], ['text' => $textbotlang['keyboard']['deletePanel']]],
        [['text' => $textbotlang['keyboard']['editPassword']], ['text' => $textbotlang['keyboard']['editUsername']]],
        [['text' => $textbotlang['keyboard']['editPanelUrl']], ['text' => $textbotlang['keyboard']['setGroupName']]],
        [['text' => $textbotlang['keyboard']['renewalMethod']], ['text' => $textbotlang['keyboard']['usernameMethod']]],
        [['text' => $textbotlang['keyboard']['accountCreateLimit']], ['text' => $textbotlang['keyboard']['changeUserGroup']]],
        [['text' => $textbotlang['keyboard']['customVolumePrice']], ['text' => $textbotlang['keyboard']['extraVolumePrice']]],
        [['text' => $textbotlang['keyboard']['extraTimePrice']], ['text' => $textbotlang['keyboard']['customTimePrice']]],
        [['text' => $textbotlang['keyboard']['minCustomVolume']], ['text' => $textbotlang['keyboard']['maxCustomVolume']]],
        [['text' => $textbotlang['keyboard']['minCustomTime']], ['text' => $textbotlang['keyboard']['maxCustomTime']]],
        [['text' => $textbotlang['keyboard']['hidePanelForUser']]],
        [['text' => $textbotlang['keyboard']['removeFromHiddenList']]],
        [['text' => $textbotlang['Admin']['backAdminBtn']], ['text' => $textbotlang['Admin']['backMenuBtn']]]
    ],
    'resize_keyboard' => true
]);
$options_ui = json_encode([
    'keyboard' => [
        [['text' => $textbotlang['keyboard']['panelFeatureStatus']]],
        [['text' => $textbotlang['keyboard']['panelName']], ['text' => $textbotlang['keyboard']['deletePanel']]],
        [['text' => $textbotlang['keyboard']['editPassword']], ['text' => $textbotlang['keyboard']['editUsername']]],
        [['text' => $textbotlang['keyboard']['editPanelUrl']], ['text' => $textbotlang['keyboard']['setProtocolInbound']]],
        [['text' => $textbotlang['keyboard']['renewalMethod']], ['text' => $textbotlang['keyboard']['usernameMethod']]],
        [['text' => $textbotlang['keyboard']['accountCreateLimit']], ['text' => $textbotlang['keyboard']['changeUserGroup']]],
        [['text' => $textbotlang['keyboard']['testServiceTime']], ['text' => $textbotlang['keyboard']['testAccountVolume']]],
        [['text' => $textbotlang['keyboard']['customVolumePrice']], ['text' => $textbotlang['keyboard']['extraVolumePrice']]],
        [['text' => $textbotlang['keyboard']['extraTimePrice']], ['text' => $textbotlang['keyboard']['customTimePrice']]],
        [['text' => $textbotlang['keyboard']['changeLocationPrice']]],
        [['text' => $textbotlang['keyboard']['minCustomVolume']], ['text' => $textbotlang['keyboard']['maxCustomVolume']]],
        [['text' => $textbotlang['keyboard']['minCustomTime']], ['text' => $textbotlang['keyboard']['maxCustomTime']]],
        [['text' => $textbotlang['keyboard']['inboundDeactivate']]],
        [['text' => $textbotlang['keyboard']['hidePanelForUser']]],
        [['text' => $textbotlang['keyboard']['removeFromHiddenList']]],
        [['text' => $textbotlang['Admin']['backAdminBtn']], ['text' => $textbotlang['Admin']['backMenuBtn']]]
    ],
    'resize_keyboard' => true
]);
$optionwg = json_encode([
    'keyboard' => [
        [['text' => $textbotlang['keyboard']['panelFeatureStatus']]],
        [['text' => $textbotlang['keyboard']['panelName']], ['text' => $textbotlang['keyboard']['deletePanel']]],
        [['text' => $textbotlang['keyboard']['editPassword']]],
        [['text' => $textbotlang['keyboard']['editPanelUrl']], ['text' => $textbotlang['keyboard']['setInboundId']]],
        [['text' => $textbotlang['keyboard']['renewalMethod']], ['text' => $textbotlang['keyboard']['usernameMethod']]],
        [['text' => $textbotlang['keyboard']['accountCreateLimit']], ['text' => $textbotlang['keyboard']['changeUserGroup']]],
        [['text' => $textbotlang['keyboard']['testServiceTime']], ['text' => $textbotlang['keyboard']['testAccountVolume']]],
        [['text' => $textbotlang['keyboard']['customVolumePrice']], ['text' => $textbotlang['keyboard']['extraVolumePrice']]],
        [['text' => $textbotlang['keyboard']['extraTimePrice']], ['text' => $textbotlang['keyboard']['customTimePrice']]],
        [['text' => $textbotlang['keyboard']['changeLocationPrice']]],
        [['text' => $textbotlang['keyboard']['minCustomVolume']], ['text' => $textbotlang['keyboard']['maxCustomVolume']]],
        [['text' => $textbotlang['keyboard']['minCustomTime']], ['text' => $textbotlang['keyboard']['maxCustomTime']]],
        [['text' => $textbotlang['keyboard']['inboundDeactivate']]],
        [['text' => $textbotlang['keyboard']['hidePanelForUser']]],
        [['text' => $textbotlang['keyboard']['removeFromHiddenList']]],
        [['text' => $textbotlang['Admin']['backAdminBtn']], ['text' => $textbotlang['Admin']['backMenuBtn']]]
    ],
    'resize_keyboard' => true
]);
$optionmarzneshin = json_encode([
    'keyboard' => [
        [['text' => $textbotlang['keyboard']['panelFeatureStatus']]],
        [['text' => $textbotlang['keyboard']['panelName']], ['text' => $textbotlang['keyboard']['deletePanel']]],
        [['text' => $textbotlang['keyboard']['editPassword']], ['text' => $textbotlang['keyboard']['editUsername']]],
        [['text' => $textbotlang['keyboard']['editPanelUrl']], ['text' => $textbotlang['keyboard']['renewalMethod']]],
        [['text' => $textbotlang['keyboard']['usernameMethod']]],
        [['text' => $textbotlang['keyboard']['serviceSettings']], ['text' => $textbotlang['keyboard']['accountCreateLimit']]],
        [['text' => $textbotlang['keyboard']['changeUserGroup']]],
        [['text' => $textbotlang['keyboard']['testServiceTime']], ['text' => $textbotlang['keyboard']['testAccountVolume']]],
        [['text' => $textbotlang['keyboard']['changeLocationPrice']], ['text' => $textbotlang['keyboard']['extraVolumePrice']]],
        [['text' => $textbotlang['keyboard']['extraTimePrice']], ['text' => $textbotlang['keyboard']['customVolumePrice']]],
        [['text' => $textbotlang['keyboard']['customTimePrice']]],
        [['text' => $textbotlang['keyboard']['minCustomVolume']], ['text' => $textbotlang['keyboard']['maxCustomVolume']]],
        [['text' => $textbotlang['keyboard']['minCustomTime']], ['text' => $textbotlang['keyboard']['maxCustomTime']]],
        [['text' => $textbotlang['keyboard']['hidePanelForUser']]],
        [['text' => $textbotlang['keyboard']['removeFromHiddenList']]],
        [['text' => $textbotlang['Admin']['backAdminBtn']], ['text' => $textbotlang['Admin']['backMenuBtn']]]
    ],
    'resize_keyboard' => true
]);
$optionManualsale = json_encode([
    'keyboard' => [
        [['text' => $textbotlang['keyboard']['panelFeatureStatus']]],
        [['text' => $textbotlang['keyboard']['panelName']], ['text' => $textbotlang['keyboard']['deletePanel']]],
        [['text' => $textbotlang['keyboard']['usernameMethod']]],
        [['text' => $textbotlang['keyboard']['accountCreateLimit']], ['text' => $textbotlang['keyboard']['changeUserGroup']]],
        [['text' => $textbotlang['keyboard']['addConfig']], ['text' => $textbotlang['keyboard']['deleteConfig']]],
        [['text' => $textbotlang['keyboard']['editConfig']]],
        [['text' => $textbotlang['keyboard']['hidePanelForUser']]],
        [['text' => $textbotlang['keyboard']['removeFromHiddenList']]],
        [['text' => $textbotlang['Admin']['backAdminBtn']], ['text' => $textbotlang['Admin']['backMenuBtn']]]
    ],
    'resize_keyboard' => true
]);
$optionX_ui_single = json_encode([
    'keyboard' => [
        [['text' => $textbotlang['keyboard']['panelFeatureStatus']]],
        [['text' => $textbotlang['keyboard']['panelName']], ['text' => $textbotlang['keyboard']['deletePanel']]],
        [['text' => $textbotlang['keyboard']['editPassword']]],
        [['text' => $textbotlang['keyboard']['editPanelUrl']], ['text' => $textbotlang['keyboard']['renewalMethod']]],
        [['text' => $textbotlang['keyboard']['setProtocolInbound']]],
        [['text' => $textbotlang['keyboard']['usernameMethod']], ['text' => $textbotlang['keyboard']['subLinkDomain']]],
        [['text' => $textbotlang['keyboard']['changeUserGroup']], ['text' => $textbotlang['keyboard']['accountCreateLimit']]],
        [['text' => $textbotlang['keyboard']['testServiceTime']], ['text' => $textbotlang['keyboard']['testAccountVolume']]],
        [['text' => $textbotlang['keyboard']['changeLocationPrice']], ['text' => $textbotlang['keyboard']['extraVolumePrice']]],
        [['text' => $textbotlang['keyboard']['extraTimePrice']], ['text' => $textbotlang['keyboard']['customVolumePrice']]],
        [['text' => $textbotlang['keyboard']['customTimePrice']]],
        [['text' => $textbotlang['keyboard']['minCustomVolume']], ['text' => $textbotlang['keyboard']['maxCustomVolume']]],
        [['text' => $textbotlang['keyboard']['minCustomTime']], ['text' => $textbotlang['keyboard']['maxCustomTime']]],
        [['text' => $textbotlang['keyboard']['hidePanelForUser']]],
        [['text' => $textbotlang['keyboard']['removeFromHiddenList']]],
        [['text' => $textbotlang['Admin']['backAdminBtn']], ['text' => $textbotlang['Admin']['backMenuBtn']]]
    ],
    'resize_keyboard' => true
]);
$optionalireza_single = json_encode([
    'keyboard' => [
        [['text' => $textbotlang['keyboard']['panelFeatureStatus']]],
        [['text' => $textbotlang['keyboard']['panelName']], ['text' => $textbotlang['keyboard']['deletePanel']]],
        [['text' => $textbotlang['keyboard']['editPassword']], ['text' => $textbotlang['keyboard']['editUsername']]],
        [['text' => $textbotlang['keyboard']['editPanelUrl']], ['text' => $textbotlang['keyboard']['renewalMethod']]],
        [['text' => $textbotlang['keyboard']['setInboundId']]],
        [['text' => $textbotlang['keyboard']['usernameMethod']]],
        [['text' => $textbotlang['keyboard']['subLinkDomain']]],
        [['text' => $textbotlang['keyboard']['changeUserGroup']], ['text' => $textbotlang['keyboard']['accountCreateLimit']]],
        [['text' => $textbotlang['keyboard']['testServiceTime']], ['text' => $textbotlang['keyboard']['testAccountVolume']]],
        [['text' => $textbotlang['keyboard']['changeLocationPrice']], ['text' => $textbotlang['keyboard']['extraVolumePrice']]],
        [['text' => $textbotlang['keyboard']['extraTimePrice']], ['text' => $textbotlang['keyboard']['customVolumePrice']]],
        [['text' => $textbotlang['keyboard']['customTimePrice']]],
        [['text' => $textbotlang['keyboard']['minCustomVolume']], ['text' => $textbotlang['keyboard']['maxCustomVolume']]],
        [['text' => $textbotlang['keyboard']['minCustomTime']], ['text' => $textbotlang['keyboard']['maxCustomTime']]],
        [['text' => $textbotlang['keyboard']['hidePanelForUser']]],
        [['text' => $textbotlang['keyboard']['removeFromHiddenList']]],
        [['text' => $textbotlang['Admin']['backAdminBtn']], ['text' => $textbotlang['Admin']['backMenuBtn']]]
    ],
    'resize_keyboard' => true
]);
$optionhiddfy = json_encode([
    'keyboard' => [
        [['text' => $textbotlang['keyboard']['panelFeatureStatus']]],
        [['text' => $textbotlang['keyboard']['panelName']], ['text' => $textbotlang['keyboard']['deletePanel']]],
        [['text' => $textbotlang['keyboard']['editPanelUrl']], ['text' => $textbotlang['keyboard']['renewalMethod']]],
        [['text' => $textbotlang['keyboard']['changeUserGroup']]],
        [['text' => $textbotlang['keyboard']['usernameMethod']]],
        [['text' => $textbotlang['keyboard']['subLinkDomain']]],
        [['text' => $textbotlang['keyboard']['accountCreateLimit']], ['text' => "🔗 uuid admin"]],
        [['text' => $textbotlang['keyboard']['testServiceTime']], ['text' => $textbotlang['keyboard']['testAccountVolume']]],
        [['text' => $textbotlang['keyboard']['changeLocationPrice']], ['text' => $textbotlang['keyboard']['extraVolumePrice']]],
        [['text' => $textbotlang['keyboard']['extraTimePrice']], ['text' => $textbotlang['keyboard']['customVolumePrice']]],
        [['text' => $textbotlang['keyboard']['customTimePrice']]],
        [['text' => $textbotlang['keyboard']['minCustomVolume']], ['text' => $textbotlang['keyboard']['maxCustomVolume']]],
        [['text' => $textbotlang['keyboard']['minCustomTime']], ['text' => $textbotlang['keyboard']['maxCustomTime']]],
        [['text' => $textbotlang['keyboard']['hidePanelForUser']]],
        [['text' => $textbotlang['keyboard']['removeFromHiddenList']]],
        [['text' => $textbotlang['Admin']['backAdminBtn']], ['text' => $textbotlang['Admin']['backMenuBtn']]]
    ],
    'resize_keyboard' => true
]);
if ($setting['statussupportpv'] == "onpvsupport") {
    $supportoption = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['textbot']['faq'], 'callback_data' => "fqQuestions"],
                ['text' => $textbotlang['keyboard']['sendMessageToSupport'], 'url' => "https://t.me/{$setting['id_support']}"],
            ],
            [
                ['text' => $textbotlang['keyboard']['backToMainMenu'], 'callback_data' => "backuser"]
            ],

        ]
    ]);
} else {
    $supportoption = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['textbot']['faq'], 'callback_data' => "fqQuestions"],
                ['text' => $textbotlang['keyboard']['sendMessageToSupport'], 'callback_data' => "support"],
            ],
            [
                ['text' => $textbotlang['keyboard']['backToMainMenu'], 'callback_data' => "backuser"]
            ],

        ]
    ]);
}
$adminrule = json_encode([
    'keyboard' => [
        [['text' => "administrator"], ['text' => "Seller"], ['text' => "support"]],
        [['text' => $textbotlang['Admin']['backAdminBtn']], ['text' => $textbotlang['Admin']['backMenuBtn']]]
    ],
    'resize_keyboard' => true
]);
$affiliates = json_encode([
    'keyboard' => [
        [['text' => $textbotlang['keyboard']['setAffiliatePercent']]],
        [['text' => $textbotlang['keyboard']['setAffiliateBanner']]],
        [['text' => $textbotlang['keyboard']['purchaseCommission']], ['text' => $textbotlang['keyboard']['startGift']]],
        [['text' => $textbotlang['keyboard']['firstPurchaseCommission']]],
        [['text' => $textbotlang['keyboard']['startGiftAmount']]],
        [['text' => $textbotlang['Admin']['backAdminBtn']], ['text' => $textbotlang['Admin']['backMenuBtn']]]
    ],
    'resize_keyboard' => true
]);
$keyboardexportdata = json_encode([
    'keyboard' => [
        [['text' => $textbotlang['keyboard']['exportUsers']], ['text' => $textbotlang['keyboard']['exportOrders']]],
        [['text' => $textbotlang['keyboard']['exportPayments']]],
        [['text' => $textbotlang['Admin']['backAdminBtn']], ['text' => $textbotlang['Admin']['backMenuBtn']]]
    ],
    'resize_keyboard' => true
]);
$helpedit = json_encode([
    'keyboard' => [
        [['text' => $textbotlang['keyboard']['editName']], ['text' => $textbotlang['keyboard']['editDescription']]],
        [['text' => $textbotlang['keyboard']['editMedia']], ['text' => $textbotlang['keyboard']['editCategory']]],
        [['text' => $textbotlang['Admin']['backAdminBtn']], ['text' => $textbotlang['Admin']['backMenuBtn']]]
    ],
    'resize_keyboard' => true
]);
$Methodextend = json_encode([
    'keyboard' => [
        [['text' => $textbotlang['keyboard']['resetVolumeTime']]],
        [['text' => $textbotlang['keyboard']['addTimeVolumeNextMonth']]],
        [['text' => $textbotlang['keyboard']['resetTimeAddVolume']]],
        [['text' => $textbotlang['keyboard']['resetVolumeAddTime']]],
        [['text' => $textbotlang['keyboard']['addTimeConvertVolume']]],
        [['text' => $textbotlang['Admin']['backAdminBtn']], ['text' => $textbotlang['Admin']['backMenuBtn']]]
    ],
    'resize_keyboard' => true
]);
$keyboardtimereset = json_encode([
    'keyboard' => [
        [['text' => "no_reset"], ['text' => "day"], ['text' => "week"]],
        [['text' => "month"], ['text' => "year"]],
        [['text' => $textbotlang['Admin']['backAdminBtn']], ['text' => $textbotlang['Admin']['backMenuBtn']]]
    ],
    'resize_keyboard' => true
]);
$keyboardtypepanel = json_encode([
    'inline_keyboard' => [
        [
            ['text' => $textbotlang['keyboard']['marzban'], 'callback_data' => "typepanel#marzban"],
            ['text' => $textbotlang['keyboard']['marzneshin'], 'callback_data' => "typepanel#marzneshin"]
        ],
        [
            ['text' => $textbotlang['keyboard']['passargadPanel'], 'callback_data' => "typepanel#pasarguard"],
            ['text' => $textbotlang['keyboard']['mirzaAgentPanel'], 'callback_data' => "typepanel#mirza_agent"]
        ],
        [
            ['text' => $textbotlang['keyboard']['panelTypeSanaei'], 'callback_data' => 'typepanel#x-ui_single'],
            ['text' => $textbotlang['keyboard']['panelTypeAlireza'], 'callback_data' => 'typepanel#alireza_single']
        ],
        [
            ['text' => $textbotlang['keyboard']['manualSale'], 'callback_data' => 'typepanel#Manualsale'],
            ['text' => $textbotlang['keyboard']['hiddify'], 'callback_data' => 'typepanel#hiddify'],
        ],
        [
            ['text' => "WGDashboard", 'callback_data' => 'typepanel#WGDashboard'],
            ['text' => "s_ui", 'callback_data' => 'typepanel#s_ui']
        ],
        [
            ['text' => "ibsng", 'callback_data' => 'typepanel#ibsng'],
            ['text' => $textbotlang['keyboard']['mikrotik'], 'callback_data' => 'typepanel#mikrotik']
        ],
        [
            ['text' => $textbotlang['keyboard']['rebecca'], 'callback_data' => 'typepanel#rebecca']
        ],
        [
            ['text' => $textbotlang['Admin']['backAdminBtn'], 'callback_data' => 'admin']
        ]
    ],
]);

$panelechekc = select("marzban_panel", "*", "MethodUsername", "agentCustomTextSequential", "count");
if ($setting['inlinebtnmain'] == "oninline") {
    $keyboardagent = [
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['keyboard']['bulkPurchase'], 'callback_data' => "kharidanbuh"],
                ['text' => $textbotlang['keyboard']['selectCustomName'], 'callback_data' => "selectname"]
            ],
            [
                ['text' => $textbotlang['users']['backbtn'], 'callback_data' => "backuser"]
            ]
        ],
        'resize_keyboard' => true
    ];
    if ($panelechekc == 0) {
        unset($keyboardagent['inline_keyboard'][0][1]);
    }
} else {
    $keyboardagent = [
        'keyboard' => [
            [['text' => $textbotlang['keyboard']['bulkPurchase']], ['text' => $textbotlang['keyboard']['selectCustomName']]],
            [['text' => $textbotlang['users']['backbtn']]]
        ],
        'resize_keyboard' => true
    ];
    if ($panelechekc == 0) {
        unset($keyboardagent['keyboard'][0][1]);
    }
}
$keyboardagent = json_encode($keyboardagent);
$Swapinokey = json_encode([
    'keyboard' => [
        [['text' => $textbotlang['keyboard']['setApi']]],
        [['text' => $textbotlang['keyboard']['cashbackIranPay1']], ['text' => $textbotlang['keyboard']['setEducationIranPay1']]],
        [['text' => $textbotlang['keyboard']['minAmountIranPay1']], ['text' => $textbotlang['keyboard']['maxAmountIranPay1']]],
        [['text' => $textbotlang['Admin']['backAdminBtn']], ['text' => $textbotlang['Admin']['backMenuBtn']]]
    ],
    'resize_keyboard' => true
]);

$tronnowpayments = json_encode([
    'keyboard' => [
        [['text' => $textbotlang['keyboard']['minAmountCryptoOffline']], ['text' => $textbotlang['keyboard']['maxAmountCryptoOffline']]],
        [['text' => $textbotlang['keyboard']['setEducationCryptoOffline']]],
        [['text' => $textbotlang['Admin']['backAdminBtn']], ['text' => $textbotlang['Admin']['backMenuBtn']]]
    ],
    'resize_keyboard' => true
]);
$optionathmarzban = json_encode([
    'keyboard' => [
        [['text' => $textbotlang['keyboard']['manualCreateConfig']], ['text' => $textbotlang['keyboard']['manageNodes']]],
        [['text' => $textbotlang['Admin']['backAdminBtn']], ['text' => $textbotlang['Admin']['backMenuBtn']]]
    ],
    'resize_keyboard' => true
]);
$optionathx_ui = json_encode([
    'keyboard' => [
        [['text' => $textbotlang['keyboard']['manualCreateConfig']]],
        [['text' => $textbotlang['Admin']['backAdminBtn']], ['text' => $textbotlang['Admin']['backMenuBtn']]]
    ],
    'resize_keyboard' => true
]);
$configedit = json_encode([
    'keyboard' => [
        [['text' => $textbotlang['keyboard']['configDetails']]],
        [['text' => $textbotlang['Admin']['backAdminBtn']], ['text' => $textbotlang['Admin']['backMenuBtn']]]
    ],
    'resize_keyboard' => true
]);
$iranpaykeyboard = json_encode([
    'keyboard' => [
        [['text' => $textbotlang['keyboard']['apiIranPay']]],
        [['text' => $textbotlang['keyboard']['minAmountIranPay3']], ['text' => $textbotlang['keyboard']['maxAmountIranPay3']]],
        [['text' => $textbotlang['keyboard']['cashbackIranPay3']]],
        [['text' => $textbotlang['keyboard']['setEducationIranPay3']]],
        [['text' => $textbotlang['Admin']['backAdminBtn']], ['text' => $textbotlang['Admin']['backMenuBtn']]]
    ],
    'resize_keyboard' => true
]);
$abangatewaykeyboard = json_encode([
    'keyboard' => [
        [['text' => $textbotlang['keyboard']['apiIranPay4']], ['text' => $textbotlang['keyboard']['endpointIranPay4']]],
        [['text' => $textbotlang['keyboard']['minAmountIranPay4']], ['text' => $textbotlang['keyboard']['maxAmountIranPay4']]],
        [['text' => $textbotlang['keyboard']['dailyLimitIranPay4']]],
        [['text' => $textbotlang['keyboard']['cashbackIranPay4']]],
        [['text' => $textbotlang['keyboard']['setEducationIranPay4']]],
        [['text' => $textbotlang['Admin']['backAdminBtn']], ['text' => $textbotlang['Admin']['backMenuBtn']]]
    ],
    'resize_keyboard' => true
]);
$supportcenter = json_encode([
    'keyboard' => [
        [['text' => $textbotlang['keyboard']['setSupportId']]],
        [['text' => $textbotlang['keyboard']['addDepartment']], ['text' => $textbotlang['keyboard']['deleteDepartment']]],
        [['text' => $textbotlang['Admin']['backAdminBtn']], ['text' => $textbotlang['Admin']['backMenuBtn']]]
    ],
    'resize_keyboard' => true
]);
//------------------  [ list departeman ]----------------//
$departeman = [];
    $stmt = $pdo->prepare("SELECT * FROM departman");
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $departeman[] = [$row['name_departman']];
    }
    $departemans = [
        'keyboard' => [],
        'resize_keyboard' => true,
    ];
    foreach ($departeman as $button) {
        $departemans['keyboard'][] = [
            ['text' => $button[0]]
        ];
    }
    $departemans['keyboard'][] = [
        ['text' => $textbotlang['Admin']['backAdminBtn']],
        ['text' => $textbotlang['Admin']['backMenuBtn']]
    ];
    $departemanslist = json_encode($departemans);
// list departeman
$list_departman = ['inline_keyboard' => []];
    $stmt = $pdo->prepare("SELECT * FROM departman");
    $stmt->execute();
    while ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $list_departman['inline_keyboard'][] = [
            ['text' => $result['name_departman'], 'callback_data' => "departman_{$result['id']}"]
        ];
    }
$list_departman['inline_keyboard'][] = [
    ['text' => $textbotlang['users']['backbtn'], 'callback_data' => "backuser"],
];
$list_departman = json_encode($list_departman);
$active_panell = json_encode([
    'keyboard' => [
        [['text' => $textbotlang['keyboard']['botReports']]],
    ],
    'resize_keyboard' => true
]);
$lottery = json_encode([
    'keyboard' => [
        [['text' => $textbotlang['keyboard']['setFirstPrize']], ['text' => $textbotlang['keyboard']['setSecondPrize']]],
        [['text' => $textbotlang['keyboard']['setThirdPrize']]],
        [['text' => $textbotlang['Admin']['backAdminBtn']]]
    ],
    'resize_keyboard' => true
]);
$wheelkeyboard = json_encode([
    'keyboard' => [
        [['text' => $textbotlang['keyboard']['lotteryWinAmount']]],
        [['text' => $textbotlang['Admin']['backAdminBtn']]]
    ],
    'resize_keyboard' => true
]);
$keyboardlinkapp = json_encode([
    'keyboard' => [
        [['text' => $textbotlang['keyboard']['addApp']], ['text' => $textbotlang['keyboard']['deleteApp']]],
        [['text' => $textbotlang['keyboard']['editApp']]],
        [['text' => $textbotlang['Admin']['backAdminBtn']], ['text' => $textbotlang['Admin']['backMenuBtn']]]
    ],
    'resize_keyboard' => true
]);
function KeyboardProduct($location, $query, $pricediscount, $datakeyboard, $statuscustom = false, $backuser = "backuser", $valuetow = null, $customvolume = "customsellvolume", $queryParams = [])
{
    global $pdo, $textbotlang, $from_id;
    $product = ['inline_keyboard' => []];
    $statusshowprice = select("shopSetting", "*", "Namevalue", "statusshowprice", "select")['value'];
    $stmt = $pdo->prepare($query);
    $stmt->execute($queryParams);
    if ($valuetow != null) {
        $valuetow = "-$valuetow";
    } else {
        $valuetow = "";
    }
    $countorder = null;
    while ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $hide_panel = json_decode($result['hide_panel'], true);
        if (in_array($location, $hide_panel))
            continue;
        if ($result['one_buy_status'] == "1") {
            if ($countorder === null) {
                $stmts2 = $pdo->prepare("SELECT COUNT(*) FROM invoice WHERE Status != 'Unpaid' AND id_user = :id_user");
                $stmts2->bindValue(':id_user', $from_id);
                $stmts2->execute();
                $countorder = (int) $stmts2->fetchColumn();
            }
            if ($countorder != 0)
                continue;
        }
        if (intval($pricediscount) != 0) {
            $resultper = ($result['price_product'] * $pricediscount) / 100;
            $result['price_product'] = $result['price_product'] - $resultper;
        }
        $namekeyboard = $result['name_product'] . " - " . number_format($result['price_product']) . $textbotlang['common']['labels']['toman'];
        if ($statusshowprice == "onshowprice") {
            $result['name_product'] = $namekeyboard;
        }
        $product['inline_keyboard'][] = [
            ['text' => $result['name_product'], 'callback_data' => "{$datakeyboard}{$result['code_product']}{$valuetow}"]
        ];
    }
    if ($statuscustom)
        $product['inline_keyboard'][] = [['text' => $textbotlang['users']['customSellVolume']['title'], 'callback_data' => $customvolume]];
    $product['inline_keyboard'][] = [
        ['text' => $textbotlang['users']['status']['backinfo'], 'callback_data' => $backuser],
    ];
    return json_encode($product);
}
function KeyboardCategory($location, $agent, $backuser = "backuser")
{
    global $pdo, $textbotlang;
    $stmts = $pdo->prepare("SELECT category, COUNT(*) AS c FROM product WHERE (Location = :location OR Location = '/all') AND agent = :agent GROUP BY category");
    $stmts->bindParam(':location', $location, PDO::PARAM_STR);
    $stmts->bindParam(':agent', $agent);
    $stmts->execute();
    $categoryCounts = [];
    foreach ($stmts->fetchAll(PDO::FETCH_ASSOC) as $catRow) {
        $categoryKey = mb_strtolower(trim((string) $catRow['category']), 'UTF-8');
        $categoryCounts[$categoryKey] = ($categoryCounts[$categoryKey] ?? 0) + (int) $catRow['c'];
    }
    $stmt = $pdo->prepare("SELECT * FROM category");
    $stmt->execute();
    $list_category = ['inline_keyboard' => [],];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (empty($categoryCounts[mb_strtolower(trim((string) $row['remark']), 'UTF-8')]))
            continue;
        $list_category['inline_keyboard'][] = [['text' => $row['remark'], 'callback_data' => "categorynames_" . $row['id']]];
    }
    $list_category['inline_keyboard'][] = [
        ['text' => $textbotlang['keyboard']['backToPreviousMenu'], "callback_data" => $backuser],
    ];
    return json_encode($list_category);
}

function keyboardTimeCategory($name_panel, $agent, $callback_data = "producttime_", $callback_data_back = "backuser", $statuscustomvolume = false, $statusbtnextend = false)
{
    global $pdo, $textbotlang;
    $stmt = $pdo->prepare("SELECT (Service_time) FROM product WHERE (Location = :name_panel OR Location = '/all') AND  agent = :agent");
    $stmt->bindValue(':name_panel', $name_panel, PDO::PARAM_STR);
    $stmt->bindValue(':agent', $agent, PDO::PARAM_STR);
    $stmt->execute();
    $montheproduct = array_flip(array_flip($stmt->fetchAll(PDO::FETCH_COLUMN)));
    $monthkeyboard = ['inline_keyboard' => []];
    if (in_array("1", $montheproduct)) {
        $monthkeyboard['inline_keyboard'][] = [
            ['text' => $textbotlang['common']['duration']['1day'], 'callback_data' => "{$callback_data}1"]
        ];
    }
    if (in_array("7", $montheproduct)) {
        $monthkeyboard['inline_keyboard'][] = [
            ['text' => $textbotlang['common']['duration']['7day'], 'callback_data' => "{$callback_data}7"]
        ];
    }
    if (in_array("31", $montheproduct)) {
        $monthkeyboard['inline_keyboard'][] = [
            ['text' => $textbotlang['common']['duration']['1'], 'callback_data' => "{$callback_data}31"]
        ];
    }
    if (in_array("30", $montheproduct)) {
        $monthkeyboard['inline_keyboard'][] = [
            ['text' => $textbotlang['common']['duration']['1'], 'callback_data' => "{$callback_data}30"]
        ];
    }
    if (in_array("61", $montheproduct)) {
        $monthkeyboard['inline_keyboard'][] = [
            ['text' => $textbotlang['common']['duration']['2'], 'callback_data' => "{$callback_data}61"]
        ];
    }
    if (in_array("60", $montheproduct)) {
        $monthkeyboard['inline_keyboard'][] = [
            ['text' => $textbotlang['common']['duration']['2'], 'callback_data' => "{$callback_data}60"]
        ];
    }
    if (in_array("91", $montheproduct)) {
        $monthkeyboard['inline_keyboard'][] = [
            ['text' => $textbotlang['common']['duration']['3'], 'callback_data' => "{$callback_data}91"]
        ];
    }
    if (in_array("90", $montheproduct)) {
        $monthkeyboard['inline_keyboard'][] = [
            ['text' => $textbotlang['common']['duration']['3'], 'callback_data' => "{$callback_data}90"]
        ];
    }
    if (in_array("121", $montheproduct)) {
        $monthkeyboard['inline_keyboard'][] = [
            ['text' => $textbotlang['common']['duration']['4'], 'callback_data' => "{$callback_data}121"]
        ];
    }
    if (in_array("120", $montheproduct)) {
        $monthkeyboard['inline_keyboard'][] = [
            ['text' => $textbotlang['common']['duration']['4'], 'callback_data' => "{$callback_data}120"]
        ];
    }
    if (in_array("181", $montheproduct)) {
        $monthkeyboard['inline_keyboard'][] = [
            ['text' => $textbotlang['common']['duration']['6'], 'callback_data' => "{$callback_data}181"]
        ];
    }
    if (in_array("180", $montheproduct)) {
        $monthkeyboard['inline_keyboard'][] = [
            ['text' => $textbotlang['common']['duration']['6'], 'callback_data' => "{$callback_data}180"]
        ];
    }
    if (in_array("365", $montheproduct)) {
        $monthkeyboard['inline_keyboard'][] = [
            ['text' => $textbotlang['common']['duration']['365'], 'callback_data' => "{$callback_data}365"]
        ];
    }
    if (in_array("0", $montheproduct)) {
        $monthkeyboard['inline_keyboard'][] = [
            ['text' => $textbotlang['common']['duration']['byVolume'], 'callback_data' => "{$callback_data}0"]
        ];
    }
    if ($statusbtnextend)
        $monthkeyboard['inline_keyboard'][] = [['text' => $textbotlang['keyboard']['renewCurrentPlan'], 'callback_data' => "exntedagei"]];
    if ($statuscustomvolume == true)
        $monthkeyboard['inline_keyboard'][] = [['text' => $textbotlang['users']['customSellVolume']['title'], 'callback_data' => "customsellvolume"]];
    $monthkeyboard['inline_keyboard'][] = [
        ['text' => $textbotlang['users']['status']['backinfo'], 'callback_data' => $callback_data_back]
    ];
    return json_encode($monthkeyboard);
}
$Startelegram = json_encode([
    'keyboard' => [
        [['text' => $textbotlang['keyboard']['cashbackStar']], ['text' => $textbotlang['keyboard']['setEducationStar']]],
        [['text' => $textbotlang['keyboard']['minAmountStar']], ['text' => $textbotlang['keyboard']['maxAmountStar']]],
        [['text' => $textbotlang['Admin']['backAdminBtn']], ['text' => $textbotlang['Admin']['backMenuBtn']]]
    ],
    'resize_keyboard' => true
]);
$keyboardchangelimit = json_encode([
    'keyboard' => [
        [['text' => $textbotlang['keyboard']['freeLimit']], ['text' => $textbotlang['keyboard']['generalLimit']]],
        [['text' => $textbotlang['keyboard']['resetAllUsersLimit']]],
        [['text' => $textbotlang['Admin']['backAdminBtn']]]
    ],
    'resize_keyboard' => true
]);
function KeyboardCategoryadmin()
{
    global $pdo, $textbotlang;
    $stmt = $pdo->prepare("SELECT * FROM category");
    $stmt->execute();
    $list_category = [
        'keyboard' => [],
        'resize_keyboard' => true,
    ];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $list_category['keyboard'][] = [['text' => $row['remark']]];
    }
    $list_category['keyboard'][] = [
        ['text' => $textbotlang['Admin']['backAdminBtn']],
    ];
    return json_encode($list_category);
}
$nowpayment_setting_keyboard = json_encode([
    'keyboard' => [
        [['text' => "API NOWPAYMENT"]],
        [['text' => $textbotlang['keyboard']['cashbackNowPayment']], ['text' => $textbotlang['keyboard']['setEducationNowPayment']]],
        [['text' => $textbotlang['keyboard']['minAmountNowPayment']], ['text' => $textbotlang['keyboard']['maxAmountNowPayment']]],
        [['text' => $textbotlang['Admin']['backAdminBtn']], ['text' => $textbotlang['Admin']['backMenuBtn']]]
    ],
    'resize_keyboard' => true
]);
$Exception_auto_cart_keyboard = json_encode([
    'keyboard' => [
        [['text' => $textbotlang['keyboard']['excludeUser']], ['text' => $textbotlang['keyboard']['removeUserFromList']]],
        [['text' => $textbotlang['keyboard']['showUserList']]],
        [['text' => $textbotlang['keyboard']['backToCardSettings']]]
    ],
    'resize_keyboard' => true
]);
function keyboard_config($config_split, $id_invoice, $back_active = true)
{
    global $textbotlang;
    $keyboard_config = ['inline_keyboard' => []];
    $keyboard_config['inline_keyboard'][] = [
        ['text' => $textbotlang['keyboard']['config'], 'callback_data' => "none"],
        ['text' => $textbotlang['keyboard']['configName'], 'callback_data' => "none"],
    ];
    for ($i = 0; $i < count($config_split); $i++) {
        $config = $config_split[$i];
        $split_config = explode("://", $config);
        $type_prtocol = $split_config[0];
        $split_config = $split_config[1] ?? '';
        if (isBase64($split_config)) {
            $split_config = base64_decode($split_config);
        }
        if ($type_prtocol == "vmess") {
            $split_config = json_decode($split_config, true)['ps'] ?? '';
        } else {
            $parts = explode("#", $split_config);
            $split_config = $parts[1] ?? $parts[0];
        }
        $keyboard_config['inline_keyboard'][] = [
            ['text' => $textbotlang['keyboard']['getConfig'], 'callback_data' => "configget_{$id_invoice}_$i"],
            ['text' => urldecode($split_config), 'callback_data' => "none"],
        ];

    }
    $keyboard_config['inline_keyboard'][] = [['text' => $textbotlang['keyboard']['getAllConfigs'], 'callback_data' => "configget_$id_invoice" . "_1520"]];
    if ($back_active) {
        $keyboard_config['inline_keyboard'][] = [['text' => $textbotlang['users']['status']['backinfo'], 'callback_data' => "product_$id_invoice"]];
    }
    return json_encode($keyboard_config);
}
$keyboard_buy = json_encode([
    'inline_keyboard' => [
        [
            ['text' => $textbotlang['keyboard']['buySubscription'], 'callback_data' => 'buy'],
        ],
    ]
]);
$keyboard_stat = json_encode([
    'inline_keyboard' => [
        [
            ['text' => $textbotlang['keyboard']['totalStats'], 'callback_data' => 'stat_all_bot'],
        ],
        [
            ['text' => $textbotlang['keyboard']['lastHourStats'], 'callback_data' => 'hoursago_stat'],
        ],
        [
            ['text' => $textbotlang['keyboard']['today'], 'callback_data' => 'today_stat'],
            ['text' => $textbotlang['keyboard']['yesterday'], 'callback_data' => 'yesterday_stat'],
        ],
        [
            ['text' => $textbotlang['keyboard']['currentMonth'], 'callback_data' => 'month_current_stat'],
            ['text' => $textbotlang['keyboard']['lastMonth'], 'callback_data' => 'month_old_stat'],
        ],
        [
            ['text' => $textbotlang['keyboard']['statsAtDate'], 'callback_data' => 'view_stat_time'],
        ]
    ]
]);
$option_mirza = json_encode([
    'keyboard' => [
        [['text' => $textbotlang['keyboard']['panelFeatureStatus']]],
        [['text' => $textbotlang['keyboard']['panelName']], ['text' => $textbotlang['keyboard']['deletePanel']]],
        [['text' => $textbotlang['keyboard']['editPassword']]],
        [['text' => $textbotlang['keyboard']['editPanelUrl']], ['text' => $textbotlang['keyboard']['panelSetting']]],
        [['text' => $textbotlang['keyboard']['accountCreateLimit']], ['text' => $textbotlang['keyboard']['changeUserGroup']]],
        [['text' => $textbotlang['keyboard']['customVolumePrice']], ['text' => $textbotlang['keyboard']['extraVolumePrice']]],
        [['text' => $textbotlang['keyboard']['extraTimePrice']], ['text' => $textbotlang['keyboard']['customTimePrice']]],
        [['text' => $textbotlang['keyboard']['minCustomVolume']], ['text' => $textbotlang['keyboard']['maxCustomVolume']]],
        [['text' => $textbotlang['keyboard']['minCustomTime']], ['text' => $textbotlang['keyboard']['maxCustomTime']]],
        [['text' => $textbotlang['keyboard']['hidePanelForUser']]],
        [['text' => $textbotlang['keyboard']['removeFromHiddenList']]],
        [['text' => $textbotlang['Admin']['backAdminBtn']], ['text' => $textbotlang['Admin']['backMenuBtn']]]
    ],
    'resize_keyboard' => true
]);
function keyboard_list_text($lang)
{
    global $textbotlang;
    $keyboard_text = ['inline_keyboard' => []];
    $keyboard_list_text = $textbotlang['bottext']['items'];
    $keyboard_text['inline_keyboard'][] = [
        ['text' => ($lang == 'fa' ? "✅" : "") . $textbotlang['bottext']['langs']['fa'], 'callback_data' => "bt_lang:fa"],
        ['text' => ($lang == 'en' ? "✅" : "") . $textbotlang['bottext']['langs']['en'], 'callback_data' => "bt_lang:en"],
        ['text' => ($lang == 'ru' ? "✅" : "") . $textbotlang['bottext']['langs']['ru'], 'callback_data' => "bt_lang:ru"],
        ['text' => ($lang == 'zh' ? "✅" : "") . $textbotlang['bottext']['langs']['zh'], 'callback_data' => "bt_lang:zh"],
    ];
    foreach ($keyboard_list_text as $data) {
        $keyboard_text['inline_keyboard'][] = [['text' => $data['label'], 'callback_data' => "bt_edit|$lang|{$data['key']}"]];
    }
    $keyboard_text['inline_keyboard'][] = [['text' => $textbotlang['bottext']['btn_close'], 'callback_data' => 'bt_close']];
    return json_encode($keyboard_text);
}
