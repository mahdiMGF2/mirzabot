<?php

/**
 * Settings rows for the AbanGateway slot, on installations that already exist.
 *
 * `db/tables/PaySetting.php` seeds only when the table is created, and
 * `update()` is a plain UPDATE — on a bot installed before this gateway
 * existed it matches zero rows, so an admin pasting their key would see it
 * accepted and saved nowhere. That is the failure this migration prevents.
 *
 * `statusiranpay4` deliberately lands on `offiranpay4`. Defaulting a gateway
 * to on for installs that have never been configured shows buyers a button
 * that cannot take their money.
 */

return static function (PDO $pdo, Schema $schema): void {
    if (!$schema->tableExists('paysetting')) {
        return;
    }

    $defaults = [
        'apiiranpay4' => '0',
        'endpointiranpay4' => '0',
        'statusiranpay4' => 'offiranpay4',
        'minbalanceiranpay4' => '20000',
        'maxbalanceiranpay4' => '1000000',
        'chashbackiranpay4' => '0',
        'helpiranpay4' => '2',
    ];

    // INSERT IGNORE rather than a delete-then-insert: an admin who configured
    // this gateway before upgrading keeps what they configured.
    $stmt = $pdo->prepare('INSERT IGNORE INTO paysetting (NamePay, ValuePay) VALUES (:name, :value)');
    foreach ($defaults as $name => $value) {
        $stmt->execute([':name' => $name, ':value' => $value]);
    }
};
