<?php

/**
 * Settings rows for Variza automated card-to-card gateway, on installations that already exist.
 *
 * `db/tables/PaySetting.php` seeds only when the table is created, and
 * `update()` is a plain UPDATE — on a bot installed before this gateway
 * existed it matches zero rows, so an admin pasting their key would see it
 * accepted and saved nowhere. That is the failure this migration prevents.
 *
 * `variza_status` deliberately lands on `offvariza`. Defaulting a gateway
 * to on for installs that have never been configured shows buyers a button
 * that cannot take their money.
 */

return static function (PDO $pdo, Schema $schema): void {
    if (!$schema->tableExists('PaySetting')) {
        return;
    }

    $defaults = [
        'variza_status' => 'offvariza',
        'variza_api_token' => '0',
        'variza_webhook_secret' => '0',
        'minbalancevariza' => '20000',
        'maxbalancevariza' => '1000000',
        'chashbackvariza' => '0',
        'helpvariza' => '2',
    ];

    // INSERT IGNORE rather than a delete-then-insert: an admin who configured
    // this gateway before upgrading keeps what they configured.
    $stmt = $pdo->prepare('INSERT IGNORE INTO PaySetting (NamePay, ValuePay) VALUES (:name, :value)');
    foreach ($defaults as $name => $value) {
        $stmt->execute([':name' => $name, ':value' => $value]);
    }
};
