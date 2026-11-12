<?php

$notifications = json_encode(['volume' => false, 'time' => false]);

return [
    'create' => <<<SQL
        id_invoice varchar(200) PRIMARY KEY,
        id_user varchar(200) NULL,
        username varchar(300) NULL,
        Service_location varchar(300) NULL,
        time_sell VARCHAR(200) NULL,
        name_product varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
        price_product varchar(200) NULL,
        Volume varchar(200) NULL,
        Service_time varchar(200) NULL,
        uuid TEXT NULL,
        note varchar(500) NULL,
        user_info TEXT NULL,
        bottype varchar(200) NULL,
        refral varchar(100) NULL,
        time_cron varchar(100) NULL,
        notifctions TEXT NOT NULL,
        Status varchar(200) NULL,
        id_payment varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
        payable_amount varchar(20) NULL,
        discount_code varchar(200) NULL
        SQL,
    'columns' => [
        ['time_sell', null, 'VARCHAR(200)'],
        ['uuid', null, 'TEXT'],
        ['note', null, 'VARCHAR(700)'],
        ['user_info', null, 'TEXT'],
        ['bottype', null, 'VARCHAR(200)'],
        ['refral', null, 'VARCHAR(100)'],
        ['time_cron', null, 'VARCHAR(100)'],
        ['notifctions', $notifications, 'TEXT NOT NULL'],
        ['Status', null, 'VARCHAR(100)'],
        ['id_payment', null, 'VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL'],
        ['payable_amount', null, 'VARCHAR(20) NULL'],
        ['discount_code', null, 'VARCHAR(200) NULL'],
    ],
];
