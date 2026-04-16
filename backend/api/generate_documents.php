<?php
declare(strict_types=1);

require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/http.php';
require __DIR__ . '/../config.php';
require __DIR__ . '/../services/WorkspaceData.php';
require __DIR__ . '/../services/DocumentGenerator.php';

require_auth(true);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

try {
    $db = get_db_connection();

    // 1. Baca JSON Input (dari panel.js)
    $rawInput = file_get_contents('php://input');
    $input = [];
    if ($rawInput) {
        $input = json_decode($rawInput, true) ?? [];
    }

    // 2. Ambil ID Dokumen
    $generalId = 0;
    // Cek query param ?id=...
    if (isset($_GET['id'])) {
        $generalId = (int) $_GET['id'];
    }
    // Cek body JSON { "id": ... }
    if ($generalId <= 0 && isset($input['id'])) {
        $generalId = (int) $input['id'];
    }
    // Fallback ke data terakhir (jika ada)
    if ($generalId <= 0) {
        $latest = fetchLatestGeneral($db);
        if ($latest) {
            $generalId = (int) $latest['id'];
        }
    }

    if ($generalId <= 0) {
        throw new InvalidArgumentException('ID Dokumen tidak ditemukan.');
    }

    // 3. Ambil data utama dari DB
    $workspace = WorkspaceData::fetch($db, $generalId);

    // 4. Masukkan data Checklist ke workspace (agar bisa dibaca Exporter)
    $checklistData = $input['checklist'] ?? [];
    if (!empty($checklistData)) {
        $workspace['transient_checklist'] = $checklistData;
    }

    // 5. Generate
    $files = DocumentGenerator::buildStatuses($generalId, $workspace);

    json_response([
        'data' => [
            'general_id' => $generalId,
            'files' => $files,
        ],
        'message' => 'Dokumen berhasil diproses',
    ]);

} catch (InvalidArgumentException $invalid) {
    json_response(['error' => $invalid->getMessage()], 422);
} catch (Throwable $th) {
    error_log('[GENERATE_WORKSPACE] ' . $th->getMessage());
    json_response(['error' => 'Gagal memproses permintaan: ' . $th->getMessage()], 500);
}

function fetchLatestGeneral(mysqli $db): ?array
{
    $result = $db->query('SELECT id_general AS id, objek, tanggal FROM general ORDER BY id_general DESC LIMIT 1');
    $row = $result ? $result->fetch_assoc() : null;

    return $row ?: null;
}