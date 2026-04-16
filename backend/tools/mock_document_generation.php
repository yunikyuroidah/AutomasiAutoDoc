<?php
declare(strict_types=1);

require __DIR__ . '/../services/DocumentGenerator.php';

$workspace = [
    'general' => [
        'id' => 9999,
        'objek' => 'Objek Dummy Pengujian Otomasi Dokumen',
        'tanggal' => '2025-12-12',
    ],
    'dokumen_pengadaan' => [
        'nama_paket' => 'Paket Pengadaan Dummy A',
        'kode_rup' => 'RUP-DUMMY-001',
        'ppk_nama' => 'PPK Dummy Satu',
        'ppk_nip' => '0000000000000001',
    ],
    'berita_acara' => [
        'paket_pekerjaan' => 'Paket Kegiatan Fiktif Untuk Uji Sistem',
        'paket_pekerjaan_administratif' => 'Administrasi Paket Fiktif',
        'penyedia_keterangan' => 'CV Penyedia Dummy',
        'tanggal_serah_terima' => '2025-12-10',
        'penyedia_nama' => 'CV Penyedia Dummy',
        'kepdin_nama' => 'Kepala Dummy',
        'ppk_nama' => 'PPK Dummy Satu',
        'ppk_nip' => '0000000000000001',
    ],
    'kwitansi' => [
        'nama_penerima' => 'Penerima Dummy / CV Fiktif',
        'nama_bank' => 'Bank Dummy',
        'npwp' => '00.000.000.0-000.000',
        'norek' => '0000000000',
        'tanggal_pembayaran' => '2025-12-11',
        'kepdin_nama' => 'Kepala Dummy',
        'kepdin_nip' => '0000000000000002',
        'bendahara_nama' => 'Bendahara Dummy',
        'bendahara_nip' => '0000000000000003',
        'jumlah_uang' => 15000000,
    ],
    'nota_dinas' => [
        'nomor' => 'ND-DUMMY-001',
        'perihal' => 'Permohonan Dummy',
        'jumlah_dpa' => 250000000,
        'keperluan' => 'Kebutuhan dummy untuk pengujian generator dokumen',
    ],
    'sptpd' => [
        'tahun' => '2025',
        'masa_pajak' => 'Januari 2025',
        'pekerjaan' => 'Kegiatan Dummy Pengadaan',
        'dasar_pengenaan_pajak' => 15000000,
        'pajak_terhutang' => 1500000,
        'nama_badan_usaha' => 'Badan Usaha Dummy',
        'alamat_badan_usaha' => 'Jalan Dummy Nomor 01, Kota Fiktif',
        'nomor_telepon_rumah' => '000-0000-0000',
        'nomor_telepon_kantor' => '000-0000-0001',
    ],
    'spesifikasi_anggaran' => [
        [
            'spesifikasi' => 'Item Dummy Materi Sosialisasi',
            'spesifikasi_jumlah' => 2,
            'satuan_ukuran' => 'paket',
            'harga_satuan' => 7500000,
            'pagu_anggaran' => 15000000,
        ],
        [
            'spesifikasi' => 'Item Dummy Pelatihan Operator',
            'spesifikasi_jumlah' => 40,
            'satuan_ukuran' => 'orang',
            'harga_satuan' => 350000,
            'pagu_anggaran' => 14000000,
        ],
    ],
];

$generalId = (int) ($workspace['general']['id'] ?? 0);
$results = DocumentGenerator::buildStatuses($generalId, $workspace);

$exportDir = realpath(__DIR__ . '/../storage/exports') ?: (__DIR__ . '/../storage/exports');

echo "Generated documents for workspace #{$generalId}\n";
echo str_repeat('=', 60) . "\n";

foreach ($results as $result) {
    $status = strtoupper($result['status']);
    $filename = $result['filename'];
    $download = $result['download_url'] ?? '-';
    $localPath = '-';

    if ($download) {
        $basename = basename($download);
        $path = rtrim($exportDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $basename;
        $localPath = is_file($path) ? $path : '(missing) ' . $path;
    }

    printf("%-16s : %-8s => %s\n", $filename, $status, $localPath);
    if ($result['status'] !== 'success') {
        echo "  Message: {$result['message']}\n";
    }
}

echo str_repeat('=', 60) . "\n";
echo "Done.\n";
