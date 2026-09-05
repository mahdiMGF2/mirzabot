<?php

/**
 * The AbanGateway daily cap, as a setting the admin can see and change.
 *
 * The first version of this gateway carried the ceiling in `index.php` as a
 * literal `1000000`, copied from `iranpay3`. Two things were wrong with it.
 *
 * It counted every `Payment_report` row for the day rather than the paid ones,
 * and that row is written *before* the payment link is requested — so a buyer
 * who opened the gateway and walked away still spent the shop's allowance. A
 * shop selling a 272,000 plan closed its own door after four taps, whether or
 * not any of them paid.
 *
 * And there was no way to see the number, let alone raise it. `0` here means
 * no cap, which is the behaviour a shop expects from a gateway it has switched
 * on deliberately; an admin who wants a ceiling now has a field for it.
 */

return static function (PDO $pdo, Schema $schema): void {
    if (!$schema->tableExists('PaySetting')) {
        return;
    }

    // INSERT IGNORE, like 008: an admin who has already set this keeps it.
    $stmt = $pdo->prepare('INSERT IGNORE INTO PaySetting (NamePay, ValuePay) VALUES (:name, :value)');
    $stmt->execute([':name' => 'dailylimitiranpay4', ':value' => '0']);
};
