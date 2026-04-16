<?php
require_once __DIR__ . '/../backend/services/DokumenPengadaanExporter.php';
require_once __DIR__ . '/../backend/lib/Formatter.php';

$workspace = [
    'dokumen_pengadaan' => [
        'nomor_dpp' => '590',
        'pagu_anggaran' => 34609000,
        'nama_paket' => 'PAKET DUMMY PENGUJIAN',
        'kode_rup' => 'RUP-DUMMY-TEST-01',
        'ppk_nama' => 'PPK DUMMY TEST',
        'ppk_nip' => '0000000000000010',
    ],
    'lembar_kegiatan' => [
        'program' => 'PRO',
        'kegiatan' => 'activities',
        'sub_kegiatan' => 'subactivities',
        'kode_rekening' => '60951314',
        'sumber_dana' => 'APBD',
    ],
    'berita_acara' => [
        'penyedia_nama' => 'CV Penyedia Dummy'
    ],
    'general' => [
        'tanggal' => date('Y-m-d')
    ],
    'spesifikasi_anggaran' => [
        ['spesifikasi' => 'Item Dummy A', 'spesifikasi_jumlah' => '80', 'satuan_ukuran' => 'kotak', 'harga_satuan' => '30000'],
        ['spesifikasi' => 'Item Dummy B', 'spesifikasi_jumlah' => '85', 'satuan_ukuran' => 'kotak', 'harga_satuan' => '25300'],
    ],
];

try {
    $out = DokumenPengadaanExporter::generate($workspace);
    echo "OUTPUT: $out\n";
    if (file_exists($out)) {
        echo "FILE_OK size=" . filesize($out) . "\n";
        $za = new ZipArchive();
        if ($za->open($out) === true) {
            echo "ZIP_OK entries=" . $za->numFiles . "\n";
            libxml_use_internal_errors(true);
            for ($i = 0; $i < $za->numFiles; $i++) {
                $name = $za->getNameIndex($i);
                if (preg_match('/\.xml$/', $name)) {
                    $content = $za->getFromIndex($i);
                    $dom = new DOMDocument();
                    $ok = $dom->loadXML($content);
                    echo "XML_FILE: $name => " . ($ok ? "OK" : "ERROR") . "\n";
                    if (!$ok) {
                        foreach (libxml_get_errors() as $err) {
                            echo trim($err->message) . " in $name at line " . $err->line . "\n";
                        }
                        libxml_clear_errors();
                    }
                }
            }
            $za->close();
        } else {
            echo "ZIP_OPEN_FAIL\n";
        }
    } else {
        echo "NO_OUTPUT_FILE\n";
    }
} catch (Throwable $e) {
    echo "EXC: " . $e->getMessage() . "\n";
}
