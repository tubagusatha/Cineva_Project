<?php
$adminTitle = 'Formulir Pemeran';
include __DIR__ . '/includes/admin-header.php';
include __DIR__ . '/includes/sidebar.php';

$id = (int)($_GET['id'] ?? 0);
$entry = null;
$error = '';

if ($id) {
    $stmt = $conn->prepare("SELECT * FROM movie_actors WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $entry = $stmt->get_result()->fetch_assoc();
    if (!$entry) { header('Location: /cineva/admin/movie-actors.php'); exit; }
}

$movies = $conn->query("SELECT id, title FROM movies ORDER BY title");
$actors = $conn->query("SELECT id, name FROM actors ORDER BY name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $movieId = (int)($_POST['movie_id'] ?? 0);
    $actorId = (int)($_POST['actor_id'] ?? 0);
    $character = trim($_POST['character'] ?? '');

    if (!$movieId || !$actorId) {
        $error = 'Film dan aktor wajib dipilih.';
    } else {
        if ($id) {
            $stmt = $conn->prepare("UPDATE movie_actors SET movie_id=?, actor_id=?, `character`=? WHERE id=?");
            $stmt->bind_param("iisi", $movieId, $actorId, $character, $id);
        } else {
            $stmt = $conn->prepare("INSERT INTO movie_actors (movie_id, actor_id, `character`) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $movieId, $actorId, $character);
        }

        if ($stmt->execute()) {
            header('Location: /cineva/admin/movie-actors.php?msg=saved');
            exit;
        }
        $error = 'Gagal menyimpan data pemeran.';
    }
}
?>

<main class="admin-main">
    <div class="admin-topbar">
        <h1><?= $id ? 'Edit Pemeran' : 'Tambah Pemeran' ?></h1>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-error" style="max-width:700px; margin-bottom:20px;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="admin-card" style="max-width:700px;">
        <div style="padding:28px;">
            <form method="POST" class="admin-form">
                <div class="form-group">
                    <label for="movie_id">Film *</label>
                    <select id="movie_id" name="movie_id" required>
                        <option value="">Pilih Film</option>
                        <?php while ($m = $movies->fetch_assoc()): ?>
                        <option value="<?= $m['id'] ?>" <?= ($entry['movie_id'] ?? $_POST['movie_id'] ?? '') == $m['id'] ? 'selected' : '' ?>><?= htmlspecialchars($m['title']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="actor_id">Aktor *</label>
                    <select id="actor_id" name="actor_id" required>
                        <option value="">Pilih Aktor</option>
                        <?php while ($a = $actors->fetch_assoc()): ?>
                        <option value="<?= $a['id'] ?>" <?= ($entry['actor_id'] ?? $_POST['actor_id'] ?? '') == $a['id'] ? 'selected' : '' ?>><?= htmlspecialchars($a['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="character">Nama Karakter / Peran</label>
                    <input type="text" id="character" name="character" value="<?= htmlspecialchars($entry['character'] ?? $_POST['character'] ?? '') ?>" placeholder="cth. Bruce Wayne / Batman">
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-submit">Simpan Pemeran</button>
                    <a href="/cineva/admin/movie-actors.php" class="btn-cancel">Batal</a>
                </div>
            </form>
        </div>
    </div>
</main>

</div>
<script src="/cineva/assets/js/admin.js"></script>
</body>
</html>
