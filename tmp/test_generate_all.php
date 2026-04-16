<?php
require_once __DIR__ . '/../backend/services/DokumenPengadaanExporter.php';
require_once __DIR__ . '/../backend/services/BeritaAcaraExporter.php';
require_once __DIR__ . '/../backend/lib/Formatter.php';

function runTest(callable $fn, $label) {
    try {
        $out = $fn();
        echo "RESULT $label: OUTPUT=$out\n";
        if (file_exists($out)) {
            echo "FILE_OK size=" . filesize($out) . "\n";
            $za = new ZipArchive();
            if ($za->open($out) === true) {
                echo "ZIP_OK entries=" . $za->numFiles . "\n";
                for ($i = 0; $i < $za->numFiles; $i++) {
                    $name = $za->getNameIndex($i);
                    if (preg_match('/\.xml$/', $name)) {
                        $content = $za->getFromIndex($i);
                        $dom = new DOMDocument();
                        libxml_use_internal_errors(true);
                        $ok = $dom->loadXML($content);
                        echo "XML_FILE: $name => " . ($ok ? "OK" : "ERROR") . "\n";
                        if (!$ok) {
                            foreach (libxml_get_errors() as $err) {
                                echo trim($err->message) . " in $name at line " . $err->line . "\n";
                            }
                            libxml_clear_errors();
                        }
                            if ($name === 'word/document.xml') {
                                // Search for potential visibility issues
                                $checks = [
                                    'w:color' => preg_match_all('/w:color/i', $content, $m1),
                                    'w:sz' => preg_match_all('/w:sz/i', $content, $m2),
                                    'w:highlight' => preg_match_all('/w:highlight/i', $content, $m3),
                                    'w:shd' => preg_match_all('/w:shd/i', $content, $m4),
                                ];
                                echo "XML_CHECKS for $name: ";
                                foreach ($checks as $k => $v) echo "$k=$v ";
                                echo "\n";
                                // Show small snippets if color occurrences exist
                                if ($checks['w:color'] > 0) {
                                    preg_match_all('/(.{0,40}w:color[^>]*>.{0,40})/is', $content, $snips);
                                    echo "SAMPLES w:color:\n";
                                    foreach (array_slice($snips[0], 0, 5) as $s) echo trim($s) . "\n";
                                }
                                    // Show first 20 text runs and their nearby rPr (font size) for debugging
                                    $dom2 = new DOMDocument();
                                    libxml_use_internal_errors(true);
                                    if (@$dom2->loadXML($content)) {
                                        $xpath = new DOMXPath($dom2);
                                        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
                                        $texts = $xpath->query('//w:t');
                                        $count = min(20, $texts->length);
                                        echo "SAMPLE_TEXT_RUNS (first $count):\n";
                                        for ($ti = 0; $ti < $count; $ti++) {
                                            $t = $texts->item($ti);
                                            $txt = trim($t->textContent);
                                            $r = $t->parentNode; // w:r
                                            $rPr = null;
                                            if ($r && $r->hasChildNodes()) {
                                                foreach ($r->childNodes as $cn) {
                                                    if ($cn->nodeName === 'w:rPr') { $rPr = $cn; break; }
                                                }
                                            }
                                            $sz = '';
                                            if ($rPr) {
                                                foreach ($rPr->childNodes as $p) {
                                                    if ($p->nodeName === 'w:sz' && $p->hasAttributes()) {
                                                        $sz = $p->getAttribute('w:val');
                                                    }
                                                }
                                            }
                                            echo "- TEXT=('" . ($txt === '' ? '[EMPTY]' : $txt) . "') sz=" . ($sz === '' ? '[none]' : $sz) . "\n";
                                        }
                                    }
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
        echo "EXC $label: " . $e->getMessage() . "\n";
    }
}

$workspace = [
    'dokumen_pengadaan' => [
        'nomor_dpp' => '590',
        'pagu_anggaran' => 34609000,
        'nama_paket' => 'PAKET DUMMY PENGUJIAN',
        'kode_rup' => 'RUP-DUMMY-TEST-01',
        'ppk_nama' => 'PPK DUMMY TEST',
        'ppk_nip' => '0000000000000010',
        'ppk_keterangan' => 'KETERANGAN DUMMY',
    ],
    'lembar_kegiatan' => [
        'program' => 'PRO',
        'kegiatan' => 'activities',
        'sub_kegiatan' => 'subactivities',
        'kode_rekening' => '60951314',
        'sumber_dana' => 'APBD',
    ],
    'berita_acara' => [
        'penyedia_nama' => 'CV Penyedia Dummy',
        'nomor_ba' => 'BA-001',
    ],
    'general' => [
        'tanggal' => date('Y-m-d')
    ],
    'spesifikasi_anggaran' => [
        ['spesifikasi' => 'Item Dummy A', 'spesifikasi_jumlah' => '80', 'satuan_ukuran' => 'kotak', 'harga_satuan' => '30000'],
        ['spesifikasi' => 'Item Dummy B', 'spesifikasi_jumlah' => '85', 'satuan_ukuran' => 'kotak', 'harga_satuan' => '25300'],
    ],
];

runTest(function() use ($workspace) {
    return DokumenPengadaanExporter::generate($workspace);
}, 'DOKUMEN_PENGADAAN');

runTest(function() use ($workspace) {
    return BeritaAcaraExporter::generate($workspace);
}, 'BERITA_ACARA');

echo "DONE\n";
