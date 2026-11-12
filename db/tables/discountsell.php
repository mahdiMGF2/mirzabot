<?php

return [
    'create' => <<<SQL
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        codeDiscount varchar(1000) NOT NULL,
        price varchar(200) NOT NULL,
        limitDiscount varchar(500) NOT NULL,
        agent varchar(500) NOT NULL,
        usefirst varchar(100) NOT NULL,
        useuser varchar(100) NOT NULL,
        code_product varchar(100) NOT NULL,
        code_panel varchar(100) NOT NULL,
        time varchar(100) NOT NULL,
        type varchar(100) NOT NULL,
        usedDiscount varchar(500) NOT NULL
        SQL,
    'columns' => [
        ['agent', null, 'VARCHAR(100)'],
        ['usefirst', null, 'VARCHAR(100)'],
        ['useuser', null, 'VARCHAR(100)'],
        ['code_product', null, 'VARCHAR(100)'],
        ['code_panel', null, 'VARCHAR(100)'],
        ['time', null, 'VARCHAR(100)'],
        ['type', null, 'VARCHAR(100)'],
    ],
];
