<?php

return [
    'options' => 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
    'create' => <<<SQL
        item_key VARCHAR(100) NOT NULL PRIMARY KEY,
        last_bytes BIGINT NOT NULL DEFAULT 0,
        changed_at INT NOT NULL DEFAULT 0,
        notice_at INT NOT NULL DEFAULT 0,
        notice_count INT NOT NULL DEFAULT 0
        SQL,
];
