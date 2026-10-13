<?php
$adminTitle = 'Kelola Film';
include __DIR__ . '/includes/admin-header.php';
include __DIR__ . '/includes/sidebar.php';

// Handle delete
if (isset($_POST['delete_id'])) {
    $delId = (int)$_POST['delete_id'];
    // Delete poster file if exists
    $old = $conn->query("SELECT poster_url FROM movies WHERE id = $delId")->fetch_assoc();
    if ($old && $old['poster_url'] && file_exists(__DIR__ . '/../' . $old['poster_url'])) {
        unlink(__DIR__ . '/../' . $old['poster_url']);
    }
    $conn->query("DELETE FROM movies WHERE id = $delId");
    header('Location: /cineva/admin/movies.php?msg=deleted');
    exit;
}

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$total = $conn->query("SELECT COUNT(*) as c FROM movies")->fetch_assoc()['c'];
$totalPages = ceil($total / $limit);

$movies = $conn->query("SELECT m.*, g.name as genre_name FROM movies m JOIN genres g ON m.genre_id = g.id ORDER BY m.created_at DESC LIMIT $limit OFFSET $offset");
?>

<main class="admin-main">
    <div class="admin-topbar">
        <h1>Film</h1>
        <a href="/cineva/admin/movie-form.php" class="btn-add">+ Tambah Film</a>
    </div>

    <?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success" style="max-width:400px; margin-bottom:20px;">
        <?php
        $msgs = ['deleted' => 'Film berhasil dihapus.', 'saved' => 'Film berhasil disimpan.'];
        echo $msgs[$_GET['msg']] ?? 'Selesai.';
        ?>
    </div>
    <?php endif; ?>

    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="admin-card-title"><?= $total ?> total film</h3>
            <div class="admin-search">
                <span class="admin-search-icon"><i class="fa-solid fa-search"></i></span>
                <input type="text" placeholder="Cari film...">
            </div>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Poster</th>
                    <th>Judul</th>
                    <th>Genre</th>
                    <th>Tahun</th>
                    <th>Rating</th>
                    <th>Durasi</th>
                    <th>Unggulan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($m = $movies->fetch_assoc()): ?>
                <tr>
                    <td>
                        <?php if ($m['poster_url']): ?>
                        <img src="/cineva/<?= htmlspecialchars($m['poster_url']) ?>" class="table-poster" alt="">
                        <?php else: ?>
                        <div class="table-poster" style="display:flex;align-items:center;justify-content:center;font-size:0.65rem;color:var(--text-dim);">No img</div>
                        <?php endif; ?>
                    </td>
                    <td style="color:var(--text-primary); font-weight:500;"><?= htmlspecialchars($m['title']) ?></td>
                    <td><?= htmlspecialchars($m['genre_name']) ?></td>
                    <td><?= $m['year'] ?></td>
                    <td><span style="color:var(--gold);"><i class="fa-solid fa-star"></i></span> <?= number_format($m['rating'], 1) ?></td>
                    <td><?= $m['duration'] ?> mnt</td>
                    <td><?= $m['is_featured'] ? '<span class="table-badge featured">Ya</span>' : '—' ?></td>
                    <td>
                        <div class="table-actions">
                            <a href="/cineva/movie.php?id=<?= $m['id'] ?>" class="table-btn view" target="_blank">Lihat</a>
                            <a href="/cineva/admin/movie-form.php?id=<?= $m['id'] ?>" class="table-btn edit">Edit</a>
                            <button class="table-btn delete btn-delete-trigger" data-id="<?= $m['id'] ?>" data-name="<?= htmlspecialchars($m['title']) ?>">Hapus</button>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>">&laquo; Seb</a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=<?= $i ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>">Sel &raquo;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Delete Modal -->
    <div class="modal-overlay" id="delete-modal">
        <div class="modal">
            <h3>Hapus Film</h3>
            <p>Apakah Anda yakin ingin menghapus film ini?</p>
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
