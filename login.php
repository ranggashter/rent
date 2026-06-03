<?php
session_start();
if(isset($_SESSION['user'])) {
    header('Location: ' . ($_SESSION['role'] === 'admin' ? 'admin_dashboard.php' : 'penyewa_dashboard.php'));
    exit;
}

$error = '';
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'config.php';
    $username = $_POST['username'];
    $password = $_POST['password'];
    $stmt = $conn->prepare("SELECT * FROM user WHERE username=? AND password=?");
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();
    if($row = $result->fetch_assoc()) {
        $_SESSION['user']    = $row['id_user'];
        $_SESSION['nama']    = $row['nama'];
        $_SESSION['role']    = $row['role'];
        header('Location: ' . ($row['role'] === 'admin' ? 'admin_dashboard.php' : 'penyewa_dashboard.php'));
        exit;
    } else {
        $error = 'Username atau password salah!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>RentalCar — Login</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root {
  --primary: #2563eb;
  --primary-dark: #1e40af;
  --primary-light: #dbeafe;
  --success: #22c55e;
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
  background: linear-gradient(135deg, var(--bg-light) 0%, #f0f4f8 100%);
  font-family: 'Inter', sans-serif;
  color: var(--text);
  -webkit-font-smoothing: antialiased;
}

.login-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.login-wrapper {
  width: 100%;
  max-width: 440px;
}

.login-header {
  text-align: center;
  margin-bottom: 40px;
}

.logo {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  margin-bottom: 20px;
}

.logo-icon {
  width: 48px;
  height: 48px;
  background: linear-gradient(135deg, var(--primary) 0%, #6366f1 100%);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.4rem;
}

.logo-text {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 1.6rem;
  font-weight: 800;
  color: var(--text);
}

.logo-text span {
  color: var(--primary);
}

.login-header p {
  color: var(--text-secondary);
  font-size: 0.95rem;
  margin-top: 8px;
}

.login-card {
  background: var(--card-bg);
  border: 1px solid var(--border);
  border-radius: 20px;
  padding: 40px;
  box-shadow: var(--shadow-lg);
}

.login-card h2 {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 1.3rem;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 8px;
}

.login-card p {
  color: var(--text-secondary);
  font-size: 0.9rem;
  margin-bottom: 28px;
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
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.form-input {
  width: 100%;
  padding: 12px 14px;
  border: 1.5px solid var(--border);
  border-radius: 10px;
  font-size: 0.95rem;
  font-family: 'Inter', sans-serif;
  color: var(--text);
  outline: none;
  transition: all 0.2s ease;
  background: white;
}

.form-input:focus {
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-input::placeholder {
  color: var(--text-muted);
}

.btn-login {
  width: 100%;
  padding: 14px;
  background: linear-gradient(135deg, var(--primary) 0%, #1e40af 100%);
  color: white;
  border: none;
  border-radius: 10px;
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 0.95rem;
  font-weight: 700;
  cursor: pointer;
  margin-top: 8px;
  transition: all 0.3s ease;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.btn-login:hover {
  background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
  box-shadow: 0 6px 20px rgba(37, 99, 235, 0.4);
  transform: translateY(-2px);
}

.btn-login:active {
  transform: translateY(0);
}

.alert {
  background: #fee2e2;
  border: 1px solid #fecaca;
  color: #991b1b;
  padding: 12px 14px;
  border-radius: 10px;
  margin-bottom: 20px;
  font-size: 0.88rem;
  display: flex;
  align-items: center;
  gap: 10px;
  animation: slideDown 0.3s ease;
}

@keyframes slideDown {
  from { transform: translateY(-10px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}

.alert i {
  font-size: 1rem;
  flex-shrink: 0;
}

.login-footer {
  text-align: center;
  margin-top: 24px;
  padding-top: 24px;
  border-top: 1px solid var(--border);
  color: var(--text-secondary);
  font-size: 0.9rem;
}

.login-footer a {
  color: var(--primary);
  text-decoration: none;
  font-weight: 600;
}

.login-footer a:hover {
  text-decoration: underline;
}

@media (max-width: 480px) {
  .login-card {
    padding: 28px;
  }

  .logo-icon {
    width: 40px;
    height: 40px;
    font-size: 1.1rem;
  }

  .logo-text {
    font-size: 1.3rem;
  }
}
</style>
</head>
<body>

<div class="login-container">
  <div class="login-wrapper">
    <div class="login-header">
      <div class="logo">
        <div class="logo-icon">🚗</div>
        <div class="logo-text">Rental<span>Car</span></div>
      </div>
      <p>Kelola penyewaan mobil dengan mudah</p>
    </div>

    <div class="login-card">
      <h2>Masuk ke Akun</h2>
      <p>Silakan login dengan akun Anda</p>

      <?php if($error): ?>
      <div class="alert">
        <i class="fas fa-exclamation-circle"></i>
        <?= htmlspecialchars($error) ?>
      </div>
      <?php endif; ?>

      <form method="POST">
        <div class="form-group">
          <label class="form-label">Username</label>
          <input type="text" name="username" class="form-input" placeholder="Masukkan username" required autofocus>
        </div>

        <div class="form-group">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-input" placeholder="Masukkan password" required>
        </div>

        <button type="submit" class="btn-login">
          <i class="fas fa-sign-in-alt"></i> Masuk
        </button>
      </form>

      <div class="login-footer">
        Demo: admin/admin atau user/user
      </div>
    </div>
  </div>
</div>

</body>
</html>
        <label>Password</label>
        <input type="password" name="password" placeholder="Masukkan password" required>
      </div>
      <button class="btn" type="submit">MASUK</button>
    </form>
  </div>
</div>
</body>
</html>