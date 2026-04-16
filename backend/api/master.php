<?php
declare(strict_types=1);

require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/http.php';
require __DIR__ . '/../config.php';

require_auth(true);

$entity = $_GET['entity'] ?? '';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    $db = get_db_connection();
    switch ($entity) {
        case 'bendahara':
        case 'kepdin':
        case 'pembuat_komitmen':
        case 'pptk':
        case 'kabid_ppa':
        case 'penyedia':
            handleManagedMaster($db, $method, $entity);
            break;
        default:
            json_response(['error' => 'Unknown entity'], 400);
    }
} catch (Throwable $th) {
    error_log('[MASTER_API] ' . $th->getMessage());
    json_response(['error' => 'Server error'], 500);
}

function handleManagedMaster(mysqli $db, string $method, string $entity): void
{
    $map = [
        'bendahara' => [
            'table' => 'bendahara',
            'primary_key' => 'id_bendahara',
            'label' => 'Bendahara',
            'order_by' => 'nama_bendahara',
            'allow_create' => false,
            'allow_delete' => false,
            'fields' => [
                'nama' => ['label' => 'Nama bendahara', 'required' => true, 'column' => 'nama_bendahara'],
                'nip' => ['label' => 'NIP', 'required' => true, 'column' => 'nip_bendahara'],
            ],
        ],
        'kepdin' => [
            'table' => 'kepdin',
            'primary_key' => 'id_kepdin',
            'label' => 'Kepala dinas',
            'order_by' => 'nama_kepdin',
            'allow_create' => false,
            'allow_delete' => false,
            'fields' => [
                'nama' => ['label' => 'Nama kepala dinas', 'required' => true, 'column' => 'nama_kepdin'],
                'nip' => ['label' => 'NIP', 'required' => true, 'column' => 'nip_kepdin'],
                'keterangan' => ['label' => 'Catatan', 'required' => false, 'column' => 'keterangan_kepdin'],
            ],
        ],
        'pembuat_komitmen' => [
            'table' => 'pembuat_komitmen',
            'primary_key' => 'id_ppk',
            'label' => 'PPK',
            'order_by' => 'nama_pembuat_komitmen',
            'allow_create' => true,
            'allow_delete' => true,
            'fields' => [
                'nama' => ['label' => 'Nama PPK', 'required' => true, 'column' => 'nama_pembuat_komitmen'],
                'nip' => ['label' => 'NIP', 'required' => true, 'column' => 'nip_pembuat_komitmen'],
                'keterangan' => ['label' => 'Catatan', 'required' => false, 'column' => 'keterangan_pembuat_komitmen'],
            ],
        ],
        'pptk' => [
            'table' => 'pptk',
            'primary_key' => 'id_pptk',
            'label' => 'PPTK',
            'order_by' => 'nama_pptk',
            'allow_create' => false,
            'allow_delete' => false,
            'fields' => [
                'nama' => ['label' => 'Nama PPTK', 'required' => true, 'column' => 'nama_pptk'],
                'nip' => ['label' => 'NIP', 'required' => true, 'column' => 'nip_pptk'],
            ],
        ],
        'kabid_ppa' => [
            'table' => 'kabid_ppa',
            'primary_key' => 'id_kabid',
            'label' => 'Kabid PPA',
            'order_by' => 'nama_kabid_ppa',
            'allow_create' => false,
            'allow_delete' => false,
            'fields' => [
                'nama' => ['label' => 'Nama Kabid PPA', 'required' => true, 'column' => 'nama_kabid_ppa'],
                'nip' => ['label' => 'NIP', 'required' => true, 'column' => 'nip_kabid_ppa'],
            ],
        ],
        'penyedia' => [
            'table' => 'penyedia',
            'primary_key' => 'id_penyedia',
            'label' => 'Penyedia',
            'order_by' => 'nama_penyedia',
            'allow_create' => false,
            'allow_delete' => false,
            'fields' => [
                'nama' => ['label' => 'Nama penyedia', 'required' => true, 'column' => 'nama_penyedia'],
                'nama_orang' => ['column' => 'nama_orang', 'label' => 'Nama Penanggung Jawab', 'required' => true],
                'keterangan' => ['label' => 'Catatan', 'required' => false, 'column' => 'keterangan'],
                'alamat' => ['label' => 'Alamat', 'required' => false, 'column' => 'alamat'],
            ],
        ],
    ];

    if (!isset($map[$entity])) {
        json_response(['error' => 'Tidak dikenal'], 400);
    }

    $config = $map[$entity];
    $table = $config['table'];
    $fields = $config['fields'];
    $orderBy = $config['order_by'];
    $primaryKey = $config['primary_key'] ?? 'id';

    if ($method === 'GET') {
        $columns = buildMasterSelectColumns($primaryKey, $fields);
        $sql = sprintf('SELECT %s FROM %s ORDER BY %s', implode(', ', $columns), $table, $orderBy);
        $result = $db->query($sql);
        json_response(['data' => $result->fetch_all(MYSQLI_ASSOC)]);
    }

    if ($method === 'POST') {
        if (empty($config['allow_create'])) {
            json_response(['error' => 'Metode tidak diizinkan'], 405);
        }

        $payload = read_json_input();
        $values = extractMasterValues($payload, $fields);
        $columns = array_keys($values);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $sql = sprintf('INSERT INTO %s (%s) VALUES (%s)', $table, implode(', ', $columns), $placeholders);
        $stmt = $db->prepare($sql);
        bindStatementParams($stmt, str_repeat('s', count($columns)), array_values($values));
        $stmt->execute();
        $newId = $stmt->insert_id;
        $stmt->close();

        json_response(['message' => sprintf('%s ditambahkan', $config['label']), 'id' => $newId], 201);
    }

    if ($method === 'PUT') {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            json_response(['error' => 'Invalid id'], 422);
        }

        $payload = read_json_input();
        $values = extractMasterValues($payload, $fields);
        $setClause = implode(', ', array_map(static fn ($column) => sprintf('%s = ?', $column), array_keys($values)));
        $sql = sprintf('UPDATE %s SET %s WHERE %s = ?', $table, $setClause, $primaryKey);
        $stmt = $db->prepare($sql);
        $valuesWithId = array_merge(array_values($values), [$id]);
        bindStatementParams($stmt, str_repeat('s', count($values)) . 'i', $valuesWithId);
        $stmt->execute();
        $stmt->close();

        json_response(['message' => sprintf('%s diperbarui', $config['label'])]);
    }

    if ($method === 'DELETE') {
        if (empty($config['allow_delete'])) {
            json_response(['error' => 'Metode tidak diizinkan'], 405);
        }

        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            json_response(['error' => 'Invalid id'], 422);
        }

        $stmt = $db->prepare(sprintf('DELETE FROM %s WHERE %s = ?', $table, $primaryKey));
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();

        json_response(['message' => sprintf('%s dihapus', $config['label'])]);
    }

    json_response(['error' => 'Unsupported method'], 405);
}

function buildMasterSelectColumns(string $primaryKey, array $fields): array
{
    $columns = [sprintf('%s AS id', $primaryKey)];
    foreach ($fields as $alias => $meta) {
        $column = $meta['column'] ?? $alias;
        $columns[] = sprintf('%s AS %s', $column, $alias);
    }

    return $columns;
}

function extractMasterValues(array $payload, array $fields): array
{
    $values = [];
    foreach ($fields as $alias => $meta) {
        $column = $meta['column'] ?? $alias;
        $raw = $payload[$alias] ?? '';
        $value = is_string($raw) ? trim($raw) : trim((string) $raw);

        if (($meta['required'] ?? false) && $value === '') {
            $label = $meta['label'] ?? ucfirst(str_replace('_', ' ', $alias));
            throw new InvalidArgumentException(sprintf('%s wajib diisi', $label));
        }

        $values[$column] = $value;
    }

    return $values;
}

function bindStatementParams(mysqli_stmt $stmt, string $types, array $values): void
{
    $params = [$types];
    foreach ($values as $key => $value) {
        $params[] = &$values[$key];
    }

    call_user_func_array([$stmt, 'bind_param'], $params);
}
