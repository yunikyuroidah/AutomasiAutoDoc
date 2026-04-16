<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/Formatter.php';
require_once __DIR__ . '/PlaceholderHelper.php';
require_once __DIR__ . '/DokumenPengadaanHelpers.php';

final class DokumenPengadaanExporter
{
    private const DEFAULT_SPELLED_DATE_TEXT = 'Kamis tanggal Tujuh Belas Bulan Juli Tahun Dua Ribu Dua Puluh Lima';
    public static function generate(array $workspace): string
    {
        $dokumen = $workspace['dokumen_pengadaan'] ?? null;
        $specs = $workspace['spesifikasi_anggaran'] ?? [];
        $lembar = $workspace['lembar_kegiatan'] ?? [];
        $berita = $workspace['berita_acara'] ?? [];
        $general = $workspace['general'] ?? [];

        if (!$dokumen) {
            throw new InvalidArgumentException('Data dokumen pengadaan belum tersedia.');
        }

        $foodLines = [];
        $quantityLines = [];
        $priceLines = [];
        $subtotalLines = [];
        $totalSubtotal = 0.0;
        $referenceItems = [];

        $letterIndex = 0;

        foreach ($specs as $item) {
            $food = trim((string)($item['spesifikasi'] ?? ''));
            $label = DokumenPengadaanHelpers::alphabetLabel($letterIndex++);
            $foodLines[] = ($label !== '' ? $label . '. ' : '') . ($food !== '' ? $food : '-');

            $qtyRaw = (string)($item['spesifikasi_jumlah'] ?? '');
            $qtyText = trim($qtyRaw);
            $quantityLines[] = $qtyText !== '' ? $qtyText . ' kotak' : '-';

            $hargaSatuan = (float)($item['harga_satuan'] ?? 0);
            $priceValue = $hargaSatuan * 1.1; // Tambah 10%
            $priceLines[] = 'Rp ' . PlaceholderHelper::plainCurrency($priceValue);

            $qtyNumeric = DokumenPengadaanHelpers::toFloat($qtyRaw);
            $subtotalValue = $qtyNumeric * $priceValue;
            $totalSubtotal += $subtotalValue;
            $subtotalLines[] = 'Rp ' . PlaceholderHelper::plainCurrency($subtotalValue);

            $referenceItems[] = [
                'product' => $food !== '' ? $food : '-',
                'price' => $priceValue,
            ];
        }

        if (!$foodLines) {
            $foodLines[] = '-';
            $quantityLines[] = '-';
            $priceLines[] = 'Rp ' . PlaceholderHelper::plainCurrency(0);
            $subtotalLines[] = 'Rp ' . PlaceholderHelper::plainCurrency(0);
        }

        $joinLines = static fn(array $rows): string => implode("\n", $rows);

        $hariIniText = DokumenPengadaanHelpers::formatSpelledDate($general['tanggal'] ?? null);
        if ($hariIniText === null) {
            $hariIniText = DokumenPengadaanHelpers::formatSpelledDate(null);
        }

        $map = [
            'nomor_dpp'              => $dokumen['nomor_dpp'] ?? '',
            'nama_pembuat_komitmen'  => $dokumen['ppk_nama'] ?? '',
            'kode_rup'               => $dokumen['kode_rup'] ?? '',
            'pagu_anggaran'          => 'Rp ' . PlaceholderHelper::plainCurrency((float)($dokumen['pagu_anggaran'] ?? 0)),
            'anggar'                 => 'Rp ' . PlaceholderHelper::plainCurrency((float)($dokumen['pagu_anggaran'] ?? 0)),
            'food'                   => $joinLines($foodLines),
            'quantity'               => $joinLines($quantityLines),
            'ukuran'                 => '',
            'price'                  => $joinLines($priceLines),
            'pagu'                   => $joinLines($subtotalLines),
            'subtotal'               => $joinLines($subtotalLines),
            'hasil_jumlah'           => 'Rp ' . PlaceholderHelper::plainCurrency($totalSubtotal),
            'tanggal_mulai'          => format_date_id((string)($dokumen['tanggal_mulai'] ?? '')),
            'tanggal_selesai'        => format_date_id((string)($dokumen['tanggal_selesai'] ?? '')),
            'nip_pembuat_komitmen'   => $dokumen['ppk_nip'] ?? '',
        ];

        $map['activity'] = $dokumen['nama_paket'] ?? '';
        $map['keterangan_pembuat_komitmen'] = $dokumen['ppk_keterangan'] ?? '';
        $map['kode_rekening'] = $lembar['kode_rekening'] ?? '';
        $program = trim((string)($lembar['program'] ?? ''));
        $activities = trim((string)($lembar['kegiatan'] ?? ''));
        $subActivities = trim((string)($lembar['sub_kegiatan'] ?? ''));
        $map['program_detail'] = $program;
        $map['activities'] = $activities;
        $map['kegiatan_detail'] = $activities;
        $map['subactivities'] = $subActivities;
        $map['sub_kegiatan_detail'] = $subActivities;
        $map['sumber_dana'] = $lembar['sumber_dana'] ?? '';
        $map['nama_penyedia'] = $berita['penyedia_nama'] ?? '';

        $year = null;
        if (!empty($general['tanggal'])) {
            try {
                $year = (new DateTimeImmutable((string)$general['tanggal']))->format('Y');
            } catch (Throwable $e) {
                $year = null;
            }
        }
        $map['tahun_anggaran'] = $year ?? date('Y');

        $escaped = [];
        foreach ($map as $key => $value) {
            $escaped[$key] = htmlspecialchars((string)$value, ENT_QUOTES | ENT_XML1, 'UTF-8');
        }

        // Replace placeholders only inside text nodes (<w:t>...</w:t>) to avoid
        // accidental modification of XML attributes or tags.
        $keys = array_keys($escaped);
        uksort($escaped, static function (string $a, string $b): int {
            return strlen($b) <=> strlen($a);
        });

        $template = realpath(__DIR__ . '/../../template/dokumen_pengadaan.docx');
        if (!$template || !is_file($template)) {
            throw new RuntimeException('Template dokumen_pengadaan.docx tidak ditemukan');
        }

        $output = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'dp_' . uniqid('', false) . '.docx';
        if (!copy($template, $output)) {
            throw new RuntimeException('Gagal menyalin template dokumen pengadaan');
        }

        $zip = new ZipArchive();
        if ($zip->open($output) !== true) {
            throw new RuntimeException('Gagal membuka template dokumen pengadaan');
        }

        $xml = $zip->getFromName('word/document.xml');
        if ($xml === false) {
            $zip->close();
            throw new RuntimeException('Dokumen pengadaan tidak memiliki word/document.xml');
        }

        $xml = DokumenPengadaanHelpers::injectReferenceRows($xml, $referenceItems, $berita['penyedia_nama'] ?? '');

        if (!empty($hariIniText)) {
            $escapedDay = htmlspecialchars($hariIniText, ENT_QUOTES | ENT_XML1, 'UTF-8');
            $xml = DokumenPengadaanHelpers::replaceStandaloneToken($xml, 'day', $escapedDay);
            $xml = DokumenPengadaanHelpers::replaceAcrossTags($xml, [self::DEFAULT_SPELLED_DATE_TEXT => $escapedDay], [self::DEFAULT_SPELLED_DATE_TEXT]);
        }

        foreach ($escaped as $key => $value) {
            $xml = str_replace($key, $value, $xml);
        }

        usort($keys, static fn($a, $b) => strlen($b) <=> strlen($a));
        $xml = preg_replace_callback('/(<w:t[^>]*>)(.*?)(<\/w:t>)/su', static function ($m) use ($escaped, $keys) {
            $text = $m[2];
            foreach ($keys as $k) {
                if (isset($escaped[$k]) && $escaped[$k] !== '') {
                    $text = str_replace($k, $escaped[$k], $text);
                }
            }
            return $m[1] . $text . $m[3];
        }, $xml);

        $xml = DokumenPengadaanHelpers::replaceAcrossTags($xml, $escaped, ['quantity']);

        $programReplacement = $program !== '' ? $program : '-';
        $escapedProgram = htmlspecialchars($programReplacement, ENT_QUOTES | ENT_XML1, 'UTF-8');
        $xml = DokumenPengadaanHelpers::replaceStandaloneToken($xml, 'PRO', $escapedProgram);

        $xml = preg_replace_callback('/(<w:t[^>]*>)([^<]*\n[^<]*)(<\/w:t>)/u', static function (array $matches): string {
            $text = $matches[2];
            $parts = explode("\n", $text);
            $first = array_shift($parts);
            $buffer = $matches[1] . $first . '</w:t>';
            foreach ($parts as $part) {
                $buffer .= '<w:br/><w:t>' . $part . '</w:t>';
            }
            return $buffer;
        }, $xml);

        $xml = preg_replace('/<w:color[^>]*\/>/i', '', $xml);
        if ($xml === null) {
            throw new RuntimeException('Gagal menormalkan warna teks dokumen pengadaan');
        }
        $xml = preg_replace('/<w:color[^>]*>.*?<\/w:color>/is', '', $xml);
        if ($xml === null) {
            throw new RuntimeException('Gagal menormalkan warna teks dokumen pengadaan');
        }

        $zip->addFromString('word/document.xml', $xml);
        $ok = @$zip->close();
        if ($ok === false) {
            if (is_file($output)) @unlink($output);
            throw new RuntimeException('Gagal menyimpan dokumen pengadaan (zip close failed)');
        }

        return $output;
    }

}

