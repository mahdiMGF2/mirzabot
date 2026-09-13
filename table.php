<?php

require_once __DIR__ . '/db/bootstrap.php';

global $domainhosts;

$webhookSecret = ensureWebhookSecret();

telegram('setWebhook', [
    'url' => "https://$domainhosts/index.php?secret={$webhookSecret['secret']}",
]);
