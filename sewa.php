<?php
session_start();
require_once 'config.php';

if($_SESSION['role'] !== 'penyewa') {
    header('Location: login.php');
    exit;
}

$message = '';
$message_type = '';

if(isset($_POST['sewa'])) {
    $id_user = (int)$_SESSION['id_user'];
    $id_mobil = (int)$_POST['id_mobil'];
    $jumlah = (int)$_POST['jumlah'];

    if($id_mobil && $jumlah > 0) {
        // Ajukan sewa dulu ke pending_sewa (jangan kurangi stok di tahap user)
        $stmt = $conn->prepare("INSERT INTO penyewaan (id_user, id_mobil, jumlah_sewa, status, tanggal_sewa, denda) VALUES (?, ?, ?, 'pending_sewa', NULL, 0)");
        $stmt->bind_param("iii", $id_user, $id_mobil, $jumlah);
        if($stmt->execute()) {
            $message = 'Permintaan sewa berhasil dikirim! Tunggu konfirmasi admin.';
            $message_type = 'success';
        } else {
            $message = 'Gagal mengajukan sewa. Silakan coba lagi.';
            $message_type = 'error';
        }
    } else {
        $message = 'Pastikan semua field terisi dengan benar.';
        $message_type = 'error';
    }
}

$mobil_list = $conn->query("SELECT id_mobil, nama_mobil, harga_sewa FROM mobil WHERE jumlah > 0");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Sewa Mobil — RentalCar</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root {
  --primary: #2563eb;
  --primary-dark: #1e40af;
  --primary-light: #dbeafe;
  --success: #22c55e;
  --warning: #f59e0b;
  --danger: #ef4444;
  --bg: #ffffff;
  --bg-light: #f8fafc;
  --card-bg: #ffffff;
  --border: #e2e8f0;
  --text: #0f172a;
  --text-secondary: #475569;
  --text-muted: #94a3b8;
  --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
  --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.08);
  --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.08);
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
}

.navbar {
  background: var(--bg);
  border-bottom: 1px solid var(--border);
  padding: 16px 40px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: var(--shadow-sm);
  position: sticky;
  top: 0;
  z-index: 100;
}

.navbar-brand {
  display: flex;
  align-items: center;
  gap: 12px;
  text-decoration: none;
  font-weight: 800;
  font-size: 1.3rem;
  color: var(--text);
  font-family: 'Plus Jakarta Sans', sans-serif;
}

.navbar-brand-icon {
  width: 40px;
  height: 40px;
  background: linear-gradient(135deg, var(--primary) 0%, #6366f1 100%);
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.1rem;
}

.navbar-actions {
  display: flex;
  gap: 16px;
  align-items: center;
}

.logout-link {
  color: var(--danger);
  text-decoration: none;
  font-weight: 600;
  font-size: 0.9rem;
  display: flex;
  align-items: center;
  gap: 6px;
  cursor: pointer;
}

.logout-link:hover {
  opacity: 0.8;
}

.dashboard-link {
  color: var(--primary);
  text-decoration: none;
  font-weight: 600;
  font-size: 0.9rem;
  display: flex;
  align-items: center;
  gap: 6px;
}

.dashboard-link:hover {
  opacity: 0.7;
}

.container {
  max-width: 600px;
  margin: 0 auto;
  padding: 40px 20px;
}

.page-header {
  text-align: center;
  margin-bottom: 40px;
}

.page-title {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 2rem;
  font-weight: 800;
  color: var(--text);
  margin-bottom: 8px;
}

.page-subtitle {
  color: var(--text-secondary);
  font-size: 0.95rem;
}

.card {
  background: var(--card-bg);
  border: 1px solid var(--border);
  border-radius: 16px;
  box-shadow: var(--shadow-md);
  padding: 32px;
}

.form-group {
  margin-bottom: 24px;
}

.form-label {
  display: block;
  font-weight: 600;
  font-size: 0.95rem;
  color: var(--text);
  margin-bottom: 8px;
  font-family: 'Plus Jakarta Sans', sans-serif;
}

.form-input,
.form-select {
  width: 100%;
  border: 1px solid var(--border);
  border-radius: 10px;
  padding: 12px 16px;
  font-size: 0.95rem;
  color: var(--text);
  font-family: 'Inter', sans-serif;
  transition: all 0.2s;
  background: white;
}

.form-input:focus,
.form-select:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px var(--primary-light);
  background: white;
}

.alert {
  padding: 16px 20px;
  border-radius: 12px;
  margin-bottom: 24px;
  display: flex;
  align-items: flex-start;
  gap: 12px;
}

.alert-success {
  background: #dcfce7;
  color: var(--success);
  border: 1px solid #86efac;
}

.alert-error {
  background: #fee2e2;
  color: var(--danger);
  border: 1px solid #fca5a5;
}

.alert i {
  font-size: 1.1rem;
  flex-shrink: 0;
  margin-top: 2px;
}

.alert-message {
  font-size: 0.95rem;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.btn-submit {
  background: linear-gradient(135deg, var(--primary) 0%, #1e40af 100%);
  color: white;
  border: none;
  padding: 14px 32px;
  border-radius: 10px;
  font-weight: 700;
  font-size: 1rem;
  cursor: pointer;
  width: 100%;
  transition: all 0.2s;
  font-family: 'Plus Jakarta Sans', sans-serif;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.btn-submit:hover {
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
  transform: translateY(-2px);
}

.btn-submit:active {
  transform: translateY(0);
}

.help-text {
  font-size: 0.85rem;
  color: var(--text-muted);
  margin-top: 6px;
}

.form-note {
  background: var(--primary-light);
  border-left: 4px solid var(--primary);
  padding: 16px;
  border-radius: 8px;
  margin-bottom: 24px;
  color: #1e40af;
  font-size: 0.9rem;
}

@media (max-width: 768px) {
  .navbar {
    padding: 12px 20px;
  }

  .container {
    padding: 20px;
  }

  .page-title {
    font-size: 1.5rem;
  }

  .card {
    padding: 24px;
  }

  .form-row {
    grid-template-columns: 1fr;
  }
}
</style>
</head>
<body>

<nav class="navbar">
  <a href="penyewa_dashboard.php" class="navbar-brand">
    <div class="navbar-brand-icon">🚗</div>
    <span>RentalCar</span>
  </a>
  
  <div class="navbar-actions">
    <a href="penyewa_dashboard.php" class="dashboard-link">
      <i class="fas fa-th-large"></i> Dashboard
    </a>
    <a href="logout.php" class="logout-link">
      <i class="fas fa-sign-out-alt"></i> Keluar
    </a>
  </div>
</nav>

<div class="container">
  <div class="page-header">
    <div class="page-title">Sewa Mobil</div>
    <div class="page-subtitle">Pilih dan sewa mobil yang Anda inginkan</div>
  </div>

  <div class="card">
    <?php if($message): ?>
    <div class="alert alert-<?= $message_type ?>">
      <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
      <span class="alert-message"><?= htmlspecialchars($message) ?></span>
    </div>
    <?php endif; ?>

    <div class="form-note">
      <i class="fas fa-info-circle"></i>
      <span style="margin-left: 8px;">Pilih mobil dan jumlah unit yang ingin Anda sewa</span>
    </div>

    <form method="POST">
      <div class="form-group">
        <label class="form-label" for="id_mobil">
          <i class="fas fa-car"></i> Pilih Mobil
        </label>
        <select name="id_mobil" id="id_mobil" class="form-select" required>
          <option value="">— Pilih mobil —</option>
          <?php while($row = $mobil_list->fetch_assoc()): ?>
          <option value="<?= $row['id_mobil'] ?>">
            <?= htmlspecialchars($row['nama_mobil']) ?> - Rp <?= number_format($row['harga_sewa'], 0, ',', '.') ?>/hari
          </option>
          <?php endwhile; ?>
        </select>
        <div class="help-text">Hanya mobil yang tersedia yang ditampilkan</div>
      </div>

      <div class="form-group">
        <label class="form-label" for="jumlah">
          <i class="fas fa-boxes"></i> Jumlah Unit
        </label>
        <input type="number" name="jumlah" id="jumlah" class="form-input" min="1" max="10" value="1" required>
        <div class="help-text">Masukkan jumlah mobil yang ingin disewa (1-10)</div>
      </div>

      <button type="submit" name="sewa" class="btn-submit">
        <i class="fas fa-check"></i> Sewa Sekarang
      </button>
    </form>
  </div>
</div>

</body>
</html>