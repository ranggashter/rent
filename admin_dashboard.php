<?php
session_start();
if(!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php'); exit;
}
require_once 'config.php';
require_once 'upload_foto.php';

// Handle actions
$msg = '';
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if($action === 'tambah_mobil') {
        $nama    = $_POST['nama_mobil'];
        $jumlah  = (int)$_POST['jumlah'];
        $kondisi = $_POST['kondisi'];
        $harga   = (int)$_POST['harga_sewa'];
        $gambar  = null;
        if(!empty($_FILES['gambar']['name'])) $gambar = upload_foto($_FILES['gambar']);
        $stmt = $conn->prepare("INSERT INTO mobil(nama_mobil,gambar,jumlah,kondisi,harga_sewa) VALUES(?,?,?,?,?)");
        $stmt->bind_param("ssisi", $nama, $gambar, $jumlah, $kondisi, $harga);
        $stmt->execute();
        $msg = 'Mobil berhasil ditambahkan!';
    }

    if($action === 'edit_mobil') {
        $id      = (int)$_POST['id_mobil'];
        $nama    = $_POST['nama_mobil'];
        $jumlah  = (int)$_POST['jumlah'];
        $kondisi = $_POST['kondisi'];
        $harga   = (int)$_POST['harga_sewa'];
        if(!empty($_FILES['gambar']['name'])) {
            $gambar = upload_foto($_FILES['gambar']);
            $stmt = $conn->prepare("UPDATE mobil SET nama_mobil=?,gambar=?,jumlah=?,kondisi=?,harga_sewa=? WHERE id_mobil=?");
            $stmt->bind_param("ssissi", $nama, $gambar, $jumlah, $kondisi, $harga, $id);
        } else {
            $stmt = $conn->prepare("UPDATE mobil SET nama_mobil=?,jumlah=?,kondisi=?,harga_sewa=? WHERE id_mobil=?");
            $stmt->bind_param("sisii", $nama, $jumlah, $kondisi, $harga, $id);
        }
        $stmt->execute();
        $msg = 'Mobil berhasil diupdate!';
    }

    if($action === 'hapus_mobil') {
        $id = (int)$_POST['id_mobil'];
        $conn->query("DELETE FROM mobil WHERE id_mobil=$id");
        $msg = 'Mobil berhasil dihapus!';
    }

    if($action === 'setujui_sewa') {
        $id_sewa = (int)$_POST['id_sewa'];

        // Ambil data penyewaan
        $r = $conn->query("SELECT id_mobil,jumlah_sewa FROM penyewaan WHERE id_sewa=$id_sewa")->fetch_assoc();
        if(!$r) {
            $msg = '❌ Data penyewaan tidak ditemukan.';
        } else {
            $id_mobil_req = (int)$r['id_mobil'];
            $jumlah_sewa  = (int)$r['jumlah_sewa'];

            // Kurangi stok mobil (hanya jika stok mencukupi)
            $conn->query("UPDATE mobil
                SET jumlah = jumlah - $jumlah_sewa
                WHERE id_mobil = $id_mobil_req AND jumlah >= $jumlah_sewa");

            // Update status jadi disewa
            $conn->query("UPDATE penyewaan
                SET status='disewa', tanggal_sewa=CURDATE()
                WHERE id_sewa=$id_sewa");

            $msg = '✅ Permintaan sewa berhasil disetujui!';
        }
    }

    if($action === 'tolak_sewa') {
        $id_sewa = (int)$_POST['id_sewa'];
        $conn->query("UPDATE penyewaan SET status='ditolak' WHERE id_sewa=$id_sewa");
        $msg = '❌ Permintaan sewa ditolak.';
    }

    if($action === 'setujui_kembali') {
        $id_sewa = (int)$_POST['id_sewa'];
        $conn->query("CALL kembalikan_mobil($id_sewa)");
        $conn->query("
            UPDATE penyewaan SET denda = CASE
                WHEN DATEDIFF(CURDATE(),tanggal_sewa) > 7
                THEN (DATEDIFF(CURDATE(),tanggal_sewa)-7)*50000
                ELSE 0
            END WHERE id_sewa=$id_sewa
        ");
        $msg = '✅ Pengembalian mobil berhasil disetujui!';
    }
}

// Fetch data
$mobil_list = $conn->query("SELECT *, status_mobil(jumlah) AS status FROM mobil");
$sewa_list  = $conn->query("
    SELECT p.id_sewa, u.nama, m.nama_mobil, p.jumlah_sewa, m.harga_sewa,
           p.tanggal_sewa, p.tanggal_kembali, p.status, p.denda
    FROM penyewaan p
    JOIN user u ON p.id_user=u.id_user
    JOIN mobil m ON p.id_mobil=m.id_mobil
    ORDER BY FIELD(p.status,'pending_sewa','pending_kembali','disewa','dikembalikan','ditolak'), p.id_sewa DESC
");

// Stats
$total_mobil     = $conn->query("SELECT COUNT(*) c FROM mobil")->fetch_assoc()['c'];
$aktif_sewa      = $conn->query("SELECT COUNT(*) c FROM penyewaan WHERE status='disewa'")->fetch_assoc()['c'];
$total_denda     = $conn->query("SELECT COALESCE(SUM(denda),0) c FROM penyewaan")->fetch_assoc()['c'];

// Total Pendapatan (sewa saja)
// Pendapatan dihitung dari harga sewa per hari * jumlah unit untuk penyewaan yang sudah selesai/dikembalikan.
// Pastikan status selesai sesuai dengan data di tabel penyewaan.
$total_pendapatan = $conn->query(
    "SELECT COALESCE(SUM(m.harga_sewa * p.jumlah_sewa),0) c " .
    "FROM penyewaan p " .
    "JOIN mobil m ON p.id_mobil=m.id_mobil " .
    "WHERE p.status IN ('dikembalikan','selesai','kembali')"
)->fetch_assoc()['c'];

$pending_sewa    = $conn->query("SELECT COUNT(*) c FROM penyewaan WHERE status='pending_sewa'")->fetch_assoc()['c'];
$pending_kembali = $conn->query("SELECT COUNT(*) c FROM penyewaan WHERE status='pending_kembali'")->fetch_assoc()['c'];
$total_pending   = $pending_sewa + $pending_kembali;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Dashboard — RentalCar</title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
:root{
  --bg:#08080f;--sidebar:#0e0e18;--card:#13131e;
  --border:#1a1a2a;--accent:#e8c547;--accent2:#f0a500;
  --text:#f0ede8;--muted:#5a5a6e;--success:#4ade80;--danger:#f87171;
  --info:#60a5fa;--warning:#fb923c;
}
body{min-height:100vh;background:var(--bg);font-family:'DM Sans',sans-serif;color:var(--text);display:flex}

/* SIDEBAR */
.sidebar{
  width:220px;min-height:100vh;background:var(--sidebar);
  border-right:1px solid var(--border);padding:28px 20px;
  display:flex;flex-direction:column;gap:6px;flex-shrink:0;
}
.brand{font-family:'Bebas Neue',sans-serif;font-size:1.6rem;letter-spacing:3px;color:var(--accent);margin-bottom:30px}
.brand span{color:#fff}
.nav-item{
  padding:10px 14px;border-radius:8px;cursor:pointer;font-size:.88rem;
  color:var(--muted);transition:.15s;display:flex;align-items:center;gap:10px;
}
.nav-item:hover,.nav-item.active{background:rgba(232,197,71,.1);color:var(--accent)}
.nav-label{font-size:.65rem;letter-spacing:1px;color:var(--muted);padding:16px 14px 6px;text-transform:uppercase}
.logout{margin-top:auto;padding:10px 14px;border-radius:8px;cursor:pointer;font-size:.88rem;color:var(--danger);display:flex;align-items:center;gap:10px}
.logout:hover{background:rgba(248,113,113,.1)}

/* MAIN */
.main{flex:1;padding:32px;overflow-x:hidden}
.topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:32px}
.page-title{font-family:'Bebas Neue',sans-serif;font-size:1.8rem;letter-spacing:2px}
.badge{background:rgba(232,197,71,.15);color:var(--accent);border-radius:20px;padding:4px 12px;font-size:.78rem}

/* STATS */
.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:28px}
.stat-card{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:20px 22px}
.stat-label{font-size:.72rem;letter-spacing:1px;color:var(--muted);text-transform:uppercase;margin-bottom:8px}
.stat-val{font-family:'Bebas Neue',sans-serif;font-size:2.2rem;letter-spacing:2px}
.stat-val.y{color:var(--accent)}
.stat-val.g{color:var(--success)}
.stat-val.r{color:var(--danger)}

/* SECTION */
.section{background:var(--card);border:1px solid var(--border);border-radius:12px;margin-bottom:24px}
.sec-header{
  display:flex;align-items:center;justify-content:space-between;
  padding:18px 22px;border-bottom:1px solid var(--border);
}
.sec-title{font-size:.88rem;font-weight:600;letter-spacing:.5px}
.btn-sm{
  background:var(--accent);color:#0a0a0a;border:none;border-radius:6px;
  padding:8px 16px;font-family:'Bebas Neue',sans-serif;font-size:.9rem;
  letter-spacing:1px;cursor:pointer;
}
.btn-sm:hover{background:var(--accent2)}
.btn-danger{background:rgba(248,113,113,.15);color:var(--danger);border:1px solid rgba(248,113,113,.3)}
.btn-info{background:rgba(96,165,250,.15);color:var(--info);border:1px solid rgba(96,165,250,.3)}

/* TABLE */
.tbl-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse;font-size:.85rem}
th{text-align:left;padding:12px 16px;font-size:.7rem;letter-spacing:1px;color:var(--muted);text-transform:uppercase;border-bottom:1px solid var(--border)}
td{padding:12px 16px;border-bottom:1px solid rgba(255,255,255,.03)}
tr:last-child td{border-bottom:none}
.pill{display:inline-block;padding:3px 10px;border-radius:20px;font-size:.72rem}
.pill.tersedia{background:rgba(74,222,128,.15);color:var(--success)}
.pill.tdk{background:rgba(248,113,113,.15);color:var(--danger)}
.pill.disewa{background:rgba(232,197,71,.15);color:var(--accent)}
.pill.kembali{background:rgba(74,222,128,.15);color:var(--success)}
.pill.pending{background:rgba(251,146,60,.15);color:var(--warning)}
.pill.baik{background:rgba(74,222,128,.15);color:var(--success)}
.pill.rusak{background:rgba(248,113,113,.15);color:var(--danger)}
.row-act{display:flex;gap:6px}

/* MODAL */
.modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:100;align-items:center;justify-content:center}
.modal-bg.open{display:flex}
.modal{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:30px;width:100%;max-width:420px}
.modal h3{font-size:1rem;font-weight:600;margin-bottom:20px}
.field{margin-bottom:14px}
.field label{display:block;font-size:.72rem;letter-spacing:.8px;color:var(--muted);margin-bottom:6px;text-transform:uppercase}
.field input,.field select{
  width:100%;background:#0a0a12;border:1px solid var(--border);
  border-radius:7px;padding:10px 12px;color:var(--text);font-family:'DM Sans',sans-serif;
  font-size:.9rem;outline:none;transition:border-color .2s;
}
.field input:focus,.field select:focus{border-color:var(--accent)}
.field select option{background:#13131e}
.modal-footer{display:flex;gap:10px;margin-top:20px}
.btn-full{flex:1;background:var(--accent);color:#0a0a0a;border:none;border-radius:7px;padding:11px;font-family:'Bebas Neue',sans-serif;font-size:1rem;letter-spacing:1px;cursor:pointer}
.btn-cancel{flex:1;background:transparent;color:var(--muted);border:1px solid var(--border);border-radius:7px;padding:11px;font-family:'DM Sans',sans-serif;font-size:.88rem;cursor:pointer}
.toast{position:fixed;top:20px;right:20px;background:var(--success);color:#0a0a0a;border-radius:8px;padding:12px 20px;font-size:.88rem;z-index:200;display:none}
</style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
  <div class="brand">RENTAL<span>CAR</span></div>
  <div class="nav-label">Menu</div>
  <div class="nav-item active" onclick="showTab('mobil')">🚗 Data Mobil</div>
  <div class="nav-item" onclick="showTab('sewa')">📋 Data Penyewaan</div>
  <div class="nav-item" onclick="showTab('laporan')">📊 Laporan</div>
  <a href="logout.php" class="logout">🚪 Keluar</a>
</aside>

<!-- MAIN -->
<main class="main">
  <div class="topbar">
    <div>
      <div class="page-title">DASHBOARD ADMIN</div>
    </div>
    <div class="badge">👤 <?= htmlspecialchars($_SESSION['nama']) ?></div>
  </div>

  <!-- STATS -->
  <div class="stats">
    <div class="stat-card">
      <div class="stat-label">Total Mobil</div>
      <div class="stat-val y"><?= $total_mobil ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Sedang Disewa</div>
      <div class="stat-val g"><?= $aktif_sewa ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Total Pendapatan</div>
      <div class="stat-val y">Rp <?= number_format($total_pendapatan,0,',','.') ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Total Denda</div>
      <div class="stat-val r">Rp <?= number_format($total_denda,0,',','.') ?></div>
    </div>
  </div>

  <!-- TAB: DATA MOBIL -->
  <div id="tab-mobil">
    <div class="section">
      <div class="sec-header">
        <div class="sec-title">🚗 Daftar Mobil</div>
        <button class="btn-sm" onclick="openModal('tambah')">+ Tambah Mobil</button>
      </div>
      <div class="tbl-wrap">
        <table>
          <thead><tr>
            <th>ID</th><th>Nama Mobil</th><th>Gambar</th><th>Jumlah</th>
            <th>Kondisi</th><th>Harga/Hari</th><th>Status</th><th>Aksi</th>
          </tr></thead>
          <tbody>
          <?php while($r = $mobil_list->fetch_assoc()): ?>
          <tr>
            <td><?= $r['id_mobil'] ?></td>
            <td><?= htmlspecialchars($r['nama_mobil']) ?></td>
            <td>
              <?php if(!empty($r['gambar']) && file_exists('uploads/'.$r['gambar'])): ?>
                <img src="uploads/<?= htmlspecialchars($r['gambar']) ?>" alt="<?= htmlspecialchars($r['nama_mobil']) ?>" style="max-width:60px;max-height:40px;border-radius:4px;object-fit:cover;">
              <?php else: ?>
                <span style="color:var(--muted);font-size:.78rem">Belum ada</span>
              <?php endif; ?>
            </td>
            <td><?= $r['jumlah'] ?></td>
            <td><span class="pill <?= $r['kondisi'] ?>"><?= ucfirst($r['kondisi']) ?></span></td>
            <td>Rp <?= number_format($r['harga_sewa'],0,',','.') ?></td>
            <td><span class="pill <?= $r['jumlah']>0?'tersedia':'tdk' ?>"><?= $r['status'] ?></span></td>
            <td>
              <div class="row-act">
                <button class="btn-sm btn-info" onclick="openEdit(<?= htmlspecialchars(json_encode($r)) ?>)">Edit</button>
                <form method="POST" onsubmit="return confirm('Hapus mobil ini?')">
                  <input type="hidden" name="action" value="hapus_mobil">
                  <input type="hidden" name="id_mobil" value="<?= $r['id_mobil'] ?>">
                  <button type="submit" class="btn-sm btn-danger">Hapus</button>
                </form>
              </div>
            </td>
          </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- TAB: DATA SEWA -->
  <div id="tab-sewa" style="display:none">
    <div class="section">
      <div class="sec-header">
        <div class="sec-title">📋 Data Penyewaan</div>
      </div>
      <div class="tbl-wrap">
        <table>
          <thead><tr>
            <th>ID</th><th>Penyewa</th><th>Mobil</th><th>Jml</th>
            <th>Tgl Sewa</th><th>Tgl Kembali</th><th>Status</th><th>Denda</th><th>Aksi</th>
          </tr></thead>
          <tbody>
          <?php while($r = $sewa_list->fetch_assoc()): ?>
          <tr>
            <td><?= $r['id_sewa'] ?></td>
            <td><?= htmlspecialchars($r['nama']) ?></td>
            <td><?= htmlspecialchars($r['nama_mobil']) ?></td>
            <td><?= $r['jumlah_sewa'] ?></td>
            <td><?= $r['tanggal_sewa'] ?></td>
            <td><?= $r['tanggal_kembali'] ?? '-' ?></td>
            <td>
              <?php
              $st = $r['status'];
              if($st==='disewa') echo '<span class="pill disewa">Disewa</span>';
              elseif($st==='pending_kembali') echo '<span class="pill pending">⏳ Minta Dikembalikan</span>';
              else echo '<span class="pill kembali">Dikembalikan</span>';
              ?>
            </td>
            <td>Rp <?= number_format($r['denda'],0,',','.') ?></td>
            <td>
              <?php if($r['status']==='pending_sewa'): ?>
              <form method="POST">
                <input type="hidden" name="action" value="setujui_sewa">
                <input type="hidden" name="id_sewa" value="<?= $r['id_sewa'] ?>">
                <button type="submit" class="btn-sm" style="background:var(--success);color:#0a0a0a">✓ Setujui</button>
                <button type="submit" class="btn-sm btn-danger" style="margin-left:8px" name="action" value="tolak_sewa">✗ Tolak</button>
              </form>
              <?php elseif($r['status']==='pending_kembali'): ?>
              <form method="POST">
                <input type="hidden" name="action" value="setujui_kembali">
                <input type="hidden" name="id_sewa" value="<?= $r['id_sewa'] ?>">
                <button type="submit" class="btn-sm" style="background:var(--success);color:#0a0a0a">✓ Setujui</button>
              </form>
              <?php elseif($r['status']==='disewa'): ?>
              <span style="color:var(--muted);font-size:.78rem">Menunggu penyewa...</span>
              <?php else: ?>
              <span style="color:var(--muted);font-size:.78rem">Selesai</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- TAB: LAPORAN -->
  <div id="tab-laporan" style="display:none">
    <div class="section">
      <div class="sec-header">
        <div class="sec-title">📊 Laporan Penyewaan per Tanggal</div>
      </div>
      <form method="GET" style="padding:18px 22px;display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
        <div class="field" style="margin:0">
          <label>Dari Tanggal</label>
          <input type="date" name="dari" value="<?= $_GET['dari'] ?? date('Y-m-01') ?>">
        </div>
        <div class="field" style="margin:0">
          <label>Sampai Tanggal</label>
          <input type="date" name="sampai" value="<?= $_GET['sampai'] ?? date('Y-m-d') ?>">
        </div>
        <input type="hidden" name="tab" value="laporan">
        <button type="submit" class="btn-sm">Tampilkan</button>
      </form>
      <div class="tbl-wrap">
        <table>
          <thead><tr>
            <th>ID</th><th>Penyewa</th><th>Mobil</th><th>Jml</th>
            <th>Tgl Sewa</th><th>Tgl Kembali</th><th>Status</th><th>Denda</th>
          </tr></thead>
          <tbody>
          <?php
          $dari    = $_GET['dari']    ?? date('Y-m-01');
          $sampai  = $_GET['sampai']  ?? date('Y-m-d');
          $lap = $conn->prepare("
              SELECT p.id_sewa,u.nama,m.nama_mobil,p.jumlah_sewa,
                     p.tanggal_sewa,p.tanggal_kembali,p.status,p.denda
              FROM penyewaan p
              JOIN user u ON p.id_user=u.id_user
              JOIN mobil m ON p.id_mobil=m.id_mobil
              WHERE p.tanggal_sewa BETWEEN ? AND ?
              ORDER BY p.tanggal_sewa DESC
          ");
          $lap->bind_param("ss",$dari,$sampai);
          $lap->execute();
          $lr = $lap->get_result();
          $total_lap = 0;
          while($r = $lr->fetch_assoc()):
              $total_lap += $r['denda'];
          ?>
          <tr>
            <td><?= $r['id_sewa'] ?></td>
            <td><?= htmlspecialchars($r['nama']) ?></td>
            <td><?= htmlspecialchars($r['nama_mobil']) ?></td>
            <td><?= $r['jumlah_sewa'] ?></td>
            <td><?= $r['tanggal_sewa'] ?></td>
            <td><?= $r['tanggal_kembali'] ?? '-' ?></td>
            <td><span class="pill <?= $r['status']==='disewa'?'disewa':'kembali' ?>"><?= ucfirst($r['status']) ?></span></td>
            <td>Rp <?= number_format($r['denda'],0,',','.') ?></td>
          </tr>
          <?php endwhile; ?>
          <tr>
            <td colspan="7" style="text-align:right;font-weight:600;color:var(--muted)">Total Denda:</td>
            <td style="color:var(--danger);font-weight:600">Rp <?= number_format($total_lap,0,',','.') ?></td>
          </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</main>

<!-- MODAL TAMBAH -->
<div class="modal-bg" id="modal-tambah">
  <div class="modal">
    <h3>Tambah Mobil</h3>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="tambah_mobil">
      <div class="field"><label>Nama Mobil</label><input type="text" name="nama_mobil" required></div>
      <div class="field"><label>Gambar Mobil</label><input type="file" name="gambar" accept="image/*" style="padding:8px 12px;cursor:pointer"></div>
      <div class="field"><label>Jumlah</label><input type="number" name="jumlah" min="1" required></div>
      <div class="field"><label>Kondisi</label>
        <select name="kondisi"><option value="baik">Baik</option><option value="rusak">Rusak</option></select>
      </div>
      <div class="field"><label>Harga Sewa/Hari (Rp)</label><input type="number" name="harga_sewa" required></div>
      <div class="modal-footer">
        <button type="button" class="btn-cancel" onclick="closeModal('tambah')">Batal</button>
        <button type="submit" class="btn-full">SIMPAN</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL EDIT -->
<div class="modal-bg" id="modal-edit">
  <div class="modal">
    <h3>Edit Mobil</h3>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="edit_mobil">
      <input type="hidden" name="id_mobil" id="edit_id">
      <div class="field"><label>Nama Mobil</label><input type="text" name="nama_mobil" id="edit_nama" required></div>
      <div class="field"><label>Gambar Mobil (Kosongkan jika tidak mengubah)</label><input type="file" name="gambar" accept="image/*" style="padding:8px 12px;cursor:pointer"></div>
      <div class="field"><label>Jumlah</label><input type="number" name="jumlah" id="edit_jumlah" min="0" required></div>
      <div class="field"><label>Kondisi</label>
        <select name="kondisi" id="edit_kondisi">
          <option value="baik">Baik</option><option value="rusak">Rusak</option>
        </select>
      </div>
      <div class="field"><label>Harga Sewa/Hari (Rp)</label><input type="number" name="harga_sewa" id="edit_harga" required></div>
      <div class="modal-footer">
        <button type="button" class="btn-cancel" onclick="closeModal('edit')">Batal</button>
        <button type="submit" class="btn-full">UPDATE</button>
      </div>
    </form>
  </div>
</div>

<?php if($msg): ?>
<div class="toast" id="toast"><?= $msg ?></div>
<?php endif; ?>

<script>
function showTab(t) {
  document.querySelectorAll('[id^="tab-"]').forEach(e=>e.style.display='none');
  document.getElementById('tab-'+t).style.display='block';
  document.querySelectorAll('.nav-item').forEach(e=>e.classList.remove('active'));
  event.target.classList.add('active');
}
function openModal(m) { document.getElementById('modal-'+m).classList.add('open') }
function closeModal(m) { document.getElementById('modal-'+m).classList.remove('open') }
function openEdit(d) {
  document.getElementById('edit_id').value     = d.id_mobil;
  document.getElementById('edit_nama').value   = d.nama_mobil;
  document.getElementById('edit_jumlah').value = d.jumlah;
  document.getElementById('edit_kondisi').value= d.kondisi;
  document.getElementById('edit_harga').value  = d.harga_sewa;
  openModal('edit');
}
// Show toast
const t = document.getElementById('toast');
if(t) { t.style.display='block'; setTimeout(()=>t.style.display='none',3000); }

// Auto show tab from URL
const p = new URLSearchParams(location.search);
if(p.get('tab')) {
  document.getElementById('tab-'+p.get('tab')).style.display='block';
  document.getElementById('tab-mobil').style.display='none';
}
</script>
</body>
</html>