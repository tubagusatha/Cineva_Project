<?php if (!isset($pageTitle)) $pageTitle = 'Cineva'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= isset($pageDesc) ? htmlspecialchars($pageDesc) : 'Cineva - Platform Streaming Film Premium Anda' ?>">
    <title><?= htmlspecialchars($pageTitle) ?> — Cineva</title>
    <link rel="stylesheet" href="/cineva/assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php if (isset($extraCss)): ?>
    <link rel="stylesheet" href="/cineva/assets/css/<?= $extraCss ?>">
    <?php endif; ?>
</head>
<body>
<?php include __DIR__ . '/navbar.php'; ?>
