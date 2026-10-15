<?php

/**
 * Settings rows for the RentoPay gateway slot, on installations that already exist.
 */

return static function (PDO $pdo, Schema $schema): void {
    if (!$schema->tableExists('PaySetting')) {
        return;
    }

    $defaults = [
        'rentopay_status' => 'offrentopay',
        'rentopay_api_key' => '0',
        'rentopay_secret_key' => '0',
        'minbalancerentopay' => '20000',
        'maxbalancerentopay' => '1000000',
        'chashbackrentopay' => '0',
        'rentopay_payer_split_percent' => 'default',
        'helprentopay' => '2',
    ];

    $stmt = $pdo->prepare('INSERT IGNORE INTO PaySetting (NamePay, ValuePay) VALUES (:name, :value)');
    foreach ($defaults as $name => $value) {
        $stmt->execute([':name' => $name, ':value' => $value]);
    }
};
