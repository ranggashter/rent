<?php
session_start();
if(!isset($_SESSION['user']) || $_SESSION['role'] !== 'penyewa') {
    header('Location: login.php'); exit;
}
require_once 'config.php';

$msg   = '';
$error = '';

// Ajukan sewa mobil (pending, menunggu konfirmasi admin)
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_mobil'])) {
    $id_mobil = (int)$_POST['id_mobil'];
    $jumlah   = (int)$_POST['jumlah'];
    $id_user  = (int)$_SESSION['user'];

    if($id_mobil > 0 && $jumlah > 0) {
        $stmt = $conn->prepare("INSERT INTO penyewaan (id_user, id_mobil, jumlah_sewa, tanggal_sewa, tanggal_kembali, status, denda) VALUES (?, ?, ?, NULL, NULL, 'pending_sewa', 0)");
        $stmt->bind_param("iii", $id_user, $id_mobil, $jumlah);

        if($stmt->execute()) {
            $msg = 'Permintaan sewa berhasil dikirim! Tunggu konfirmasi admin.';
        } else {
            $error = 'Gagal mengajukan sewa. Silakan coba lagi.';
        }
    } else {
        $error = 'Pastikan jumlah dan mobil yang dipilih benar.';
    }
}

// Ajukan pengembalian
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajukan_kembali'])) {
    $id_sewa = (int)$_POST['id_sewa'];
    $id_user = $_SESSION['user'];
    $stmt = $conn->prepare("UPDATE penyewaan SET status='pending_kembali' WHERE id_sewa=? AND id_user=? AND status='disewa'");
    $stmt->bind_param("ii", $id_sewa, $id_user);
    $stmt->execute();
    if($stmt->affected_rows > 0) {
        $msg = 'Pengajuan pengembalian berhasil dikirim! Tunggu persetujuan admin.';
    } else {
        $error = 'Gagal mengajukan pengembalian.';
    }
}

$mobil_list = $conn->query("SELECT *, status_mobil(jumlah) AS status FROM mobil ORDER BY id_mobil");
$riwayat    = $conn->prepare("
    SELECT p.*,m.nama_mobil,m.harga_sewa
    FROM penyewaan p
    JOIN mobil m ON p.id_mobil=m.id_mobil
    WHERE p.id_user=?
    ORDER BY p.id_sewa DESC
");
$riwayat->bind_param("i", $_SESSION['user']);
$riwayat->execute();
$riwayat_list = $riwayat->get_result();

// Statistik dashboard
$stats = [
    'aktif' => $conn->query("SELECT COUNT(*) as cnt FROM penyewaan WHERE id_user=".$_SESSION['user']." AND status='disewa'")->fetch_assoc()['cnt'],
    'total' => $conn->query("SELECT COUNT(*) as cnt FROM penyewaan WHERE id_user=".$_SESSION['user'])->fetch_assoc()['cnt'],
    'pending' => $conn->query("SELECT COUNT(*) as cnt FROM penyewaan WHERE id_user=".$_SESSION['user']." AND status='pending_kembali'")->fetch_assoc()['cnt']
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard Penyewa — RentalCar</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root {
  --primary: #2563eb;
  --primary-dark: #1e40af;
  --primary-light: #dbeafe;
  --success: #22c55e;
  --success-light: #dcfce7;
  --warning: #f59e0b;
  --warning-light: #fef3c7;
  --danger: #ef4444;
  --danger-light: #fee2e2;
  --info: #6366f1;
  --info-light: #e0e7ff;
  
  --bg: #ffffff;
  --bg-light: #f8fafc;
  --card-bg: #ffffff;
  --border: #e2e8f0;
  --text: #0f172a;
  --text-secondary: #475569;
  --text-muted: #94a3b8;
  --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
  --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.08), 0 1px 2px -1px rgb(0 0 0 / 0.08);
  --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.08), 0 2px 4px -2px rgb(0 0 0 / 0.08);
  --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.08), 0 4px 6px -4px rgb(0 0 0 / 0.08);
  
  --radius-sm: 8px;
  --radius: 12px;
  --radius-lg: 16px;
  --radius-xl: 20px;
}

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

html, body {
  min-height: 100vh;
  background: var(--bg-light);
  font-family: 'Inter', sans-serif;
  color: var(--text);
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

/* TOP NAVIGATION */
.navbar {
  background: var(--bg);
  border-bottom: 1px solid var(--border);
  padding: 16px 40px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  position: sticky;
  top: 0;
  z-index: 100;
  box-shadow: var(--shadow-sm);
}

.navbar-brand {
  display: flex;
  align-items: center;
  gap: 12px;
  text-decoration: none;
  font-weight: 800;
  font-size: 1.4rem;
  color: var(--text);
  font-family: 'Plus Jakarta Sans', sans-serif;
}

.navbar-brand-icon {
  width: 40px;
  height: 40px;
  background: linear-gradient(135deg, var(--primary) 0%, var(--info) 100%);
  border-radius: var(--radius);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.2rem;
}

.navbar-menu {
  display: flex;
  gap: 32px;
  align-items: center;
}

.navbar-menu a, .navbar-menu div {
  color: var(--text-secondary);
  text-decoration: none;
  font-size: 0.95rem;
  font-weight: 500;
  transition: color 0.2s ease;
  cursor: pointer;
}

.navbar-menu a:hover, .navbar-menu div:hover {
  color: var(--primary);
}

.navbar-user {
  display: flex;
  align-items: center;
  gap: 16px;
}

.user-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--primary) 0%, var(--info) 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 700;
  font-size: 0.95rem;
}
.dropdown-menu {
  position: relative;
}

.dropdown-content {
  display: none;
  position: absolute;
  right: 0;
  top: calc(100% + 8px);
  background: #fff;
  border: 1px solid var(--border);
  border-radius: 16px;
  box-shadow: 0 10px 30px rgba(0,0,0,.12);
  min-width: 220px;
  overflow: hidden;
  z-index: 9999;
}

.dropdown-content.show {
  display: block;
}

.dropdown-content a {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 14px 18px;
  color: var(--text-secondary);
  text-decoration: none;
  font-size: 0.9rem;
  font-weight: 500;
  border-bottom: 1px solid var(--border);
  transition: all .2s ease;
}

.dropdown-content a:last-child {
  border-bottom: none;
}

.dropdown-content a:hover {
  background: #F8FAFC;
  color: var(--primary);
}

.user-toggle {
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 6px;
  user-select: none;
}

/* MAIN CONTAINER */
.container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 40px 20px;
}

/* HERO SECTION */
.hero {
  background: linear-gradient(135deg, var(--primary) 0%, var(--info) 100%);
  border-radius: var(--radius-xl);
  padding: 60px 40px;
  color: white;
  margin-bottom: 40px;
  box-shadow: var(--shadow-lg);
}

.hero h1 {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 2.5rem;
  font-weight: 800;
  margin-bottom: 12px;
  letter-spacing: -0.5px;
}

.hero p {
  font-size: 1.1rem;
  opacity: 0.95;
  margin-bottom: 32px;
  max-width: 500px;
}

.search-bar {
  display: flex;
  gap: 12px;
  background: white;
  padding: 16px;
  border-radius: var(--radius-lg);
  max-width: 900px;
}

.search-field {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.search-field label {
  font-size: 0.75rem;
  color: var(--text-secondary);
  font-weight: 600;
  text-transform: uppercase;
}

.search-field input {
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  padding: 10px 12px;
  font-size: 0.95rem;
  color: var(--text);
  outline: none;
  transition: border-color 0.2s ease;
}

.search-field input:focus {
  border-color: var(--primary);
}

.search-btn {
  align-self: flex-end;
  background: var(--primary);
  color: white;
  border: none;
  padding: 12px 32px;
  border-radius: var(--radius-sm);
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s ease;
}

.search-btn:hover {
  background: var(--primary-dark);
}

/* STATS SECTION */
.stats-section {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 20px;
  margin-bottom: 50px;
}

.stat-card {
  background: var(--card-bg);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 24px;
  display: flex;
  align-items: flex-start;
  gap: 16px;
  transition: all 0.3s ease;
  box-shadow: var(--shadow-sm);
}

.stat-card:hover {
  box-shadow: var(--shadow-md);
  border-color: var(--primary);
  transform: translateY(-2px);
}

.stat-icon {
  width: 56px;
  height: 56px;
  border-radius: var(--radius);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.4rem;
  flex-shrink: 0;
}

.stat-icon.green {
  background: var(--success-light);
  color: var(--success);
}

.stat-icon.orange {
  background: var(--warning-light);
  color: var(--warning);
}

.stat-icon.blue {
  background: var(--primary-light);
  color: var(--primary);
}

.stat-icon.purple {
  background: var(--info-light);
  color: var(--info);
}

.stat-content h3 {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 1.8rem;
  font-weight: 800;
  color: var(--text);
  margin-bottom: 4px;
}

.stat-content p {
  font-size: 0.9rem;
  color: var(--text-secondary);
}

/* SECTION HEADER */
.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 24px;
}

.section-title {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 1.4rem;
  font-weight: 800;
  color: var(--text);
  display: flex;
  align-items: center;
  gap: 10px;
}

.section-title i {
  font-size: 1.3rem;
}

/* CAR GRID */
.car-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 24px;
  margin-bottom: 50px;
}

.car-card {
  background: var(--card-bg);
  border: 1.5px solid var(--border);
  border-radius: var(--radius-lg);
  overflow: hidden;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  display: flex;
  flex-direction: column;
  box-shadow: var(--shadow-sm);
}

.car-card:hover {
  border-color: var(--primary);
  box-shadow: var(--shadow-lg);
  transform: translateY(-8px);
}

.car-image-container {
  position: relative;
  width: 100%;
  height: 200px;
  background: linear-gradient(135deg, var(--primary) 0%, var(--info) 100%);
  overflow: hidden;
}

.car-image-container img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.car-image-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 4rem;
  color: rgba(255, 255, 255, 0.9);
}

.car-badge {
  position: absolute;
  top: 12px;
  right: 12px;
  z-index: 10;
}

.badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 14px;
  border-radius: 50px;
  font-size: 0.8rem;
  font-weight: 600;
  white-space: nowrap;
}

.badge-success {
  background: var(--success-light);
  color: var(--success);
}

.badge-danger {
  background: var(--danger-light);
  color: var(--danger);
}

.badge-warning {
  background: var(--warning-light);
  color: var(--warning);
}

.car-details {
  padding: 20px;
  flex: 1;
  display: flex;
  flex-direction: column;
}

.car-name {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-weight: 700;
  font-size: 1.1rem;
  color: var(--text);
  margin-bottom: 8px;
}

.car-specs {
  display: flex;
  gap: 12px;
  margin-bottom: 14px;
  flex-wrap: wrap;
  font-size: 0.85rem;
  color: var(--text-secondary);
}

.car-spec {
  display: flex;
  align-items: center;
  gap: 4px;
}

.car-spec i {
  color: var(--text-muted);
}

.car-rating {
  display: flex;
  align-items: center;
  gap: 4px;
  margin-bottom: 12px;
  font-size: 0.85rem;
  color: var(--warning);
}

.car-price {
  margin-top: auto;
  padding-top: 14px;
  border-top: 1px solid var(--border);
  margin-bottom: 12px;
}

.car-price-value {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-weight: 800;
  font-size: 1.4rem;
  color: var(--primary);
  margin-bottom: 2px;
}

.car-price-period {
  font-size: 0.8rem;
  color: var(--text-muted);
}

.car-btn {
  width: 100%;
  padding: 12px;
  background: var(--primary);
  color: white;
  border: none;
  border-radius: var(--radius-sm);
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.car-btn:hover {
  background: var(--primary-dark);
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

.car-btn:disabled {
  background: var(--text-muted);
  cursor: not-allowed;
}

/* ALERTS */
.alert {
  padding: 16px 20px;
  border-radius: var(--radius-lg);
  margin-bottom: 24px;
  display: flex;
  align-items: center;
  gap: 12px;
  font-weight: 500;
  animation: slideDown 0.3s ease;
}

@keyframes slideDown {
  from { transform: translateY(-10px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}

.alert-success {
  background: var(--success-light);
  color: var(--success);
  border: 1px solid var(--success);
}

.alert-error {
  background: var(--danger-light);
  color: var(--danger);
  border: 1px solid var(--danger);
}

/* HISTORY SECTION */
.history-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.history-card {
  background: var(--card-bg);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  transition: all 0.2s ease;
  box-shadow: var(--shadow-sm);
}

.history-card:hover {
  box-shadow: var(--shadow-md);
  border-color: var(--primary);
}

.history-info {
  flex: 1;
}

.history-car-name {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 1rem;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 6px;
}

.history-dates {
  font-size: 0.9rem;
  color: var(--text-secondary);
  margin-bottom: 8px;
}

.history-status {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  border-radius: 50px;
  font-size: 0.8rem;
  font-weight: 600;
}

.history-status.active {
  background: var(--primary-light);
  color: var(--primary);
}

.history-status.pending {
  background: var(--warning-light);
  color: var(--warning);
}

.history-status.completed {
  background: var(--success-light);
  color: var(--success);
}

.history-price {
  text-align: right;
  margin-right: 20px;
}

.history-price-value {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-weight: 700;
  font-size: 1rem;
  color: var(--text);
}

.history-price-label {
  font-size: 0.8rem;
  color: var(--text-muted);
  margin-top: 2px;
}

.history-action {
  display: flex;
  gap: 8px;
}

.btn-small {
  padding: 8px 16px;
  border: 1px solid var(--border);
  background: white;
  color: var(--primary);
  border-radius: var(--radius-sm);
  cursor: pointer;
  font-weight: 600;
  font-size: 0.85rem;
  transition: all 0.2s ease;
}

.btn-small:hover {
  background: var(--primary-light);
  border-color: var(--primary);
}

/* MODAL */
.modal-overlay {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.6);
  z-index: 1000;
  align-items: center;
  justify-content: center;
  backdrop-filter: blur(4px);
  animation: fadeIn 0.2s ease;
}

.modal-overlay.open {
  display: flex;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.modal {
  background: white;
  border-radius: var(--radius-xl);
  padding: 32px;
  width: 100%;
  max-width: 480px;
  box-shadow: var(--shadow-lg);
  animation: slideUp 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes slideUp {
  from { transform: translateY(20px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}

.modal-header {
  margin-bottom: 24px;
}

.modal-header h3 {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 1.3rem;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 4px;
}

.modal-header p {
  color: var(--text-secondary);
  font-size: 0.9rem;
}

.car-highlight {
  background: var(--primary-light);
  border: 1px solid #bfdbfe;
  border-radius: var(--radius-lg);
  padding: 16px;
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  gap: 12px;
}

.car-highlight i {
  font-size: 2rem;
  color: var(--primary);
  flex-shrink: 0;
}

.car-highlight-name {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-weight: 700;
  font-size: 1.05rem;
  color: var(--text);
}

.car-highlight-stock {
  font-size: 0.8rem;
  color: var(--text-secondary);
  margin-top: 2px;
}

.form-group {
  margin-bottom: 18px;
}

.form-label {
  display: block;
  font-weight: 600;
  color: var(--text);
  margin-bottom: 8px;
  font-size: 0.9rem;
}

.form-input {
  width: 100%;
  padding: 12px 14px;
  border: 1.5px solid var(--border);
  border-radius: var(--radius-sm);
  font-size: 0.95rem;
  color: var(--text);
  outline: none;
  transition: all 0.2s ease;
  font-family: 'Inter', sans-serif;
}

.form-input:focus {
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.modal-footer {
  display: flex;
  gap: 12px;
  margin-top: 24px;
}

.btn-modal {
  flex: 1;
  padding: 12px;
  border: none;
  border-radius: var(--radius-sm);
  font-weight: 600;
  font-size: 0.95rem;
  cursor: pointer;
  transition: all 0.2s ease;
  font-family: 'Plus Jakarta Sans', sans-serif;
}

.btn-modal-primary {
  background: var(--primary);
  color: white;
}

.btn-modal-primary:hover {
  background: var(--primary-dark);
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

.btn-modal-cancel {
  background: var(--bg-light);
  color: var(--text-secondary);
  border: 1px solid var(--border);
}

.btn-modal-cancel:hover {
  background: var(--border);
}

/* RESPONSIVE */
@media (max-width: 1200px) {
  .car-grid {
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  }
}

@media (max-width: 768px) {
  .navbar {
    padding: 12px 20px;
    flex-direction: column;
    gap: 16px;
  }

  .navbar-menu {
    gap: 16px;
    flex-wrap: wrap;
    justify-content: center;
  }

  .hero {
    padding: 40px 20px;
    margin-bottom: 30px;
  }

  .hero h1 {
    font-size: 1.8rem;
  }

  .hero p {
    font-size: 1rem;
  }

  .search-bar {
    flex-direction: column;
  }

  .search-field {
    flex: 1;
  }

  .search-btn {
    width: 100%;
    align-self: auto;
  }

  .car-grid {
    grid-template-columns: 1fr;
  }

  .stats-section {
    grid-template-columns: 1fr;
  }

  .history-card {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }

  .history-price {
    text-align: left;
    margin-right: 0;
    width: 100%;
  }

  .history-action {
    width: 100%;
  }

  .btn-small {
    flex: 1;
  }

  .container {
    padding: 20px 16px;
  }
}
  align-items: center;
  margin-bottom: 32px;
}

.page-header h1 {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 1.8rem;
  font-weight: 800;
  letter-spacing: -0.5px;
  color: var(--text);
  margin-bottom: 4px;
}

.page-header p {
  color: var(--text-secondary);
  font-size: 0.95rem;
}

.badge-premium {
  background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
  color: #1e293b;
  padding: 10px 20px;
  border-radius: 50px;
  font-weight: 700;
  font-size: 0.85rem;
  display: flex;
  align-items: center;
  gap: 8px;
  box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
}

/* ALERTS */
.alert {
  border-radius: var(--radius);
  padding: 16px 20px;
  margin-bottom: 24px;
  font-size: 0.9rem;
  display: flex;
  align-items: center;
  gap: 12px;
  font-weight: 500;
  animation: slideDown 0.3s ease;
}

@keyframes slideDown {
  from { transform: translateY(-10px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}

.alert-success {
  background: var(--success-light);
  color: #065f46;
  border: 1px solid #a7f3d0;
}

.alert-error {
  background: var(--danger-light);
  color: #991b1b;
  border: 1px solid #fecaca;
}

.alert i {
  font-size: 1.2rem;
}

/* STATS GRID */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 20px;
  margin-bottom: 32px;
}

.stat-card {
  background: var(--card-bg);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 24px;
  display: flex;
  align-items: flex-start;
  gap: 16px;
  transition: all 0.3s ease;
  box-shadow: var(--shadow-sm);
}

.stat-card:hover {
  box-shadow: var(--shadow-lg);
  transform: translateY(-2px);
}

.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: var(--radius);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.4rem;
  flex-shrink: 0;
}

.stat-icon.blue {
  background: var(--primary-light);
  color: var(--primary);
}

.stat-icon.green {
  background: var(--success-light);
  color: var(--success);
}

.stat-icon.orange {
  background: var(--warning-light);
  color: var(--warning);
}

.stat-icon.purple {
  background: var(--info-light);
  color: var(--info);
}

.stat-content {
  flex: 1;
}

.stat-number {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 2rem;
  font-weight: 800;
  color: var(--text);
  letter-spacing: -0.5px;
  line-height: 1;
  margin-bottom: 8px;
}

.stat-label {
  font-size: 0.85rem;
  color: var(--text-secondary);
  font-weight: 500;
}

/* SECTION */
.section {
  background: var(--card-bg);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  margin-bottom: 32px;
  box-shadow: var(--shadow-sm);
  overflow: hidden;
}

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 28px;
  border-bottom: 1px solid var(--border);
  background: var(--bg);
}

.section-title {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 1.1rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 10px;
  color: var(--text);
}

.section-title i {
  color: var(--primary);
  font-size: 1.2rem;
}

/* CAR GRID */
.car-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 24px;
  padding: 28px;
}

.car-card {
  background: var(--card-bg);
  border: 1.5px solid var(--border);
  border-radius: var(--radius-lg);
  overflow: hidden;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  display: flex;
  flex-direction: column;
}

.car-card:hover {
  border-color: var(--primary);
  box-shadow: var(--shadow-xl);
  transform: translateY(-4px);
}

.car-image-container {
  position: relative;
  width: 100%;
  height: 200px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  overflow: hidden;
}

.car-image-container::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 60px;
  background: linear-gradient(to top, rgba(0,0,0,0.3), transparent);
}

.car-image-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 5rem;
  color: rgba(255,255,255,0.9);
  filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1));
}

.car-badge {
  position: absolute;
  top: 16px;
  right: 16px;
  z-index: 10;
}

.car-details {
  padding: 20px;
  flex: 1;
  display: flex;
  flex-direction: column;
}

.car-name {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-weight: 700;
  font-size: 1.1rem;
  color: var(--text);
  margin-bottom: 8px;
}

.car-meta {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 16px;
  font-size: 0.85rem;
  color: var(--text-secondary);
}

.car-meta-item {
  display: flex;
  align-items: center;
  gap: 6px;
}

.car-meta-item i {
  font-size: 0.9rem;
  color: var(--text-muted);
}

.car-price-section {
  margin-top: auto;
  padding-top: 16px;
  border-top: 1px solid var(--border);
}

.car-price {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-weight: 800;
  font-size: 1.4rem;
  color: var(--primary);
  margin-bottom: 4px;
}

.car-price-period {
  font-size: 0.8rem;
  color: var(--text-muted);
  font-weight: 500;
}

.car-stock-info {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 12px;
  margin-bottom: 12px;
}

.stock-indicator {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.85rem;
  font-weight: 500;
}

.stock-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
}

.stock-dot.available {
  background: var(--success);
  box-shadow: 0 0 0 3px var(--success-light);
}

.stock-dot.limited {
  background: var(--warning);
  box-shadow: 0 0 0 3px var(--warning-light);
}

.stock-dot.unavailable {
  background: var(--danger);
  box-shadow: 0 0 0 3px var(--danger-light);
}

/* BUTTONS */
.btn {
  padding: 10px 20px;
  border-radius: var(--radius-sm);
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  border: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  white-space: nowrap;
}

.btn-primary {
  background: var(--primary);
  color: white;
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
  width: 100%;
  padding: 12px;
  font-size: 0.95rem;
}

.btn-primary:hover:not(:disabled) {
  background: var(--primary-dark);
  box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4);
  transform: translateY(-2px);
}

.btn-primary:disabled {
  background: #cbd5e1;
  color: #94a3b8;
  cursor: not-allowed;
  box-shadow: none;
}

.btn-warning {
  background: var(--warning);
  color: #1e293b;
  box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
  width: 100%;
  padding: 10px;
}

.btn-warning:hover {
  background: #fbbf24;
  box-shadow: 0 6px 16px rgba(245, 158, 11, 0.4);
  transform: translateY(-2px);
}

.btn-outline {
  background: white;
  color: var(--text-secondary);
  border: 1.5px solid var(--border);
}

.btn-outline:hover {
  background: var(--bg);
  border-color: var(--text-muted);
}

/* PILLS / BADGES */
.pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 14px;
  border-radius: 50px;
  font-size: 0.78rem;
  font-weight: 600;
  white-space: nowrap;
}

.pill-success {
  background: var(--success-light);
  color: #065f46;
  border: 1px solid #a7f3d0;
}

.pill-danger {
  background: var(--danger-light);
  color: #991b1b;
  border: 1px solid #fecaca;
}

.pill-warning {
  background: var(--warning-light);
  color: #92400e;
  border: 1px solid #fde68a;
}

.pill-info {
  background: var(--info-light);
  color: #5b21b6;
  border: 1px solid #c4b5fd;
}

.pill-primary {
  background: var(--primary-light);
  color: #1e40af;
  border: 1px solid #93c5fd;
}

/* TABLE */
.table-container {
  overflow-x: auto;
  padding: 0;
}

table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.9rem;
}

thead {
  background: var(--bg);
  border-bottom: 2px solid var(--border);
}

th {
  text-align: left;
  padding: 16px 20px;
  font-size: 0.8rem;
  letter-spacing: 0.5px;
  color: var(--text-secondary);
  text-transform: uppercase;
  font-weight: 700;
}

td {
  padding: 16px 20px;
  border-bottom: 1px solid var(--border);
  font-weight: 500;
}

tbody tr {
  transition: background 0.2s ease;
}

tbody tr:hover {
  background: var(--bg);
}

tbody tr:last-child td {
  border-bottom: none;
}

/* MODAL */
.modal-overlay {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.6);
  z-index: 1000;
  align-items: center;
  justify-content: center;
  backdrop-filter: blur(4px);
  animation: fadeIn 0.2s ease;
}

.modal-overlay.open {
  display: flex;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.modal {
  background: white;
  border-radius: var(--radius-xl);
  padding: 32px;
  width: 100%;
  max-width: 440px;
  box-shadow: var(--shadow-xl);
  animation: slideUp 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes slideUp {
  from { transform: translateY(20px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}

.modal-header {
  margin-bottom: 24px;
}

.modal-header h3 {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 1.3rem;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 4px;
}

.modal-header p {
  color: var(--text-secondary);
  font-size: 0.9rem;
}

.car-highlight {
  background: var(--primary-light);
  border: 1px solid #bfdbfe;
  border-radius: var(--radius);
  padding: 16px;
  margin-bottom: 24px;
  display: flex;
  align-items: center;
  gap: 12px;
}

.car-highlight i {
  font-size: 2rem;
  color: var(--primary);
}

.car-highlight-name {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-weight: 700;
  font-size: 1.1rem;
  color: var(--text);
}

.car-highlight-stock {
  font-size: 0.85rem;
  color: var(--text-secondary);
  margin-top: 2px;
}

.form-group {
  margin-bottom: 20px;
}

.form-label {
  display: block;
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--text);
  margin-bottom: 8px;
}

.form-input {
  width: 100%;
  background: white;
  border: 1.5px solid var(--border);
  border-radius: var(--radius-sm);
  padding: 12px 16px;
  color: var(--text);
  font-family: 'Inter', sans-serif;
  font-size: 0.95rem;
  outline: none;
  transition: all 0.2s ease;
}

.form-input:focus {
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.modal-actions {
  display: flex;
  gap: 12px;
  margin-top: 24px;
}

.modal-actions .btn {
  flex: 1;
}

.info-notice {
  background: var(--warning-light);
  border: 1px solid #fde68a;
  border-radius: var(--radius);
  padding: 14px 16px;
  display: flex;
  align-items: flex-start;
  gap: 10px;
  margin-bottom: 24px;
  font-size: 0.88rem;
  color: #92400e;
  line-height: 1.6;
}

.info-notice i {
  margin-top: 2px;
  flex-shrink: 0;
}

/* RESPONSIVE */
@media (max-width: 1200px) {
  .car-grid {
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  }
}

@media (max-width: 768px) {
  body {
    flex-direction: column;
  }
  
  .sidebar {
    width: 100%;
    min-height: auto;
    position: relative;
    padding: 20px;
    flex-direction: row;
    flex-wrap: wrap;
    gap: 12px;
  }
  
  .brand {
    margin-bottom: 0;
  }
  
  .nav-section {
    display: flex;
    gap: 8px;
  }
  
  .nav-label {
    display: none;
  }
  
  .main {
    padding: 20px;
    max-width: 100%;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .car-grid {
    grid-template-columns: 1fr;
    padding: 20px;
  }
  
  .modal {
    margin: 20px;
    max-width: calc(100% - 40px);
  }
}
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
  <a href="#" class="navbar-brand">
    <div class="navbar-brand-icon">🚗</div>
    <span>RentalCar</span>
  </a>
  
  <div class="navbar-menu">
    <a href="#" onclick="showTab('mobil', event)">Daftar Mobil</a>
    <a href="#" onclick="showTab('riwayat', event)">Riwayat Sewa</a>
    <a href="#">Bantuan</a>
  </div>

  <div class="navbar-user">
    <div class="user-avatar"><?= strtoupper(substr($_SESSION['nama'] ?? 'U', 0, 1)) ?></div>
<div class="dropdown-menu">
  <span id="userToggle" class="user-toggle">
    <?= htmlspecialchars($_SESSION['nama'] ?? 'User') ?>
    <i class="fas fa-chevron-down"></i>
  </span>

  <div class="dropdown-content" id="userDropdown">
    <a href="#"><i class="fas fa-user"></i> Profil Saya</a>
    <a href="#"><i class="fas fa-cog"></i> Pengaturan</a>
    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Keluar</a>
  </div>
</div>
  </div>
</nav>

<div class="container">
  <!-- ALERTS -->
  <?php if($msg): ?>
  <div class="alert alert-success">
    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($msg) ?>
  </div>
  <?php endif; ?>
  
  <?php if($error): ?>
  <div class="alert alert-error">
    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
  </div>
  <?php endif; ?>

  <!-- HERO SECTION -->
  <div class="hero" id="tab-mobil">
    <h1>Temukan Mobil Impian Anda</h1>
    <p>Sewa kendaraan berkualitas dengan harga terjangkau</p>
    
    <!-- <div class="search-bar">
      <div class="search-field" style="flex: 2;">
        <label>Lokasi</label>
        <input type="text" placeholder="Kota atau lokasi" />
      </div>
      <div class="search-field">
        <label>Mulai</label>
        <input type="date" />
      </div>
      <div class="search-field">
        <label>Selesai</label>
        <input type="date" />
      </div>
      <button class="search-btn" style="align-self: flex-end;">
        <i class="fas fa-search"></i> Cari Mobil
      </button>
    </div> -->
  </div>

  <!-- STATS SECTION -->
  <div class="stats-section">
    <div class="stat-card">
      <div class="stat-icon green">
        <i class="fas fa-key"></i>
      </div>
      <div class="stat-content">
        <h3><?= $stats['aktif'] ?></h3>
        <p>Sewa Aktif</p>
      </div>
    </div>
    
    <div class="stat-card">
      <div class="stat-icon orange">
        <i class="fas fa-clock"></i>
      </div>
      <div class="stat-content">
        <h3><?= $stats['pending'] ?></h3>
        <p>Menunggu Persetujuan</p>
      </div>
    </div>
    
    <div class="stat-card">
      <div class="stat-icon blue">
        <i class="fas fa-list"></i>
      </div>
      <div class="stat-content">
        <h3><?= $stats['total'] ?></h3>
        <p>Total Penyewaan</p>
      </div>
    </div>
    
    <!-- <div class="stat-card">
      <div class="stat-icon purple">
        <i class="fas fa-crown"></i>
      </div>
      <div class="stat-content">
        <h3>Premium</h3>
        <p>Status Member</p>
      </div>
    </div> -->
  </div>

  <!-- MOBIL SECTION -->
  <div class="section-header">
    <div class="section-title">
      <i class="fas fa-car-side"></i> Daftar Mobil Tersedia
    </div>
    <span style="color: var(--text-muted);">
      <?= $mobil_list->num_rows ?> kendaraan
    </span>
  </div>

  <div class="car-grid">
    <?php $mobil_list->data_seek(0); while($r = $mobil_list->fetch_assoc()): 
      $available = $r['jumlah'] > 0 && $r['kondisi'] !== 'rusak';
    ?>
    <div class="car-card">
      <div class="car-image-container" style="background: linear-gradient(135deg, <?= $available ? '#667eea 0%, #764ba2 100%' : '#94a3b8 0%, #64748b 100%' ?>);">
        <?php if(!empty($r['gambar']) && file_exists('uploads/'.$r['gambar'])): ?>
          <img src="uploads/<?= htmlspecialchars($r['gambar']) ?>" alt="<?= htmlspecialchars($r['nama_mobil']) ?>" style="width:100%;height:100%;object-fit:cover;">
        <?php else: ?>
          <div class="car-image-placeholder">🚗</div>
        <?php endif; ?>
        <div class="car-badge">
          <span class="badge <?= $available ? 'badge-success' : 'badge-danger' ?>">
            <i class="fas <?= $available ? 'fa-check-circle' : 'fa-times-circle' ?>"></i>
            <?= $available ? 'Tersedia' : 'Tidak Tersedia' ?>
          </span>
        </div>
      </div>
      
      <div class="car-details">
        <div class="car-name"><?= htmlspecialchars($r['nama_mobil']) ?></div>
        
        <div class="car-specs">
          <div class="car-spec">
            <i class="fas fa-heart"></i>
            Kondisi: <?= ucfirst($r['kondisi']) ?>
          </div>
          <div class="car-spec">
            <i class="fas fa-box"></i>
            Stok: <?= $r['jumlah'] ?>
          </div>
        </div>

        <div class="car-rating">
          <i class="fas fa-star"></i>
          <i class="fas fa-star"></i>
          <i class="fas fa-star"></i>
          <i class="fas fa-star"></i>
          <i class="fas fa-star-half-alt"></i>
          <span style="color: var(--text-muted); margin-left: 4px;">4.8 (156)</span>
        </div>
        
        <div class="car-price">
          <div class="car-price-value">Rp <?= number_format($r['harga_sewa'],0,',','.') ?></div>
          <div class="car-price-period">per hari</div>
        </div>
        
        <button class="car-btn" 
          <?= !$available ? 'disabled' : '' ?>
          onclick="openSewa(<?= $r['id_mobil'] ?>, '<?= htmlspecialchars($r['nama_mobil'], ENT_QUOTES) ?>', <?= $r['jumlah'] ?>)">
          <i class="fas fa-shopping-cart"></i>
          <?= $available ? 'Sewa Sekarang' : 'Tidak Tersedia' ?>
        </button>
      </div>
    </div>
    <?php endwhile; ?>
  </div>

  <!-- RIWAYAT SECTION -->
  <div id="tab-riwayat" style="display:none">
    <div class="section-header" style="margin-top: 50px;">
      <div class="section-title">
        <i class="fas fa-history"></i> Riwayat Penyewaan Anda
      </div>
    </div>
    
    <div class="history-list">
      <?php while($r = $riwayat_list->fetch_assoc()): ?>
      <div class="history-card">
        <div class="history-info">
          <div class="history-car-name"><?= htmlspecialchars($r['nama_mobil']) ?></div>
          <div class="history-dates">
            📅 <?= $r['tanggal_sewa'] ? date('d M Y', strtotime($r['tanggal_sewa'])) : '-' ?> 
            <span style="color: var(--text-muted);">hingga</span> 
            <?= $r['tanggal_kembali'] ? date('d M Y', strtotime($r['tanggal_kembali'])) : '-' ?>
          </div>
          <div>
            <?php
            $status = $r['status'];
            if($status === 'disewa') {
              echo '<span class="history-status active"><i class="fas fa-car"></i> Sedang Disewa</span>';
            } elseif($status === 'pending_kembali') {
              echo '<span class="history-status pending"><i class="fas fa-clock"></i> Menunggu Persetujuan</span>';
            } elseif($status === 'pending_sewa') {
              echo '<span class="history-status pending"><i class="fas fa-hourglass"></i> Permintaan Sewa Menunggu</span>';
            } else {
              echo '<span class="history-status completed"><i class="fas fa-check"></i> Selesai</span>';
            }
            ?>
          </div>
        </div>

        <div class="history-price">
          <div class="history-price-value">Rp <?= number_format($r['harga_sewa'] * (isset($r['jumlah_sewa']) ? $r['jumlah_sewa'] : 1), 0, ',', '.') ?></div>
          <div class="history-price-label">Total Harga</div>
        </div>

        <div class="history-action">
          <?php if($r['status'] === 'disewa'): ?>
            <button class="btn-small" onclick="openKembali(<?= $r['id_sewa'] ?>, '<?= htmlspecialchars($r['nama_mobil'], ENT_QUOTES) ?>')">
              <i class="fas fa-reply"></i> Ajukan Kembali
            </button>
          <?php else: ?>
            <button class="btn-small" style="opacity: 0.5; cursor: not-allowed;" disabled>
              <!-- <i class="fas fa-info-circle"></i> Lihat Detail -->
            </button>
          <?php endif; ?>
        </div>
      </div>
      <?php endwhile; ?>
    </div>
  </div>
</div>

<!-- MODAL SEWA -->


<!-- MODAL SEWA -->
<div class="modal-overlay" id="modal-sewa">
  <div class="modal">
    <div class="modal-header">
      <h3>Sewa Mobil</h3>
      <p>Pilih jumlah unit yang ingin disewa</p>
    </div>
    
    <div class="car-highlight">
      <i class="fas fa-car"></i>
      <div>
        <div class="car-highlight-name" id="sewa-nama"></div>
        <div class="car-highlight-stock">Stok tersedia: <strong id="sewa-stok" style="color: var(--primary);">0</strong> unit</div>
      </div>
    </div>
    
    <form method="POST">
      <input type="hidden" name="id_mobil" id="sewa-id">
      
      <div class="form-group">
        <label class="form-label">Jumlah Unit</label>
        <input type="number" name="jumlah" min="1" id="sewa-jml" class="form-input" placeholder="Masukkan jumlah unit" required>
      </div>
      
      <div class="modal-footer">
        <button type="button" class="btn-modal btn-modal-cancel" onclick="closeModal('modal-sewa')">
          <i class="fas fa-times"></i> Batal
        </button>
        <button type="submit" class="btn-modal btn-modal-primary">
          <i class="fas fa-check"></i> Konfirmasi Sewa
        </button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL AJUKAN KEMBALI -->
<div class="modal-overlay" id="modal-kembali">
  <div class="modal">
    <div class="modal-header">
      <h3>Ajukan Pengembalian</h3>
      <p>Konfirmasi pengembalian mobil</p>
    </div>
    
    <div class="car-highlight">
      <i class="fas fa-car"></i>
      <div>
        <div class="car-highlight-name" id="kembali-nama"></div>
        <div class="car-highlight-stock">Ajukan pengembalian untuk mobil ini</div>
      </div>
    </div>
    
    <form method="POST">
      <input type="hidden" name="ajukan_kembali" value="1">
      <input type="hidden" name="id_sewa" id="kembali-id">
      
      <div class="modal-footer">
        <button type="button" class="btn-modal btn-modal-cancel" onclick="closeModal('modal-kembali')">
          <i class="fas fa-times"></i> Batal
        </button>
        <button type="submit" class="btn-modal btn-modal-primary" style="background: var(--warning); color: #1e293b;">
          <i class="fas fa-check"></i> Ajukan Kembali
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function showTab(tabName, e) {
  if(e) e.preventDefault();
  const tabs = document.querySelectorAll('[id^="tab-"]');
  tabs.forEach(tab => tab.style.display = 'none');
  
  const activeTab = document.getElementById('tab-' + tabName);
  if(activeTab) activeTab.style.display = 'block';
  
  window.scrollTo(0, 0);
}

function openSewa(id, nama, stok) {
  document.getElementById('sewa-id').value = id;
  document.getElementById('sewa-nama').textContent = nama;
  document.getElementById('sewa-stok').textContent = stok;
  document.getElementById('sewa-jml').max = stok;
  document.getElementById('sewa-jml').value = 1;
  document.getElementById('sewa-jml').focus();
  document.getElementById('modal-sewa').classList.add('open');
}

function openKembali(id, nama) {
  document.getElementById('kembali-id').value = id;
  document.getElementById('kembali-nama').textContent = nama;
  document.getElementById('modal-kembali').classList.add('open');
}

function closeModal(modalId) {
  document.getElementById(modalId).classList.remove('open');
}

document.querySelectorAll('.modal-overlay').forEach(overlay => {
  overlay.addEventListener('click', function(e) {
    if(e.target === this) {
      closeModal(this.id);
    }
  });
});

const userToggle = document.getElementById('userToggle');
const userDropdown = document.getElementById('userDropdown');

userToggle.addEventListener('click', function(e) {
    e.stopPropagation();
    userDropdown.classList.toggle('show');
});

document.addEventListener('click', function() {
    userDropdown.classList.remove('show');
});
</script>
</body>
</html>

<!-- MODAL SEWA -->
<div class="modal-overlay" id="modal-sewa">
  <div class="modal">
    <div class="modal-header">
      <h3>Sewa Mobil</h3>
      <p>Pilih jumlah unit yang ingin disewa</p>
    </div>
    
    <div class="car-highlight">
      <i class="fas fa-car"></i>
      <div>
        <div class="car-highlight-name" id="sewa-nama"></div>
        <div class="car-highlight-stock">Stok tersedia: <strong id="sewa-stok" style="color: var(--primary);">0</strong> unit</div>
      </div>
    </div>
    
    <form method="POST">
      <input type="hidden" name="id_mobil" id="sewa-id">
      
      <div class="form-group">
        <label class="form-label">Jumlah Unit</label>
        <input type="number" name="jumlah" min="1" id="sewa-jml" class="form-input" placeholder="Masukkan jumlah unit" required>
      </div>
      
      <div class="modal-actions">
        <button type="button" class="btn btn-outline" onclick="closeModal('modal-sewa')">
          <i class="fas fa-times"></i> Batal
        </button>
        <button type="submit" class="btn btn-primary">
          <i class="fas fa-check"></i> Konfirmasi Sewa
        </button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL AJUKAN KEMBALI -->
<div class="modal-overlay" id="modal-kembali">
  <div class="modal">
    <div class="modal-header">
      <h3>Ajukan Pengembalian</h3>
      <p>Konfirmasi pengembalian mobil</p>
    </div>
    
    <div class="car-highlight">
      <i class="fas fa-car"></i>
      <div>
        <div class="car-highlight-name" id="kembali-nama"></div>
        <div class="car-highlight-stock">Ajukan pengembalian untuk mobil ini</div>
      </div>
    </div>
    
    <div class="info-notice">
      <i class="fas fa-info-circle"></i>
      <span>Setelah mengajukan pengembalian, admin akan memproses permohonan Anda. Pastikan mobil dalam kondisi baik dan siap dikembalikan.</span>
    </div>
    
    <form method="POST">
      <input type="hidden" name="ajukan_kembali" value="1">
      <input type="hidden" name="id_sewa" id="kembali-id">
      
      <div class="modal-footer">
        <button type="button" class="btn-modal btn-modal-cancel" onclick="closeModal('modal-kembali')">
          <i class="fas fa-times"></i> Batal
        </button>
        <button type="submit" class="btn-modal btn-modal-primary" style="background: var(--warning); color: #1e293b;">
          <i class="fas fa-check"></i> Ajukan Kembali
        </button>
      </div>
    </form>
  </div>
</div>

</body>
</html>