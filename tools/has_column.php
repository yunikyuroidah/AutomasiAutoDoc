<?php
require __DIR__ . '/../backend/config.php';

$table = $argv[1] ?? '';
$column = $argv[2] ?? '';
if ($table === '' || $column === '') {
    fwrite(STDERR, "Usage: php tools/has_column.php <table> <column>\n");
    exit(1);
}

$db = get_db_connection();
$safeTable = $db->real_escape_string($table);
$safeColumn = $db->real_escape_string($column);
$result = $db->query("SHOW COLUMNS FROM `{$safeTable}` LIKE '{$safeColumn}'");
$row = $result->fetch_assoc();

echo $row ? '1' : '0';
