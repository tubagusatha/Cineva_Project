<button class="sidebar-toggle"><i class="fa-solid fa-bars"></i></button>

<aside class="admin-sidebar">
    <div class="sidebar-header">
        <a href="/cineva/admin/" class="sidebar-logo">Cine<span>va</span></a>
        <div class="sidebar-label">Panel Admin</div>
    </div>
    <nav class="sidebar-nav">
        <div class="sidebar-nav-group">
            <div class="sidebar-nav-label">Menu Utama</div>
            <a href="/cineva/admin/" class="sidebar-link <?= $adminPage === 'index' ? 'active' : '' ?>">
                <span class="icon"><i class="fa-solid fa-chart-simple"></i></span> Dasbor
            </a>
        </div>
        <div class="sidebar-nav-group">
            <div class="sidebar-nav-label">Konten</div>
            <a href="/cineva/admin/movies.php" class="sidebar-link <?= $adminPage === 'movies' || $adminPage === 'movie-form' ? 'active' : '' ?>">
                <span class="icon"><i class="fa-solid fa-film"></i></span> Film
            </a>
            <a href="/cineva/admin/genres.php" class="sidebar-link <?= $adminPage === 'genres' || $adminPage === 'genre-form' ? 'active' : '' ?>">
                <span class="icon"><i class="fa-solid fa-tag"></i></span> Genre
            </a>
            <a href="/cineva/admin/actors.php" class="sidebar-link <?= $adminPage === 'actors' || $adminPage === 'actor-form' ? 'active' : '' ?>">
                <span class="icon"><i class="fa-solid fa-masks-theater"></i></span> Aktor
            </a>
            <a href="/cineva/admin/movie-actors.php" class="sidebar-link <?= $adminPage === 'movie-actors' || $adminPage === 'movie-actor-form' ? 'active' : '' ?>">
                <span class="icon"><i class="fa-solid fa-users"></i></span> Pemeran Film
            </a>
        </div>
        <div class="sidebar-nav-group">
            <div class="sidebar-nav-label">Pengguna</div>
            <a href="/cineva/admin/users.php" class="sidebar-link <?= $adminPage === 'users' || $adminPage === 'user-form' ? 'active' : '' ?>">
                <span class="icon"><i class="fa-solid fa-user"></i></span> Pengguna
            </a>
            <a href="/cineva/admin/watch-history.php" class="sidebar-link <?= $adminPage === 'watch-history' ? 'active' : '' ?>">
                <span class="icon"><i class="fa-solid fa-tv"></i></span> Riwayat Tontonan
            </a>
        </div>
        <div class="sidebar-nav-group">
            <div class="sidebar-nav-label">Keuangan</div>
            <a href="/cineva/admin/transactions.php" class="sidebar-link <?= $adminPage === 'transactions' ? 'active' : '' ?>">
                <span class="icon"><i class="fa-solid fa-receipt"></i></span> Transaksi Midtrans
            </a>
        </div>
    </nav>
    <div class="sidebar-footer">
        <a href="/cineva/"><i class="fa-solid fa-arrow-left"></i> Kembali ke Web</a>
    </div>
</aside>
