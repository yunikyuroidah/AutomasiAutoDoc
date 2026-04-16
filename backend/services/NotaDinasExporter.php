<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/Formatter.php';
require_once __DIR__ . '/PlaceholderHelper.php';

final class NotaDinasExporter
{
    public static function generate(array $workspace): string
    {
        $record = $workspace['nota_dinas'] ?? null;
        $dokumen = $workspace['dokumen_pengadaan'] ?? null;
        $specs = $workspace['spesifikasi_anggaran'] ?? [];

        if (!$record) {
            throw new InvalidArgumentException('Data nota dinas belum tersedia.');
        }

        // Hitung total anggaran: prefer saved nilai `jumlah_dpa` jika diisi (dari form),
        // otherwise hitung dari spesifikasi yang tersimpan di DB.
        $sumFromSpecs = array_reduce($specs, static fn (float $carry, array $item): float => $carry + (float) ($item['pagu_anggaran'] ?? 0), 0.0);
        $sum = $sumFromSpecs;
        if (!empty($record['jumlah_dpa']) && (float) $record['jumlah_dpa'] > 0) {
            $sum = (float) $record['jumlah_dpa'];
        }
        $sumCurrency = PlaceholderHelper::plainCurrency($sum);
        $terbilang = strtoupper(trim(terbilang($sum))) . ' RUPIAH';

        $ppkNama = (string) ($dokumen['ppk_nama'] ?? $record['nama_pembuat_komitmen'] ?? '');
        $ppkNip = (string) ($dokumen['ppk_nip'] ?? $record['nip_pembuat_komitmen'] ?? '');
        $ppkNamaUpper = mb_strtoupper($ppkNama, 'UTF-8');
        $tanggalSurat = format_date_id((string) ($record['tanggal'] ?? '')); // format ke tanggal Indonesia

        // Diagnostic: dump record and computed sums to storage/exports for inspection
        try {
            $exportDir = realpath(__DIR__ . '/../../storage/exports') ?: (__DIR__ . '/../../storage/exports');
            if (!is_dir($exportDir)) @mkdir($exportDir, 0775, true);
            $dbgFile = $exportDir . DIRECTORY_SEPARATOR . 'nota_dinas_debug_' . ($workspace['general']['id'] ?? 'unknown') . '.json';
            @file_put_contents($dbgFile, json_encode(['record' => $record, 'sum' => $sum, 'sumFromSpecs' => $sumFromSpecs, 'map_candidates' => array_keys((array)($record ?? []))], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } catch (Throwable $e) {
            // ignore diagnostics failures
        }

        // Mapping Data
        $map = [
            'tujuan' => (string) ($record['tujuan'] ?? ''),
            'asal' => (string) ($record['asal'] ?? ''),
            'tanggal' => $tanggalSurat,
            'nomor' => (string) ($record['nomor'] ?? ''),
            'nomor_nota_dinas' => (string) ($record['nomor'] ?? ''),
            'perihal' => (string) ($record['perihal'] ?? ''),
            'keperluan' => (string) ($record['keperluan'] ?? ''),
            'jumlah_dpa' => PlaceholderHelper::plainCurrency((float) ($record['jumlah_dpa'] ?? 0)),
            'tahun_anggaran' => (string) ($record['tahun_anggaran'] ?? date('Y')),
            'sifat' => (string) ($record['sifat'] ?? ''),
            'pemerintahan' => (string) ($record['pemerintahan'] ?? ''),
            
            // Mapping Pejabat (Kabid PPA)
            'nama_kabid' => mb_strtoupper((string) ($record['kabid_nama'] ?? ''), 'UTF-8'),
            'nip_kabid' => (string) ($record['kabid_nip'] ?? ''),
            
            // Mapping Pejabat (PPK)
            'nama_pembuat_komitmen' => $ppkNamaUpper,
            'nip_pembuat_komitmen' => $ppkNip,

            // Total & Terbilang
            'total_biaya' => $sumCurrency,
            'total di spesifikasi_anggaran' => $sumCurrency,
            '(penulisan bilangan dengan huruf dari total biaya)' => '(' . $terbilang . ')',
        ];

        // Add some common alternate keys/aliases that templates sometimes use
        $map['nota_dinas.perihal'] = $map['perihal'];
        $map['nota.perihal'] = $map['perihal'];
        $map['nota_dinas.jumlah_dpa'] = $map['jumlah_dpa'];
        $map['nota.jumlah_dpa'] = $map['jumlah_dpa'];

        $template = realpath(__DIR__ . '/../../template/nota_dinas.xlsx');
        if ($template === false || !is_file($template)) {
            throw new RuntimeException('Template nota_dinas.xlsx tidak ditemukan');
        }

        $tmpDir = sys_get_temp_dir();

        if (!is_readable($template)) {
            throw new RuntimeException('Template nota_dinas.xlsx tidak dapat dibaca oleh PHP: ' . $template);
        }

        // jika sys_get_temp_dir() tidak bisa ditulis (shared hosting, open_basedir),
        // coba fallback ke folder proyek `storage/exports` atau `tmp`.
        $selectedTmp = null;
        if ($tmpDir !== '' && is_dir($tmpDir) && is_writable($tmpDir)) {
            $selectedTmp = $tmpDir;
        } else {
            $candidates = [
                __DIR__ . '/../../storage/exports',
                __DIR__ . '/../../tmp',
                __DIR__ . '/../../',
            ];
            foreach ($candidates as $cand) {
                $path = realpath($cand) ?: $cand;
                if (!is_dir($path)) @mkdir($path, 0775, true);
                if (is_dir($path) && is_writable($path)) {
                    $selectedTmp = $path;
                    break;
                }
            }
        }

        if ($selectedTmp === null) {
            throw new RuntimeException('Direktori temporary tidak dapat ditulis oleh PHP: ' . ($tmpDir ?: 'sys_get_temp_dir kosong') . '. Periksa permission atau buat folder writable (storage/exports atau tmp).');
        }

        $output = tempnam($selectedTmp, 'nota_dinas_');
        if ($output === false) {
            throw new RuntimeException('Gagal membuat file temporary di ' . $tmpDir);
        }

        // Robust stream copy (works when copy() or file_get_contents() are restricted)
        $in = @fopen($template, 'rb');
        $out = @fopen($output, 'wb');
        $streamOk = false;
        if ($in !== false && $out !== false) {
            while (!feof($in)) {
                $chunk = fread($in, 8192);
                if ($chunk === false) break;
                if (fwrite($out, $chunk) === false) break;
            }
            fclose($in);
            fclose($out);
            // ensure file is non-empty
            if (filesize($output) > 0) $streamOk = true;
        }

        if (!$streamOk) {
            // last-resort fallback
            $contents = @file_get_contents($template);
            if ($contents === false || @file_put_contents($output, $contents) === false) {
                // remove temp file when failing
                @unlink($output);
                throw new RuntimeException('Gagal menyalin template nota dinas dari ' . $template . ' ke ' . $output . '. Periksa permission dan open_basedir.');
            }
        }

        // make output readable by PHP process
        @chmod($output, 0644);

        $zip = new ZipArchive();
        if ($zip->open($output) !== true) {
            throw new RuntimeException('Tidak dapat membuka dokumen nota dinas');
        }

        // 1. Ganti Teks & Warna di SharedStrings (Konten Teks)
        $xmlStrings = $zip->getFromName('xl/sharedStrings.xml');
        if ($xmlStrings !== false) {
            $xmlStrings = self::replaceTokens($xmlStrings, $map);
            $xmlStrings = self::normalizeColors($xmlStrings); // Ubah Merah -> Hitam
            $zip->addFromString('xl/sharedStrings.xml', $xmlStrings);
        }

        // 2. Ganti Warna di Styles (Format Sel) -> INI PERBAIKANNYA
        $xmlStyles = $zip->getFromName('xl/styles.xml');
        if ($xmlStyles !== false) {
            $xmlStyles = self::normalizeColors($xmlStyles); // Paksa style merah jadi hitam
            $zip->addFromString('xl/styles.xml', $xmlStyles);
        }

        $zip->close();

        return $output;
    }

    private static function replaceTokens(string $xml, array $map): string
    {
        foreach ($map as $token => $value) {
            $escaped = htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
            // Ganti token >token<
            $xml = str_replace('>' . $token . '<', '>' . $escaped . '<', $xml);
        }
        return $xml;
    }

    private static function normalizeColors(string $xml): string
    {
        // Regex agresif untuk menangkap warna merah di Excel (Format RGB Hex)
        // Menangkap FFFF0000 (Merah murni), FF0000, atau varian transparansi
        $xml = preg_replace('/rgb="FF[0-9A-Fa-f]{2}0000"/u', 'rgb="FF000000"', $xml) ?? $xml;
        $xml = preg_replace('/rgb="00FF0000"/u', 'rgb="FF000000"', $xml) ?? $xml; // Kadang Excel pakai format ini
        
        return $xml;
    }
}