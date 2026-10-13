<?php
$adminTitle = 'Formulir Film';
include __DIR__ . '/includes/admin-header.php';
include __DIR__ . '/includes/sidebar.php';

$id = (int)($_GET['id'] ?? 0);
$movie = null;
$error = '';

if ($id) {
    $stmt = $conn->prepare("SELECT * FROM movies WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $movie = $stmt->get_result()->fetch_assoc();
    if (!$movie) { header('Location: /cineva/admin/movies.php'); exit; }
}

$genres = $conn->query("SELECT * FROM genres ORDER BY name");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $genreId = (int)($_POST['genre_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $year = (int)($_POST['year'] ?? 0);
    $rating = (float)($_POST['rating'] ?? 0);
    $duration = (int)($_POST['duration'] ?? 0);
    $trailerUrl = trim($_POST['trailer_url'] ?? '');
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;

    if (empty($title) || !$genreId) {
        $error = 'Judul dan genre wajib diisi.';
    } else {
        $posterUrl = $movie ? $movie['poster_url'] : '';

        // Handle poster upload
        if (isset($_FILES['poster']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../uploads/posters/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            $ext = strtolower(pathinfo($_FILES['poster']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];

            if (in_array($ext, $allowed)) {
                $fileName = 'poster_' . time() . '_' . uniqid() . '.' . $ext;
                $filePath = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES['poster']['tmp_name'], $filePath)) {
                    // Delete old poster
                    if ($posterUrl && file_exists(__DIR__ . '/../' . $posterUrl)) {
                        unlink(__DIR__ . '/../' . $posterUrl);
                    }
                    $posterUrl = 'uploads/posters/' . $fileName;
                }
            } else {
                $error = 'Hanya file JPG, PNG, dan WEBP yang diperbolehkan.';
            }
        }

        if (!$error) {
            if ($id) {
                $stmt = $conn->prepare("UPDATE movies SET genre_id=?, title=?, description=?, poster_url=?, year=?, rating=?, duration=?, trailer_url=?, is_featured=? WHERE id=?");
                $stmt->bind_param("isssiidisi", $genreId, $title, $description, $posterUrl, $year, $rating, $duration, $trailerUrl, $isFeatured, $id);
            } else {
                $stmt = $conn->prepare("INSERT INTO movies (genre_id, title, description, poster_url, year, rating, duration, trailer_url, is_featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssidisi", $genreId, $title, $description, $posterUrl, $year, $rating, $duration, $trailerUrl, $isFeatured);
            }

            if ($stmt->execute()) {
                header('Location: /cineva/admin/movies.php?msg=saved');
                exit;
            } else {
                $error = 'Gagal menyimpan film.';
            }
        }
    }
}
?>

<main class="admin-main">
    <div class="admin-topbar">
        <h1><?= $id ? 'Edit Film' : 'Tambah Film' ?></h1>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-error" style="max-width:700px; margin-bottom:20px;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="admin-card" style="max-width:700px;">
        <div style="padding:28px;">
            <form method="POST" enctype="multipart/form-data" class="admin-form">
                <div class="form-group">
                    <label for="title">Judul Film *</label>
                    <input type="text" id="title" name="title" value="<?= htmlspecialchars($movie['title'] ?? $_POST['title'] ?? '') ?>" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="genre_id">Genre *</label>
                        <select id="genre_id" name="genre_id" required>
                            <option value="">Pilih Genre</option>
                            <?php while ($g = $genres->fetch_assoc()): ?>
                            <option value="<?= $g['id'] ?>" <?= ($movie['genre_id'] ?? $_POST['genre_id'] ?? '') == $g['id'] ? 'selected' : '' ?>><?= htmlspecialchars($g['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="year">Tahun Rilis</label>
                        <input type="number" id="year" name="year" value="<?= $movie['year'] ?? $_POST['year'] ?? date('Y') ?>" min="1900" max="2100">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="rating">Rating (0-10)</label>
                        <input type="number" id="rating" name="rating" value="<?= $movie['rating'] ?? $_POST['rating'] ?? '0' ?>" min="0" max="10" step="0.1">
                    </div>
                    <div class="form-group">
                        <label for="duration">Durasi (menit)</label>
                        <input type="number" id="duration" name="duration" value="<?= $movie['duration'] ?? $_POST['duration'] ?? '0' ?>" min="0">
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Deskripsi / Sinopsis</label>
                    <textarea id="description" name="description"><?= htmlspecialchars($movie['description'] ?? $_POST['description'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label for="trailer_url">URL Trailer (YouTube)</label>
                    <input type="url" id="trailer_url" name="trailer_url" value="<?= htmlspecialchars($movie['trailer_url'] ?? $_POST['trailer_url'] ?? '') ?>" placeholder="https://www.youtube.com/watch?v=...">
                </div>

                <div class="form-group">
                    <label>Gambar Poster</label>
                    <div class="file-input-wrapper">
                        <div class="file-input-label">
                            <span><i class="fa-solid fa-camera"></i></span>
                            <span class="file-input-text">Pilih gambar poster (JPG, PNG, WEBP)</span>
                        </div>
                        <input type="file" name="poster" accept=".jpg,.jpeg,.png,.webp">
                    </div>
                    <?php if ($movie && $movie['poster_url']): ?>
                    <div class="current-image">
                        <img src="/cineva/<?= htmlspecialchars($movie['poster_url']) ?>" alt="Poster saat ini">
                    </div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                        <input type="checkbox" name="is_featured" value="1" <?= ($movie['is_featured'] ?? 0) ? 'checked' : '' ?> style="accent-color:var(--accent);">
                        Film Unggulan (ditampilkan di carousel beranda)
                    </label>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-submit">Simpan Film</button>
                    <a href="/cineva/admin/movies.php" class="btn-cancel">Batal</a>
                </div>
            </form>
        </div>
    </div>
</main>

</div>
<script src="/cineva/assets/js/admin.js"></script>
</body>
</html>
