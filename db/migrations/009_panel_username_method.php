<?php

return static function (PDO $pdo, Schema $schema): void {
    if (!$schema->tableExists('marzban_panel')) {
        return;
    }
    $rows = $pdo->query('SELECT id, MethodUsername FROM marzban_panel')->fetchAll(PDO::FETCH_ASSOC);
    $update = $pdo->prepare('UPDATE marzban_panel SET MethodUsername = ? WHERE id = ?');
    foreach ($rows as $row) {
        $key = usernameMethodKey($row['MethodUsername'], null);
        if ($key === null || $key === $row['MethodUsername']) {
            continue;
        }
        $update->execute([$key, $row['id']]);
    }
};
