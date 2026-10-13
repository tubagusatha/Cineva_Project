<?php
$adminTitle = 'Formulir Genre';
include __DIR__ . '/includes/admin-header.php';
include __DIR__ . '/includes/sidebar.php';

$id = (int)($_GET['id'] ?? 0);
$genre = null;
$error = '';

if ($id) {
    $stmt = $conn->prepare("SELECT * FROM genres WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $genre = $stmt->get_result()->fetch_assoc();
    if (!$genre) { header('Location: /cineva/admin/genres.php'); exit; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($name)) {
        $error = 'Nama genre wajib diisi.';
    } else {
        if ($id) {
            $stmt = $conn->prepare("UPDATE genres SET name=?, description=? WHERE id=?");
            $stmt->bind_param("ssi", $name, $description, $id);
        } else {
            $stmt = $conn->prepare("INSERT INTO genres (name, description) VALUES (?, ?)");
            $stmt->bind_param("ss", $name, $description);
        }

        if ($stmt->execute()) {
            header('Location: /cineva/admin/genres.php?msg=saved');
            exit;
        }
        $error = 'Gagal menyimpan genre.';
    }
}
?>

<main class="admin-main">
    <div class="admin-topbar">
        <h1><?= $id ? 'Edit Genre' : 'Tambah Genre' ?></h1>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-error" style="max-width:700px; margin-bottom:20px;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="admin-card" style="max-width:700px;">
        <div style="padding:28px;">
            <form method="POST" class="admin-form">
                <div class="form-group">
                    <label for="name">Nama Genre *</label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($genre['name'] ?? $_POST['name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="description">Deskripsi</label>
                    <textarea id="description" name="description"><?= htmlspecialchars($genre['description'] ?? $_POST['description'] ?? '') ?></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-submit">Simpan Genre</button>
                    <a href="/cineva/admin/genres.php" class="btn-cancel">Batal</a>
                </div>
            </form>
        </div>
    </div>
</main>

</div>
<script src="/cineva/assets/js/admin.js"></script>
</body>
</html>
