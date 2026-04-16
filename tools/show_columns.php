<?php
require __DIR__ . '/../backend/config.php';

$table = $argv[1] ?? '';
if ($table === '') {
    fwrite(STDERR, "Usage: php tools/show_columns.php <table>\n");
    exit(1);
}

$db = get_db_connection();
$result = $db->query('SHOW COLUMNS FROM `' . $db->real_escape_string($table) . '`');
$index = 1;
while ($row = $result->fetch_assoc()) {
    echo $index++, '. ', $row['Field'], ' (', $row['Type'], ')', PHP_EOL;
}
