<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$redirect = trim($_GET['redirect'] ?? ($_POST['redirect'] ?? ''));
// Validate redirect to prevent open redirect
if ($redirect && !str_starts_with($redirect, '/cineva/')) {
    $redirect = '';
}

if (isLoggedIn()) {
    if (isAdmin()) {
        header('Location: /cineva/admin/');
    } elseif (!hasActiveSubscription($conn)) {
        header('Location: /cineva/subscription.php?required=1');
    } else {
        header('Location: ' . ($redirect ?: '/cineva/'));
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Silakan isi email dan password.';
    } else {
        $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] === 'admin') {
                header('Location: /cineva/admin/');
                exit;
            }

            // For regular user: must have active subscription
            if (!hasActiveSubscription($conn, $user['id'])) {
                header('Location: /cineva/subscription.php?required=1');
                exit;
            }

            header('Location: ' . ($redirect ?: '/cineva/'));
            exit;
        } else {
            $error = 'Email atau password salah.';
        }
    }
}

$pageTitle = 'Masuk';
$extraCss = 'auth.css';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> — Cineva</title>
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
        <h1 class="auth-title">Selamat Datang</h1>
        <p class="auth-subtitle">Masuk untuk melanjutkan menonton film favorit</p>

        <?php if ($redirect): ?>
        <div class="alert" style="background: rgba(229, 9, 20, 0.12); border: 1px solid rgba(229, 9, 20, 0.35); color: #fca5a5; font-size: 0.88rem; margin-bottom: 20px;">
            <i class="fa-solid fa-lock" style="margin-right: 6px;"></i>
            Silakan masuk akun terlebih dahulu untuk melihat dan menonton film.
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" class="auth-form">
            <?php if ($redirect): ?>
            <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">
            <?php endif; ?>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="nama@email.com" value="<?= htmlspecialchars($email ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Masukkan password Anda" required>
            </div>
            <button type="submit" class="btn-submit">Masuk</button>
        </form>

        <div class="auth-footer">
            Belum punya akun? <a href="/cineva/auth/register.php">Daftar Sekarang</a>
        </div>
    </div>
</div>
</body>
</html>
