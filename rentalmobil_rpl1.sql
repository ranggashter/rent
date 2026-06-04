-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 04 Jun 2026 pada 05.12
-- Versi server: 10.4.32-MariaDB-log
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `rentalmobil_rpl1`
--

DELIMITER $$
--
-- Prosedur
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `kembalikan_mobil` (IN `p_id_sewa` INT)   BEGIN

    DECLARE v_id_mobil INT;
    DECLARE v_jumlah INT;

    SELECT id_mobil,jumlah_sewa
    INTO v_id_mobil,v_jumlah
    FROM penyewaan
    WHERE id_sewa=p_id_sewa;

    UPDATE mobil
    SET jumlah = jumlah + v_jumlah
    WHERE id_mobil=v_id_mobil;

    UPDATE penyewaan
    SET
        status='dikembalikan',
        tanggal_kembali=CURDATE()
    WHERE id_sewa=p_id_sewa;

END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sewa_mobil` (IN `p_id_user` INT, IN `p_id_mobil` INT, IN `p_jumlah` INT)   BEGIN

    DECLARE stok INT;

    SELECT jumlah
    INTO stok
    FROM mobil
    WHERE id_mobil = p_id_mobil;

    IF stok >= p_jumlah THEN

        INSERT INTO penyewaan(
            id_user,
            id_mobil,
            jumlah_sewa,
            tanggal_sewa
        )
        VALUES(
            p_id_user,
            p_id_mobil,
            p_jumlah,
            CURDATE()
        );

        UPDATE mobil
        SET jumlah = jumlah - p_jumlah
        WHERE id_mobil = p_id_mobil;

    ELSE

        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT='Stok mobil tidak cukup';

    END IF;

END$$

--
-- Fungsi
--
CREATE DEFINER=`root`@`localhost` FUNCTION `status_mobil` (`stok` INT) RETURNS VARCHAR(20) CHARSET utf8mb4 COLLATE utf8mb4_general_ci  BEGIN

    DECLARE hasil VARCHAR(20);

    IF stok <= 0 THEN
        SET hasil='Tidak Tersedia';
    ELSE
        SET hasil='Tersedia';
    END IF;

    RETURN hasil;

END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Struktur dari tabel `mobil`
--

CREATE TABLE `mobil` (
  `id_mobil` int(11) NOT NULL,
  `nama_mobil` varchar(100) DEFAULT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `jumlah` int(11) DEFAULT NULL,
  `kondisi` enum('baik','rusak') DEFAULT NULL,
  `harga_sewa` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `mobil`
--

INSERT INTO `mobil` (`id_mobil`, `nama_mobil`, `gambar`, `jumlah`, `kondisi`, `harga_sewa`) VALUES
(1, 'Toyota Avanza', '1780461403_avanza.webp', 4, 'baik', 300000),
(2, 'Honda Brio', '1780461412_brio.webp', 4, 'baik', 250000),
(3, 'Daihatsu Xenia', '1780461418_xenia.webp', 2, 'rusak', 280000),
(4, 'civic', '1780461425_civic.webp', 6, 'baik', 300000);

-- --------------------------------------------------------

--
-- Struktur dari tabel `penyewaan`
--

CREATE TABLE `penyewaan` (
  `id_sewa` int(11) NOT NULL,
  `id_user` int(11) DEFAULT NULL,
  `id_mobil` int(11) DEFAULT NULL,
  `jumlah_sewa` int(11) DEFAULT NULL,
  `tanggal_sewa` date DEFAULT NULL,
  `tanggal_kembali` date DEFAULT NULL,
  `status` enum('disewa','dikembalikan','pending_kembali','pending_sewa') DEFAULT 'disewa',
  `denda` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `penyewaan`
--

INSERT INTO `penyewaan` (`id_sewa`, `id_user`, `id_mobil`, `jumlah_sewa`, `tanggal_sewa`, `tanggal_kembali`, `status`, `denda`) VALUES
(1, 2, 1, 1, '2026-06-03', '2026-06-03', 'dikembalikan', 0),
(2, 2, 1, 1, '2026-06-03', '2026-06-03', '', 0),
(3, 2, 1, 1, '2026-06-03', '2026-06-03', '', 0),
(4, 2, 1, 1, '2026-06-03', '2026-06-03', '', 0),
(5, 2, 4, 1, '2026-06-03', '2026-06-03', '', 0),
(6, 2, 1, 1, '2026-06-03', '2026-06-03', '', 0),
(7, 2, 2, 1, '2026-06-03', '2026-06-03', '', 0),
(8, 2, 1, 1, '2026-06-03', '2026-06-03', '', 0),
(9, 2, 1, 1, '2026-06-03', '2026-06-03', '', 0),
(10, 2, 1, 1, '2026-06-03', '2026-06-03', '', 0),
(11, 2, 1, 1, '2026-06-03', '2026-06-03', '', 0),
(12, 2, 1, 1, '2026-06-03', '2026-06-03', '', 0),
(13, 2, 1, 1, '2026-06-03', '2026-06-03', '', 0),
(14, 2, 1, 1, '2026-06-03', '2026-06-03', '', 0),
(15, 2, 4, 1, '2026-06-03', '2026-06-03', '', 0),
(16, 2, 2, 1, '2026-06-03', '2026-06-03', '', 0),
(17, 2, 1, 1, '2026-06-03', '2026-06-03', '', 0),
(18, 2, 2, 1, '2026-06-03', '2026-06-03', '', 0),
(19, 2, 4, 1, '2026-06-03', '2026-06-03', '', 0),
(20, 2, 4, 1, '2026-06-03', '2026-06-03', '', 0),
(21, 2, 4, 1, '2026-06-03', '2026-06-03', '', 0),
(22, 2, 4, 1, '2026-06-03', '2026-06-03', '', 0),
(23, 2, 1, 1, NULL, NULL, '', 0),
(24, 2, 1, 1, NULL, NULL, '', 0),
(25, 2, 1, 1, NULL, NULL, '', 0),
(26, 2, 2, 1, NULL, NULL, 'dikembalikan', 0),
(27, 2, 1, 1, NULL, NULL, '', 0),
(28, 2, 1, 1, NULL, NULL, '', 0),
(29, 2, 1, 1, NULL, NULL, '', 0),
(30, 2, 1, 1, '2026-06-03', '2026-06-03', '', 0),
(31, 2, 1, 1, '2026-06-03', '2026-06-03', '', 0),
(32, 2, 1, 1, '2026-06-03', NULL, '', 0),
(33, 2, 1, 1, '2026-06-03', '2026-06-03', 'dikembalikan', 0),
(34, 2, 4, 1, '2026-06-03', '2026-06-03', 'dikembalikan', 0),
(35, 2, 4, 1, '2026-06-03', '2026-06-03', 'dikembalikan', 0),
(36, 2, 4, 1, '2026-06-03', '2026-06-03', 'dikembalikan', 0),
(37, 2, 4, 1, '2026-06-03', '2026-06-03', 'dikembalikan', 0),
(38, 2, 1, 1, '2026-06-03', '2026-06-03', 'dikembalikan', 0);

-- --------------------------------------------------------

--
-- Struktur dari tabel `user`
--

CREATE TABLE `user` (
  `id_user` int(11) NOT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('admin','penyewa') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `user`
--

INSERT INTO `user` (`id_user`, `nama`, `username`, `password`, `role`) VALUES
(1, 'rangga', 'rangga', '123', 'admin'),
(2, 'marvel', 'marvel', '123', 'penyewa');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `mobil`
--
ALTER TABLE `mobil`
  ADD PRIMARY KEY (`id_mobil`);

--
-- Indeks untuk tabel `penyewaan`
--
ALTER TABLE `penyewaan`
  ADD PRIMARY KEY (`id_sewa`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `id_mobil` (`id_mobil`);

--
-- Indeks untuk tabel `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `mobil`
--
ALTER TABLE `mobil`
  MODIFY `id_mobil` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `penyewaan`
--
ALTER TABLE `penyewaan`
  MODIFY `id_sewa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT untuk tabel `user`
--
ALTER TABLE `user`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `penyewaan`
--
ALTER TABLE `penyewaan`
  ADD CONSTRAINT `penyewaan_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`),
  ADD CONSTRAINT `penyewaan_ibfk_2` FOREIGN KEY (`id_mobil`) REFERENCES `mobil` (`id_mobil`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
