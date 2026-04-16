<?php
require __DIR__ . '/../backend/config.php';

$table = $argv[1] ?? '';
if ($table === '') {
    fwrite(STDERR, "Usage: php tools/dump_table.php <table>\n");
    exit(1);
}

$db = get_db_connection();
$result = $db->query('SELECT * FROM `' . $db->real_escape_string($table) . '` LIMIT 5');
while ($row = $result->fetch_assoc()) {
    print_r($row);
}
