<?php
session_start();
if(!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
require_once 'config.php';

$data = $conn->query("SELECT *, status_mobil(jumlah) AS status FROM mobil");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Daftar Mobil — RentalCar Admin</title>
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

.btn-new {
  background: linear-gradient(135deg, var(--primary) 0%, #1e40af 100%);
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.9rem;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  gap: 8px;
  text-decoration: none;
}

.btn-new:hover {
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
  transform: translateY(-2px);
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

.container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 40px 20px;
}

.page-header {
  margin-bottom: 32px;
}

.page-title {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 2rem;
  font-weight: 800;
  color: var(--text);
  margin-bottom: 4px;
}

.page-subtitle {
  color: var(--text-secondary);
  font-size: 0.95rem;
}

.card {
  background: var(--card-bg);
  border: 1px solid var(--border);
  border-radius: 16px;
  box-shadow: var(--shadow-sm);
  overflow: hidden;
}

.card-header {
  padding: 24px;
  border-bottom: 1px solid var(--border);
  background: var(--bg-light);
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 16px;
}

.card-title {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--text);
  display: flex;
  align-items: center;
  gap: 10px;
}

.table-wrapper {
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.9rem;
}

th {
  text-align: left;
  padding: 16px 24px;
  border-bottom: 2px solid var(--border);
  font-weight: 700;
  color: var(--text-secondary);
  text-transform: uppercase;
  font-size: 0.8rem;
  letter-spacing: 0.5px;
  background: var(--bg-light);
}

td {
  padding: 16px 24px;
  border-bottom: 1px solid var(--border);
}

tbody tr:hover {
  background: var(--bg-light);
  transition: all 0.2s;
}

tbody tr:last-child td {
  border-bottom: none;
}

.badge {
  display: inline-block;
  padding: 6px 12px;
  border-radius: 50px;
  font-size: 0.8rem;
  font-weight: 600;
}

.badge-success {
  background: #dcfce7;
  color: var(--success);
}

.badge-danger {
  background: #fee2e2;
  color: var(--danger);
}

.badge-warning {
  background: #fef3c7;
  color: var(--warning);
}

.action-buttons {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.btn-sm {
  padding: 8px 14px;
  border: 1px solid var(--border);
  background: white;
  color: var(--primary);
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  font-size: 0.85rem;
  text-decoration: none;
  transition: all 0.2s;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.btn-sm:hover {
  background: var(--primary-light);
  border-color: var(--primary);
}

.btn-sm.danger {
  color: var(--danger);
  border-color: var(--danger);
}

.btn-sm.danger:hover {
  background: #fee2e2;
  border-color: var(--danger);
}

.empty-state {
  text-align: center;
  padding: 60px 24px;
  color: var(--text-muted);
}

.empty-state i {
  font-size: 3rem;
  margin-bottom: 16px;
  opacity: 0.5;
}

.empty-state p {
  margin: 8px 0;
}

.empty-state a {
  color: var(--primary);
  text-decoration: none;
  font-weight: 600;
}

.empty-state a:hover {
  text-decoration: underline;
}

.count-badge {
  background: var(--primary-light);
  color: var(--primary);
  padding: 4px 12px;
  border-radius: 50px;
  font-size: 0.85rem;
  font-weight: 600;
}

@media (max-width: 768px) {
  .navbar {
    padding: 12px 20px;
    flex-direction: column;
    gap: 16px;
  }

  .container {
    padding: 20px;
  }

  .page-title {
    font-size: 1.5rem;
  }

  .card-header {
    flex-direction: column;
    align-items: flex-start;
  }

  table {
    font-size: 0.8rem;
  }

  th, td {
    padding: 12px 16px;
  }

  .action-buttons {
    flex-direction: column;
    width: 100%;
  }

  .btn-sm {
    width: 100%;
    justify-content: center;
  }
}
</style>
</head>
<body>

<nav class="navbar">
  <a href="admin_dashboard.php" class="navbar-brand">
    <div class="navbar-brand-icon">🚗</div>
    <span>RentalCar Admin</span>
  </a>
  
  <div class="navbar-actions">
    <a href="tambah_mobil.php" class="btn-new">
      <i class="fas fa-plus"></i> Tambah Mobil
    </a>
    <a href="logout.php" class="logout-link">
      <i class="fas fa-sign-out-alt"></i> Keluar
    </a>
  </div>
</nav>

<div class="container">
  <div class="page-header">
    <div class="page-title">Daftar Mobil</div>
    <div class="page-subtitle">Kelola semua kendaraan dalam sistem</div>
  </div>

  <div class="card">
    <div class="card-header">
      <div class="card-title">
        <i class="fas fa-car"></i> Semua Mobil
      </div>
      <span class="count-badge">
        <?= $data->num_rows ?> mobil
      </span>
    </div>

    <div class="table-wrapper">
      <?php if($data->num_rows > 0): ?>
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Nama Mobil</th>
            <th>Gambar</th>
            <th>Jumlah</th>
            <th>Kondisi</th>
            <th>Harga/Hari</th>
            <th>Status</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php while($row = $data->fetch_assoc()): ?>
          <tr>
            <td><strong>#<?= $row['id_mobil'] ?></strong></td>
            <td><?= htmlspecialchars($row['nama_mobil']) ?></td>
            <td>
              <?php if(!empty($row['gambar']) && file_exists('uploads/'.$row['gambar'])): ?>
                <img src="uploads/<?= htmlspecialchars($row['gambar']) ?>" alt="car" style="width: 50px; height: 40px; border-radius: 6px; object-fit: cover;">
              <?php else: ?>
                <span style="color: var(--text-muted); font-size: 0.85rem;">—</span>
              <?php endif; ?>
            </td>
            <td><?= $row['jumlah'] ?> unit</td>
            <td>
              <span class="badge <?= $row['kondisi'] === 'baik' ? 'badge-success' : ($row['kondisi'] === 'rusak' ? 'badge-danger' : 'badge-warning') ?>">
                <?= ucfirst($row['kondisi']) ?>
              </span>
            </td>
            <td>Rp <?= number_format($row['harga_sewa'], 0, ',', '.') ?></td>
            <td>
              <span class="badge <?= $row['jumlah'] > 0 ? 'badge-success' : 'badge-danger' ?>">
                <?= $row['status'] ?>
              </span>
            </td>
            <td>
              <div class="action-buttons">
                <a href="admin_dashboard.php?action=edit&id=<?= $row['id_mobil'] ?>" class="btn-sm">
                  <i class="fas fa-edit"></i> Edit
                </a>
                <a href="hapus_mobil.php?id=<?= $row['id_mobil'] ?>" class="btn-sm danger" onclick="return confirm('Yakin hapus mobil ini?');">
                  <i class="fas fa-trash"></i> Hapus
                </a>
              </div>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
      <?php else: ?>
      <div class="empty-state">
        <i class="fas fa-car"></i>
        <p><strong>Belum ada mobil yang ditambahkan</strong></p>
        <p style="font-size: 0.9rem; margin-top: 12px;">
          <a href="tambah_mobil.php">Tambah mobil sekarang »</a>
        </p>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

</body>
</html>