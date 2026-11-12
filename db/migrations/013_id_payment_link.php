<?php

return static function (PDO $pdo, Schema $schema): void {
    $run = static function (string $scope, string $sql) use ($pdo): void {
        try {
            $pdo->exec($sql);
        } catch (Throwable $e) {
            error_log("[db:migration:id_payment_link:$scope] " . $e->getMessage());
        }
    };
    $columnInfo = static function (string $table, string $column) use ($pdo) {
        $stmt = $pdo->prepare("SELECT CHARACTER_SET_NAME, COLLATION_NAME, CHARACTER_MAXIMUM_LENGTH FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
        $stmt->execute([$table, $column]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    };
    $indexExists = static function (string $table, string $name) use ($pdo): bool {
        $stmt = $pdo->prepare("SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1");
        $stmt->execute([$table, $name]);
        return (bool) $stmt->fetchColumn();
    };
    $foreignKeyExists = static function (string $table, string $name) use ($pdo): bool {
        $stmt = $pdo->prepare("SELECT 1 FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = 'FOREIGN KEY' LIMIT 1");
        $stmt->execute([$table, $name]);
        return (bool) $stmt->fetchColumn();
    };

    $idOrder = $columnInfo('payment_report', 'id_order');
    if (!$idOrder) {
        return;
    }
    if ((int) $idOrder['CHARACTER_MAXIMUM_LENGTH'] > 80) {
        $longest = (int) $pdo->query("SELECT COALESCE(MAX(CHAR_LENGTH(id_order)), 0) FROM payment_report")->fetchColumn();
        if ($longest > 80) {
            error_log("[db:migration:id_payment_link] payment_report.id_order has values longer than 80 characters; foreign keys skipped");
            return;
        }
        if ($indexExists('payment_report', 'idx_payreport_id_order')) {
            $run('drop_prefix_index', "ALTER TABLE `payment_report` DROP INDEX `idx_payreport_id_order`");
        }
        $run('shrink_id_order', "ALTER TABLE `payment_report` MODIFY `id_order` VARCHAR(80) CHARACTER SET {$idOrder['CHARACTER_SET_NAME']} COLLATE {$idOrder['COLLATION_NAME']} NULL");
    }
    if (!$indexExists('payment_report', 'idx_payreport_id_order')) {
        $run('id_order_index', "ALTER TABLE `payment_report` ADD INDEX `idx_payreport_id_order` (`id_order`)");
    }

    foreach (['invoice' => ['idx_invoice_id_payment', 'fk_invoice_payment_report'], 'service_other' => ['idx_serviceother_id_payment', 'fk_service_other_payment_report']] as $table => [$indexName, $foreignKeyName]) {
        $idPayment = $columnInfo($table, 'id_payment');
        if (!$idPayment || $foreignKeyExists($table, $foreignKeyName)) {
            continue;
        }
        if ($idPayment['COLLATION_NAME'] !== $idOrder['COLLATION_NAME']) {
            $run("$table:collation", "ALTER TABLE `$table` MODIFY `id_payment` VARCHAR(20) CHARACTER SET {$idOrder['CHARACTER_SET_NAME']} COLLATE {$idOrder['COLLATION_NAME']} NULL");
        }
        if (!$indexExists($table, $indexName)) {
            $run("$table:index", "ALTER TABLE `$table` ADD INDEX `$indexName` (`id_payment`)");
        }
        $run("$table:orphans", "UPDATE `$table` t SET t.id_payment = NULL WHERE t.id_payment IS NOT NULL AND NOT EXISTS (SELECT 1 FROM payment_report pr WHERE pr.id_order = t.id_payment)");
        $run("$table:foreign_key", "ALTER TABLE `$table` ADD CONSTRAINT `$foreignKeyName` FOREIGN KEY (`id_payment`) REFERENCES `payment_report` (`id_order`) ON DELETE SET NULL ON UPDATE CASCADE");
    }
};
