<?php
session_start();
if(!isset($_SESSION['user'])) { header('Location: login.php'); exit; }
require_once 'config.php';

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_sewa'])) {
    $id_sewa = (int)$_POST['id_sewa'];

    // Panggil procedure kembalikan
    $conn->query("CALL kembalikan_mobil($id_sewa)");

    // Hitung denda: lewat 7 hari kena Rp 50.000/hari
    $conn->query("
        UPDATE penyewaan
        SET denda = CASE
            WHEN DATEDIFF(CURDATE(), tanggal_sewa) > 7
            THEN (DATEDIFF(CURDATE(), tanggal_sewa) - 7) * 300000
            ELSE 0
        END
        WHERE id_sewa = $id_sewa
    ");
}

header('Location: admin_dashboard.php?tab=sewa');
exit;
?>