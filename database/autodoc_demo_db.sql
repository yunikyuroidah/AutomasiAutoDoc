-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Apr 16, 2026 at 06:17 AM
-- Server version: 8.0.43
-- PHP Version: 8.2.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `autodoc_demo_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `bendahara`
--

CREATE TABLE `bendahara` (
  `id_bendahara` int NOT NULL,
  `nama_bendahara` varchar(150) DEFAULT NULL,
  `nip_bendahara` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `bendahara`
--

INSERT INTO `bendahara` (`id_bendahara`, `nama_bendahara`, `nip_bendahara`) VALUES
(1, 'BENDAHARA DUMMY SATU', '000000000000000001');

-- --------------------------------------------------------

--
-- Table structure for table `berita_acara`
--

CREATE TABLE `berita_acara` (
  `id_berita` int NOT NULL,
  `id_general` int DEFAULT NULL,
  `nomor_satuan_kerja` varchar(100) DEFAULT NULL,
  `tanggal_satuan_kerja` date DEFAULT NULL,
  `nomor_sp` varchar(150) DEFAULT NULL,
  `tanggal_sp` date DEFAULT NULL,
  `id_kepdin` int DEFAULT NULL,
  `id_ppk` int DEFAULT NULL,
  `id_penyedia` int DEFAULT NULL,
  `nomor_sk_bahp` varchar(100) DEFAULT NULL,
  `tanggal_sk_bahp` date DEFAULT NULL,
  `nomor_sp_bahp` varchar(100) DEFAULT NULL,
  `nomor_kontrak_bahp` varchar(150) DEFAULT NULL,
  `tanggal_sp_bahp` date DEFAULT NULL,
  `keterangan` text,
  `tanggal_serah_terima` date DEFAULT NULL,
  `paket_pekerjaan` text,
  `paket_pekerjaan_administratif` text,
  `nomor_sk_bahpa` varchar(100) DEFAULT NULL,
  `tanggal_sk_bahpa` date DEFAULT NULL,
  `nomor_sp_bahpa` varchar(100) DEFAULT NULL,
  `tanggal_sp_bahpa` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `berita_acara`
--

INSERT INTO `berita_acara` (`id_berita`, `id_general`, `nomor_satuan_kerja`, `tanggal_satuan_kerja`, `nomor_sp`, `tanggal_sp`, `id_kepdin`, `id_ppk`, `id_penyedia`, `nomor_sk_bahp`, `tanggal_sk_bahp`, `nomor_sp_bahp`, `nomor_kontrak_bahp`, `tanggal_sp_bahp`, `keterangan`, `tanggal_serah_terima`, `paket_pekerjaan`, `paket_pekerjaan_administratif`, `nomor_sk_bahpa`, `tanggal_sk_bahpa`, `nomor_sp_bahpa`, `tanggal_sp_bahpa`) VALUES
(1, 1, 'DUMMY-SATKER-001', '2026-01-19', 'DUMMY-SP-001', '2025-11-03', 1, 2, 1, 'DUMMY/SK-BAHP/001', '2026-02-11', 'DUMMY/SP-BAHP/001', 'DUMMY-KONTRAK-001', '2026-02-12', 'Data kegiatan dummy', '2026-01-19', 'PAKET KEGIATAN FIKTIF UNTUK UJI SISTEM', 'PAKET ADMINISTRATIF FIKTIF UNTUK UJI SISTEM', 'DUMMY/SK-BAHPA/001', '2025-07-22', 'DUMMY/SP-BAHPA/001', '2025-07-21');

-- --------------------------------------------------------

--
-- Table structure for table `dokumen_pengadaan`
--

CREATE TABLE `dokumen_pengadaan` (
  `id_dokumen` int NOT NULL,
  `id_general` int DEFAULT NULL,
  `nama_paket` varchar(150) DEFAULT NULL,
  `kode_rup` varchar(50) DEFAULT NULL,
  `pagu_anggaran` decimal(15,2) DEFAULT NULL,
  `tanggal_mulai` date DEFAULT NULL,
  `tanggal_selesai` date DEFAULT NULL,
  `id_ppk` int DEFAULT NULL,
  `nomor_dpp` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `dokumen_pengadaan`
--

INSERT INTO `dokumen_pengadaan` (`id_dokumen`, `id_general`, `nama_paket`, `kode_rup`, `pagu_anggaran`, `tanggal_mulai`, `tanggal_selesai`, `id_ppk`, `nomor_dpp`) VALUES
(1, 1, 'PAKET PENGADAAN DUMMY', 'DUMMY-RUP-001', '34609000.00', '2026-02-11', '2026-02-13', 2, 'DPP-DUMMY-001');

-- --------------------------------------------------------

--
-- Table structure for table `general`
--

CREATE TABLE `general` (
  `id_general` int NOT NULL,
  `objek` varchar(255) NOT NULL,
  `tanggal` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `general`
--

INSERT INTO `general` (`id_general`, `objek`, `tanggal`) VALUES
(1, 'OBJEK DUMMY FIKTIF', '2026-02-11');

-- --------------------------------------------------------

--
-- Table structure for table `kabid_ppa`
--

CREATE TABLE `kabid_ppa` (
  `id_kabid` int NOT NULL,
  `nama_kabid_ppa` varchar(150) DEFAULT NULL,
  `nip_kabid_ppa` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `kabid_ppa`
--

INSERT INTO `kabid_ppa` (`id_kabid`, `nama_kabid_ppa`, `nip_kabid_ppa`) VALUES
(1, 'NAMA DUMMY KABID', '000000000000000002');

-- --------------------------------------------------------

--
-- Table structure for table `kepdin`
--

CREATE TABLE `kepdin` (
  `id_kepdin` int NOT NULL,
  `nama_kepdin` varchar(150) DEFAULT NULL,
  `nip_kepdin` varchar(30) DEFAULT NULL,
  `keterangan_kepdin` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `kepdin`
--

INSERT INTO `kepdin` (`id_kepdin`, `nama_kepdin`, `nip_kepdin`, `keterangan_kepdin`) VALUES
(1, 'NAMA DUMMY KEPALA DINAS', '000000000000000003', 'PANGKAT DUMMY');

-- --------------------------------------------------------

--
-- Table structure for table `kwitansi`
--

CREATE TABLE `kwitansi` (
  `id_kwitansi` int NOT NULL,
  `id_general` int DEFAULT NULL,
  `nama_penerima` varchar(150) DEFAULT NULL,
  `nama_bank` varchar(100) DEFAULT NULL,
  `npwp` varchar(50) DEFAULT NULL,
  `norek` varchar(50) DEFAULT NULL,
  `tanggal_pembayaran` date NOT NULL,
  `jumlah_uang` decimal(18,2) NOT NULL DEFAULT '0.00',
  `id_kepdin` int DEFAULT NULL,
  `id_bendahara` int DEFAULT NULL,
  `id_pptk` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `kwitansi`
--

INSERT INTO `kwitansi` (`id_kwitansi`, `id_general`, `nama_penerima`, `nama_bank`, `npwp`, `norek`, `tanggal_pembayaran`, `jumlah_uang`, `id_kepdin`, `id_bendahara`, `id_pptk`) VALUES
(1, 1, 'PENERIMA DUMMY / CV FIKTIF', 'BANK DUMMY', '00.000.000.0-000.000', '0000000000', '2026-02-12', '4505000.00', 1, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `lembar_kegiatan`
--

CREATE TABLE `lembar_kegiatan` (
  `id_lembar` int NOT NULL,
  `id_general` int DEFAULT NULL,
  `bulan` varchar(50) DEFAULT NULL,
  `daftar` text,
  `program` text,
  `kegiatan` text,
  `sub_kegiatan` text,
  `kode_rekening` varchar(50) DEFAULT NULL,
  `sumber_dana` varchar(100) DEFAULT NULL,
  `id_kepdin` int DEFAULT NULL,
  `id_bendahara` int DEFAULT NULL,
  `id_pptk` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `lembar_kegiatan`
--

INSERT INTO `lembar_kegiatan` (`id_lembar`, `id_general`, `bulan`, `daftar`, `program`, `kegiatan`, `sub_kegiatan`, `kode_rekening`, `sumber_dana`, `id_kepdin`, `id_bendahara`, `id_pptk`) VALUES
(1, 1, 'FEBRUARI 2026', 'BELANJA DUMMY', 'PROGRAM DUMMY', 'KEGIATAN FIKTIF UNTUK TESTING', 'SUB KEGIATAN FIKTIF UNTUK TESTING', '2.08.07.2.01.0004.5.1.02.01.0052', 'SUMBER DANA DUMMY', 1, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `nota_dinas`
--

CREATE TABLE `nota_dinas` (
  `id_nota` int NOT NULL,
  `id_general` int DEFAULT NULL,
  `tujuan` text,
  `asal` text,
  `tanggal` date DEFAULT NULL,
  `nomor` varchar(100) DEFAULT NULL,
  `sifat` varchar(50) DEFAULT NULL,
  `perihal` text,
  `keperluan` text,
  `pemerintahan` varchar(50) DEFAULT NULL,
  `jumlah_dpa` decimal(15,2) DEFAULT NULL,
  `tahun_anggaran` year DEFAULT NULL,
  `id_kabid` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `nota_dinas`
--

INSERT INTO `nota_dinas` (`id_nota`, `id_general`, `tujuan`, `asal`, `tanggal`, `nomor`, `sifat`, `perihal`, `keperluan`, `pemerintahan`, `jumlah_dpa`, `tahun_anggaran`, `id_kabid`) VALUES
(1, 1, 'TUJUAN UNIT DUMMY', 'ASAL UNIT DUMMY', '2025-11-06', 'ND/DUMMY/001', 'Penting', 'PERIHAL DUMMY', 'KEPERLUAN DUMMY UNTUK UJI SISTEM', 'UNIT DUMMY', '80050000.00', 2026, 1);

-- --------------------------------------------------------

--
-- Table structure for table `pembuat_komitmen`
--

CREATE TABLE `pembuat_komitmen` (
  `id_ppk` int NOT NULL,
  `nama_pembuat_komitmen` varchar(150) DEFAULT NULL,
  `nip_pembuat_komitmen` varchar(30) DEFAULT NULL,
  `keterangan_pembuat_komitmen` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `pembuat_komitmen`
--

INSERT INTO `pembuat_komitmen` (`id_ppk`, `nama_pembuat_komitmen`, `nip_pembuat_komitmen`, `keterangan_pembuat_komitmen`) VALUES
(1, 'NAMA DUMMY PPK SATU', '000000000000000004', 'JABATAN DUMMY 1'),
(2, 'NAMA DUMMY PPK DUA', '000000000000000005', 'JABATAN DUMMY 2');

-- --------------------------------------------------------

--
-- Table structure for table `penyedia`
--

CREATE TABLE `penyedia` (
  `id_penyedia` int NOT NULL,
  `nama_penyedia` varchar(150) DEFAULT NULL,
  `keterangan` text,
  `alamat` text,
  `nama_orang` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `penyedia`
--

INSERT INTO `penyedia` (`id_penyedia`, `nama_penyedia`, `keterangan`, `alamat`, `nama_orang`) VALUES
(1, 'CV PENYEDIA DUMMY', 'PENANGGUNG JAWAB DUMMY', 'ALAMAT DUMMY KOTA FIKTIF', 'KONTAK DUMMY');

-- --------------------------------------------------------

--
-- Table structure for table `permohonan_narasumber`
--

CREATE TABLE `permohonan_narasumber` (
  `id_permohonan` int NOT NULL,
  `tanggal` date DEFAULT NULL,
  `sifat` varchar(50) DEFAULT NULL,
  `tujuan` varchar(150) DEFAULT NULL,
  `hari_tanggal` varchar(100) DEFAULT NULL,
  `pukul` varchar(50) DEFAULT NULL,
  `tempat` text,
  `acara` text,
  `id_kepdin` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `permohonan_narasumber`
--

INSERT INTO `permohonan_narasumber` (`id_permohonan`, `tanggal`, `sifat`, `tujuan`, `hari_tanggal`, `pukul`, `tempat`, `acara`, `id_kepdin`) VALUES
(1, '2025-11-03', 'Biasa', 'INSTANSI DUMMY', 'Senin, 01 Januari 2026', '10.00 WIB - selesai', 'RUANG RAPAT DUMMY', 'ACARA DUMMY UNTUK SIMULASI', 1);

-- --------------------------------------------------------

--
-- Table structure for table `pptk`
--

CREATE TABLE `pptk` (
  `id_pptk` int NOT NULL,
  `nama_pptk` varchar(150) DEFAULT NULL,
  `nip_pptk` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `pptk`
--

INSERT INTO `pptk` (`id_pptk`, `nama_pptk`, `nip_pptk`) VALUES
(1, 'NAMA DUMMY PPTK', '000000000000000006');

-- --------------------------------------------------------

--
-- Table structure for table `spesifikasi_anggaran`
--

CREATE TABLE `spesifikasi_anggaran` (
  `id_spesifikasi` int NOT NULL,
  `id_general` int DEFAULT NULL,
  `spesifikasi` varchar(100) DEFAULT NULL,
  `spesifikasi_jumlah` int DEFAULT NULL,
  `harga_satuan` decimal(15,2) DEFAULT NULL,
  `pagu_anggaran` decimal(15,2) DEFAULT NULL,
  `satuan_ukuran` varchar(50) DEFAULT NULL,
  `jumlah` int DEFAULT NULL,
  `pajak_daerah` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `spesifikasi_anggaran`
--

INSERT INTO `spesifikasi_anggaran` (`id_spesifikasi`, `id_general`, `spesifikasi`, `spesifikasi_jumlah`, `harga_satuan`, `pagu_anggaran`, `satuan_ukuran`, `jumlah`, `pajak_daerah`) VALUES
(347, 1, 'Item Dummy A', 80, '30000.00', '2400000.00', 'kotak', NULL, NULL),
(348, 1, 'Item Dummy B', 85, '23000.00', '1955000.00', 'kotak', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sptpd`
--

CREATE TABLE `sptpd` (
  `id_sptpd` int NOT NULL,
  `id_general` int DEFAULT NULL,
  `tahun` year DEFAULT NULL,
  `pekerjaan` text,
  `harga_jual` decimal(15,2) DEFAULT NULL,
  `dasar_pengenaan_pajak` decimal(15,2) DEFAULT NULL,
  `pajak_terhutang` decimal(15,2) DEFAULT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `jabatan` varchar(50) DEFAULT NULL,
  `telp_kantor` varchar(255) DEFAULT NULL,
  `nama_badan_usaha` varchar(150) DEFAULT NULL,
  `alamat_badan_usaha` text,
  `kontak` varchar(50) DEFAULT NULL,
  `masa_pajak` varchar(20) DEFAULT NULL,
  `dasar_pengenaan_pajak_restoran` decimal(15,2) DEFAULT NULL,
  `pajak_restoran_terhutang` decimal(15,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `sptpd`
--

INSERT INTO `sptpd` (`id_sptpd`, `id_general`, `tahun`, `pekerjaan`, `harga_jual`, `dasar_pengenaan_pajak`, `pajak_terhutang`, `nama`, `jabatan`, `telp_kantor`, `nama_badan_usaha`, `alamat_badan_usaha`, `kontak`, `masa_pajak`, `dasar_pengenaan_pajak_restoran`, `pajak_restoran_terhutang`) VALUES
(1, 1, 2026, 'KEGIATAN DUMMY UNTUK PERHITUNGAN PAJAK', '440000.00', '400000.00', '40000.00', 'NAMA DUMMY', 'JABATAN DUMMY', '000', 'BADAN USAHA DUMMY', 'ALAMAT USAHA DUMMY', '080000000000', 'JANUARI', '4095454.00', '409545.45');

-- --------------------------------------------------------

--
-- Table structure for table `undangan_peminjaman`
--

CREATE TABLE `undangan_peminjaman` (
  `id_undangan` int NOT NULL,
  `id_general` int DEFAULT NULL,
  `tanggal_peminjaman` date DEFAULT NULL,
  `tanggal_undangan` date DEFAULT NULL,
  `hal_peminjaman` text,
  `hal_undangan` text,
  `tujuan` text,
  `jumlah_kehadiran` int DEFAULT NULL,
  `nomor` varchar(100) DEFAULT NULL,
  `tanggal_pelaksanaan` date DEFAULT NULL,
  `pukul` varchar(50) DEFAULT NULL,
  `tempat` text,
  `narasumber` text,
  `id_kepdin` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `undangan_peminjaman`
--

INSERT INTO `undangan_peminjaman` (`id_undangan`, `id_general`, `tanggal_peminjaman`, `tanggal_undangan`, `hal_peminjaman`, `hal_undangan`, `tujuan`, `jumlah_kehadiran`, `nomor`, `tanggal_pelaksanaan`, `pukul`, `tempat`, `narasumber`, `id_kepdin`) VALUES
(1, 1, '2025-10-30', '2025-10-29', 'Peminjaman fasilitas dummy', 'Undangan kegiatan dummy', 'PESERTA DUMMY SATU; PESERTA DUMMY DUA', 4, 'UND/DUMMY/001', '2025-11-04', '09.00 WIB - selesai', 'LOKASI DUMMY', 'NARASUMBER DUMMY', 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`) VALUES
(1, 'dummy_admin', '91971ba9b2045d322029096975709b78559a6470f3b92890067acb520acdc79e'),
(2, 'dummy_operator', 'a7260fccdb512f0381f8ae17432376e12d5efca4be1120766d45fb893363e7ae');

--
-- Triggers `users`
--
DELIMITER $$
CREATE TRIGGER `trg_users_hash_password` BEFORE INSERT ON `users` FOR EACH ROW BEGIN
    SET NEW.password = SHA2(NEW.password, 256);
END
$$
DELIMITER ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bendahara`
--
ALTER TABLE `bendahara`
  ADD PRIMARY KEY (`id_bendahara`);

--
-- Indexes for table `berita_acara`
--
ALTER TABLE `berita_acara`
  ADD PRIMARY KEY (`id_berita`),
  ADD KEY `id_general` (`id_general`),
  ADD KEY `fk_berita_kepdin` (`id_kepdin`),
  ADD KEY `fk_berita_ppk` (`id_ppk`);

--
-- Indexes for table `dokumen_pengadaan`
--
ALTER TABLE `dokumen_pengadaan`
  ADD PRIMARY KEY (`id_dokumen`),
  ADD KEY `id_general` (`id_general`),
  ADD KEY `fk_pengadaan_ppk` (`id_ppk`);

--
-- Indexes for table `general`
--
ALTER TABLE `general`
  ADD PRIMARY KEY (`id_general`);

--
-- Indexes for table `kabid_ppa`
--
ALTER TABLE `kabid_ppa`
  ADD PRIMARY KEY (`id_kabid`);

--
-- Indexes for table `kepdin`
--
ALTER TABLE `kepdin`
  ADD PRIMARY KEY (`id_kepdin`);

--
-- Indexes for table `kwitansi`
--
ALTER TABLE `kwitansi`
  ADD PRIMARY KEY (`id_kwitansi`),
  ADD KEY `id_general` (`id_general`),
  ADD KEY `fk_kwitansi_kepdin` (`id_kepdin`),
  ADD KEY `fk_kwitansi_bendahara` (`id_bendahara`),
  ADD KEY `fk_kwitansi_pptk` (`id_pptk`);

--
-- Indexes for table `lembar_kegiatan`
--
ALTER TABLE `lembar_kegiatan`
  ADD PRIMARY KEY (`id_lembar`),
  ADD KEY `id_general` (`id_general`),
  ADD KEY `fk_lembar_kepdin` (`id_kepdin`),
  ADD KEY `fk_lembar_bendahara` (`id_bendahara`),
  ADD KEY `fk_lembar_pptk` (`id_pptk`);

--
-- Indexes for table `nota_dinas`
--
ALTER TABLE `nota_dinas`
  ADD PRIMARY KEY (`id_nota`),
  ADD KEY `id_general` (`id_general`),
  ADD KEY `fk_nota_kabid` (`id_kabid`);

--
-- Indexes for table `pembuat_komitmen`
--
ALTER TABLE `pembuat_komitmen`
  ADD PRIMARY KEY (`id_ppk`);

--
-- Indexes for table `penyedia`
--
ALTER TABLE `penyedia`
  ADD PRIMARY KEY (`id_penyedia`);

--
-- Indexes for table `permohonan_narasumber`
--
ALTER TABLE `permohonan_narasumber`
  ADD PRIMARY KEY (`id_permohonan`),
  ADD KEY `fk_permohonan_kepdin` (`id_kepdin`);

--
-- Indexes for table `pptk`
--
ALTER TABLE `pptk`
  ADD PRIMARY KEY (`id_pptk`);

--
-- Indexes for table `spesifikasi_anggaran`
--
ALTER TABLE `spesifikasi_anggaran`
  ADD PRIMARY KEY (`id_spesifikasi`),
  ADD KEY `id_general` (`id_general`);

--
-- Indexes for table `sptpd`
--
ALTER TABLE `sptpd`
  ADD PRIMARY KEY (`id_sptpd`),
  ADD KEY `id_general` (`id_general`);

--
-- Indexes for table `undangan_peminjaman`
--
ALTER TABLE `undangan_peminjaman`
  ADD PRIMARY KEY (`id_undangan`),
  ADD KEY `id_general` (`id_general`),
  ADD KEY `fk_undangan_kepdin` (`id_kepdin`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bendahara`
--
ALTER TABLE `bendahara`
  MODIFY `id_bendahara` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `berita_acara`
--
ALTER TABLE `berita_acara`
  MODIFY `id_berita` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `dokumen_pengadaan`
--
ALTER TABLE `dokumen_pengadaan`
  MODIFY `id_dokumen` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `general`
--
ALTER TABLE `general`
  MODIFY `id_general` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `kabid_ppa`
--
ALTER TABLE `kabid_ppa`
  MODIFY `id_kabid` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `kepdin`
--
ALTER TABLE `kepdin`
  MODIFY `id_kepdin` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `kwitansi`
--
ALTER TABLE `kwitansi`
  MODIFY `id_kwitansi` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `lembar_kegiatan`
--
ALTER TABLE `lembar_kegiatan`
  MODIFY `id_lembar` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `nota_dinas`
--
ALTER TABLE `nota_dinas`
  MODIFY `id_nota` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pembuat_komitmen`
--
ALTER TABLE `pembuat_komitmen`
  MODIFY `id_ppk` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `penyedia`
--
ALTER TABLE `penyedia`
  MODIFY `id_penyedia` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `permohonan_narasumber`
--
ALTER TABLE `permohonan_narasumber`
  MODIFY `id_permohonan` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pptk`
--
ALTER TABLE `pptk`
  MODIFY `id_pptk` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `spesifikasi_anggaran`
--
ALTER TABLE `spesifikasi_anggaran`
  MODIFY `id_spesifikasi` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=349;

--
-- AUTO_INCREMENT for table `sptpd`
--
ALTER TABLE `sptpd`
  MODIFY `id_sptpd` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `undangan_peminjaman`
--
ALTER TABLE `undangan_peminjaman`
  MODIFY `id_undangan` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `berita_acara`
--
ALTER TABLE `berita_acara`
  ADD CONSTRAINT `berita_acara_ibfk_1` FOREIGN KEY (`id_general`) REFERENCES `general` (`id_general`),
  ADD CONSTRAINT `fk_berita_kepdin` FOREIGN KEY (`id_kepdin`) REFERENCES `kepdin` (`id_kepdin`),
  ADD CONSTRAINT `fk_berita_ppk` FOREIGN KEY (`id_ppk`) REFERENCES `pembuat_komitmen` (`id_ppk`);

--
-- Constraints for table `dokumen_pengadaan`
--
ALTER TABLE `dokumen_pengadaan`
  ADD CONSTRAINT `dokumen_pengadaan_ibfk_1` FOREIGN KEY (`id_general`) REFERENCES `general` (`id_general`),
  ADD CONSTRAINT `fk_pengadaan_ppk` FOREIGN KEY (`id_ppk`) REFERENCES `pembuat_komitmen` (`id_ppk`);

--
-- Constraints for table `kwitansi`
--
ALTER TABLE `kwitansi`
  ADD CONSTRAINT `fk_kwitansi_bendahara` FOREIGN KEY (`id_bendahara`) REFERENCES `bendahara` (`id_bendahara`),
  ADD CONSTRAINT `fk_kwitansi_kepdin` FOREIGN KEY (`id_kepdin`) REFERENCES `kepdin` (`id_kepdin`),
  ADD CONSTRAINT `fk_kwitansi_pptk` FOREIGN KEY (`id_pptk`) REFERENCES `pptk` (`id_pptk`),
  ADD CONSTRAINT `kwitansi_ibfk_1` FOREIGN KEY (`id_general`) REFERENCES `general` (`id_general`);

--
-- Constraints for table `lembar_kegiatan`
--
ALTER TABLE `lembar_kegiatan`
  ADD CONSTRAINT `fk_lembar_bendahara` FOREIGN KEY (`id_bendahara`) REFERENCES `bendahara` (`id_bendahara`),
  ADD CONSTRAINT `fk_lembar_kepdin` FOREIGN KEY (`id_kepdin`) REFERENCES `kepdin` (`id_kepdin`),
  ADD CONSTRAINT `fk_lembar_pptk` FOREIGN KEY (`id_pptk`) REFERENCES `pptk` (`id_pptk`),
  ADD CONSTRAINT `lembar_kegiatan_ibfk_1` FOREIGN KEY (`id_general`) REFERENCES `general` (`id_general`);

--
-- Constraints for table `nota_dinas`
--
ALTER TABLE `nota_dinas`
  ADD CONSTRAINT `fk_nota_kabid` FOREIGN KEY (`id_kabid`) REFERENCES `kabid_ppa` (`id_kabid`),
  ADD CONSTRAINT `nota_dinas_ibfk_1` FOREIGN KEY (`id_general`) REFERENCES `general` (`id_general`);

--
-- Constraints for table `permohonan_narasumber`
--
ALTER TABLE `permohonan_narasumber`
  ADD CONSTRAINT `fk_permohonan_kepdin` FOREIGN KEY (`id_kepdin`) REFERENCES `kepdin` (`id_kepdin`);

--
-- Constraints for table `spesifikasi_anggaran`
--
ALTER TABLE `spesifikasi_anggaran`
  ADD CONSTRAINT `spesifikasi_anggaran_ibfk_1` FOREIGN KEY (`id_general`) REFERENCES `general` (`id_general`);

--
-- Constraints for table `sptpd`
--
ALTER TABLE `sptpd`
  ADD CONSTRAINT `sptpd_ibfk_1` FOREIGN KEY (`id_general`) REFERENCES `general` (`id_general`);

--
-- Constraints for table `undangan_peminjaman`
--
ALTER TABLE `undangan_peminjaman`
  ADD CONSTRAINT `fk_undangan_kepdin` FOREIGN KEY (`id_kepdin`) REFERENCES `kepdin` (`id_kepdin`),
  ADD CONSTRAINT `undangan_peminjaman_ibfk_1` FOREIGN KEY (`id_general`) REFERENCES `general` (`id_general`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
