<?php
declare(strict_types=1);

require_once __DIR__ . '/LembarKegiatanExporter.php';
require_once __DIR__ . '/BeritaAcaraExporter.php';
require_once __DIR__ . '/DokumenPengadaanExporter.php';
require_once __DIR__ . '/KwitansiExporter.php';
require_once __DIR__ . '/NotaDinasExporter.php';
require_once __DIR__ . '/SptpdExporter.php';

final class DocumentGenerator
{
    public static function buildStatuses(int $generalId, array $workspace): array
    {
        $definitions = self::definitions();
        $results = [];
        $now = (new DateTimeImmutable('now'))->format(DateTimeInterface::ATOM);

        foreach ($definitions as $key => $def) {
            // Kita tidak generate file di sini, kita hanya kasih link download dinamis
            // "Generator" hanya dijalankan saat user klik Unduh di frontend
            $results[] = [
                'key' => $key,
                'label' => $def['label'],
                'filename' => $def['filename'],
                'status' => 'success', // Selalu success karena data sudah di DB
                'message' => 'Siap diunduh',
                // Link menunjuk ke api/generate.php
                'download_url' => "generate.php?type={$key}&id={$generalId}",
                'general_id' => $generalId,
                'updated_at' => $now,
            ];
        }

        return $results;
    }
    
    // Hapus method persistGeneratedFile() karena tidak dipakai lagi.

    private static function definitions(): array
    {
        return [
            'dokumen_pengadaan' => [
                'label' => 'Dokumen Pengadaan',
                'filename' => 'dokumen_pengadaan.docx',
                'generator' => static fn (array $data): string => DokumenPengadaanExporter::generate($data),
            ],
            'berita_acara' => [
                'label' => 'Berita Acara',
                'filename' => 'berita_acara.docx',
                'generator' => static fn (array $data): string => BeritaAcaraExporter::generate($data),
            ],
            'kwitansi' => [
                'label' => 'Kwitansi',
                'filename' => 'kwitansi.xlsx',
                'generator' => static fn (array $data): string => KwitansiExporter::generate($data),
            ],
            'lembar_kegiatan' => [
                'label' => 'Lembar Kegiatan',
                'filename' => 'lembar_kegiatan.docx',
                'generator' => static fn (array $data): string => LembarKegiatanExporter::generateFromWorkspace($data),
            ],
            'nota_dinas' => [
                'label' => 'Nota Dinas',
                'filename' => 'nota_dinas.xlsx',
                'generator' => static fn (array $data): string => NotaDinasExporter::generate($data),
            ],
            'sptpd' => [
                'label' => 'SPTPD',
                'filename' => 'sptpd.xlsx',
                'generator' => static fn (array $data): string => SptpdExporter::generate($data),
            ],
        ];
    }

    
}
