<?php

return [
    'options' => 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
    'create' => <<<SQL
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        id_user varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
        id_order varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
        time varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
        at_updated varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
        price varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
        dec_not_confirmed TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
        Payment_Method varchar(400) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
        payment_Status varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
        bottype varchar(300) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
        message_id INT NULL,
        id_invoice varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL
        SQL,
    'ensureUtf8mb4' => true,
    'columns' => [
        ['at_updated', null, 'VARCHAR(200)'],
        ['Payment_Method', null, 'VARCHAR(200)'],
        ['bottype', null, 'VARCHAR(300)'],
        ['message_id', null, 'INT'],
        ['id_invoice', 'none', 'VARCHAR(400)'],
    ],
];
