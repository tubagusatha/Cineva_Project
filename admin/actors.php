<?php
$adminTitle = 'Aktor';
include __DIR__ . '/includes/admin-header.php';
include __DIR__ . '/includes/sidebar.php';

if (isset($_POST['delete_id'])) {
    $delId = (int)$_POST['delete_id'];
    $old = $conn->query("SELECT url_photo FROM actors WHERE id = $delId")->fetch_assoc();
    if ($old && $old['url_photo'] && file_exists(__DIR__ . '/../' . $old['url_photo'])) {
        unlink(__DIR__ . '/../' . $old['url_photo']);
    }
    $conn->query("DELETE FROM actors WHERE id = $delId");
    header('Location: /cineva/admin/actors.php?msg=deleted');
    exit;
}

$actors = $conn->query("
    SELECT a.*, COUNT(ma.id) as movie_count
    FROM actors a
    LEFT JOIN movie_actors ma ON a.id = ma.actor_id
    GROUP BY a.id
    ORDER BY a.name
");
?>

<main class="admin-main">
    <div class="admin-topbar">
        <h1>Aktor</h1>
        <a href="/cineva/admin/actor-form.php" class="btn-add">+ Tambah Aktor</a>
    </div>

    <?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success" style="max-width:400px; margin-bottom:20px;">
        <?= $_GET['msg'] === 'deleted' ? 'Aktor berhasil dihapus.' : 'Aktor berhasil disimpan.' ?>
    </div>
    <?php endif; ?>

    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="admin-card-title">Semua Aktor</h3>
            <div class="admin-search">
                <span class="admin-search-icon"><i class="fa-solid fa-search"></i></span>
                <input type="text" placeholder="Cari aktor...">
            </div>
        </div>
        <table class="data-table">
            <thead>
                <tr><th>Foto</th><th>Nama</th><th>Tanggal Lahir</th><th>Jumlah Film</th><th>Aksi</th></tr>
            </thead>
            <tbody>
                <?php while ($a = $actors->fetch_assoc()): ?>
                <tr>
                    <td>
                        <?php if ($a['url_photo']): ?>
                        <img src="/cineva/<?= htmlspecialchars($a['url_photo']) ?>" class="table-avatar" alt="">
                        <?php else: ?>
                        <div class="table-avatar" style="display:flex;align-items:center;justify-content:center;font-weight:700;color:var(--text-dim);font-size:0.85rem;"><?= strtoupper(substr($a['name'], 0, 1)) ?></div>
                        <?php endif; ?>
                    </td>
                    <td style="color:var(--text-primary); font-weight:500;"><?= htmlspecialchars($a['name']) ?></td>
                    <td><?= $a['birthdate'] ? date('d M Y', strtotime($a['birthdate'])) : '—' ?></td>
                    <td><?= $a['movie_count'] ?> film</td>
                    <td>
                        <div class="table-actions">
                            <a href="/cineva/admin/actor-form.php?id=<?= $a['id'] ?>" class="table-btn edit">Edit</a>
                            <button class="table-btn delete btn-delete-trigger" data-id="<?= $a['id'] ?>" data-name="<?= htmlspecialchars($a['name']) ?>">Hapus</button>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div class="modal-overlay" id="delete-modal">
        <div class="modal">
            <h3>Hapus Aktor</h3>
            <p>Apakah Anda yakin ingin menghapus data aktor ini?</p>
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
