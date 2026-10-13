<?php
$adminTitle = 'Pemeran Film';
include __DIR__ . '/includes/admin-header.php';
include __DIR__ . '/includes/sidebar.php';

if (isset($_POST['delete_id'])) {
    $delId = (int)$_POST['delete_id'];
    $conn->query("DELETE FROM movie_actors WHERE id = $delId");
    header('Location: /cineva/admin/movie-actors.php?msg=deleted');
    exit;
}

$casts = $conn->query("
    SELECT ma.*, m.title as movie_title, a.name as actor_name
    FROM movie_actors ma
    JOIN movies m ON ma.movie_id = m.id
    JOIN actors a ON ma.actor_id = a.id
    ORDER BY m.title, a.name
");
?>

<main class="admin-main">
    <div class="admin-topbar">
        <h1>Pemeran Film</h1>
        <a href="/cineva/admin/movie-actor-form.php" class="btn-add">+ Tambah Pemeran</a>
    </div>

    <?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success" style="max-width:400px; margin-bottom:20px;">
        <?= $_GET['msg'] === 'deleted' ? 'Pemeran berhasil dihapus.' : 'Pemeran berhasil disimpan.' ?>
    </div>
    <?php endif; ?>

    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="admin-card-title">Semua Pemeran Film</h3>
            <div class="admin-search">
                <span class="admin-search-icon"><i class="fa-solid fa-search"></i></span>
                <input type="text" placeholder="Cari pemeran...">
            </div>
        </div>
        <table class="data-table">
            <thead>
                <tr><th>Film</th><th>Aktor</th><th>Karakter / Peran</th><th>Aksi</th></tr>
            </thead>
            <tbody>
                <?php while ($c = $casts->fetch_assoc()): ?>
                <tr>
                    <td style="color:var(--text-primary); font-weight:500;"><?= htmlspecialchars($c['movie_title']) ?></td>
                    <td><?= htmlspecialchars($c['actor_name']) ?></td>
                    <td><?= htmlspecialchars($c['character']) ?></td>
                    <td>
                        <div class="table-actions">
                            <a href="/cineva/admin/movie-actor-form.php?id=<?= $c['id'] ?>" class="table-btn edit">Edit</a>
                            <button class="table-btn delete btn-delete-trigger" data-id="<?= $c['id'] ?>" data-name="<?= htmlspecialchars($c['actor_name'] . ' di ' . $c['movie_title']) ?>">Hapus</button>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div class="modal-overlay" id="delete-modal">
        <div class="modal">
            <h3>Hapus Pemeran</h3>
            <p>Apakah Anda yakin ingin menghapus data pemeran ini?</p>
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
