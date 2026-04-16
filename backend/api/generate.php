<?php
declare(strict_types=1);

require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../config.php';
require __DIR__ . '/../services/WorkspaceData.php';
require __DIR__ . '/../services/DocumentGenerator.php'; // Load semua exporter

require_auth();

$type = $_GET['type'] ?? '';
$id = (int) ($_GET['id'] ?? 0); // Ini adalah General ID

if ($id <= 0 || !in_array($type, ['dokumen_pengadaan', 'berita_acara', 'kwitansi', 'lembar_kegiatan', 'nota_dinas', 'sptpd'], true)) {
    http_response_code(400);
    exit('Parameter tidak valid');
}

try {
    $db = get_db_connection();
    // Ambil data live dari DB
    $workspace = WorkspaceData::fetch($db, $id);
    
    $filePath = '';
    $filename = '';

    // Generate ke folder TEMP sistem (bukan storage project)
    switch ($type) {
        case 'berita_acara':
            $filePath = BeritaAcaraExporter::generate($workspace);
            $filename = 'berita_acara.docx';
            break;
        case 'dokumen_pengadaan':
            $filePath = DokumenPengadaanExporter::generate($workspace);
            $filename = 'dokumen_pengadaan.docx';
            break;
        case 'kwitansi':
            $filePath = KwitansiExporter::generate($workspace);
            $filename = 'kwitansi.xlsx';
            break;
        case 'lembar_kegiatan':
            $filePath = LembarKegiatanExporter::generateFromWorkspace($workspace);
            $filename = 'lembar_kegiatan.docx';
            break;
        case 'nota_dinas':
            $filePath = NotaDinasExporter::generate($workspace);
            $filename = 'nota_dinas.xlsx';
            break;
        case 'sptpd':
            $filePath = SptpdExporter::generate($workspace);
            $filename = 'sptpd.xlsx';
            break;
    }

    $db->close();

    if (!is_file($filePath)) {
        throw new RuntimeException('Dokumen gagal dibuat');
    }

    // Serve file untuk download
    // Clear any output buffers to avoid corrupting binary output
    while (ob_get_level()) ob_end_clean();

    header('Content-Description: File Transfer');
    // Use appropriate content type for known extensions
    $contentType = 'application/octet-stream';
    if (str_ends_with($filename, '.docx')) {
        $contentType = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
    } elseif (str_ends_with($filename, '.xlsx')) {
        $contentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    }
    header('Content-Type: ' . $contentType);
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($filePath));

    readfile($filePath);

    // Hapus file temp setelah dikirim
    unlink($filePath);
    exit;

} catch (Throwable $th) {
    if (isset($filePath) && is_file($filePath)) unlink($filePath);
    http_response_code(500);
    echo 'Error: ' . $th->getMessage();
}