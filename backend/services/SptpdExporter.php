<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/Formatter.php';
require_once __DIR__ . '/PlaceholderHelper.php';

final class SptpdExporter
{
    public static function generate(array $workspace): string
    {
        // 1. Validasi Data
        $sptpd = $workspace['sptpd'] ?? null;
        $general = $workspace['general'] ?? null;
        $lembar = $workspace['lembar_kegiatan'] ?? null;
        $specs = $workspace['spesifikasi_anggaran'] ?? [];

        if (!$sptpd) {
            throw new InvalidArgumentException('Data SPTPD belum tersedia.');
        }
        if (!$general) {
            throw new InvalidArgumentException('Data umum belum tersedia.');
        }

        // 2. Ambil Nama Kegiatan
        $kegiatan = (string) ($lembar['kegiatan'] ?? $lembar['daftar'] ?? $general['objek'] ?? ($specs[0]['spesifikasi'] ?? ''));

        // 3. Mapping Data (Placeholder -> Nilai Database)
        $map = [
            'tahun' => (string) ($sptpd['tahun'] ?? date('Y')),
            'masa_pajak' => (string) ($sptpd['masa_pajak'] ?? ''),
            'kegiatan di lembar_kegiatan' => $kegiatan,
            'pekerjaan' => (string) ($sptpd['pekerjaan'] ?? ''),
            
            // --- FIX HARGA JUAL ---
            'harga_jual' => PlaceholderHelper::plainCurrency((float) ($sptpd['harga_jual'] ?? 0)),
            
            'dasar_pengenaan_pajak_restoran' => PlaceholderHelper::plainCurrency((float) ($sptpd['dasar_pengenaan_pajak'] ?? 0)),
            'pajak_restoran_terhutang' => PlaceholderHelper::plainCurrency((float) ($sptpd['pajak_terhutang'] ?? 0)),
            
            // --- FIX NAMA ---
            'nama' => (string) ($sptpd['nama'] ?? ''), 
            
            'jabatan' => (string) ($sptpd['jabatan'] ?? ''),
            'alamat' => (string) ($sptpd['alamat'] ?? ''),
            
            // --- FIX KONTAK ---
            'kontak' => (string) ($sptpd['kontak'] ?? '-'),
            
            // --- FIX KANTOR (MENGAMBIL DATA TELP_KANTOR) ---
            'telp_kantor' => (string) ($sptpd['telp_kantor'] ?? '-'),
            
            'nama_badan_usaha' => (string) ($sptpd['nama_badan_usaha'] ?? ''),
            'alamat_badan_usaha' => (string) ($sptpd['alamat_badan_usaha'] ?? ''),
        ];

        // 4. Proses Template
        $template = realpath(__DIR__ . '/../../template/sptpd.xlsx');
        if ($template === false || !is_file($template)) {
            throw new RuntimeException('Template sptpd.xlsx tidak ditemukan');
        }

        $output = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sptpd_' . uniqid('', true) . '.xlsx';
        if (!copy($template, $output)) {
            throw new RuntimeException('Gagal menyalin template SPTPD');
        }

        $zip = new ZipArchive();
        if ($zip->open($output) !== true) {
            throw new RuntimeException('Tidak dapat membuka file Excel sementara');
        }

        // A. Ganti Teks (Content)
        $xmlStrings = $zip->getFromName('xl/sharedStrings.xml');
        if ($xmlStrings !== false) {
            $xmlStrings = self::replaceTokens($xmlStrings, $map);
            $xmlStrings = self::normalizeColors($xmlStrings); // Fix Warna Merah
            $zip->addFromString('xl/sharedStrings.xml', $xmlStrings);
        }

        // B. Ganti Style Warna (Format Cell)
        $xmlStyles = $zip->getFromName('xl/styles.xml');
        if ($xmlStyles !== false) {
            $xmlStyles = self::normalizeColors($xmlStyles); // Fix Warna Merah
            $zip->addFromString('xl/styles.xml', $xmlStyles);
        }

        $zip->close();

        return $output;
    }

    private static function replaceTokens(string $xml, array $map): string
    {
        foreach ($map as $token => $value) {
            $escaped = htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $xml = str_replace('[' . $token . ']', $escaped, $xml);
            $xml = str_replace('>' . $token . '<', '>' . $escaped . '<', $xml);
        }
        return $xml;
    }

    private static function normalizeColors(string $xml): string
    {
        return preg_replace('/rgb="FF(FF)?0000"/i', 'rgb="FF000000"', $xml);
    }
}