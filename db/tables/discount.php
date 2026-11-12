<?php

return [
    'create' => <<<SQL
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        code varchar(2000) NULL,
        price varchar(200) NULL,
        limituse varchar(200) NULL,
        limitused varchar(200) NULL
        SQL,
    'columns' => [
        ['limituse', null, 'VARCHAR(200)'],
        ['limitused', null, 'VARCHAR(200)'],
    ],
];
