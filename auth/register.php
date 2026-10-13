<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

if (isLoggedIn()) {
    header('Location: /cineva/');
    exit;
}

$error = '';
$name = $email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Silakan lengkapi semua kolom yang wajib diisi.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal harus 6 karakter.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();

        if ($check->get_result()->num_rows > 0) {
            $error = 'Alamat email ini sudah terdaftar. Silakan masuk akun.';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $role = 'user';

            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $hashedPassword, $role);

            if ($stmt->execute()) {
                $newUserId = $conn->insert_id;
                // Also insert into customers table (sub-entity)
                $cstmt = $conn->prepare("INSERT INTO customers (user_id, name, email, password, subscription) VALUES (?, ?, ?, ?, 'free')");
                $cstmt->bind_param("isss", $newUserId, $name, $email, $hashedPassword);
                $cstmt->execute();

                $_SESSION['user_id'] = $newUserId;
                $_SESSION['user_name'] = $name;
                $_SESSION['role'] = 'user';
                header('Location: /cineva/subscription.php?welcome=1');
                exit;
            } else {
                $error = 'Pendaftaran akun gagal. Silakan coba beberapa saat lagi.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun — Cineva</title>
    <link rel="stylesheet" href="/cineva/assets/css/style.css">
    <link rel="stylesheet" href="/cineva/assets/css/auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="auth-page">
    <div class="auth-card">
        <div class="auth-logo">
            <a href="/cineva/">Cine<span>va</span></a>
        </div>
        <h1 class="auth-title">Buat Akun Baru</h1>
        <p class="auth-subtitle">Mulai perjalanan streaming film Anda di Cineva</p>

        <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" class="auth-form">
            <div class="form-group">
                <label for="name">Nama Lengkap</label>
                <input type="text" id="name" name="name" placeholder="Nama lengkap Anda" value="<?= htmlspecialchars($name) ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Alamat Email</label>
                <input type="email" id="email" name="email" placeholder="nama@email.com" value="<?= htmlspecialchars($email) ?>" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Minimal 6 karakter" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Konfirmasi Password</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Ketik ulang password" required>
            </div>
            <button type="submit" class="btn-submit">Daftar Akun</button>
        </form>

        <div class="auth-footer">
            Sudah memiliki akun? <a href="/cineva/auth/login.php">Masuk Sekarang</a>
        </div>
    </div>
</div>
</body>
</html>
