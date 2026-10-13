<?php
$pageTitle = 'Profil Saya';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
requireLogin();

$user = getCurrentUser($conn);
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($name) || empty($email)) {
        $error = 'Nama lengkap dan email wajib diisi.';
    } else {
        // Check email uniqueness
        $check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check->bind_param("si", $email, $user['id']);
        $check->execute();

        if ($check->get_result()->num_rows > 0) {
            $error = 'Alamat email sudah terdaftar pada akun lain.';
        } elseif ($newPassword && strlen($newPassword) < 6) {
            $error = 'Password baru minimal harus 6 karakter.';
        } elseif ($newPassword && $newPassword !== $confirmPassword) {
            $error = 'Konfirmasi password tidak cocok.';
        } else {
            if ($newPassword) {
                $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, password = ? WHERE id = ?");
                $stmt->bind_param("sssi", $name, $email, $hashed, $user['id']);
            } else {
                $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
                $stmt->bind_param("ssi", $name, $email, $user['id']);
            }

            if ($stmt->execute()) {
                $_SESSION['user_name'] = $name;
                $success = 'Profil akun Anda berhasil diperbarui.';
                $user = getCurrentUser($conn);
            } else {
                $error = 'Gagal memperbarui profil akun. Silakan coba lagi.';
            }
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="profile-page">
    <div class="container">
        <div class="page-header">
            <h1>Profil Saya</h1>
            <p>Kelola informasi akun dan pengaturan keamanan kata sandi Anda</p>
        </div>

        <div class="profile-card">
            <div class="profile-avatar"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
            <?php
            $subColors = ['free'=>'#9ca3af','basic'=>'#60a5fa','standard'=>'#fbbf24','premium'=>'#f87171','admin'=>'#e50914'];
            $subIcons  = ['free'=>'fa-circle-user','basic'=>'fa-circle-play','standard'=>'fa-fire','premium'=>'fa-crown','admin'=>'fa-shield-halved'];
            $plan = isAdmin() ? 'admin' : ($user['subscription'] ?? 'free');
            $planName = isAdmin() ? 'Administrator (Akses Penuh)' : 'Paket ' . ucfirst($plan) . ' (Aktif)';
            ?>
            <div style="text-align:center; margin-bottom:16px;">
                <a href="/cineva/subscription.php" style="display:inline-flex; align-items:center; gap:8px; background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.12); border-radius:50px; padding:6px 18px; font-size:0.84rem; font-weight:600; color:<?= $subColors[$plan] ?? '#e50914' ?>; text-decoration:none; transition:background 0.2s;">
                    <i class="fa-solid <?= $subIcons[$plan] ?? 'fa-crown' ?>"></i>
                    <?= $planName ?>
                    <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:0.7rem; opacity:0.7;"></i>
                </a>
            </div>

            <?php if ($success): ?>
            <div class="alert alert-success"><i class="fa-solid fa-circle-check" style="margin-right:6px;"></i> <?= $success ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
            <div class="alert alert-error"><i class="fa-solid fa-triangle-exclamation" style="margin-right:6px;"></i> <?= $error ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="name">Nama Lengkap</label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Alamat Email</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="new_password">Password Baru <span style="color:var(--text-dim); font-size:0.8rem;">(kosongkan jika tidak ingin diubah)</span></label>
                    <input type="password" id="new_password" name="new_password" placeholder="Minimal 6 karakter">
                </div>
                <div class="form-group">
                    <label for="confirm_password">Konfirmasi Password Baru</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Masukkan ulang password baru">
                </div>
                <button type="submit" class="btn-submit">Simpan Perubahan</button>
            </form>

            <?php
            // Ambil riwayat transaksi Midtrans user
            $stmtUserTrx = $conn->prepare("SELECT * FROM subscription_transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
            $stmtUserTrx->bind_param("i", $user['id']);
            $stmtUserTrx->execute();
            $userTrxList = $stmtUserTrx->get_result();
            ?>

            <div style="margin-top:32px; border-top:1px solid rgba(255,255,255,0.08); padding-top:24px; text-align:left;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
                    <h3 style="font-size:1.05rem; font-weight:700; color:#fff; display:flex; align-items:center; gap:8px;">
                        <i class="fa-solid fa-receipt text-accent"></i> Riwayat Transaksi Midtrans
                    </h3>
                    <a href="/cineva/subscription.php" style="font-size:0.8rem; color:#38bdf8; text-decoration:none;">
                        Langganan / Upgrade &rarr;
                    </a>
                </div>

                <?php if (!empty($user['subscription_expires_at']) && $plan !== 'free'): ?>
                <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); border-radius:12px; padding:12px 16px; margin-bottom:16px; font-size:0.85rem; color:#9ca3af;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
                        <span>Masa Aktif Paket:</span>
                        <strong style="color:#10b981;"><?= date('d M Y H:i', strtotime($user['subscription_expires_at'])) ?></strong>
                    </div>
                    <?php if (!empty($user['active_order_id'])): ?>
                    <div style="display:flex; justify-content:space-between;">
                        <span>Order ID Aktif:</span>
                        <span style="font-family:monospace; color:#38bdf8;"><?= htmlspecialchars($user['active_order_id']) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if ($userTrxList && $userTrxList->num_rows > 0): ?>
                <div style="overflow-x:auto;">
                    <table style="width:100%; border-collapse:collapse; font-size:0.82rem;">
                        <thead>
                            <tr style="border-bottom:1px solid rgba(255,255,255,0.1); color:#9ca3af;">
                                <th style="text-align:left; padding:8px;">Order ID</th>
                                <th style="text-align:left; padding:8px;">Paket</th>
                                <th style="text-align:left; padding:8px;">Nominal</th>
                                <th style="text-align:left; padding:8px;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($trx = $userTrxList->fetch_assoc()): ?>
                            <tr style="border-bottom:1px solid rgba(255,255,255,0.05); color:#d1d5db;">
                                <td style="padding:8px; font-family:monospace; color:#38bdf8;"><?= htmlspecialchars($trx['order_id']) ?></td>
                                <td style="padding:8px;"><?= ucfirst($trx['plan']) ?></td>
                                <td style="padding:8px;">Rp <?= number_format($trx['amount'], 0, ',', '.') ?></td>
                                <td style="padding:8px;">
                                    <span class="badge-trx-status <?= htmlspecialchars($trx['transaction_status']) ?>">
                                        <?= ucfirst($trx['transaction_status']) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div style="text-align:center; padding:16px; color:#6b7280; font-size:0.85rem;">
                    Belum ada catatan transaksi Midtrans.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
