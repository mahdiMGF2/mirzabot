<?php

return [
    'create' => <<<SQL
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        code varchar(2000) NULL,
        id_user varchar(200) NULL
        SQL,
];
