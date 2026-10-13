<?php
$adminTitle = 'Riwayat Tontonan';
include __DIR__ . '/includes/admin-header.php';
include __DIR__ . '/includes/sidebar.php';

if (isset($_POST['delete_id'])) {
    $delId = (int)$_POST['delete_id'];
    $conn->query("DELETE FROM watch_history WHERE id = $delId");
    header('Location: /cineva/admin/watch-history.php?msg=deleted');
    exit;
}

$history = $conn->query("
    SELECT wh.*, u.name as user_name, u.email, m.title as movie_title
    FROM watch_history wh
    JOIN users u ON wh.user_id = u.id
    JOIN movies m ON wh.movie_id = m.id
    ORDER BY wh.watched_at DESC
");
?>

<main class="admin-main">
    <div class="admin-topbar">
        <h1>Riwayat Tontonan</h1>
    </div>

    <?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success" style="max-width:400px; margin-bottom:20px;">Data riwayat berhasil dihapus.</div>
    <?php endif; ?>

    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="admin-card-title">Semua Riwayat Menonton</h3>
            <div class="admin-search">
                <span class="admin-search-icon"><i class="fa-solid fa-search"></i></span>
                <input type="text" placeholder="Cari riwayat...">
            </div>
        </div>
        <table class="data-table">
            <thead>
                <tr><th>Pengguna</th><th>Film</th><th>Waktu Menonton</th><th>Aksi</th></tr>
            </thead>
            <tbody>
                <?php while ($h = $history->fetch_assoc()): ?>
                <tr>
                    <td>
                        <div>
                            <div style="color:var(--text-primary); font-weight:500;"><?= htmlspecialchars($h['user_name']) ?></div>
                            <div style="font-size:0.78rem; color:var(--text-dim);"><?= htmlspecialchars($h['email']) ?></div>
                        </div>
                    </td>
                    <td style="color:var(--text-primary);"><?= htmlspecialchars($h['movie_title']) ?></td>

                    <td><?= date('d M Y H:i', strtotime($h['watched_at'])) ?></td>
                    <td>
                        <button class="table-btn delete btn-delete-trigger" data-id="<?= $h['id'] ?>" data-name="riwayat ini">Hapus</button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div class="modal-overlay" id="delete-modal">
        <div class="modal">
            <h3>Hapus Riwayat</h3>
            <p>Apakah Anda yakin ingin menghapus data riwayat tontonan ini?</p>
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
