<?php

require_once __DIR__ . '/../lib/Formatter.php';
require_once __DIR__ . '/PlaceholderHelper.php';
require_once __DIR__ . '/WorkspaceData.php';
require_once __DIR__ . '/BeritaAcaraHelpers.php';

final class BeritaAcaraExporter
{
    private const DEFAULT_SPELLED_DATE_TEXT = 'Senin, tanggal Sembilan Belas bulan Mei tahun Dua Ribu Dua Puluh Empat';
    public static function generate(array $workspace): string
    {
        $berita = $workspace['berita_acara'] ?? null;
        $general = $workspace['general'] ?? null;
        $lembar = $workspace['lembar_kegiatan'] ?? [];
        // Ambil data checklist yang dikirim (Isinya: chk_1_l => 'TL', chk_1_s => 'S', dst)
        $checklist = $workspace['transient_checklist'] ?? [];
        $specs = $workspace['spesifikasi_anggaran'] ?? [];

        if (!$berita) throw new InvalidArgumentException('Data berita acara belum tersedia.');

        // ==========================================
        // 1. DATA TABEL SPESIFIKASI
        // ==========================================
        $colSpec = [];
        $colJml = [];
        $colSat = [];
        $colHarga = [];
        $colPagu = [];
        $totalPagu = 0;

        foreach ($specs as $s) {
            $colSpec[]  = isset($s['spesifikasi']) ? (string)$s['spesifikasi'] : '-';
            $colJml[]   = isset($s['spesifikasi_jumlah']) ? (string)$s['spesifikasi_jumlah'] : '';
            $colSat[]   = isset($s['satuan_ukuran']) ? (string)$s['satuan_ukuran'] : '';
            $colHarga[] = isset($s['harga_satuan']) ? PlaceholderHelper::plainCurrency((float)$s['harga_satuan']) : '';
            $valPagu    = isset($s['pagu_anggaran']) ? (float)$s['pagu_anggaran'] : 0;
            $colPagu[]  = PlaceholderHelper::plainCurrency($valPagu);
            $totalPagu += $valPagu;
        }

        // Helper: gabungkan dengan newline, nanti dikonversi ke <w:br/> di tahap XML
        $joinWithNewline = function($arr) {
            return implode("\n", $arr);
        };

        $pajak = $totalPagu * 0.1;
        // Nilai total yang benar adalah jumlah + pajak
        $grandTotal = $totalPagu + $pajak;
        $terbilangText = trim(terbilang($grandTotal)) . ' Rupiah';

        // ==========================================
        // 2. MAPPING DATA UTAMA
        // ==========================================
        $map = [
            // --- DATA HALAMAN 1 ---
            'paket_pekerjaan'       => $berita['paket_pekerjaan'] ?? '',
            'nomor_satuan_kerja'    => $berita['nomor_satuan_kerja'] ?? '',
            'tanggal_satuan_kerja'  => format_date_id($berita['tanggal_satuan_kerja'] ?? ''),
            'nomor_sp'              => $berita['nomor_sp'] ?? '',
            'nomor_kontrak'         => $berita['nomor_kontrak_bahp'] ?? '',
            'nomor_kontrak_bahp'    => $berita['nomor_kontrak_bahp'] ?? '', // Alternative placeholder name
            'tanggal_sp'            => format_date_id($berita['tanggal_sp'] ?? ''),
            'tanggal_serah_terima'  => format_date_id($berita['tanggal_serah_terima'] ?? ''),
            'kode_rekening'         => $lembar['kode_rekening'] ?? '',
            
            // --- TABEL SPESIFIKASI ---
            // Gunakan baris baru (\n) sebagai pemisah antar item
            'spesifikas_jumlah'     => $joinWithNewline($colJml),
            'spesifikasi_jumlah'    => $joinWithNewline($colJml),
            'spesifikasi_jumlah_'   => $joinWithNewline($colJml),
            'harga_satuan'          => $joinWithNewline($colHarga),
            'harga_satuan_'         => $joinWithNewline($colHarga),
            'pagu_anggaran'         => $joinWithNewline($colPagu),
            'satuan_ukuran'         => $joinWithNewline($colSat),
            // NOTE: intentionally do NOT replace the plain token 'spesifikasi' in the template.
            // Some templates contain a literal label 'Spesifikasi:' which should remain as-is.
            // Use aliases (e.g. 'food') when you want DB-driven replacement for spesifikasi rows.
            // Template aliases (allow renaming placeholders in template)
            'food'                  => $joinWithNewline($colSpec),
            'quantity'              => $joinWithNewline($colJml),
            
            // --- FOOTER & TOTAL ---
            'pajak_daerah'          => PlaceholderHelper::plainCurrency($pajak),
            'jumlah'                => PlaceholderHelper::plainCurrency($totalPagu),
            // Nilai dan penulisan bilangan harus ambil dari total (jumlah + pajak)
            'nilai'                 => PlaceholderHelper::plainCurrency($grandTotal),
            'total'                 => PlaceholderHelper::plainCurrency($grandTotal),
            'penulisan bilangan dari' => $terbilangText,

            // --- PIHAK & TANDA TANGAN ---
            'nama_penyedia'           => mb_strtoupper($berita['penyedia_nama'] ?? '', 'UTF-8'),
            'nama_penganggung_jawab'  => mb_strtoupper($berita['penyedia_nama_orang'] ?? '', 'UTF-8'),
            'nama_penanggung_jawab'   => mb_strtoupper($berita['penyedia_nama_orang'] ?? '', 'UTF-8'),
            'nama_orang'              => mb_strtoupper($berita['penyedia_nama_orang'] ?? '', 'UTF-8'),
            // Template uses alias `job` (replacing old `jabatan`). Map it to penyedia.keterangan.
            'job'                   => $berita['penyedia_keterangan'] ?? $berita['penyedia_job'] ?? $berita['keterangan'] ?? '',
            // Fix: Get nama_pembuat_komitmen from pembuat_komitmen table (already joined in WorkspaceData)
            'nama_pembuat_komitmen'   => mb_strtoupper($berita['ppk_nama'] ?? '', 'UTF-8'),
            'nip_pembuat_komitmen'    => $berita['ppk_nip'] ?? '',
            // Fix: Get alamat from penyedia table
            // Alamat penyedia (alias dari WorkspaceData: penyedia_alamat_penyedia)
            'alamat_penyedia'        => $berita['penyedia_alamat_penyedia'] ?? '',
            // Fix: nama_badan_usaha should use nama_penyedia (since there's no nama_badan_usaha column in penyedia)
            'nama_badan_usaha'        => mb_strtoupper($berita['penyedia_nama'] ?? '', 'UTF-8'),
            'keterangan'              => $berita['keterangan'] ?? '', 
           
            // --- HALAMAN 3 ---
            'paket_pekerjaan_administratif' => $berita['paket_pekerjaan_administratif'] ?? '',
            'nomor_sk_bahpa'        => $berita['nomor_sk_bahpa'] ?? '',
            'tanggal_sk_bahpa'      => format_date_id($berita['tanggal_sk_bahpa'] ?? ''),
            'nomor_sp_bahpa'        => $berita['nomor_sp_bahpa'] ?? '',
            'tanggal_sp_bahpa'      => format_date_id($berita['tanggal_sp_bahpa'] ?? ''),
            'nama_kepdin'           => mb_strtoupper($berita['kepdin_nama'] ?? '', 'UTF-8'),
            'nip_kepdin'            => $berita['kepdin_nip'] ?? '',
            
            '(tahun menyesuaikan)'  => date('Y', strtotime($general['tanggal'] ?? 'now')),
        ];

        // Do not alias 'total' to 'Total' — templates may intentionally
        // include the capitalized word 'Total' as literal text.

        $spelledDateSource = $berita['tanggal_serah_terima'] ?? null;
        if (!$spelledDateSource && is_array($general) && isset($general['tanggal'])) {
            $spelledDateSource = $general['tanggal'];
        }

        $spelledDateText = BeritaAcaraHelpers::formatSpelledDate($spelledDateSource);
        $escapedSpelledDateText = $spelledDateText !== null
            ? htmlspecialchars($spelledDateText, ENT_QUOTES | ENT_XML1, 'UTF-8')
            : null;

        $escapedDayTokenText = $escapedSpelledDateText;
        if ($escapedDayTokenText === null) {
            $dayNameText = self::formatDayName($spelledDateSource);
            if ($dayNameText !== null) {
                $escapedDayTokenText = htmlspecialchars($dayNameText, ENT_QUOTES | ENT_XML1, 'UTF-8');
            }
        }

        $numericDateText = BeritaAcaraHelpers::formatNumericDate($spelledDateSource);
        $escapedNumericDateText = $numericDateText !== null
            ? htmlspecialchars($numericDateText, ENT_QUOTES | ENT_XML1, 'UTF-8')
            : null;

        // Escape nilai agar aman untuk XML Word
        $escape = fn(string $t): string => htmlspecialchars($t, ENT_QUOTES | ENT_XML1, 'UTF-8');
        foreach ($map as $k => $v) {
            $map[$k] = $escape((string)$v);
        }

        // Sorting kunci dari Panjang ke Pendek (PENTING)
        uksort($map, function($a, $b) {
            return strlen($b) - strlen($a);
        });

        // ==========================================
        // 3. PROSES WORD
        // ==========================================
        $template = realpath(__DIR__ . '/../../template/berita_acara.docx');
        if (!is_file($template)) throw new RuntimeException('Template berita_acara.docx tidak ditemukan');

        $output = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ba_' . uniqid() . '.docx';
        copy($template, $output);

        $zip = new ZipArchive();
        if ($zip->open($output) !== true) throw new RuntimeException('Gagal membuka docx');
        $xml = $zip->getFromName('word/document.xml');

        // A. CLEANUP XML - Convert all colors to black
        // Remove all color attributes to make everything black (default)
            $xml = preg_replace('/<w:color[^>]*\/?>/i', '', $xml);
            $xml = preg_replace('/<w:color[^>]*>.*?<\/w:color>/i', '', $xml);
            $xml = preg_replace('/<w:proofErr[^>]*\/?\s*>/', '', $xml);
            $xml = preg_replace('/<w:lang[^>]*\/?\s*>/', '', $xml);
            $xml = preg_replace('/<w:lastRenderedPageBreak[^>]*\/?\s*>/', '', $xml);

        // B. REPLACE DATA UTAMA
        // IMPORTANT: Replace in order from longest to shortest (already sorted)
        // Do multiple passes to ensure all replacements work
        foreach ($map as $key => $val) {
            // Simple string replacement (case sensitive first)
            $xml = str_replace($key, $val, $xml);
        }

        if ($escapedSpelledDateText !== null) {
            $xml = str_replace(self::DEFAULT_SPELLED_DATE_TEXT, $escapedSpelledDateText, $xml);
            $xml = BeritaAcaraHelpers::replaceAcrossTagsMulti($xml, [self::DEFAULT_SPELLED_DATE_TEXT => $escapedSpelledDateText]);
        }

        if ($escapedDayTokenText !== null) {
            $xml = BeritaAcaraHelpers::replaceStandaloneToken($xml, 'day', $escapedDayTokenText);
        }

        if ($escapedNumericDateText !== null) {
            $xml = BeritaAcaraHelpers::replaceStandaloneToken($xml, 'date', $escapedNumericDateText);
        }
        
        // Additional pass: Handle case-insensitive replacements for common placeholders
        // Also handle placeholders that might be split by XML tags
            $caseInsensitiveKeys = ['spesifikasi_jumlah', 'harga_satuan', 'nama_orang', 'alamat_penyedia', 'nama_pembuat_komitmen', 'nama_badan_usaha', 'nomor_kontrak', 'tanggal_serah_terima', 'nomor_kontrak_bahp'];

        // Robust replacer for placeholders split across XML tags
        $replaceAcrossTags = function(string $xml, string $key, string $val): string {
            $chars = preg_split('//u', $key, -1, PREG_SPLIT_NO_EMPTY);
            $parts = array_map(function($c){ return preg_quote($c, '/'); }, $chars);
            $pattern = implode('(?:<[^>]+>)*\s*', $parts);
            return preg_replace('/' . $pattern . '/iu', $val, $xml);
        };

        foreach ($caseInsensitiveKeys as $key) {
            if (isset($map[$key])) {
                $val = $map[$key];
                // Replace case-insensitive version
                $xml = preg_replace('/' . preg_quote($key, '/') . '/iu', $val, $xml);
                // Handle placeholder that might be split by XML tags (e.g., "spesifikasi<tag>_jumlah")
                $keyParts = explode('_', $key);
                if (count($keyParts) > 1) {
                    // Try to match pattern like "spesifikasi<tag>_jumlah" or "spesifikasi <tag> _ jumlah"
                    $pattern = preg_quote($keyParts[0], '/') . '(?:<[^>]+>)*\s*' . preg_quote('_', '/') . '\s*(?:<[^>]+>)*' . preg_quote($keyParts[1], '/');
                    $xml = preg_replace('/' . $pattern . '/iu', $val, $xml);
                    // Also handle if there are more parts (e.g., "nomor_kontrak_bahp")
                    if (count($keyParts) > 2) {
                        $pattern2 = preg_quote($keyParts[0], '/') . '(?:<[^>]+>)*\s*' . preg_quote('_', '/') . '\s*(?:<[^>]+>)*' . preg_quote($keyParts[1], '/') . '(?:<[^>]+>)*\s*' . preg_quote('_', '/') . '\s*(?:<[^>]+>)*' . preg_quote($keyParts[2], '/');
                        $xml = preg_replace('/' . $pattern2 . '/iu', $val, $xml);
                    }
                }
            }

                // Only run the aggressive across-tags replacer for keys that are compound (contain underscore)
                // or for explicit aliases (like 'food'/'quantity') that must be matched even without underscore.
                $explicitCross = ['food', 'quantity', 'job', 'jabatan'];
                foreach (array_keys($map) as $k) {
                    if (strpos($k, '_') !== false || in_array($k, $explicitCross, true)) {
                        $xml = $replaceAcrossTags($xml, $k, $map[$k]);
                    }
                }

                // Handle plain 'spesifikasi' key carefully: replace only when it's an exact standalone token
                if (isset($map['spesifikasi'])) {
                    $xml = preg_replace_callback('/(<w:t[^>]*>)(\s*)(spesifikasi)(\s*)(<\/w:t>)/iu', function($m) use ($map) {
                        return $m[1] . $map['spesifikasi'] . $m[5];
                    }, $xml);
                }
        }
            // Replace placeholders only inside text nodes (<w:t>...</w:t>) to avoid corrupting tags/attributes
            $keys = array_keys($map);
            usort($keys, function($a, $b) { return strlen($b) - strlen($a); });
            $xml = preg_replace_callback('/(<w:t[^>]*>)(.*?)(<\/w:t>)/su', function($m) use ($map, $keys, $caseInsensitiveKeys) {
                $text = $m[2];
                foreach ($keys as $k) {
                    if (isset($map[$k]) && $map[$k] !== '') {
                        // Always perform a case-sensitive replacement first
                        $text = str_replace($k, $map[$k], $text);
                        // Only do a case-insensitive replacement for known keys
                        // that must match regardless of case (defined earlier)
                        if (in_array($k, $caseInsensitiveKeys, true)) {
                            $text = str_ireplace($k, $map[$k], $text);
                        }
                    }
                }
                return $m[1] . $text . $m[3];
            }, $xml);
        
        // Convert any newline embedded inside <w:t> into Word line breaks (<w:br/>)
        $xml = preg_replace_callback('/(<w:t[^>]*>)([^<]*\n[^<]*)(<\/w:t>)/u', function($m) {
            $text = $m[2];
            $parts = explode("\n", $text);
            $first = array_shift($parts);
            $out = $m[1] . $first . '</w:t>';
            foreach ($parts as $p) {
                $out .= '<w:br/><w:t>' . $p . '</w:t>'; 
            }
            return $out; // we intentionally omit original closing tag (it's already closed per segment)
        }, $xml);

        // NOTE: Removed legacy cleanup of 'spesifikasi_*' tokens to avoid accidental replacement
        // of literal template labels. If you still have specific leftover tokens in a
        // template, please provide the surrounding document.xml fragment and I'll add
        // a safe, targeted replacer.
        
        // C. FIX TERBILANG: Ganti hanya isi <w:t> yang mengandung 'TERBILANG:'
        $xml = preg_replace_callback(
            '/<w:t[^>]*>\s*TERBILANG:?\s*<\/w:t>/iu',
            function($m) use ($terbilangText) {
                return '<w:t>TERBILANG: ' . htmlspecialchars($terbilangText, ENT_QUOTES | ENT_XML1, 'UTF-8') . '</w:t>';
            },
            $xml
        );

        // Cleanup: hilangkan sisa suffix seperti "_usaha" setelah penggantian nama badan usaha
        $xml = preg_replace('/([A-Za-z0-9 .,&\-]+)_usaha/iu', '$1', $xml);

        // ==========================================
        // 4. LOGIKA CHECKLIST (PERBAIKAN LOGIKA VALUE)
        // ==========================================
        
        // 1. "Healer": Satukan kode yang terbelah di Word
        $xml = preg_replace('/(L|TL|S|TS)(?:<[^>]+>)+(\d)/', '$1$2', $xml);

        // 2. Loop Baris 1 s/d 8
        for ($i = 1; $i <= 8; $i++) {
            
            // --- KELENGKAPAN (L/TL) ---
            // FIX: Logic terbalik - jika "TL" dipilih (checked), maka "L" dicoret
            // Jika "L" dipilih (checked), maka "TL" dicoret
            $valL = $checklist["chk_{$i}_l"] ?? null; 

                if ($valL === 'TL') {
                // "TL" DIPILIH (checked) - Coret "L", Biarkan "TL"
                $xml = str_replace("L{$i}", BeritaAcaraHelpers::makeStrikethrough("Lengkap"), $xml);
                $xml = str_replace("TL{$i}", "Tidak Lengkap", $xml);
            } else {
                // "L" DIPILIH (checked) atau tidak ada pilihan - Coret "TL", Biarkan "L"
                $xml = str_replace("TL{$i}", BeritaAcaraHelpers::makeStrikethrough("Tidak Lengkap"), $xml);
                $xml = str_replace("L{$i}", "Lengkap", $xml);
            }

            // --- KESESUAIAN (S/TS) ---
            // FIX: Logic terbalik - jika "TS" dipilih (checked), maka "S" dicoret
            // Jika "S" dipilih (checked), maka "TS" dicoret
            $valS = $checklist["chk_{$i}_s"] ?? null;

            if ($valS === 'TS') {
                // "TS" DIPILIH (checked) - Coret "S", Biarkan "TS"
                $xml = str_replace("S{$i}", BeritaAcaraHelpers::makeStrikethrough("Sesuai"), $xml);
                $xml = str_replace("TS{$i}", "Tidak Sesuai", $xml);
            } else {
                // "S" DIPILIH (checked) atau tidak ada pilihan - Coret "TS", Biarkan "S"
                $xml = str_replace("TS{$i}", BeritaAcaraHelpers::makeStrikethrough("Tidak Sesuai"), $xml);
                $xml = str_replace("S{$i}", "Sesuai", $xml);
            }
        }

        $zip->addFromString('word/document.xml', $xml);
        $ok = @$zip->close();
        if ($ok === false) {
            if (is_file($output)) @unlink($output);
            throw new RuntimeException('Gagal menyimpan berita acara (zip close failed)');
        }

        return $output;
    }

    // Helper methods were moved to BeritaAcaraHelpers.php to reduce file size.
}