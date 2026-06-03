<?php
session_start();
if(!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
require_once 'config.php';

$message = '';
$message_type = '';

if(isset($_POST['simpan'])) {
    $nama = $conn->real_escape_string($_POST['nama']);
    $jumlah = intval($_POST['jumlah']);
    $kondisi = $conn->real_escape_string($_POST['kondisi']);
    $harga = intval($_POST['harga']);
    
    if($nama && $jumlah && $harga) {
        // Insert mobil
        $result = $conn->query("INSERT INTO mobil (nama_mobil, jumlah, kondisi, harga_sewa) 
                              VALUES ('$nama', $jumlah, '$kondisi', $harga)");
        
        if($result) {
            $id_mobil = $conn->insert_id;
            
            // Handle gambar upload jika ada
            if(!empty($_FILES['gambar']['name'])) {
                $file = $_FILES['gambar'];
                $allowed = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png'];
                $filename = basename($file['name']);
                $filetype = $file['type'];
                $filesize = $file['size'];
                
                if(in_array($filetype, $allowed)) {
                    if($filesize < 5000000) {
                        $new_filename = 'mobil_' . $id_mobil . '_' . time() . '.' . pathinfo($filename, PATHINFO_EXTENSION);
                        
                        if(!is_dir('uploads')) {
                            mkdir('uploads', 0755, true);
                        }
                        
                        if(move_uploaded_file($file['tmp_name'], 'uploads/' . $new_filename)) {
                            $conn->query("UPDATE mobil SET gambar = '$new_filename' WHERE id_mobil = $id_mobil");
                        }
                    }
                }
            }
            
            $message = 'Mobil berhasil ditambahkan!';
            $message_type = 'success';
            $_POST = [];
        } else {
            $message = 'Gagal menambahkan mobil: ' . $conn->error;
            $message_type = 'error';
        }
    } else {
        $message = 'Pastikan semua field wajib diisi.';
        $message_type = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Tambah Mobil — RentalCar Admin</title>
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

.back-link {
  color: var(--primary);
  text-decoration: none;
  font-weight: 600;
  font-size: 0.9rem;
  display: flex;
  align-items: center;
  gap: 6px;
  cursor: pointer;
}

.back-link:hover {
  opacity: 0.7;
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
  max-width: 700px;
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

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.help-text {
  font-size: 0.85rem;
  color: var(--text-muted);
  margin-top: 6px;
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

.file-upload-wrapper {
  border: 2px dashed var(--border);
  border-radius: 12px;
  padding: 24px;
  text-align: center;
  transition: all 0.2s;
  cursor: pointer;
  background: var(--bg-light);
}

.file-upload-wrapper:hover {
  border-color: var(--primary);
  background: var(--primary-light);
}

.file-upload-wrapper input[type="file"] {
  display: none;
}

.file-upload-icon {
  font-size: 2rem;
  color: var(--primary);
  margin-bottom: 8px;
}

.file-upload-text {
  font-weight: 600;
  color: var(--text);
  margin-bottom: 4px;
}

.file-upload-hint {
  font-size: 0.85rem;
  color: var(--text-muted);
}

.image-preview {
  margin-top: 16px;
  text-align: center;
}

.image-preview img {
  max-width: 200px;
  max-height: 200px;
  border-radius: 10px;
  box-shadow: var(--shadow-md);
}

.btn-group {
  display: flex;
  gap: 12px;
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
  flex: 1;
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

.btn-cancel {
  background: white;
  color: var(--text-secondary);
  border: 1px solid var(--border);
  padding: 14px 32px;
  border-radius: 10px;
  font-weight: 700;
  font-size: 1rem;
  cursor: pointer;
  flex: 1;
  transition: all 0.2s;
  font-family: 'Plus Jakarta Sans', sans-serif;
  text-decoration: none;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.btn-cancel:hover {
  background: var(--bg-light);
  border-color: var(--text-secondary);
}

@media (max-width: 768px) {
  .navbar {
    padding: 12px 20px;
    flex-direction: column;
    gap: 12px;
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

  .btn-group {
    flex-direction: column;
  }

  .btn-submit,
  .btn-cancel {
    width: 100%;
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
    <a href="mobil.php" class="back-link">
      <i class="fas fa-arrow-left"></i> Kembali
    </a>
    <a href="logout.php" class="logout-link">
      <i class="fas fa-sign-out-alt"></i> Keluar
    </a>
  </div>
</nav>

<div class="container">
  <div class="page-header">
    <div class="page-title">Tambah Mobil Baru</div>
    <div class="page-subtitle">Masukkan detail kendaraan baru ke dalam sistem</div>
  </div>

  <div class="card">
    <?php if($message): ?>
    <div class="alert alert-<?= $message_type ?>">
      <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
      <span class="alert-message"><?= htmlspecialchars($message) ?></span>
    </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
      <div class="form-group">
        <label class="form-label" for="nama">
          <i class="fas fa-heading"></i> Nama Mobil <span style="color: var(--danger);">*</span>
        </label>
        <input type="text" name="nama" id="nama" class="form-input" placeholder="Contoh: Toyota Avanza 2023" value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>" required>
        <div class="help-text">Nama lengkap dan spesifikasi mobil</div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="jumlah">
            <i class="fas fa-boxes"></i> Jumlah Unit <span style="color: var(--danger);">*</span>
          </label>
          <input type="number" name="jumlah" id="jumlah" class="form-input" min="1" max="100" value="<?= htmlspecialchars($_POST['jumlah'] ?? '1') ?>" required>
          <div class="help-text">Jumlah unit mobil yang tersedia</div>
        </div>

        <div class="form-group">
          <label class="form-label" for="harga">
            <i class="fas fa-money-bill"></i> Harga Sewa/Hari <span style="color: var(--danger);">*</span>
          </label>
          <input type="number" name="harga" id="harga" class="form-input" min="10000" step="1000" value="<?= htmlspecialchars($_POST['harga'] ?? '') ?>" placeholder="Contoh: 250000" required>
          <div class="help-text">Harga dalam Rupiah</div>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="kondisi">
          <i class="fas fa-tools"></i> Kondisi Mobil <span style="color: var(--danger);">*</span>
        </label>
        <select name="kondisi" id="kondisi" class="form-select" required>
          <option value="">— Pilih kondisi —</option>
          <option value="baik" <?= ($_POST['kondisi'] ?? '') === 'baik' ? 'selected' : '' ?>>Baik</option>
          <option value="rusak" <?= ($_POST['kondisi'] ?? '') === 'rusak' ? 'selected' : '' ?>>Rusak</option>
        </select>
        <div class="help-text">Status kondisi kendaraan saat ini</div>
      </div>

      <div class="form-group">
        <label class="form-label" for="gambar">
          <i class="fas fa-image"></i> Gambar Mobil (Opsional)
        </label>
        <div class="file-upload-wrapper" onclick="document.getElementById('gambar').click();">
          <div class="file-upload-icon">📷</div>
          <div class="file-upload-text">Klik untuk upload atau drag & drop</div>
          <div class="file-upload-hint">JPG, PNG • Max 5MB</div>
          <input type="file" name="gambar" id="gambar" accept="image/jpeg,image/png" onchange="previewImage(event)">
        </div>
        <div class="image-preview" id="imagePreview"></div>
      </div>

      <div class="btn-group">
        <button type="submit" name="simpan" class="btn-submit">
          <i class="fas fa-save"></i> Simpan Mobil
        </button>
        <a href="mobil.php" class="btn-cancel">
          <i class="fas fa-times"></i> Batal
        </a>
      </div>
    </form>
  </div>
</div>

<script>
function previewImage(event) {
  const file = event.target.files[0];
  const preview = document.getElementById('imagePreview');
  
  if(file) {
    const reader = new FileReader();
    reader.onload = function(e) {
      preview.innerHTML = '<img src="' + e.target.result + '" alt="preview">';
    };
    reader.readAsDataURL(file);
  }
}

// Drag & drop support
const wrapper = document.querySelector('.file-upload-wrapper');
wrapper.addEventListener('dragover', (e) => {
  e.preventDefault();
  wrapper.style.borderColor = 'var(--primary)';
  wrapper.style.background = 'var(--primary-light)';
});

wrapper.addEventListener('dragleave', () => {
  wrapper.style.borderColor = 'var(--border)';
  wrapper.style.background = 'var(--bg-light)';
});

wrapper.addEventListener('drop', (e) => {
  e.preventDefault();
  const files = e.dataTransfer.files;
  document.getElementById('gambar').files = files;
  previewImage({target: {files: files}});
  wrapper.style.borderColor = 'var(--border)';
  wrapper.style.background = 'var(--bg-light)';
});
</script>

</body>
</html>