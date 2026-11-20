<?php

return [
    'create' => <<<SQL
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        code_product varchar(200) NULL,
        name_product varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
        price_product varchar(2000) NULL,
        Volume_constraint varchar(2000) NULL,
        Location varchar(200) NULL,
        Service_time varchar(200) NULL,
        agent varchar(100) NULL,
        note TEXT NULL,
        data_limit_reset varchar(200) NULL,
        one_buy_status varchar(20) NOT NULL,
        inbounds TEXT NULL,
        proxies TEXT NULL,
        category varchar(400) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
        hide_panel TEXT NOT NULL,
        status_product varchar(20) NOT NULL DEFAULT 'active',
        display_order INT NOT NULL DEFAULT 0
        SQL,
    'columns' => [
        ['code_product', null, 'varchar(50)'],
        ['Location', null, 'VARCHAR(200)'],
        ['agent', 'f', 'varchar(50)'],
        ['note', '', 'TEXT'],
        ['data_limit_reset', 'no_reset', 'varchar(100)'],
        ['one_buy_status', '0', 'VARCHAR(20)'],
        ['inbounds', null, 'TEXT'],
        ['proxies', null, 'TEXT'],
        ['category', null, 'varchar(200)'],
        ['hide_panel', '{}', 'TEXT'],
        ['status_product', 'active', "VARCHAR(20) NOT NULL DEFAULT 'active'"],
        ['display_order', null, 'INT NOT NULL DEFAULT 0'],
        ['limit_ip', '0', 'INT NOT NULL DEFAULT 0'],
    ],
];
