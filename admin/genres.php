<?php
$adminTitle = 'Genre';
include __DIR__ . '/includes/admin-header.php';
include __DIR__ . '/includes/sidebar.php';

if (isset($_POST['delete_id'])) {
    $delId = (int)$_POST['delete_id'];
    $conn->query("DELETE FROM genres WHERE id = $delId");
    header('Location: /cineva/admin/genres.php?msg=deleted');
    exit;
}

$genres = $conn->query("
    SELECT g.*, COUNT(m.id) as movie_count
    FROM genres g
    LEFT JOIN movies m ON g.id = m.genre_id
    GROUP BY g.id
    ORDER BY g.name
");
?>

<main class="admin-main">
    <div class="admin-topbar">
        <h1>Genre</h1>
        <a href="/cineva/admin/genre-form.php" class="btn-add">+ Tambah Genre</a>
    </div>

    <?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success" style="max-width:400px; margin-bottom:20px;">
        <?= $_GET['msg'] === 'deleted' ? 'Genre berhasil dihapus.' : 'Genre berhasil disimpan.' ?>
    </div>
    <?php endif; ?>

    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="admin-card-title">Semua Genre</h3>
            <div class="admin-search">
                <span class="admin-search-icon"><i class="fa-solid fa-search"></i></span>
                <input type="text" placeholder="Cari genre...">
            </div>
        </div>
        <table class="data-table">
            <thead>
                <tr><th>Nama</th><th>Deskripsi</th><th>Jumlah Film</th><th>Aksi</th></tr>
            </thead>
            <tbody>
                <?php while ($g = $genres->fetch_assoc()): ?>
                <tr>
                    <td style="color:var(--text-primary); font-weight:500;"><?= htmlspecialchars($g['name']) ?></td>
                    <td><?= htmlspecialchars(mb_substr($g['description'], 0, 80)) ?><?= mb_strlen($g['description']) > 80 ? '...' : '' ?></td>
                    <td><?= $g['movie_count'] ?> film</td>
                    <td>
                        <div class="table-actions">
                            <a href="/cineva/admin/genre-form.php?id=<?= $g['id'] ?>" class="table-btn edit">Edit</a>
                            <button class="table-btn delete btn-delete-trigger" data-id="<?= $g['id'] ?>" data-name="<?= htmlspecialchars($g['name']) ?>">Hapus</button>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div class="modal-overlay" id="delete-modal">
        <div class="modal">
            <h3>Hapus Genre</h3>
            <p>Apakah Anda yakin? Semua film dalam genre ini juga akan terhapus.</p>
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
