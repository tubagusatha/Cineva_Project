<?php
$adminTitle = 'Pengguna';
include __DIR__ . '/includes/admin-header.php';
include __DIR__ . '/includes/sidebar.php';

if (isset($_POST['delete_id'])) {
    $delId = (int)$_POST['delete_id'];
    if ($delId !== $_SESSION['user_id']) {
        $conn->query("DELETE FROM users WHERE id = $delId");
    }
    header('Location: /cineva/admin/users.php?msg=deleted');
    exit;
}

$users = $conn->query("SELECT u.*, COALESCE(c.subscription, 'free') AS subscription, COUNT(wh.id) as watch_count FROM users u LEFT JOIN customers c ON c.user_id = u.id LEFT JOIN watch_history wh ON u.id = wh.user_id GROUP BY u.id ORDER BY u.created_at DESC");
?>

<main class="admin-main">
    <div class="admin-topbar">
        <h1>Pengguna</h1>
    </div>

    <?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success" style="max-width:400px; margin-bottom:20px;">
        <?= $_GET['msg'] === 'deleted' ? 'Pengguna berhasil dihapus.' : 'Pengguna berhasil diperbarui.' ?>
    </div>
    <?php endif; ?>

    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="admin-card-title">Semua Pengguna</h3>
            <div class="admin-search">
                <span class="admin-search-icon"><i class="fa-solid fa-search"></i></span>
                <input type="text" placeholder="Cari pengguna...">
            </div>
        </div>
        <table class="data-table">
            <thead>
                <tr><th>Nama</th><th>Email</th><th>Peran</th><th>Langganan</th><th>Ditonton</th><th>Bergabung</th><th>Aksi</th></tr>
            </thead>
            <tbody>
                <?php while ($u = $users->fetch_assoc()): ?>
                <tr>
                    <td style="color:var(--text-primary); font-weight:500;"><?= htmlspecialchars($u['name']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><span class="table-badge <?= $u['role'] ?>"><?= $u['role'] ?></span></td>
                    <td><span class="table-badge <?= $u['subscription'] ?>" title="Langganan"><?= ucfirst($u['subscription']) ?></span></td>
                    <td><?= $u['watch_count'] ?> film</td>
                    <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                    <td>
                        <div class="table-actions">
                            <a href="/cineva/admin/user-form.php?id=<?= $u['id'] ?>" class="table-btn edit">Edit</a>
                            <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                            <button class="table-btn delete btn-delete-trigger" data-id="<?= $u['id'] ?>" data-name="<?= htmlspecialchars($u['name']) ?>">Hapus</button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div class="modal-overlay" id="delete-modal">
        <div class="modal">
            <h3>Hapus Pengguna</h3>
            <p>Apakah Anda yakin? Riwayat tontonan pengguna ini juga akan terhapus.</p>
            <form method="POST" id="delete-form">
                <input type="hidden" name="delete_id" value="">
                <div class="modal-actions">
                    <button type="button" class="btn-cancel btn-cancel-modal">Batal</button>
                    <button type="submit" class="btn-submit" style="background:#dc2626;">Hapus</button>
                </div>
            </form>
        </div>
    </div>
</main>

</div>
<script src="/cineva/assets/js/admin.js"></script>
</body>
</html>
