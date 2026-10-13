<?php
$adminTitle = 'Formulir Aktor';
include __DIR__ . '/includes/admin-header.php';
include __DIR__ . '/includes/sidebar.php';

$id = (int)($_GET['id'] ?? 0);
$actor = null;
$error = '';

if ($id) {
    $stmt = $conn->prepare("SELECT * FROM actors WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $actor = $stmt->get_result()->fetch_assoc();
    if (!$actor) { header('Location: /cineva/admin/actors.php'); exit; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $birthdate = $_POST['birthdate'] ?? null;

    if (empty($name)) {
        $error = 'Nama aktor wajib diisi.';
    } else {
        $urlPhoto = $actor ? $actor['url_photo'] : '';

        // Handle photo upload
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../uploads/actors/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];

            if (in_array($ext, $allowed)) {
                $fileName = 'actor_' . time() . '_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $fileName)) {
                    if ($urlPhoto && file_exists(__DIR__ . '/../' . $urlPhoto)) {
                        unlink(__DIR__ . '/../' . $urlPhoto);
                    }
                    $urlPhoto = 'uploads/actors/' . $fileName;
                }
            } else {
                $error = 'Hanya file JPG, PNG, dan WEBP yang diperbolehkan.';
            }
        }

        if (!$error) {
            $birthdateVal = $birthdate ?: null;

            if ($id) {
                $stmt = $conn->prepare("UPDATE actors SET name=?, bio=?, url_photo=?, birthdate=? WHERE id=?");
                $stmt->bind_param("ssssi", $name, $bio, $urlPhoto, $birthdateVal, $id);
            } else {
                $stmt = $conn->prepare("INSERT INTO actors (name, bio, url_photo, birthdate) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $name, $bio, $urlPhoto, $birthdateVal);
            }

            if ($stmt->execute()) {
                header('Location: /cineva/admin/actors.php?msg=saved');
                exit;
            }
            $error = 'Gagal menyimpan aktor.';
        }
    }
}
?>

<main class="admin-main">
    <div class="admin-topbar">
        <h1><?= $id ? 'Edit Aktor' : 'Tambah Aktor' ?></h1>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-error" style="max-width:700px; margin-bottom:20px;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="admin-card" style="max-width:700px;">
        <div style="padding:28px;">
            <form method="POST" enctype="multipart/form-data" class="admin-form">
                <div class="form-group">
                    <label for="name">Nama Aktor *</label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($actor['name'] ?? $_POST['name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="birthdate">Tanggal Lahir</label>
                    <input type="date" id="birthdate" name="birthdate" value="<?= $actor['birthdate'] ?? $_POST['birthdate'] ?? '' ?>">
                </div>
                <div class="form-group">
                    <label for="bio">Biografi</label>
                    <textarea id="bio" name="bio"><?= htmlspecialchars($actor['bio'] ?? $_POST['bio'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label>Foto Aktor</label>
                    <div class="file-input-wrapper">
                        <div class="file-input-label">
                            <span><i class="fa-solid fa-camera"></i></span>
                            <span class="file-input-text">Pilih foto (JPG, PNG, WEBP)</span>
                        </div>
                        <input type="file" name="photo" accept=".jpg,.jpeg,.png,.webp">
                    </div>
                    <?php if ($actor && $actor['url_photo']): ?>
                    <div class="current-image">
                        <img src="/cineva/<?= htmlspecialchars($actor['url_photo']) ?>" alt="Foto saat ini">
                    </div>
                    <?php endif; ?>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-submit">Simpan Aktor</button>
                    <a href="/cineva/admin/actors.php" class="btn-cancel">Batal</a>
                </div>
            </form>
        </div>
    </div>
</main>

</div>
<script src="/cineva/assets/js/admin.js"></script>
</body>
</html>
