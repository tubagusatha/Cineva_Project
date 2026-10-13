<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

// Not logged in -> redirect to login
if (!isLoggedIn()) {
    header('Location: /cineva/auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Regular user must have active subscription
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

$embedUrl = getYoutubeEmbedUrl($movie['trailer_url']);

// Save/update watch history if logged in
if (isLoggedIn()) {
    $userId = $_SESSION['user_id'];

    $histCheck = $conn->prepare("SELECT id FROM watch_history WHERE user_id = ? AND movie_id = ?");
    $histCheck->bind_param("ii", $userId, $id);
    $histCheck->execute();
    $existing = $histCheck->get_result()->fetch_assoc();

    if ($existing) {
        $upd = $conn->prepare("UPDATE watch_history SET watched_at = NOW() WHERE id = ?");
        $upd->bind_param("i", $existing['id']);
        $upd->execute();
    } else {
        $ins = $conn->prepare("INSERT INTO watch_history (user_id, movie_id, watched_at) VALUES (?, ?, NOW())");
        $ins->bind_param("ii", $userId, $id);
        $ins->execute();
    }
}

$pageTitle = 'Menonton: ' . $movie['title'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> — Cineva</title>
    <link rel="stylesheet" href="/cineva/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="watch-page">
    <div class="watch-header">
        <a href="/cineva/movie.php?id=<?= $movie['id'] ?>" class="watch-back"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
        <span class="watch-title"><?= htmlspecialchars($movie['title']) ?></span>
    </div>

    <div class="video-container">
        <?php if ($embedUrl): ?>
        <iframe src="<?= $embedUrl ?>?autoplay=1&rel=0" allowfullscreen allow="autoplay; encrypted-media"></iframe>
        <?php else: ?>
        <div style="display:flex;align-items:center;justify-content:center;height:100%;color:var(--text-dim);">
            <p>Trailer tidak tersedia untuk film ini.</p>
        </div>
        <?php endif; ?>
    </div>

    <div class="watch-info">
        <h2><?= htmlspecialchars($movie['title']) ?></h2>
        <div class="watch-info-meta">
            <span><i class="fa-solid fa-star"></i> <?= number_format($movie['rating'], 1) ?></span>
            <span><?= $movie['year'] ?></span>
            <span><?= $movie['duration'] ?> menit</span>
            <span><?= htmlspecialchars($movie['genre_name']) ?></span>
        </div>
        <p class="watch-info-desc"><?= nl2br(htmlspecialchars($movie['description'])) ?></p>


    </div>
</div>

<script src="/cineva/assets/js/main.js"></script>
</body>
</html>
