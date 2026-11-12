<?php

require_once __DIR__ . '/../function.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../botapi.php';
require_once __DIR__ . '/Schema.php';

global $pdo, $adminnumber;

$schema = new Schema($pdo, [
    'adminnumber' => $adminnumber,
    'textbotlang' => static fn() => languagechange(),
]);

$renames = require __DIR__ . '/renames.php';
$tableFiles = scandir(__DIR__ . '/tables') ?: [];
foreach (array_keys($renames) as $oldTable) {
    if (in_array("$oldTable.php", $tableFiles, true)) {
        @unlink(__DIR__ . "/tables/$oldTable.php");
    }
}
$schema->renameTables($renames);
$schema->applyTables(__DIR__ . '/tables', require __DIR__ . '/tables.php');
$schema->runMigrations(__DIR__ . '/migrations');
$schema->applyIndexes(require __DIR__ . '/indexes.php');

return $schema;
