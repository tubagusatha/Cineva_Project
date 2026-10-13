<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

if (isLoggedIn() && !isAdmin() && !hasActiveSubscription($conn)) {
    header('Location: /cineva/subscription.php?required=1');
    exit;
}

$pageTitle = 'Genre';
include __DIR__ . '/includes/header.php';

$genreId = (int)($_GET['id'] ?? 0);
$genres = $conn->query("SELECT * FROM genres ORDER BY name");

$movies = null;
$activeGenre = null;

if ($genreId) {
    $stmt = $conn->prepare("SELECT * FROM genres WHERE id = ?");
    $stmt->bind_param("i", $genreId);
    $stmt->execute();
    $activeGenre = $stmt->get_result()->fetch_assoc();

    if ($activeGenre) {
        $movieStmt = $conn->prepare("SELECT m.*, g.name as genre_name FROM movies m JOIN genres g ON m.genre_id = g.id WHERE m.genre_id = ? ORDER BY m.rating DESC");
        $movieStmt->bind_param("i", $genreId);
        $movieStmt->execute();
        $movies = $movieStmt->get_result();
        $pageTitle = 'Film ' . $activeGenre['name'];
    }
} else {
    $movies = $conn->query("SELECT m.*, g.name as genre_name FROM movies m JOIN genres g ON m.genre_id = g.id ORDER BY m.rating DESC");
}
?>

<div class="genre-page">
    <div class="container">
        <div class="page-header">
            <h1><?= $activeGenre ? 'Film ' . htmlspecialchars($activeGenre['name']) : 'Jelajahi Berdasarkan Genre' ?></h1>
            <?php if ($activeGenre && $activeGenre['description']): ?>
            <p><?= htmlspecialchars($activeGenre['description']) ?></p>
            <?php endif; ?>
        </div>

        <div class="genre-chips">
            <a href="/cineva/genre.php" class="genre-chip <?= !$genreId ? 'active' : '' ?>">Semua</a>
            <?php while ($g = $genres->fetch_assoc()): ?>
            <a href="/cineva/genre.php?id=<?= $g['id'] ?>" class="genre-chip <?= $genreId == $g['id'] ? 'active' : '' ?>"><?= htmlspecialchars($g['name']) ?></a>
            <?php endwhile; ?>
        </div>

        <?php if ($movies && $movies->num_rows > 0): ?>
        <div class="movie-grid">
            <?php while ($movie = $movies->fetch_assoc()): ?>
            <a href="/cineva/movie.php?id=<?= $movie['id'] ?>" class="movie-card">
                <?php if ($movie['poster_url']): ?>
                <img src="<?= asset($movie['poster_url']) ?>" alt="<?= htmlspecialchars($movie['title']) ?>" class="movie-card-poster">
                <?php else: ?>
                <div class="movie-card-placeholder"><span><?= htmlspecialchars($movie['title']) ?></span></div>
                <?php endif; ?>
                <div class="movie-card-overlay">
                    <div class="movie-card-title"><?= htmlspecialchars($movie['title']) ?></div>
                    <div class="movie-card-info">
                        <span class="movie-card-rating"><i class="fa-solid fa-star"></i> <?= number_format($movie['rating'], 1) ?></span>
                        <span><?= $movie['year'] ?></span>
                    </div>
                </div>
            </a>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <div class="empty-state-icon"><i class="fa-solid fa-film"></i></div>
            <h3>Tidak ada film ditemukan</h3>
            <p>Belum ada film yang tersedia dalam kategori genre ini.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
