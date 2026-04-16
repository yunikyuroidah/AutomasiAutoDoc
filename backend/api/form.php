<?php
declare(strict_types=1);

// Load dependencies
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/http.php';
require __DIR__ . '/../config.php';

// Pastikan user login
require_auth(true);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    $db = get_db_connection();
    
    // ==========================================
    // 1. GET: AMBIL DATA (LOAD FORM)
    // ==========================================
    if ($method === 'GET') {
        $payload = fetchWorkspacePayload($db);
        if (!headers_sent()) header('Content-Type: application/json');
        echo json_encode(['data' => $payload]);
        exit;
    }

    // ==========================================
    // 2. POST: SIMPAN DATA (SAVE)
    // ==========================================
    if ($method === 'POST' || $method === 'PUT') {
        // Gunakan helper read_json_input jika ada
        $payload = function_exists('read_json_input') ? read_json_input() : json_decode(file_get_contents('php://input'), true);

        $db->begin_transaction();
        $inTransaction = true;

        $generalId = upsertGeneral($db, $payload['general'] ?? []);
        upsertDokumenPengadaan($db, $generalId, $payload['dokumen_pengadaan'] ?? []);
        upsertLembarKegiatan($db, $generalId, $payload['lembar_kegiatan'] ?? []);
        upsertBeritaAcara($db, $generalId, $payload['berita_acara'] ?? []);
        upsertKwitansi($db, $generalId, $payload['kwitansi'] ?? []);
        upsertNotaDinas($db, $generalId, $payload['nota_dinas'] ?? []);
        
        // FUNGSI INI SUDAH DIUPDATE UNTUK MENYIMPAN TELP_KANTOR
        upsertSptpd($db, $generalId, $payload['sptpd'] ?? []);
        
        upsertSpesifikasi($db, $generalId, $payload['spesifikasi_anggaran'] ?? []);

        $db->commit();
        $inTransaction = false;

        if (!headers_sent()) header('Content-Type: application/json');
        echo json_encode(['message' => 'Data berhasil disimpan', 'id' => $generalId]);
        exit;
    }

} catch (Throwable $th) {
    if (isset($inTransaction) && $inTransaction) $db->rollback();
    error_log('[API ERROR form.php] ' . $th->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Terjadi kesalahan: ' . $th->getMessage()]);
    exit;
}

// ============================================================================
// FUNGSI PENGAMBILAN DATA (FETCH)
// ============================================================================

function fetchWorkspacePayload(mysqli $db): array
{
    // 1. Ambil General ID Terakhir
    $stmt = $db->query("SELECT id_general as id, objek, tanggal FROM general ORDER BY id_general DESC LIMIT 1");
    $general = $stmt->fetch_assoc();

    $data = [
        'general' => $general ?: null,
        'masters' => fetchMasters($db), // PENTING: Ambil Master Data dengan Mapping Benar
        'spesifikasi_anggaran' => []
    ];

    if (!$general) {
        // Data kosong, return struktur kosong agar JS tidak error
        $data['dokumen_pengadaan'] = [];
        $data['lembar_kegiatan'] = [];
        $data['berita_acara'] = [];
        $data['kwitansi'] = [];
        $data['nota_dinas'] = [];
        $data['sptpd'] = [];
        return $data;
    }

    $generalId = (int)$general['id'];

    // 2. Ambil Data Tabel (Query Manual agar Alias Aman)
    
    // Dokumen Pengadaan
    $data['dokumen_pengadaan'] = fetchOne($db, "SELECT 
        nama_paket, kode_rup, nomor_dpp, pagu_anggaran, tanggal_mulai, tanggal_selesai, 
        id_ppk as pembuat_komitmen_id 
        FROM dokumen_pengadaan WHERE id_general = $generalId");

    // Lembar Kegiatan
    $data['lembar_kegiatan'] = fetchOne($db, "SELECT 
        bulan, daftar, program, kegiatan, sub_kegiatan, kode_rekening, sumber_dana, 
        id_kepdin as kepdin_id, id_bendahara as bendahara_id, id_pptk as pptk_id 
        FROM lembar_kegiatan WHERE id_general = $generalId");

    // Berita Acara (Fixed Alias ID)
    $data['berita_acara'] = fetchOne($db, "SELECT 
        nomor_satuan_kerja, tanggal_satuan_kerja, nomor_sp, tanggal_sp,
        id_kepdin as kepdin_id, id_ppk as pembuat_komitmen_id, id_penyedia as penyedia_id,
        nomor_sk_bahp, tanggal_sk_bahp, nomor_sp_bahp, nomor_kontrak_bahp, tanggal_sp_bahp,
        nomor_sk_bahpa, tanggal_sk_bahpa, nomor_sp_bahpa, tanggal_sp_bahpa,
        keterangan, tanggal_serah_terima, paket_pekerjaan, paket_pekerjaan_administratif
        FROM berita_acara WHERE id_general = $generalId");

    // Kwitansi
    $data['kwitansi'] = fetchOne($db, "SELECT 
        nama_penerima, nama_bank, npwp, norek, jumlah_uang, tanggal_pembayaran, 
        id_kepdin as kepdin_id, id_bendahara as bendahara_id, id_pptk as pptk_id 
        FROM kwitansi WHERE id_general = $generalId");

    // Nota Dinas
    $data['nota_dinas'] = fetchOne($db, "SELECT 
        nomor, perihal, keperluan, jumlah_dpa, tahun_anggaran, 
        id_kabid as kabid_id 
        FROM nota_dinas WHERE id_general = $generalId");

    // SPTPD - (UPDATE: DITAMBAHKAN telp_kantor)
    $data['sptpd'] = fetchOne($db, "SELECT 
        tahun, harga_jual, dasar_pengenaan_pajak, pajak_terhutang, nama_badan_usaha, masa_pajak, pekerjaan, telp_kantor
        FROM sptpd WHERE id_general = $generalId");

    // 3. Spesifikasi
    $resSpec = $db->query("SELECT * FROM spesifikasi_anggaran WHERE id_general = $generalId ORDER BY id_spesifikasi ASC");
    if ($resSpec) {
        while ($row = $resSpec->fetch_assoc()) $data['spesifikasi_anggaran'][] = $row;
    }

    return $data;
}

function fetchOne(mysqli $db, string $query): array
{
    $res = $db->query($query);
    return ($res && $row = $res->fetch_assoc()) ? $row : [];
}

// === FUNGSI FETCH MASTERS YANG SUDAH DIPERBAIKI TOTAL ===
function fetchMasters(mysqli $db): array
{
    $masters = [];

    // 1. KEPALA DINAS (Fix: nip_kepdin, keterangan_kepdin)
    $rows = [];
    $res = $db->query("SELECT id_kepdin as id, nama_kepdin as nama, nip_kepdin as nip, keterangan_kepdin as keterangan FROM kepdin ORDER BY id_kepdin DESC");
    if ($res) while ($r = $res->fetch_assoc()) $rows[] = $r;
    $masters['kepdin'] = $rows;

    // 2. BENDAHARA (Fix: nip_bendahara)
    $rows = [];
    $res = $db->query("SELECT id_bendahara as id, nama_bendahara as nama, nip_bendahara as nip FROM bendahara ORDER BY id_bendahara DESC");
    if ($res) while ($r = $res->fetch_assoc()) $rows[] = $r;
    $masters['bendahara'] = $rows;

    // 3. PPTK (Fix: nip_pptk)
    $rows = [];
    $res = $db->query("SELECT id_pptk as id, nama_pptk as nama, nip_pptk as nip FROM pptk ORDER BY id_pptk DESC");
    if ($res) while ($r = $res->fetch_assoc()) $rows[] = $r;
    $masters['pptk'] = $rows;

    // 4. KABID PPA (Fix: nama_kabid_ppa)
    // Ini yg sebelumnya kosong karena salah nama kolom
    $rows = [];
    $res = $db->query("SELECT id_kabid as id, nama_kabid_ppa as nama, nip_kabid_ppa as nip FROM kabid_ppa ORDER BY id_kabid DESC");
    if ($res) while ($r = $res->fetch_assoc()) $rows[] = $r;
    $masters['kabid_ppa'] = $rows;

    // 5. PPK (Fix: nama_pembuat_komitmen, nip_pembuat_komitmen, keterangan_pembuat_komitmen)
    $rows = [];
    $res = $db->query("SELECT id_ppk as id, nama_pembuat_komitmen as nama, nip_pembuat_komitmen as nip, keterangan_pembuat_komitmen as keterangan FROM pembuat_komitmen ORDER BY id_ppk DESC");
    if ($res) while ($r = $res->fetch_assoc()) $rows[] = $r;
    $masters['pembuat_komitmen'] = $rows;

    // 6. PENYEDIA (Fix: id_penyedia)
    $rows = [];
    $res = $db->query("SELECT id_penyedia as id, nama_penyedia as nama,nama_orang, alamat as alamat_penyedia, keterangan FROM penyedia ORDER BY id_penyedia DESC");
    if ($res) while ($r = $res->fetch_assoc()) $rows[] = $r;
    $masters['penyedia'] = $rows;

    return $masters;
}

// ============================================================================
// FUNGSI SIMPAN (UPSERT)
// ============================================================================

function upsertGeneral(mysqli $db, array $data): int
{
    $objek = trim((string)($data['objek'] ?? ''));
    $tanggal = (string)($data['tanggal'] ?? date('Y-m-d'));
    $existingId = $data['id'] ?? null;
    
    if (!$existingId) {
        $res = $db->query("SELECT id_general FROM general ORDER BY id_general DESC LIMIT 1");
        if ($row = $res->fetch_assoc()) $existingId = $row['id_general'];
    }

    if ($existingId) {
        $stmt = $db->prepare('UPDATE general SET objek = ?, tanggal = ? WHERE id_general = ?');
        $stmt->bind_param('ssi', $objek, $tanggal, $existingId);
        $stmt->execute();
        return (int)$existingId;
    } else {
        $stmt = $db->prepare('INSERT INTO general (objek, tanggal) VALUES (?, ?)');
        $stmt->bind_param('ss', $objek, $tanggal);
        $stmt->execute();
        return (int)$stmt->insert_id;
    }
}

function upsertDokumenPengadaan(mysqli $db, int $generalId, array $data): void
{
    $nama = trim((string)($data['nama_paket'] ?? ''));
    $rup = trim((string)($data['kode_rup'] ?? ''));
    $nomorDpp = trim((string)($data['nomor_dpp'] ?? ''));
    $pagu = (float)($data['pagu_anggaran'] ?? 0);
    $mulai = (string)($data['tanggal_mulai'] ?? '');
    $selesai = (string)($data['tanggal_selesai'] ?? '');
    $mulai = ($mulai === '') ? null : $mulai;
    $selesai = ($selesai === '') ? null : $selesai;
    $ppk = (int)($data['pembuat_komitmen_id'] ?? 0);

    $existing = findIdByGeneral($db, 'dokumen_pengadaan', $generalId);

    if ($existing) {
        $stmt = $db->prepare('UPDATE dokumen_pengadaan SET nama_paket=?, kode_rup=?, nomor_dpp=?, pagu_anggaran=?, tanggal_mulai=?, tanggal_selesai=?, id_ppk=? WHERE id_dokumen=?');
        $stmt->bind_param('sssdssii', $nama, $rup, $nomorDpp, $pagu, $mulai, $selesai, $ppk, $existing);
    } else {
        $stmt = $db->prepare('INSERT INTO dokumen_pengadaan (id_general, nama_paket, kode_rup, nomor_dpp, pagu_anggaran, tanggal_mulai, tanggal_selesai, id_ppk) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('isssdssi', $generalId, $nama, $rup, $nomorDpp, $pagu, $mulai, $selesai, $ppk);
    }
    $stmt->execute();
}

function upsertLembarKegiatan(mysqli $db, int $generalId, array $data): void
{
    $bulan = (string)($data['bulan'] ?? '');
    $daftar = (string)($data['daftar'] ?? '');
    $prog = (string)($data['program'] ?? '');
    $keg = (string)($data['kegiatan'] ?? '');
    $sub = (string)($data['sub_kegiatan'] ?? '');
    $rek = (string)($data['kode_rekening'] ?? '');
    $sumber = (string)($data['sumber_dana'] ?? '');
    $kepdin = (int)($data['kepdin_id'] ?? 0);
    $bendahara = (int)($data['bendahara_id'] ?? 0);
    $pptk = (int)($data['pptk_id'] ?? 0);

    $existing = findIdByGeneral($db, 'lembar_kegiatan', $generalId);

    if ($existing) {
        $stmt = $db->prepare('UPDATE lembar_kegiatan SET bulan=?, daftar=?, program=?, kegiatan=?, sub_kegiatan=?, kode_rekening=?, sumber_dana=?, id_kepdin=?, id_bendahara=?, id_pptk=? WHERE id_lembar=?');
        $stmt->bind_param('sssssssiiii', $bulan, $daftar, $prog, $keg, $sub, $rek, $sumber, $kepdin, $bendahara, $pptk, $existing);
    } else {
        $stmt = $db->prepare('INSERT INTO lembar_kegiatan (id_general, bulan, daftar, program, kegiatan, sub_kegiatan, kode_rekening, sumber_dana, id_kepdin, id_bendahara, id_pptk) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('isssssssiii', $generalId, $bulan, $daftar, $prog, $keg, $sub, $rek, $sumber, $kepdin, $bendahara, $pptk);
    }
    $stmt->execute();
}

function upsertBeritaAcara(mysqli $db, int $generalId, array $data): void
{
    $nomorSatker = trim((string)($data['nomor_satuan_kerja'] ?? ''));
    $tanggalSatker = (string)($data['tanggal_satuan_kerja'] ?? '');
    $nomorSp = trim((string)($data['nomor_sp'] ?? ''));
    $tanggalSp = (string)($data['tanggal_sp'] ?? '');
    
    $kepdinId = (int)($data['kepdin_id'] ?? 0);
    $ppkId = (int)($data['pembuat_komitmen_id'] ?? 0);
    $penyediaId = (int)($data['penyedia_id'] ?? 0);
    
    $noSkBahp = trim((string)($data['nomor_sk_bahp'] ?? ''));
    $tglSkBahp = (string)($data['tanggal_sk_bahp'] ?? '');
    $noSpBahp = trim((string)($data['nomor_sp_bahp'] ?? ''));
    $noKontrakBahp = trim((string)($data['nomor_kontrak_bahp'] ?? ''));
    $tglSpBahp = (string)($data['tanggal_sp_bahp'] ?? '');
    
    $keterangan = (string)($data['keterangan'] ?? '');
    $tanggalSerah = (string)($data['tanggal_serah_terima'] ?? '');
    $paket = (string)($data['paket_pekerjaan'] ?? '');
    $paketAdm = (string)($data['paket_pekerjaan_administratif'] ?? '');
    
    $noSkBahpa = trim((string)($data['nomor_sk_bahpa'] ?? ''));
    $tglSkBahpa = (string)($data['tanggal_sk_bahpa'] ?? '');
    $noSpBahpa = trim((string)($data['nomor_sp_bahpa'] ?? ''));
    $tglSpBahpa = (string)($data['tanggal_sp_bahpa'] ?? '');

    // Konversi string kosong menjadi NULL untuk semua kolom tanggal
    $tanggalSatker = ($tanggalSatker === '') ? null : $tanggalSatker;
    $tanggalSp     = ($tanggalSp === '') ? null : $tanggalSp;
    $tglSkBahp     = ($tglSkBahp === '') ? null : $tglSkBahp;
    $tglSpBahp     = ($tglSpBahp === '') ? null : $tglSpBahp;
    $tanggalSerah  = ($tanggalSerah === '') ? null : $tanggalSerah;
    $tglSkBahpa    = ($tglSkBahpa === '') ? null : $tglSkBahpa;
    $tglSpBahpa    = ($tglSpBahpa === '') ? null : $tglSpBahpa;

    $existing = findIdByGeneral($db, 'berita_acara', $generalId);

    if ($existing) {
        $sql = "UPDATE berita_acara SET 
                nomor_satuan_kerja=?, tanggal_satuan_kerja=?, nomor_sp=?, tanggal_sp=?, 
                id_kepdin=?, id_ppk=?, id_penyedia=?, 
                nomor_sk_bahp=?, tanggal_sk_bahp=?, nomor_sp_bahp=?, nomor_kontrak_bahp=?, tanggal_sp_bahp=?, 
                keterangan=?, tanggal_serah_terima=?, paket_pekerjaan=?, paket_pekerjaan_administratif=?, 
                nomor_sk_bahpa=?, tanggal_sk_bahpa=?, nomor_sp_bahpa=?, tanggal_sp_bahpa=? 
                WHERE id_berita=?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param(
            'ssssiiisssssssssssssi', 
            $nomorSatker, $tanggalSatker, $nomorSp, $tanggalSp,
            $kepdinId, $ppkId, $penyediaId,
            $noSkBahp, $tglSkBahp, $noSpBahp, $noKontrakBahp, $tglSpBahp,
            $keterangan, $tanggalSerah, $paket, $paketAdm,
            $noSkBahpa, $tglSkBahpa, $noSpBahpa, $tglSpBahpa,
            $existing
        );
    } else {
        $sql = "INSERT INTO berita_acara (
                id_general, nomor_satuan_kerja, tanggal_satuan_kerja, nomor_sp, tanggal_sp, 
                id_kepdin, id_ppk, id_penyedia, 
                nomor_sk_bahp, tanggal_sk_bahp, nomor_sp_bahp, nomor_kontrak_bahp, tanggal_sp_bahp, 
                keterangan, tanggal_serah_terima, paket_pekerjaan, paket_pekerjaan_administratif, 
                nomor_sk_bahpa, tanggal_sk_bahpa, nomor_sp_bahpa, tanggal_sp_bahpa
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->bind_param(
            'issssiiisssssssssssss', 
            $generalId, $nomorSatker, $tanggalSatker, $nomorSp, $tanggalSp,
            $kepdinId, $ppkId, $penyediaId,
            $noSkBahp, $tglSkBahp, $noSpBahp, $noKontrakBahp, $tglSpBahp,
            $keterangan, $tanggalSerah, $paket, $paketAdm,
            $noSkBahpa, $tglSkBahpa, $noSpBahpa, $tglSpBahpa
        );
    }
    $stmt->execute();
}

function upsertKwitansi(mysqli $db, int $generalId, array $data): void
{
    $nama = trim((string)($data['nama_penerima'] ?? ''));
    $bank = trim((string)($data['nama_bank'] ?? ''));
    $npwp = trim((string)($data['npwp'] ?? ''));
    $norek = trim((string)($data['norek'] ?? ''));
    $kepdin = (int)($data['kepdin_id'] ?? 0);
    $bendahara = (int)($data['bendahara_id'] ?? 0);
    $pptk = (int)($data['pptk_id'] ?? 0);
    $jumlah = (float)($data['jumlah_uang'] ?? 0);
    $tgl = (string)($data['tanggal_pembayaran'] ?? '');
    $tgl = ($tgl === '') ? null : $tgl;

    $existing = findIdByGeneral($db, 'kwitansi', $generalId);

    if ($existing) {
        $stmt = $db->prepare('UPDATE kwitansi SET nama_penerima=?, nama_bank=?, npwp=?, norek=?, id_kepdin=?, id_bendahara=?, id_pptk=?, jumlah_uang=?, tanggal_pembayaran=? WHERE id_kwitansi=?');
        $stmt->bind_param('ssssiiidsi', $nama, $bank, $npwp, $norek, $kepdin, $bendahara, $pptk, $jumlah, $tgl, $existing);
    } else {
        $stmt = $db->prepare('INSERT INTO kwitansi (id_general, nama_penerima, nama_bank, npwp, norek, id_kepdin, id_bendahara, id_pptk, jumlah_uang, tanggal_pembayaran) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('issssiiids', $generalId, $nama, $bank, $npwp, $norek, $kepdin, $bendahara, $pptk, $jumlah, $tgl);
    }
    $stmt->execute();
}

function upsertNotaDinas(mysqli $db, int $generalId, array $data): void
{
    $nomor = trim((string)($data['nomor'] ?? ''));
    $perihal = trim((string)($data['perihal'] ?? ''));
    $keperluan = trim((string)($data['keperluan'] ?? ''));
    $dpa = (float)($data['jumlah_dpa'] ?? 0);
    $tahun = (int)($data['tahun_anggaran'] ?? date('Y'));
    $kabid = (int)($data['kabid_id'] ?? 0);

    $existing = findIdByGeneral($db, 'nota_dinas', $generalId);

    if ($existing) {
        $stmt = $db->prepare('UPDATE nota_dinas SET nomor=?, perihal=?, keperluan=?, jumlah_dpa=?, tahun_anggaran=?, id_kabid=? WHERE id_nota=?');
        $stmt->bind_param('sssdiii', $nomor, $perihal, $keperluan, $dpa, $tahun, $kabid, $existing);
    } else {
        $stmt = $db->prepare('INSERT INTO nota_dinas (id_general, nomor, perihal, keperluan, jumlah_dpa, tahun_anggaran, id_kabid) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('isssdii', $generalId, $nomor, $perihal, $keperluan, $dpa, $tahun, $kabid);
    }
    $stmt->execute();
}

// === UPDATE: DITAMBAHKAN telp_kantor ===
function upsertSptpd(mysqli $db, int $generalId, array $data): void
{
    $tahun = (int)($data['tahun'] ?? date('Y'));
    $harga = (float)($data['harga_jual'] ?? 0);
    $dasar = (float)($data['dasar_pengenaan_pajak'] ?? 0);
    $pajak = (float)($data['pajak_terhutang'] ?? 0);
    $nama = trim((string)($data['nama_badan_usaha'] ?? ''));
    $masa = trim((string)($data['masa_pajak'] ?? ''));
    $kerja = trim((string)($data['pekerjaan'] ?? ''));
    
    // --- TAMBAHAN BARU ---
    $telpKantor = trim((string)($data['telp_kantor'] ?? ''));

    $existing = findIdByGeneral($db, 'sptpd', $generalId);

    if ($existing) {
        // Update query (ditambah telp_kantor)
        $stmt = $db->prepare('UPDATE sptpd SET tahun=?, harga_jual=?, dasar_pengenaan_pajak=?, pajak_terhutang=?, nama_badan_usaha=?, masa_pajak=?, pekerjaan=?, telp_kantor=? WHERE id_sptpd=?');
        $stmt->bind_param('idddssssi', $tahun, $harga, $dasar, $pajak, $nama, $masa, $kerja, $telpKantor, $existing);
    } else {
        // Insert query (ditambah telp_kantor)
        $stmt = $db->prepare('INSERT INTO sptpd (id_general, tahun, harga_jual, dasar_pengenaan_pajak, pajak_terhutang, nama_badan_usaha, masa_pajak, pekerjaan, telp_kantor) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('iidddssss', $generalId, $tahun, $harga, $dasar, $pajak, $nama, $masa, $kerja, $telpKantor);
    }
    $stmt->execute();
}

function upsertSpesifikasi(mysqli $db, int $generalId, array $items): void
{
    $db->query("DELETE FROM spesifikasi_anggaran WHERE id_general = $generalId");
    if (empty($items)) return;
    $stmt = $db->prepare('INSERT INTO spesifikasi_anggaran (id_general, spesifikasi, spesifikasi_jumlah, satuan_ukuran, harga_satuan, pagu_anggaran) VALUES (?, ?, ?, ?, ?, ?)');
    foreach ($items as $item) {
        $spec = trim((string)($item['spesifikasi'] ?? ''));
        $jumlah = (float)($item['spesifikasi_jumlah'] ?? 0);
        $satuan = trim((string)($item['satuan_ukuran'] ?? ''));
        $harga = (float)($item['harga_satuan'] ?? 0);
        $pagu = (float)($item['pagu_anggaran'] ?? 0);
        if ($spec === '') continue;
        $stmt->bind_param('isdsdd', $generalId, $spec, $jumlah, $satuan, $harga, $pagu);
        $stmt->execute();
    }
}

function findIdByGeneral(mysqli $db, string $table, int $generalId): ?int
{
    $allowed = ['dokumen_pengadaan', 'lembar_kegiatan', 'berita_acara', 'kwitansi', 'nota_dinas', 'sptpd'];
    if (!in_array($table, $allowed)) return null;
    $pkMap = [
        'dokumen_pengadaan' => 'id_dokumen',
        'lembar_kegiatan' => 'id_lembar',
        'berita_acara' => 'id_berita',
        'kwitansi' => 'id_kwitansi',
        'nota_dinas' => 'id_nota',
        'sptpd' => 'id_sptpd'
    ];
    $pk = $pkMap[$table];
    $res = $db->query("SELECT $pk FROM $table WHERE id_general = $generalId LIMIT 1");
    if ($row = $res->fetch_assoc()) return (int)$row[$pk];
    return null;
}