<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/Formatter.php';
require_once __DIR__ . '/PlaceholderHelper.php';
require_once __DIR__ . '/WorkspaceData.php';
require_once __DIR__ . '/LembarKegiatanHelpers.php';

final class LembarKegiatanExporter
{

    public static function generate(mysqli $db, int $id): string
    {
        $stmt = $db->prepare('SELECT id_general FROM lembar_kegiatan WHERE id_lembar = ? LIMIT 1');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            throw new InvalidArgumentException('Lembar kegiatan tidak ditemukan');
        }

        $generalId = (int) ($row['id_general'] ?? 0);
        if ($generalId <= 0) {
            throw new RuntimeException('Relasi general untuk lembar kegiatan belum tersedia');
        }

        $workspace = WorkspaceData::fetch($db, $generalId);

        return self::generateFromWorkspace($workspace);
    }

    public static function generateFromWorkspace(array $workspace): string
    {
        $general = $workspace['general'] ?? null;
        $dokumen = $workspace['dokumen_pengadaan'] ?? null;
        $lembar = $workspace['lembar_kegiatan'] ?? null;
        $specs = $workspace['spesifikasi_anggaran'] ?? [];

        if (!$general) {
            throw new InvalidArgumentException('Data umum belum tersedia');
        }

        if (!$dokumen) {
            throw new InvalidArgumentException('Data dokumen pengadaan belum tersedia');
        }

        if (!$lembar) {
            throw new InvalidArgumentException('Data lembar kegiatan belum tersedia');
        }

        if (count($specs) === 0) {
            throw new InvalidArgumentException('Spesifikasi anggaran belum diisi');
        }

        $normalizedItems = self::normalizeItems(array_map(
            static function (array $item): array {
                $uraian = (string) ($item['spesifikasi'] ?? '');
                $jumlah = (int) ($item['spesifikasi_jumlah'] ?? 0);
                $satuan = trim((string) ($item['satuan_ukuran'] ?? ''));
                $volume = trim($jumlah . ' ' . $satuan);

                return [
                    'uraian' => $uraian,
                    'spesifikasi' => $uraian,
                    'spesifikasi_jumlah' => $volume !== '' ? $volume : (string) $jumlah,
                    'harga_satuan' => (float) ($item['harga_satuan'] ?? 0),
                    'jumlah_uang' => (float) ($item['pagu_anggaran'] ?? 0),
                ];
            },
            $specs
        ));

        $totalAmount = array_reduce(
            $normalizedItems,
            static fn (float $carry, array $item): float => $carry + (float) ($item['jumlah_uang'] ?? 0),
            0.0
        );

        $daftar = (string) ($lembar['daftar'] ?? $general['objek'] ?? '');
        $programLabel = (string) ($lembar['program'] ?? $dokumen['nama_paket'] ?? '');
        $activityLabel = (string) ($lembar['kegiatan'] ?? $programLabel);
        $subActivityLabel = (string) ($lembar['sub_kegiatan'] ?? $activityLabel);
        $kodeRekening = (string) ($lembar['kode_rekening'] ?? $dokumen['kode_rup'] ?? '');
        $bulanReference = (string) ($lembar['bulan'] ?? ($general['tanggal'] ?? ''));
        $kepalaName = (string) ($lembar['kepdin_nama'] ?? $workspace['berita_acara']['kepdin_nama'] ?? $workspace['kwitansi']['kepdin_nama'] ?? '');
        $kepalaNip = (string) ($lembar['kepdin_nip'] ?? $workspace['berita_acara']['kepdin_nip'] ?? $workspace['kwitansi']['kepdin_nip'] ?? '');
        $bendaharaName = (string) ($lembar['bendahara_nama'] ?? $workspace['kwitansi']['bendahara_nama'] ?? '');
        $bendaharaNip = (string) ($lembar['bendahara_nip'] ?? $workspace['kwitansi']['bendahara_nip'] ?? '');
        $ppkName = (string) ($dokumen['ppk_nama'] ?? $workspace['berita_acara']['ppk_nama'] ?? '');
        $ppkNip = (string) ($dokumen['ppk_nip'] ?? $workspace['berita_acara']['ppk_nip'] ?? '');

        $payload = [
            'id' => (int) ($lembar['id'] ?? $general['id'] ?? 0),
            'daftar' => $daftar,
            'program' => $programLabel,
            'activity' => $activityLabel,
            'sub_activity' => $subActivityLabel,
            'kode_rekening' => $kodeRekening,
            'bulan_label' => format_month_label($bulanReference),
            'kepala' => [
                'name' => $kepalaName,
                'nip' => $kepalaNip,
            ],
            'bendahara' => [
                'name' => $bendaharaName,
                'nip' => $bendaharaNip,
            ],
            'pembuat' => [
                'name' => $ppkName,
                'nip' => $ppkNip,
            ],
            'items' => $normalizedItems,
            'total_amount' => $totalAmount,
            'total_plain' => PlaceholderHelper::plainCurrency($totalAmount),
            'terbilang' => strtoupper(trim(terbilang($totalAmount))) . ' RUPIAH',
        ];

        return self::render($payload);
    }

    private static function render(array $payload): string
    {
        $template = realpath(__DIR__ . '/../../template/lembar_kegiatan.docx');
        if ($template === false || !is_file($template)) {
            throw new RuntimeException('Template lembar_kegiatan.docx tidak ditemukan');
        }

        $output = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lembar_kegiatan_' . uniqid('', true) . '.docx';
        if (!copy($template, $output)) {
            throw new RuntimeException('Gagal menyalin template lembar kegiatan');
        }

        $zip = new ZipArchive();
        if ($zip->open($output) !== true) {
            throw new RuntimeException('Tidak dapat membuka dokumen lembar kegiatan');
        }

        $xml = $zip->getFromName('word/document.xml');
        if ($xml === false) {
            $zip->close();
            throw new RuntimeException('Berkas document.xml tidak ditemukan dalam template lembar kegiatan');
        }

        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        libxml_use_internal_errors(true);
        if (!$dom->loadXML($xml)) {
            $zip->close();
            $errors = libxml_get_errors();
            libxml_clear_errors();
            libxml_use_internal_errors(false);
            throw new RuntimeException('Gagal memuat template lembar kegiatan: ' . ($errors[0]->message ?? 'unknown error'));
        }
        libxml_clear_errors();
        libxml_use_internal_errors(false);

        LembarKegiatanHelpers::applyPlaceholders($dom, $payload);

        $zip->addFromString('word/document.xml', $dom->saveXML());
        $zip->close();

        return $output;
    }

    private static function normalizeItems(array $items): array
    {
        $normalized = [];

        foreach ($items as $item) {
            $uraian = trim((string) ($item['uraian'] ?? $item['spesifikasi'] ?? ''));
            $spesifikasi = trim((string) ($item['spesifikasi'] ?? $uraian));

            $rawQty = $item['spesifikasi_jumlah'] ?? '';
            $satuan = trim((string) ($item['satuan_ukuran'] ?? ''));
            if ($rawQty === '' && isset($item['jumlah'])) {
                $rawQty = $item['jumlah'];
            }

            if (is_numeric($rawQty) && $satuan !== '') {
                $volume = trim((string) $rawQty . ' ' . $satuan);
            } else {
                $volume = trim((string) $rawQty);
            }

            $harga = (float) ($item['harga_satuan'] ?? 0);
            $jumlahUang = (float) ($item['jumlah_uang'] ?? $item['pagu_anggaran'] ?? 0);

            if ($jumlahUang <= 0 && is_numeric($rawQty)) {
                $jumlahUang = $harga * (float) $rawQty;
            }

            $normalized[] = [
                'uraian' => $uraian,
                'spesifikasi' => $spesifikasi !== '' ? $spesifikasi : $uraian,
                'spesifikasi_jumlah' => $volume !== '' ? $volume : (string) $rawQty,
                'harga_satuan' => $harga,
                'jumlah_uang' => $jumlahUang,
            ];
        }

        return $normalized;
    }

    // DOM placeholder implementation moved to LembarKegiatanHelpers.php
}
