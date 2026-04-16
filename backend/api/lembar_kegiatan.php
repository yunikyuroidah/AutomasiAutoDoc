<?php
declare(strict_types=1);

require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/http.php';
require __DIR__ . '/../config.php';

require_auth(true);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    $db = get_db_connection();

    if ($method !== 'GET') {
        json_response(['error' => 'Metode tidak diizinkan'], 405);
    }

    handleGet($db);
} catch (Throwable $th) {
    error_log('[LEMBAR_API] ' . $th->getMessage());
    json_response(['error' => 'Server error'], 500);
}

function handleGet(mysqli $db): void
{
    $id = (int) ($_GET['id'] ?? 0);

    if ($id > 0) {
        $header = fetchLembarById($db, $id);
        if (!$header) {
            json_response(['error' => 'Data tidak ditemukan'], 404);
        }

        $specs = fetchSpesifikasiByGeneral($db, (int) $header['general_id']);

        json_response([
            'data' => [
                'header' => $header,
                'spesifikasi_anggaran' => $specs,
            ],
        ]);
    }

    $result = $db->query('SELECT
            lk.id_lembar AS id,
            lk.id_general AS general_id,
            g.objek,
            g.tanggal,
            lk.daftar,
            lk.program,
            lk.kegiatan,
            lk.kode_rekening,
            lk.sumber_dana,
            lk.bulan,
            kep.nama_kepdin,
            ben.nama_bendahara,
            pptk.nama_pptk
        FROM lembar_kegiatan lk
        LEFT JOIN general g ON lk.id_general = g.id_general
        LEFT JOIN kepdin kep ON lk.id_kepdin = kep.id_kepdin
        LEFT JOIN bendahara ben ON lk.id_bendahara = ben.id_bendahara
        LEFT JOIN pptk ON lk.id_pptk = pptk.id_pptk
        ORDER BY lk.id_lembar DESC');

    if (!$result) {
        throw new RuntimeException($db->error);
    }

    json_response(['data' => $result->fetch_all(MYSQLI_ASSOC)]);
}

function fetchLembarById(mysqli $db, int $id): ?array
{
    $sql = 'SELECT
            lk.id_lembar AS id,
            lk.id_general AS general_id,
            lk.bulan,
            lk.daftar,
            lk.program,
            lk.kegiatan,
            lk.sub_kegiatan,
            lk.kode_rekening,
            lk.sumber_dana,
            lk.id_kepdin AS kepdin_id,
            lk.id_bendahara AS bendahara_id,
            lk.id_pptk AS pptk_id,
            g.objek AS general_objek,
            g.tanggal AS general_tanggal,
            kep.nama_kepdin,
            kep.nip_kepdin,
            ben.nama_bendahara,
            ben.nip_bendahara,
            pptk.nama_pptk,
            pptk.nip_pptk
        FROM lembar_kegiatan lk
        LEFT JOIN general g ON lk.id_general = g.id_general
        LEFT JOIN kepdin kep ON lk.id_kepdin = kep.id_kepdin
        LEFT JOIN bendahara ben ON lk.id_bendahara = ben.id_bendahara
        LEFT JOIN pptk ON lk.id_pptk = pptk.id_pptk
        WHERE lk.id_lembar = ?
        LIMIT 1';

    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ?: null;
}

function fetchSpesifikasiByGeneral(mysqli $db, int $generalId): array
{
    if ($generalId <= 0) {
        return [];
    }

    $stmt = $db->prepare('SELECT
            id_spesifikasi AS id,
            spesifikasi,
            spesifikasi_jumlah,
            satuan_ukuran,
            harga_satuan,
            pagu_anggaran
        FROM spesifikasi_anggaran
        WHERE id_general = ?
        ORDER BY id_spesifikasi ASC');
    $stmt->bind_param('i', $generalId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return array_map(static function (array $row): array {
        $volume = formatSpecVolume($row['spesifikasi_jumlah'] ?? '', $row['satuan_ukuran'] ?? '');

        return [
            'id' => (int) $row['id'],
            'spesifikasi' => $row['spesifikasi'],
            'spesifikasi_jumlah' => $row['spesifikasi_jumlah'],
            'satuan_ukuran' => $row['satuan_ukuran'],
            'volume_label' => $volume,
            'harga_satuan' => (float) $row['harga_satuan'],
            'pagu_anggaran' => (float) $row['pagu_anggaran'],
        ];
    }, $rows);
}

function formatSpecVolume($jumlah, $satuan): string
{
    $jumlah = is_numeric($jumlah) ? (string) $jumlah : trim((string) $jumlah);
    $satuan = trim((string) $satuan);

    if ($jumlah === '' && $satuan === '') {
        return '';
    }

    if ($satuan === '') {
        return $jumlah;
    }

    return trim($jumlah . ' ' . $satuan);
}
