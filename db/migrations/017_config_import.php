<?php

return static function (PDO $pdo, Schema $schema): void {
    if (!$schema->tableExists('config')) {
        return;
    }
    $sources = [
        'paysetting' => ['pay', 'NamePay', 'ValuePay'],
        'shopsetting' => ['shop', 'Namevalue', 'value'],
        'topicid' => ['topic', 'report', 'idreport'],
    ];
    $imported = [];
    foreach ($sources as $table => [$scope, $nameColumn, $valueColumn]) {
        if ($schema->tableExists($table)) {
            $pdo->prepare("INSERT INTO config (scope, name, value) SELECT ?, `$nameColumn`, `$valueColumn` FROM `$table` ON DUPLICATE KEY UPDATE value = VALUES(value)")->execute([$scope]);
            $imported[] = $table;
        }
    }
    if ($schema->tableExists('setting')) {
        $row = $pdo->query('SELECT * FROM `setting` LIMIT 1')->fetch(PDO::FETCH_ASSOC) ?: [];
        unset($row['id']);
        $statement = $pdo->prepare("INSERT INTO config (scope, name, value) VALUES ('setting', ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
        foreach ($row as $name => $value) {
            $statement->execute([$name, $value]);
        }
        $imported[] = 'setting';
    }
    if ($imported) {
        $pdo->exec('DROP TABLE `' . implode('`, `', $imported) . '`');
        error_log('[db:migration:config_import] moved ' . implode(', ', $imported) . ' into config');
    }
};
