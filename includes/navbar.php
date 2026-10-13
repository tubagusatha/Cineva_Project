<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/database.php';

$currentUser = isLoggedIn() ? getCurrentUser($conn) : null;
$genresNav = $conn->query("SELECT id, name FROM genres ORDER BY name");
$navGenres = [];
while ($g = $genresNav->fetch_assoc()) $navGenres[] = $g;

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>

<nav class="navbar" id="navbar">
    <div class="container">
        <a href="/cineva/" class="nav-logo">Cine<span>va</span></a>

        <ul class="nav-links">
            <li><a href="/cineva/" class="<?= $currentPage === 'index' ? 'active' : '' ?>">Beranda</a></li>
            <li><a href="/cineva/genre.php" class="<?= $currentPage === 'genre' ? 'active' : '' ?>">Genre</a></li>
            <li><a href="/cineva/search.php" class="<?= $currentPage === 'search' ? 'active' : '' ?>">Cari Film</a></li>
            <?php if (isLoggedIn()): ?>
            <li><a href="/cineva/history.php" class="<?= $currentPage === 'history' ? 'active' : '' ?>">Riwayat</a></li>
            <li><a href="/cineva/subscription.php" class="<?= $currentPage === 'subscription' ? 'active' : '' ?>">Langganan</a></li>
            <?php endif; ?>
        </ul>

        <div class="nav-right">
            <a href="/cineva/search.php" class="nav-search-btn" title="Cari Film"><i class="fa-solid fa-search"></i></a>

            <?php if ($currentUser): ?>
            <div class="nav-user" id="nav-user-menu">
                <button type="button" class="nav-user-btn" id="nav-user-btn" aria-haspopup="true" aria-expanded="false">
                    <span class="nav-user-avatar"><?= strtoupper(substr($currentUser['name'], 0, 1)) ?></span>
                    <span class="nav-user-name"><?= htmlspecialchars($currentUser['name']) ?></span>
                    <i class="fa-solid fa-chevron-down nav-user-arrow"></i>
                </button>
                <div class="nav-dropdown" id="nav-user-dropdown" role="menu">
                    <div class="nav-dropdown-header">
                        <div class="nav-dropdown-name"><?= htmlspecialchars($currentUser['name']) ?></div>
                        <div class="nav-dropdown-email"><?= htmlspecialchars($currentUser['email']) ?></div>
                        <div class="nav-dropdown-plan">
                            <span class="plan-pill <?= htmlspecialchars($currentUser['subscription'] ?? 'free') ?>">
                                <i class="fa-solid fa-shield-halved"></i>
                                <?= isAdmin() ? 'Administrator' : 'Paket ' . ucfirst($currentUser['subscription'] ?? 'Free') ?>
                            </span>
                        </div>
                    </div>
                    <div class="dropdown-divider"></div>
                    <?php if (isAdmin()): ?>
                    <a href="/cineva/admin/"><i class="fa-solid fa-chart-line"></i> Panel Admin</a>
                    <div class="dropdown-divider"></div>
                    <?php endif; ?>
                    <a href="/cineva/profile.php"><i class="fa-solid fa-user"></i> Profil Akun</a>
                    <a href="/cineva/subscription.php"><i class="fa-solid fa-credit-card"></i> Paket Langganan</a>
                    <a href="/cineva/history.php"><i class="fa-solid fa-clock-rotate-left"></i> Riwayat Tontonan</a>
                    <div class="dropdown-divider"></div>
                    <a href="/cineva/auth/logout.php" class="dropdown-logout"><i class="fa-solid fa-arrow-right-from-bracket"></i> Keluar</a>
                </div>
            </div>
            <?php else: ?>
            <div class="nav-auth-links">
                <a href="/cineva/auth/login.php" class="btn-login">Masuk</a>
                <a href="/cineva/auth/register.php" class="btn-register">Daftar</a>
            </div>
            <?php endif; ?>

            <button class="hamburger" id="hamburger-btn" aria-label="Buka Menu Navigasi" aria-expanded="false" type="button">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>
</nav>

<!-- Mobile Navigation Backdrop -->
<div class="mobile-nav-backdrop" id="mobile-nav-backdrop"></div>

<!-- Mobile Navigation Drawer -->
<aside class="mobile-nav" id="mobile-nav" aria-label="Menu Mobile">
    <div class="mobile-nav-header">
        <a href="/cineva/" class="mobile-nav-logo">Cine<span>va</span></a>
        <button class="mobile-nav-close" id="mobile-nav-close" aria-label="Tutup Menu" type="button">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <?php if ($currentUser): ?>
    <div class="mobile-user-card">
        <div class="mobile-user-avatar"><?= strtoupper(substr($currentUser['name'], 0, 1)) ?></div>
        <div class="mobile-user-details">
            <div class="mobile-user-name"><?= htmlspecialchars($currentUser['name']) ?></div>
            <div class="mobile-user-email"><?= htmlspecialchars($currentUser['email']) ?></div>
            <span class="plan-pill <?= htmlspecialchars($currentUser['subscription'] ?? 'free') ?>">
                <i class="fa-solid <?= isAdmin() ? 'fa-shield-halved' : 'fa-crown' ?>"></i>
                <?= isAdmin() ? 'Administrator' : 'Paket ' . ucfirst($currentUser['subscription'] ?? 'Free') ?>
            </span>
        </div>
    </div>
    <?php endif; ?>

    <nav class="mobile-nav-links">
        <div class="mobile-nav-section-title">Menu Utama</div>
        <a href="/cineva/" class="mobile-nav-item <?= $currentPage === 'index' ? 'active' : '' ?>">
            <i class="fa-solid fa-house"></i>
            <span>Beranda</span>
        </a>
        <a href="/cineva/genre.php" class="mobile-nav-item <?= $currentPage === 'genre' ? 'active' : '' ?>">
            <i class="fa-solid fa-layer-group"></i>
            <span>Genre Film</span>
        </a>
        <a href="/cineva/search.php" class="mobile-nav-item <?= $currentPage === 'search' ? 'active' : '' ?>">
            <i class="fa-solid fa-magnifying-glass"></i>
            <span>Cari Film</span>
        </a>

        <?php if ($currentUser): ?>
        <div class="mobile-nav-section-title">Akun & Langganan</div>
        <a href="/cineva/subscription.php" class="mobile-nav-item <?= $currentPage === 'subscription' ? 'active' : '' ?>">
            <i class="fa-solid fa-crown text-gold"></i>
            <span>Paket Langganan</span>
        </a>
        <a href="/cineva/history.php" class="mobile-nav-item <?= $currentPage === 'history' ? 'active' : '' ?>">
            <i class="fa-solid fa-clock-rotate-left"></i>
            <span>Riwayat Tontonan</span>
        </a>
        <a href="/cineva/profile.php" class="mobile-nav-item <?= $currentPage === 'profile' ? 'active' : '' ?>">
            <i class="fa-solid fa-user-gear"></i>
            <span>Profil Akun</span>
        </a>
        <?php if (isAdmin()): ?>
        <a href="/cineva/admin/" class="mobile-nav-item">
            <i class="fa-solid fa-shield-halved text-accent"></i>
            <span>Panel Admin</span>
        </a>
        <?php endif; ?>

        <div class="mobile-nav-divider"></div>
        <a href="/cineva/auth/logout.php" class="mobile-nav-item text-danger">
            <i class="fa-solid fa-arrow-right-from-bracket"></i>
            <span>Keluar Akun</span>
        </a>
        <?php else: ?>
        <div class="mobile-nav-divider"></div>
        <div class="mobile-guest-actions">
            <a href="/cineva/auth/login.php" class="mobile-btn-login">
                <i class="fa-solid fa-arrow-right-to-bracket"></i> Masuk Akun
            </a>
            <a href="/cineva/auth/register.php" class="mobile-btn-register">
                <i class="fa-solid fa-user-plus"></i> Daftar Sekarang
            </a>
        </div>
        <?php endif; ?>
    </nav>
</aside>
