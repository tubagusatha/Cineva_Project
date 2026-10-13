<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

// Not logged in -> redirect to login
if (!isLoggedIn()) {
    header('Location: /cineva/auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Logged in as regular user without active subscription -> redirect to subscription
if (!isAdmin() && !hasActiveSubscription($conn)) {
    header('Location: /cineva/subscription.php?required=1');
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: /cineva/'); exit; }

$stmt = $conn->prepare("SELECT m.*, g.name as genre_name FROM movies m JOIN genres g ON m.genre_id = g.id WHERE m.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$movie = $stmt->get_result()->fetch_assoc();

if (!$movie) { header('Location: /cineva/'); exit; }

// Get cast
$castStmt = $conn->prepare("
    SELECT a.*, ma.`character`
    FROM movie_actors ma
    JOIN actors a ON ma.actor_id = a.id
    WHERE ma.movie_id = ?
");
$castStmt->bind_param("i", $id);
$castStmt->execute();
$cast = $castStmt->get_result();

$pageTitle = $movie['title'];
$pageDesc = substr($movie['description'], 0, 160);
include __DIR__ . '/includes/header.php';
?>

<div class="movie-detail">
    <section class="movie-detail-hero">
        <div class="movie-detail-bg" style="background-image: url('<?= asset($movie['poster_url']) ?>')"></div>
        <div class="container">
            <div class="movie-detail-content">
                <div class="movie-detail-poster">
                    <?php if ($movie['poster_url']): ?>
                    <img src="<?= asset($movie['poster_url']) ?>" alt="<?= htmlspecialchars($movie['title']) ?>">
                    <?php else: ?>
                    <div class="movie-detail-poster-placeholder"><span><?= htmlspecialchars($movie['title']) ?></span></div>
                    <?php endif; ?>
                </div>
                <div class="movie-detail-info">
                    <span class="movie-detail-genre"><?= htmlspecialchars($movie['genre_name']) ?></span>
                    <h1 class="movie-detail-title"><?= htmlspecialchars($movie['title']) ?></h1>
                    <div class="movie-detail-meta">
                        <span class="rating"><i class="fa-solid fa-star"></i> <?= number_format($movie['rating'], 1) ?></span>
                        <span><i class="fa-solid fa-calendar-days"></i> <?= $movie['year'] ?></span>
                        <span><i class="fa-solid fa-clock"></i> <?= $movie['duration'] ?> menit</span>
                    </div>
                    <p class="movie-detail-synopsis"><?= nl2br(htmlspecialchars($movie['description'])) ?></p>
                    <div class="movie-detail-actions">
                        <?php if ($movie['trailer_url']): ?>
                        <a href="/cineva/watch.php?id=<?= $movie['id'] ?>" class="btn-primary"><i class="fa-solid fa-play"></i> Tonton Trailer</a>
                        <?php endif; ?>
                        <a href="/cineva/genre.php?id=<?= $movie['genre_id'] ?>" class="btn-secondary">Film <?= htmlspecialchars($movie['genre_name']) ?></a>
                    </div>

                    <?php if ($cast->num_rows > 0): ?>
                    <div class="movie-detail-cast">
                        <h3>Daftar Pemeran</h3>
                        <div class="cast-list">
                            <?php while ($actor = $cast->fetch_assoc()): ?>
                            <div class="cast-card">
                                <?php if ($actor['url_photo']): ?>
                                <img src="<?= asset($actor['url_photo']) ?>" alt="<?= htmlspecialchars($actor['name']) ?>" class="cast-photo">
                                <?php else: ?>
                                <div class="cast-photo-placeholder"><?= strtoupper(substr($actor['name'], 0, 1)) ?></div>
                                <?php endif; ?>
                                <div class="cast-name"><?= htmlspecialchars($actor['name']) ?></div>
                                <div class="cast-character"><?= htmlspecialchars($actor['character']) ?></div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
