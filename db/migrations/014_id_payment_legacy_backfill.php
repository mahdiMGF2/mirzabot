<?php

return static function (PDO $pdo, Schema $schema): void {
    if (!$schema->hasColumn('invoice', 'id_payment') || !$schema->hasColumn('service_other', 'id_payment') || !$schema->hasColumn('payment_report', 'id_invoice')) {
        return;
    }
    $openPayment = "pr.payment_Status IN ('Unpaid', 'waiting')";
    $target = "SUBSTRING_INDEX(pr.id_invoice, '|', -1)";
    $targetUsername = "SUBSTRING_INDEX($target, '%', 1)";
    $targetValue = "SUBSTRING_INDEX($target, '%', -1)";
    $statements = [
        'buy' => "UPDATE invoice i JOIN payment_report pr ON i.username = $target SET i.id_payment = pr.id_order WHERE pr.id_invoice LIKE 'getconfigafterpay|%' AND $openPayment AND i.Status = 'unpaid' AND i.id_payment IS NULL",
        'extend' => "UPDATE service_other s JOIN payment_report pr ON s.username = $targetUsername AND s.id_user = pr.id_user AND s.value LIKE CONCAT('%\"id_order\":\"', $targetValue, '\"%') SET s.id_payment = pr.id_order WHERE pr.id_invoice LIKE 'getextenduser|%' AND $openPayment AND s.type = 'extend_user' AND s.status = 'unpaid' AND s.id_payment IS NULL",
        'extra_volume' => "INSERT INTO service_other (id_user, username, value, time, price, type, status, output, id_payment) SELECT pr.id_user, $targetUsername, JSON_OBJECT('volume_value', $targetValue), pr.time, pr.price, 'extra_user', 'unpaid', '', pr.id_order FROM payment_report pr WHERE pr.id_invoice LIKE 'getextravolumeuser|%' AND $openPayment AND NOT EXISTS (SELECT 1 FROM service_other s WHERE s.id_payment = pr.id_order)",
        'extra_time' => "INSERT INTO service_other (id_user, username, value, time, price, type, status, output, id_payment) SELECT pr.id_user, $targetUsername, JSON_OBJECT('day', $targetValue), pr.time, pr.price, 'extra_time_user', 'unpaid', '', pr.id_order FROM payment_report pr WHERE pr.id_invoice LIKE 'getextratimeuser|%' AND $openPayment AND NOT EXISTS (SELECT 1 FROM service_other s WHERE s.id_payment = pr.id_order)",
        'reset_steps' => "UPDATE user SET step = 'home', Processing_value_tow = '0' WHERE step = 'get_step_payment' AND Processing_value_tow IN ('getconfigafterpay', 'getextenduser', 'getextravolumeuser', 'getextratimeuser')",
    ];
    foreach ($statements as $scope => $sql) {
        try {
            $pdo->exec($sql);
        } catch (Throwable $e) {
            error_log("[db:migration:id_payment_legacy_backfill:$scope] " . $e->getMessage());
        }
    }
};
