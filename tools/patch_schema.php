<?php
require __DIR__ . '/../backend/config.php';

$db = get_db_connection();

function column_exists(mysqli $db, string $table, string $column): bool
{
    $safeTable = $db->real_escape_string($table);
    $safeColumn = $db->real_escape_string($column);
    $result = $db->query("SHOW COLUMNS FROM `{$safeTable}` LIKE '{$safeColumn}'");
    return (bool) $result->fetch_assoc();
}

function ensure_column(mysqli $db, string $table, string $column, string $definition, ?string $after = null): void
{
    if (column_exists($db, $table, $column)) {
        return;
    }

    $safeTable = $db->real_escape_string($table);
    $safeColumn = $db->real_escape_string($column);
    $afterClause = $after ? ' AFTER `' . $db->real_escape_string($after) . '`' : '';
    $db->query("ALTER TABLE `{$safeTable}` ADD COLUMN `{$safeColumn}` {$definition}{$afterClause}");
    echo "Added column {$table}.{$column}\n";
}

function rename_column(mysqli $db, string $table, string $from, string $to, string $definition): void
{
    if (!column_exists($db, $table, $from) || column_exists($db, $table, $to)) {
        return;
    }

    $safeTable = $db->real_escape_string($table);
    $safeFrom = $db->real_escape_string($from);
    $safeTo = $db->real_escape_string($to);
    $db->query("ALTER TABLE `{$safeTable}` CHANGE COLUMN `{$safeFrom}` `{$safeTo}` {$definition}");
    echo "Renamed column {$table}.{$from} -> {$to}\n";
}

rename_column($db, 'kwitansi', 'tanggal', 'tanggal_pembayaran', 'DATE NOT NULL');
ensure_column($db, 'kwitansi', 'jumlah_uang', 'DECIMAL(18,2) NOT NULL DEFAULT 0', 'tanggal_pembayaran');
ensure_column($db, 'berita_acara', 'id_penyedia', 'INT NULL', 'id_ppk');
ensure_column($db, 'nota_dinas', 'keperluan', 'TEXT NULL', 'perihal');
ensure_column($db, 'nota_dinas', 'tahun_anggaran', 'YEAR NULL', 'jumlah_dpa');

$db->close();

echo "Schema patch completed.\n";
