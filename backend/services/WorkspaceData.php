<?php
declare(strict_types=1);

final class WorkspaceData
{
    public static function fetch(mysqli $db, int $generalId): array
    {
        // 1. Fetch General
        $general = self::fetchRow(
            $db,
            'SELECT id_general AS id, objek, tanggal FROM general WHERE id_general = ? LIMIT 1',
            $generalId
        );
        if (!$general) {
            throw new InvalidArgumentException('Data umum belum tersedia. Simpan form terlebih dahulu.');
        }

        // 2. Fetch Dokumen Pengadaan
        $dokumen = self::fetchRow(
            $db,
            'SELECT dp.id_dokumen AS id, dp.id_general AS general_id, dp.nama_paket, dp.kode_rup, dp.nomor_dpp, dp.pagu_anggaran, dp.tanggal_mulai, dp.tanggal_selesai, dp.id_ppk AS pembuat_komitmen_id,
                pk.nama_pembuat_komitmen AS ppk_nama, pk.nip_pembuat_komitmen AS ppk_nip, pk.keterangan_pembuat_komitmen AS ppk_keterangan
            FROM dokumen_pengadaan dp
            LEFT JOIN pembuat_komitmen pk ON dp.id_ppk = pk.id_ppk
            WHERE dp.id_general = ?
            ORDER BY dp.id_dokumen DESC LIMIT 1',
            $generalId
        );

        // 3. Fetch Lembar Kegiatan
        $lembar = self::fetchRow(
            $db,
                'SELECT lk.*, lk.id_lembar AS id,
                    -- provide both long and short aliases for compatibility
                    kd.nama_kepdin AS kepdin_nama, kd.nama_kepdin AS namekepdin, kd.nip_kepdin AS kepdin_nip,
                    bd.nama_bendahara AS bendahara_nama, bd.nama_bendahara AS namebendahara, bd.nip_bendahara AS bendahara_nip, bd.nip_bendahara AS nipbendahara
             FROM lembar_kegiatan lk
                 LEFT JOIN kepdin kd ON lk.id_kepdin = kd.id_kepdin
                 LEFT JOIN bendahara bd ON lk.id_bendahara = bd.id_bendahara
             WHERE lk.id_general = ? LIMIT 1',
            $generalId
        );

        // 4. Fetch Berita Acara (UPDATE LENGKAP)
        $berita = self::fetchRow(
            $db,
            'SELECT ba.*, ba.id_berita AS id,
                kd.nama_kepdin AS kepdin_nama, kd.nip_kepdin AS kepdin_nip,
                pk.nama_pembuat_komitmen AS ppk_nama, pk.nip_pembuat_komitmen AS ppk_nip,
                
                -- UPDATE: Pastikan kolom ini sesuai dengan tabel penyedia Anda
                py.nama_penyedia AS penyedia_nama, 
                py.nama_orang AS penyedia_nama_orang, 
                py.alamat AS penyedia_alamat_penyedia,
                py.keterangan AS penyedia_keterangan,
                
                -- Karena tidak ada kolom nama_badan_usaha di DB, kita pakai nama_penyedia
                py.nama_penyedia AS penyedia_badan_usaha,
                py.keterangan AS penyedia_keterangan

            FROM berita_acara ba
            LEFT JOIN kepdin kd ON ba.id_kepdin = kd.id_kepdin
            LEFT JOIN pembuat_komitmen pk ON ba.id_ppk = pk.id_ppk
            LEFT JOIN penyedia py ON ba.id_penyedia = py.id_penyedia
            WHERE ba.id_general = ? LIMIT 1',
            $generalId
        );

        // 5. Kwitansi (join master tables so exporters can read names/NIP)
        $kwitansi = self::fetchRow(
            $db,
            'SELECT kw.*, kw.id_kwitansi AS id,
                    kd.nama_kepdin AS kepdin_nama, kd.nip_kepdin AS kepdin_nip,
                    bd.nama_bendahara AS bendahara_nama, bd.nip_bendahara AS bendahara_nip
             FROM kwitansi kw
             LEFT JOIN kepdin kd ON kw.id_kepdin = kd.id_kepdin
             LEFT JOIN bendahara bd ON kw.id_bendahara = bd.id_bendahara
             WHERE kw.id_general = ? LIMIT 1',
            $generalId
        );

        // 6. Nota Dinas
        // Include kabid, pembuat_komitmen (via dokumen_pengadaan), and aggregated spesifikasi total
        $nota = self::fetchRow(
            $db,
            'SELECT nd.*, nd.id_nota AS id, nd.tujuan, nd.asal, nd.tanggal, nd.nomor, nd.keperluan, nd.jumlah_dpa, nd.sifat, nd.perihal, nd.id_kabid AS kabid_id,
                    kab.nama_kabid_ppa AS kabid_nama, kab.nip_kabid_ppa AS kabid_nip,
                    pk.nama_pembuat_komitmen AS nama_pembuat_komitmen, pk.nip_pembuat_komitmen AS nip_pembuat_komitmen,
                    pk.nama_pembuat_komitmen AS ppk_nama, pk.nip_pembuat_komitmen AS ppk_nip,
                    (SELECT COALESCE(SUM(sa.pagu_anggaran),0) FROM spesifikasi_anggaran sa WHERE sa.id_general = nd.id_general) AS total_spesifikasi,
                    (SELECT COALESCE(SUM(sa.pagu_anggaran),0) FROM spesifikasi_anggaran sa WHERE sa.id_general = nd.id_general) AS total
             FROM nota_dinas nd
             LEFT JOIN kabid_ppa kab ON nd.id_kabid = kab.id_kabid
             LEFT JOIN dokumen_pengadaan dp ON dp.id_general = nd.id_general
             LEFT JOIN pembuat_komitmen pk ON dp.id_ppk = pk.id_ppk
             WHERE nd.id_general = ?
             ORDER BY nd.id_nota DESC LIMIT 1',
            $generalId
        );
        if (!$nota) {
            throw new InvalidArgumentException('Lengkapi bagian nota dinas sebelum generate.');
        }

        // 7. SPTPD
        $sptpd = self::fetchRow(
            $db,
            'SELECT sp.*, sp.id_sptpd AS id, sp.telp_kantor 
             FROM sptpd sp 
             WHERE sp.id_general = ? LIMIT 1',
            $generalId
        );

        // 8. Spesifikasi
        $spesifikasi = self::fetchAll(
            $db,
            'SELECT sa.*, sa.id_spesifikasi AS id FROM spesifikasi_anggaran sa WHERE sa.id_general = ? ORDER BY sa.id_spesifikasi ASC',
            $generalId
        );

        return [
            'general' => $general,
            'dokumen_pengadaan' => $dokumen,
            'lembar_kegiatan' => $lembar,
            'berita_acara' => $berita,
            'kwitansi' => $kwitansi,
            'nota_dinas' => $nota,
            'sptpd' => $sptpd,
            'spesifikasi_anggaran' => $spesifikasi,
        ];
    }

    private static function fetchRow(mysqli $db, string $sql, int $id): ?array
    {
        $stmt = $db->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    private static function fetchAll(mysqli $db, string $sql, int $id): array
    {
        $stmt = $db->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) $rows[] = $row;
        $stmt->close();
        return $rows;
    }
}