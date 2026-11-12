<?php

require_once __DIR__ . '/db/bootstrap.php';

foreach (['Marzban.php', 'marzneshin.php', 'hiddify.php', 'ibsng.php', 'mikrotik.php', 'mirza_agent.php', 'nexora.php', 'Rebecca.php', 'WGDashboard.php', 'wg_mate.php', 'x-ui_single.php', 'alireza_single.php', 's_ui.php'] as $oldPanelFile) {
    if (is_file(__DIR__ . '/' . $oldPanelFile)) {
        @unlink(__DIR__ . '/' . $oldPanelFile);
    }
}

global $domainhosts;

$setting = config_all('setting');
if (intval($setting['Channel_Report'] ?? 0) != 0) {
    syncReportTopics($setting['Channel_Report'], languagechange());
}

$webhookSecret = ensureWebhookSecret();

telegram('setWebhook', [
    'url' => "https://$domainhosts/index.php?secret={$webhookSecret['secret']}",
]);
