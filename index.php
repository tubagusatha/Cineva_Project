<?php
$pageTitle = 'Beranda';
$pageDesc = 'Cineva - Platform streaming film dan serial bioskop terbaik';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

// If logged in as regular user without active subscription → force to subscription
if (isLoggedIn() && !isAdmin()) {
    if (!hasActiveSubscription($conn)) {
        header('Location: /cineva/subscription.php?required=1');
        exit;
    }
}


// Featured movies
$featuredMovies = $conn->query("
    SELECT m.*, g.name as genre_name 
    FROM movies m 
    JOIN genres g ON m.genre_id = g.id 
    WHERE m.is_featured = 1 
    ORDER BY m.created_at DESC 
    LIMIT 5
");

// Trending (most watched)
$trending = $conn->query("
    SELECT m.*, g.name as genre_name, COUNT(wh.id) as watch_count
    FROM movies m
    JOIN genres g ON m.genre_id = g.id
    LEFT JOIN watch_history wh ON m.id = wh.movie_id
    GROUP BY m.id
    ORDER BY watch_count DESC, m.rating DESC
    LIMIT 15
");

// New Releases
$newReleases = $conn->query("SELECT m.*, g.name as genre_name FROM movies m JOIN genres g ON m.genre_id = g.id ORDER BY m.created_at DESC LIMIT 15");

// Top Rated
$topRated = $conn->query("SELECT m.*, g.name as genre_name FROM movies m JOIN genres g ON m.genre_id = g.id ORDER BY m.rating DESC LIMIT 15");

// Continue watching (if logged in)
$continueWatching = null;
if (isLoggedIn()) {
    $uid = $_SESSION['user_id'];
    $continueWatching = $conn->query("
        SELECT m.*, g.name as genre_name, wh.watched_at
        FROM watch_history wh
        JOIN movies m ON wh.movie_id = m.id
        JOIN genres g ON m.genre_id = g.id
        WHERE wh.user_id = $uid
        ORDER BY wh.watched_at DESC
        LIMIT 10
    ");
}
?>

<?php include __DIR__ . '/includes/header.php'; ?>

<?php if (isset($_GET['welcome_sub']) || isset($_GET['subscribed'])): ?>
<div class="toast-popup-wrap" id="cinevaWelcomeToast">
    <div class="toast-popup-card">
        <div class="toast-icon-circle">
            <i class="fa-solid fa-crown"></i>
        </div>
        <div class="toast-popup-body">
            <div class="toast-popup-title">Selamat Datang di Cineva! 🍿</div>
            <div class="toast-popup-sub">Paket langganan Anda aktif. Nikmati ribuan film & series tanpa batas.</div>
        </div>
        <button type="button" class="toast-close-btn" onclick="dismissCinevaToast()" aria-label="Tutup">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
</div>
<script>
function dismissCinevaToast() {
    const t = document.getElementById('cinevaWelcomeToast');
    if (t) {
        t.style.opacity = '0';
        t.style.transform = 'translateY(20px)';
        setTimeout(() => t.remove(), 300);
    }
}
setTimeout(dismissCinevaToast, 6000);
</script>
<?php endif; ?>

<?php if ($featuredMovies && $featuredMovies->num_rows > 0): ?>
<section class="hero hero-slider">
    <?php 
    $slideIndex = 0;
    while ($featured = $featuredMovies->fetch_assoc()): 
        $activeClass = $slideIndex === 0 ? 'active' : '';
    ?>
    <div class="hero-slide <?= $activeClass ?>">
        <div class="hero-bg" style="background-image: url('<?= asset($featured['poster_url']) ?>')"></div>
        <div class="container" style="width: 100%;">
            <div class="hero-content">
                <span class="hero-badge"><i class="fa-solid fa-star"></i> Pilihan Utama</span>
                <h1 class="hero-title"><?= htmlspecialchars($featured['title']) ?></h1>
                <div class="hero-meta">
                    <span class="hero-meta-item">
                        <span class="star"><i class="fa-solid fa-star"></i></span> <?= number_format($featured['rating'], 1) ?>
                    </span>
                    <span class="hero-meta-item"><?= $featured['year'] ?></span>
                    <span class="hero-meta-item"><?= $featured['duration'] ?> menit</span>
                    <span class="hero-meta-item"><?= htmlspecialchars($featured['genre_name']) ?></span>
                </div>
                <p class="hero-desc"><?= htmlspecialchars($featured['description']) ?></p>
                <div class="hero-actions">
                    <a href="/cineva/watch.php?id=<?= $featured['id'] ?>" class="btn-primary"><i class="fa-solid fa-play"></i> Tonton Trailer</a>
                    <a href="/cineva/movie.php?id=<?= $featured['id'] ?>" class="btn-secondary"><i class="fa-solid fa-circle-info"></i> Detail Film</a>
                </div>
            </div>
        </div>
    </div>
    <?php 
        $slideIndex++;
    endwhile; 
    ?>

    <?php if ($featuredMovies->num_rows > 1): ?>
    <div class="slider-nav">
        <button class="slider-arrow prev" aria-label="Sebelumnya"><i class="fa-solid fa-chevron-left"></i></button>
        <div class="slider-dots">
            <?php for ($i = 0; $i < $featuredMovies->num_rows; $i++): ?>
                <button class="slider-dot <?= $i === 0 ? 'active' : '' ?>" data-index="<?= $i ?>"></button>
            <?php endfor; ?>
        </div>
        <button class="slider-arrow next" aria-label="Berikutnya"><i class="fa-solid fa-chevron-right"></i></button>
    </div>
    <?php endif; ?>
</section>
<?php endif; ?>

<?php if ($continueWatching && $continueWatching->num_rows > 0): ?>
<section class="section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Baru Saja Ditonton</h2>
            <a href="/cineva/history.php" class="section-link">Lihat Semua &rarr;</a>
        </div>
        <div class="movie-row">
            <?php while ($movie = $continueWatching->fetch_assoc()): ?>
            <a href="/cineva/watch.php?id=<?= $movie['id'] ?>" class="movie-card">
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
    </div>
</section>
<?php endif; ?>

<section class="section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Sedang Tren</h2>
        </div>
        <div class="movie-row">
            <?php while ($movie = $trending->fetch_assoc()): ?>
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
                        <span><?= $movie['duration'] ?> mnt</span>
                    </div>
                </div>
            </a>
            <?php endwhile; ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Rilis Terbaru</h2>
        </div>
        <div class="movie-row">
            <?php while ($movie = $newReleases->fetch_assoc()): ?>
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
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Rating Tertinggi</h2>
        </div>
        <div class="movie-row">
            <?php while ($movie = $topRated->fetch_assoc()): ?>
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
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
