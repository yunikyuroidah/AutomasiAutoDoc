<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/Formatter.php';
require_once __DIR__ . '/PlaceholderHelper.php';

final class KwitansiExporter
{
    public static function generate(array $workspace): string
    {
        $record = $workspace['kwitansi'] ?? null;
        $dokumen = $workspace['dokumen_pengadaan'] ?? null;
        $lembar = $workspace['lembar_kegiatan'] ?? null;
        $specs = $workspace['spesifikasi_anggaran'] ?? [];

        if (!$record) {
            throw new InvalidArgumentException('Data kwitansi belum tersedia.');
        }
        // ... (validasi lainnya tetap sama)

        $sum = array_reduce($specs, static fn (float $carry, array $item): float => $carry + (float) ($item['pagu_anggaran'] ?? 0), 0.0);
        if ($sum <= 0 && isset($record['jumlah_uang'])) {
            $sum = (float) $record['jumlah_uang'];
        }

        $firstSpec = (string) ($specs[0]['spesifikasi'] ?? '');
        $lembarUraian = (string) ($lembar['daftar'] ?? $firstSpec);
        $kodeRekening = (string) ($lembar['kode_rekening'] ?? $dokumen['kode_rup'] ?? '');
        $terbilang = strtoupper(trim(terbilang($sum))) . ' RUPIAH';

        $map = [
            'total di spesifika' => PlaceholderHelper::plainCurrency($sum),
            'uraian di lembar_kegiatan' => $lembarUraian,
            'kode_rekening di lembar_kegiatan' => $kodeRekening,
            'nama_penerima' => (string) ($record['nama_penerima'] ?? ''),
            'nama_bank' => (string) ($record['nama_bank'] ?? ''),
            'npwp' => (string) ($record['npwp'] ?? ''),
            'norek' => (string) ($record['norek'] ?? ''),
            'tanggal' => format_date_id((string) ($record['tanggal_pembayaran'] ?? '')),
            'nama_kepdin' => mb_strtoupper((string) ($record['kepdin_nama'] ?? ''), 'UTF-8'),
            'nip_kepdin' => (string) ($record['kepdin_nip'] ?? ''),
            'nama_bendahara' => mb_strtoupper((string) ($record['bendahara_nama'] ?? ''), 'UTF-8'),
            'nip_bendahara' => (string) ($record['bendahara_nip'] ?? ''),
            'nama_pembuat_komitmen' => mb_strtoupper((string) ($dokumen['ppk_nama'] ?? ''), 'UTF-8'),
            'nip_pembuat_komitmen' => (string) ($dokumen['ppk_nip'] ?? ''),
            '(penulisan bilangan dengan huruf dari total di spesifikasi anggaran)' => '(' . $terbilang . ')',
            ' (penulisan bilangan dengan huruf dari total di spesifikasi anggaran)' => ' (' . $terbilang . ')',
        ];

        $template = realpath(__DIR__ . '/../../template/kwitansi.xlsx');
        if ($template === false || !is_file($template)) {
            throw new RuntimeException('Template kwitansi.xlsx tidak ditemukan');
        }

        $output = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kwitansi_' . uniqid('', true) . '.xlsx';
        if (!copy($template, $output)) {
            throw new RuntimeException('Gagal menyalin template kwitansi');
        }

        $zip = new ZipArchive();
        if ($zip->open($output) !== true) {
            throw new RuntimeException('Tidak dapat membuka kwitansi.xlsx');
        }

        // 1. Ganti teks dan warna di Shared Strings (isi teks)
        $xmlStrings = $zip->getFromName('xl/sharedStrings.xml');
        if ($xmlStrings !== false) {
            $xmlStrings = self::replaceTokens($xmlStrings, $map);
            $xmlStrings = self::normalizeColors($xmlStrings);
            $zip->addFromString('xl/sharedStrings.xml', $xmlStrings);
        }

        // 2. Ganti warna merah di Styles (untuk sel yang diformat merah) -> TAMBAHAN BARU
        $xmlStyles = $zip->getFromName('xl/styles.xml');
        if ($xmlStyles !== false) {
            $xmlStyles = self::normalizeColors($xmlStyles);
            $zip->addFromString('xl/styles.xml', $xmlStyles);
        }

        $zip->close();

        return $output;
    }

    private static function replaceTokens(string $xml, array $map): string
    {
        foreach ($map as $token => $value) {
            $escaped = htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $xml = str_replace('>' . $token . '<', '>' . $escaped . '<', $xml);
        }

        return $xml;
    }

    private static function normalizeColors(string $xml): string
    {
        // Ganti warna merah standar (FFFF0000) dan variannya menjadi hitam
        $xml = preg_replace('/rgb="FF[0-9A-Fa-f]{2}0000"/u', 'rgb="FF000000"', $xml) ?? $xml;
        $xml = preg_replace('/rgb="00FF0000"/u', 'rgb="FF000000"', $xml) ?? $xml;
        return $xml;
    }
}
