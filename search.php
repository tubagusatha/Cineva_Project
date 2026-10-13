<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

if (isLoggedIn() && !isAdmin() && !hasActiveSubscription($conn)) {
    header('Location: /cineva/subscription.php?required=1');
    exit;
}

$pageTitle = 'Cari Film';
$q = trim($_GET['q'] ?? '');
include __DIR__ . '/includes/header.php';

$movies = null;
if ($q) {
    $searchTerm = '%' . $q . '%';
    $stmt = $conn->prepare("SELECT m.*, g.name as genre_name FROM movies m JOIN genres g ON m.genre_id = g.id WHERE m.title LIKE ? OR m.description LIKE ? ORDER BY m.rating DESC");
    $stmt->bind_param("ss", $searchTerm, $searchTerm);
    $stmt->execute();
    $movies = $stmt->get_result();
}
?>

<div class="search-page">
    <div class="container">
        <div class="search-hero">
            <h1>Pencarian Film</h1>
            <form class="search-form" method="GET" action="/cineva/search.php">
                <span class="search-form-icon"><i class="fa-solid fa-search"></i></span>
                <input type="text" name="q" placeholder="Cari berdasarkan judul atau deskripsi film..." value="<?= htmlspecialchars($q) ?>" autofocus>
            </form>
        </div>

        <?php if ($q): ?>
            <?php if ($movies && $movies->num_rows > 0): ?>
            <p class="search-results-info">Ditemukan <strong><?= $movies->num_rows ?></strong> film untuk kata kunci "<?= htmlspecialchars($q) ?>"</p>
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
                <div class="empty-state-icon"><i class="fa-solid fa-search"></i></div>
                <h3>Film tidak ditemukan</h3>
                <p>Coba gunakan kata kunci atau judul film yang lain</p>
            </div>
            <?php endif; ?>
        <?php else: ?>
        <div class="empty-state">
            <div class="empty-state-icon"><i class="fa-solid fa-film"></i></div>
            <h3>Jelajahi Ribuan Film</h3>
            <p>Ketikkan judul atau kata kunci untuk mulai mencari tayangan favorit</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
