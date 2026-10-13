<?php
$pageTitle = 'Riwayat Tontonan';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
requireSubscription($conn);

$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("
    SELECT wh.*, m.title, m.poster_url, m.year, m.rating, m.duration, g.name as genre_name
    FROM watch_history wh
    JOIN movies m ON wh.movie_id = m.id
    JOIN genres g ON m.genre_id = g.id
    WHERE wh.user_id = ?
    ORDER BY wh.watched_at DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$history = $stmt->get_result();

include __DIR__ . '/includes/header.php';
?>

<div class="history-page">
    <div class="container">
        <div class="page-header">
            <h1>Riwayat Tontonan</h1>
            <p>Daftar film yang baru saja Anda tonton</p>
        </div>

        <?php if ($history->num_rows > 0): ?>
        <div class="history-list">
            <?php while ($item = $history->fetch_assoc()): ?>
            <div class="history-item">
                <div class="history-poster">
                    <?php if ($item['poster_url']): ?>
                    <img src="<?= asset($item['poster_url']) ?>" alt="<?= htmlspecialchars($item['title']) ?>">
                    <?php else: ?>
                    <div class="history-poster-placeholder"><?= htmlspecialchars($item['title']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="history-info">
                    <h4><a href="/cineva/movie.php?id=<?= $item['movie_id'] ?>"><?= htmlspecialchars($item['title']) ?></a></h4>
                    <div class="history-info-meta">
                        <span><i class="fa-solid fa-star"></i> <?= number_format($item['rating'], 1) ?></span>
                        <span><?= $item['year'] ?></span>
                        <span><?= $item['duration'] ?> menit</span>
                        <span><?= htmlspecialchars($item['genre_name']) ?></span>
                    </div>
                    <span style="font-size:0.78rem; color:var(--text-dim); margin-top:4px; display:block;">
                        Ditonton pada <?= date('d M Y, H:i', strtotime($item['watched_at'])) ?> WIB
                    </span>
                </div>
                <div class="history-actions">
                    <a href="/cineva/watch.php?id=<?= $item['movie_id'] ?>" class="btn-sm primary"><i class="fa-solid fa-play"></i> Tonton Lagi</a>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <div class="empty-state-icon"><i class="fa-solid fa-tv"></i></div>
            <h3>Belum ada riwayat tontonan</h3>
            <p>Mulai tonton film favorit Anda dan riwayat tontonan akan muncul di sini</p>
            <div style="margin-top: 16px;">
                <a href="/cineva/" class="btn-primary" style="padding: 10px 24px; font-size: 0.9rem;">Mulai Menonton</a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
