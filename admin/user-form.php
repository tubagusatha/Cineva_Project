<?php
$adminTitle = 'Edit Pengguna';
include __DIR__ . '/includes/admin-header.php';
include __DIR__ . '/includes/sidebar.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: /cineva/admin/users.php'); exit; }

$stmt = $conn->prepare("SELECT u.*, COALESCE(c.subscription, 'free') AS subscription FROM users u LEFT JOIN customers c ON c.user_id = u.id WHERE u.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
if (!$user) { header('Location: /cineva/admin/users.php'); exit; }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'user';
    $subscription = $_POST['subscription'] ?? 'free';

    if (empty($name) || empty($email)) {
        $error = 'Nama dan email wajib diisi.';
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check->bind_param("si", $email, $id);
        $check->execute();

        if ($check->get_result()->num_rows > 0) {
            $error = 'Email sudah digunakan oleh akun lain.';
        } else {
            $stmt = $conn->prepare("UPDATE users SET name=?, email=?, role=? WHERE id=?");
            $stmt->bind_param("sssi", $name, $email, $role, $id);

            if ($stmt->execute()) {
                // Update or insert customer subscription
                $cstmt = $conn->prepare("
                    INSERT INTO customers (user_id, name, email, password, subscription)
                    SELECT id, name, email, password, ? FROM users WHERE id = ?
                    ON DUPLICATE KEY UPDATE name = VALUES(name), email = VALUES(email), subscription = VALUES(subscription)
                ");
                $cstmt->bind_param("si", $subscription, $id);
                $cstmt->execute();
                header('Location: /cineva/admin/users.php?msg=saved');
                exit;
            }
            $error = 'Gagal memperbarui pengguna.';
        }
    }
}
?>

<main class="admin-main">
    <div class="admin-topbar">
        <h1>Edit Pengguna</h1>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-error" style="max-width:700px; margin-bottom:20px;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="admin-card" style="max-width:700px;">
        <div style="padding:28px;">
            <form method="POST" class="admin-form">
                <div class="form-group">
                    <label for="name">Nama</label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="role">Peran</label>
                    <select id="role" name="role">
                        <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>User</option>
                        <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="subscription">Paket Langganan</label>
                    <select id="subscription" name="subscription">
                        <option value="free"     <?= $user['subscription'] === 'free'     ? 'selected' : '' ?>>Free</option>
                        <option value="basic"    <?= $user['subscription'] === 'basic'    ? 'selected' : '' ?>>Basic</option>
                        <option value="standard" <?= $user['subscription'] === 'standard' ? 'selected' : '' ?>>Standard</option>
                        <option value="premium"  <?= $user['subscription'] === 'premium'  ? 'selected' : '' ?>>Premium</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-submit">Simpan Perubahan</button>
                    <a href="/cineva/admin/users.php" class="btn-cancel">Batal</a>
                </div>
            </form>
        </div>
    </div>
</main>

</div>
<script src="/cineva/assets/js/admin.js"></script>
</body>
</html>
