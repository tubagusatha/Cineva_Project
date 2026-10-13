<?php
$adminTitle = 'Dasbor';
include __DIR__ . '/includes/admin-header.php';
include __DIR__ . '/includes/sidebar.php';

$totalMovies = $conn->query("SELECT COUNT(*) as c FROM movies")->fetch_assoc()['c'];
$totalGenres = $conn->query("SELECT COUNT(*) as c FROM genres")->fetch_assoc()['c'];
$totalActors = $conn->query("SELECT COUNT(*) as c FROM actors")->fetch_assoc()['c'];
$totalUsers = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
$totalWatched = $conn->query("SELECT COUNT(*) as c FROM watch_history")->fetch_assoc()['c'];
$totalRevenue = $conn->query("SELECT COALESCE(SUM(amount), 0) as s FROM subscription_transactions WHERE transaction_status IN ('settlement', 'capture')")->fetch_assoc()['s'];

$recentMovies = $conn->query("SELECT m.*, g.name as genre_name FROM movies m JOIN genres g ON m.genre_id = g.id ORDER BY m.created_at DESC LIMIT 5");
$recentUsers = $conn->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");
$recentTrx = $conn->query("SELECT st.*, u.name as user_name FROM subscription_transactions st LEFT JOIN users u ON u.id = st.user_id ORDER BY st.created_at DESC LIMIT 5");
?>

<main class="admin-main">
    <div class="admin-topbar">
        <h1>Dasbor</h1>
        <div class="admin-topbar-right">
            <span style="color:var(--text-dim); font-size:0.85rem;">Selamat datang, <?= htmlspecialchars($_SESSION['user_name']) ?></span>
        </div>
    </div>

    <div class="stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
        <div class="stat-card">
            <div class="stat-card-header">
                <div>
                    <div class="stat-card-value">Rp <?= number_format($totalRevenue, 0, ',', '.') ?></div>
                    <div class="stat-card-label">Pendapatan Langganan</div>
                </div>
                <div class="stat-card-icon movies" style="background:rgba(16,185,129,0.15); color:#10b981;"><i class="fa-solid fa-money-bill-wave"></i></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card-header">
                <div>
                    <div class="stat-card-value"><?= $totalMovies ?></div>
                    <div class="stat-card-label">Total Film</div>
                </div>
                <div class="stat-card-icon movies"><i class="fa-solid fa-film"></i></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card-header">
                <div>
                    <div class="stat-card-value"><?= $totalGenres ?></div>
                    <div class="stat-card-label">Total Genre</div>
                </div>
                <div class="stat-card-icon genres"><i class="fa-solid fa-tag"></i></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card-header">
                <div>
                    <div class="stat-card-value"><?= $totalActors ?></div>
                    <div class="stat-card-label">Total Aktor</div>
                </div>
                <div class="stat-card-icon actors"><i class="fa-solid fa-masks-theater"></i></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card-header">
                <div>
                    <div class="stat-card-value"><?= $totalUsers ?></div>
                    <div class="stat-card-label">Total Pengguna</div>
                </div>
                <div class="stat-card-icon users"><i class="fa-solid fa-users"></i></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card-header">
                <div>
                    <div class="stat-card-value"><?= $totalWatched ?></div>
                    <div class="stat-card-label">Riwayat Menonton</div>
                </div>
                <div class="stat-card-icon history"><i class="fa-solid fa-tv"></i></div>
            </div>
        </div>
    </div>

    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:24px; margin-bottom:24px;">
        <div class="admin-card">
            <div class="admin-card-header">
                <h3 class="admin-card-title">Film Terbaru</h3>
                <a href="/cineva/admin/movies.php" style="font-size:0.82rem; color:var(--accent);">Lihat semua &rarr;</a>
            </div>
            <table class="data-table">
                <thead>
                    <tr><th>Judul</th><th>Genre</th><th>Rating</th></tr>
                </thead>
                <tbody>
                    <?php while ($m = $recentMovies->fetch_assoc()): ?>
                    <tr>
                        <td style="color:var(--text-primary); font-weight:500;"><?= htmlspecialchars($m['title']) ?></td>
                        <td><?= htmlspecialchars($m['genre_name']) ?></td>
                        <td><span style="color:var(--gold);"><i class="fa-solid fa-star"></i></span> <?= number_format($m['rating'], 1) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="admin-card">
            <div class="admin-card-header">
                <h3 class="admin-card-title">Pengguna Terbaru</h3>
                <a href="/cineva/admin/users.php" style="font-size:0.82rem; color:var(--accent);">Lihat semua &rarr;</a>
            </div>
            <table class="data-table">
                <thead>
                    <tr><th>Nama</th><th>Email</th><th>Peran</th></tr>
                </thead>
                <tbody>
                    <?php while ($u = $recentUsers->fetch_assoc()): ?>
                    <tr>
                        <td style="color:var(--text-primary); font-weight:500;"><?= htmlspecialchars($u['name']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><span class="table-badge <?= $u['role'] ?>"><?= $u['role'] ?></span></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Midtrans Transactions Card -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="admin-card-title"><i class="fa-solid fa-receipt text-accent" style="margin-right:6px;"></i> Transaksi Midtrans Terbaru</h3>
            <a href="/cineva/admin/transactions.php" style="font-size:0.82rem; color:var(--accent);">Lihat semua transaksi &rarr;</a>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Pengguna</th>
                    <th>Paket</th>
                    <th>Nominal</th>
                    <th>Metode</th>
                    <th>Status</th>
                    <th>Tanggal</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($recentTrx && $recentTrx->num_rows > 0): ?>
                    <?php while ($t = $recentTrx->fetch_assoc()): ?>
                    <tr>
                        <td style="font-family:monospace; color:#38bdf8; font-size:0.84rem;"><?= htmlspecialchars($t['order_id']) ?></td>
                        <td style="color:var(--text-primary); font-weight:500;"><?= htmlspecialchars($t['user_name'] ?? 'User #' . $t['user_id']) ?></td>
                        <td><span class="table-badge <?= htmlspecialchars($t['plan']) ?>"><?= ucfirst($t['plan']) ?></span></td>
                        <td style="font-weight:600; color:#fff;">Rp <?= number_format($t['amount'], 0, ',', '.') ?></td>
                        <td style="text-transform:uppercase; font-size:0.8rem; color:#9ca3af;"><?= htmlspecialchars($t['payment_type'] ?? 'midtrans') ?></td>
                        <td>
                            <span class="badge-trx-status <?= htmlspecialchars($t['transaction_status']) ?>">
                                <?= ucfirst($t['transaction_status']) ?>
                            </span>
                        </td>
                        <td style="font-size:0.82rem; color:var(--text-dim);"><?= date('d M Y H:i', strtotime($t['created_at'])) ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align:center; padding:20px; color:var(--text-dim);">Belum ada transaksi.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

</div>
<script src="/cineva/assets/js/admin.js"></script>
</body>
</html>
