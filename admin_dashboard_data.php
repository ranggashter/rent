<?php
session_start();
require_once 'config.php';

if(!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

header('Content-Type: application/json');

$months = 12; // tampil 12 bulan terakhir

// Build list bulan terakhir
$labels = [];
$start = new DateTime('first day of this month');
$start->modify('-'.($months-1).' months');
for($i=0;$i<$months;$i++){
    $d = (clone $start)->modify('+'.$i.' months');
    $labels[] = $d->format('M Y');
}

function monthKey($dateStr){
    return date('Y-m', strtotime($dateStr));
}

// Pendapatan per bulan (hitung dari harga_sewa * jumlah_sewa, hanya status selesai/dikembalikan)
$pendapatan = array_fill(0, $months, 0);

$sqlPend = "
    SELECT DATE_FORMAT(p.tanggal_sewa,'%Y-%m') AS ym,
           SUM(m.harga_sewa * p.jumlah_sewa) AS total
    FROM penyewaan p
    JOIN mobil m ON p.id_mobil=m.id_mobil
    WHERE p.status IN ('dikembalikan','selesai','kembali')
      AND p.tanggal_sewa >= DATE_FORMAT(CURDATE() - INTERVAL ? MONTH,'%Y-%m-01')
    GROUP BY ym
    ORDER BY ym
";
$stmtPend = $conn->prepare($sqlPend);
$lowerMonths = $months;
$stmtPend->bind_param('i',$lowerMonths);
$stmtPend->execute();
$resPend = $stmtPend->get_result();

// mapping ym => index
$ymToIndex = [];
$startYm = (new DateTime('first day of this month'))->modify('-'.($months-1).' months')->format('Y-m');
$cur = new DateTime($startYm.'-01');
for($i=0;$i<$months;$i++){
    $ymToIndex[$cur->format('Y-m')] = $i;
    $cur->modify('+1 month');
}

while($row = $resPend->fetch_assoc()){
    $ym = $row['ym'];
    $idx = $ymToIndex[$ym] ?? null;
    if($idx !== null) $pendapatan[$idx] = (float)($row['total'] ?? 0);
}

// Total denda per bulan
$denda = array_fill(0, $months, 0);
$sqlDenda = "
    SELECT DATE_FORMAT(p.tanggal_sewa,'%Y-%m') AS ym,
           SUM(p.denda) AS total
    FROM penyewaan p
    WHERE p.tanggal_sewa >= DATE_FORMAT(CURDATE() - INTERVAL ? MONTH,'%Y-%m-01')
    GROUP BY ym
    ORDER BY ym
";
$stmtDen = $conn->prepare($sqlDenda);
$stmtDen->bind_param('i',$lowerMonths);
$stmtDen->execute();
$resDen = $stmtDen->get_result();
while($row = $resDen->fetch_assoc()){
    $ym = $row['ym'];
    $idx = $ymToIndex[$ym] ?? null;
    if($idx !== null) $denda[$idx] = (float)($row['total'] ?? 0);
}

// Jumlah transaksi per status (untuk legend) per bulan
$statusKeys = ['pending_sewa','pending_kembali','disewa','dikembalikan','ditolak'];
$warna = [
    'pending_sewa' => '#fb923c',
    'pending_kembali' => '#f59e0b',
    'disewa' => '#e8c547',
    'dikembalikan' => '#4ade80',
    'ditolak' => '#f87171'
];

$series = [];
foreach($statusKeys as $st){
    $series[$st] = array_fill(0,$months,0);
}

$sqlStatus = "
    SELECT DATE_FORMAT(p.tanggal_sewa,'%Y-%m') AS ym,
           p.status AS status,
           COUNT(*) AS cnt
    FROM penyewaan p
    WHERE p.tanggal_sewa >= DATE_FORMAT(CURDATE() - INTERVAL ? MONTH,'%Y-%m-01')
      AND p.status IN ('".implode("','", array_map(fn($x)=>$x,$statusKeys))."')
    GROUP BY ym, p.status
    ORDER BY ym
";
$stmtSt = $conn->prepare($sqlStatus);
$stmtSt->bind_param('i',$lowerMonths);
$stmtSt->execute();
$resSt = $stmtSt->get_result();
while($row = $resSt->fetch_assoc()){
    $ym = $row['ym'];
    $st = $row['status'];
    $idx = $ymToIndex[$ym] ?? null;
    if($idx !== null && isset($series[$st])){
        $series[$st][$idx] = (int)($row['cnt'] ?? 0);
    }
}

echo json_encode([
    'labels' => $labels,
    'pendapatan' => $pendapatan,
    'denda' => $denda,
    'statusKeys' => $statusKeys,
    'statusWarna' => $warna,
    'series' => $series
]);

