<?php
$adminTitle = 'Transaksi Langganan';
$adminPage = 'transactions';
include __DIR__ . '/includes/admin-header.php';
include __DIR__ . '/includes/sidebar.php';

// Handle aksi ubah status manual oleh admin (misal untuk testing / verifikasi manual)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_update_status'])) {
    $trxId = (int)$_POST['trx_id'];
    $newStatus = trim($_POST['new_status']);
    $allowed = ['pending', 'settlement', 'cancel', 'expire'];

    if (in_array($newStatus, $allowed)) {
        // Ambil data transaksi
        $trxQ = $conn->query("SELECT * FROM subscription_transactions WHERE id = $trxId");
        if ($trxQ && $rowTrx = $trxQ->fetch_assoc()) {
            $isPaid = ($newStatus === 'settlement');
            $conn->query("
                UPDATE subscription_transactions 
                SET transaction_status = '$newStatus', 
                    paid_at = " . ($isPaid ? "NOW()" : "paid_at") . " 
                WHERE id = $trxId
            ");

            // Jika diubah menjadi settlement, update langganan pengguna
            if ($isPaid) {
                $plan = $rowTrx['plan'];
                $uId = $rowTrx['user_id'];
                $oid = $rowTrx['order_id'];
                $conn->query("
                    INSERT INTO customers (user_id, name, email, password, subscription, subscription_expires_at, active_order_id, payment_method)
                    SELECT u.id, u.name, u.email, u.password, '$plan', DATE_ADD(NOW(), INTERVAL 30 DAY), '$oid', 'manual_admin'
                    FROM users u
                    WHERE u.id = $uId
                    ON DUPLICATE KEY UPDATE 
                        subscription = VALUES(subscription),
                        subscription_expires_at = VALUES(subscription_expires_at),
                        active_order_id = VALUES(active_order_id),
                        payment_method = VALUES(payment_method)
                ");
            }
        }
    }
    header('Location: /cineva/admin/transactions.php?msg=updated');
    exit;
}

// Filter status
$statusFilter = isset($_GET['status']) ? trim($_GET['status']) : '';
$whereClause = "";
if ($statusFilter && in_array($statusFilter, ['settlement', 'pending', 'cancel', 'expire'])) {
    $whereClause = "WHERE st.transaction_status = '$statusFilter'";
}

$query = "
    SELECT st.*, u.name AS user_name, u.email AS user_email 
    FROM subscription_transactions st 
    LEFT JOIN users u ON u.id = st.user_id 
    $whereClause
    ORDER BY st.created_at DESC
";
$transactions = $conn->query($query);

// Hitung total ringkasan
$totalCount = $conn->query("SELECT COUNT(*) as c FROM subscription_transactions")->fetch_assoc()['c'];
$totalSettled = $conn->query("SELECT COUNT(*) as c FROM subscription_transactions WHERE transaction_status IN ('settlement', 'capture')")->fetch_assoc()['c'];
$totalRevenue = $conn->query("SELECT COALESCE(SUM(amount), 0) as s FROM subscription_transactions WHERE transaction_status IN ('settlement', 'capture')")->fetch_assoc()['s'];
?>

<main class="admin-main">
    <div class="admin-topbar">
        <h1>Transaksi Midtrans</h1>
        <div class="admin-topbar-right">
            <span style="color:var(--text-dim); font-size:0.85rem;">Manajemen pembayaran langganan pengguna</span>
        </div>
    </div>

    <?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success" style="max-width:500px; margin-bottom:20px;">
        Status transaksi dan langganan berhasil diperbarui!
    </div>
    <?php endif; ?>

    <!-- Summary Stats Bar -->
    <div class="stat-grid" style="grid-template-columns: repeat(3, 1fr); margin-bottom:24px;">
        <div class="stat-card">
            <div class="stat-card-header">
                <div>
                    <div class="stat-card-value">Rp <?= number_format($totalRevenue, 0, ',', '.') ?></div>
                    <div class="stat-card-label">Total Pendapatan Sukses</div>
                </div>
                <div class="stat-card-icon movies"><i class="fa-solid fa-money-bill-wave"></i></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card-header">
                <div>
                    <div class="stat-card-value"><?= $totalSettled ?></div>
                    <div class="stat-card-label">Transaksi Berhasil (Settlement)</div>
                </div>
                <div class="stat-card-icon users"><i class="fa-solid fa-circle-check"></i></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card-header">
                <div>
                    <div class="stat-card-value"><?= $totalCount ?></div>
                    <div class="stat-card-label">Semua Percobaan Transaksi</div>
                </div>
                <div class="stat-card-icon history"><i class="fa-solid fa-receipt"></i></div>
            </div>
        </div>
    </div>

    <!-- Main Card -->
    <div class="admin-card">
        <div class="admin-card-header" style="flex-wrap:wrap; gap:16px;">
            <div style="display:flex; align-items:center; gap:12px;">
                <h3 class="admin-card-title">Daftar Transaksi</h3>
                <div style="display:flex; gap:6px;">
                    <a href="/cineva/admin/transactions.php" class="table-btn <?= empty($statusFilter) ? 'edit' : '' ?>" style="text-decoration:none;">Semua</a>
                    <a href="/cineva/admin/transactions.php?status=settlement" class="table-btn <?= $statusFilter === 'settlement' ? 'edit' : '' ?>" style="text-decoration:none;">Settlement</a>
                    <a href="/cineva/admin/transactions.php?status=pending" class="table-btn <?= $statusFilter === 'pending' ? 'edit' : '' ?>" style="text-decoration:none;">Pending</a>
                    <a href="/cineva/admin/transactions.php?status=cancel" class="table-btn <?= $statusFilter === 'cancel' ? 'edit' : '' ?>" style="text-decoration:none;">Batal / Expire</a>
                </div>
            </div>
            <div class="admin-search">
                <span class="admin-search-icon"><i class="fa-solid fa-search"></i></span>
                <input type="text" placeholder="Cari order ID atau nama...">
            </div>
        </div>

        <div style="overflow-x:auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Pengguna</th>
                        <th>Paket</th>
                        <th>Nominal</th>
                        <th>Metode Bayar</th>
                        <th>Status</th>
                        <th>Waktu Transaksi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($transactions && $transactions->num_rows > 0): ?>
                        <?php while ($t = $transactions->fetch_assoc()): ?>
                        <tr>
                            <td style="font-family:monospace; color:#38bdf8; font-size:0.84rem; font-weight:600;">
                                <?= htmlspecialchars($t['order_id']) ?>
                            </td>
                            <td>
                                <div style="color:var(--text-primary); font-weight:500;"><?= htmlspecialchars($t['user_name'] ?? 'User #' . $t['user_id']) ?></div>
                                <div style="font-size:0.75rem; color:var(--text-dim);"><?= htmlspecialchars($t['user_email'] ?? '-') ?></div>
                            </td>
                            <td>
                                <span class="table-badge <?= htmlspecialchars($t['plan']) ?>">
                                    <?= ucfirst($t['plan']) ?>
                                </span>
                            </td>
                            <td style="font-weight:600; color:#fff;">
                                Rp <?= number_format($t['amount'], 0, ',', '.') ?>
                            </td>
                            <td style="text-transform:uppercase; font-size:0.8rem; color:#9ca3af;">
                                <?= htmlspecialchars($t['payment_type'] ?? 'midtrans') ?>
                            </td>
                            <td>
                                <span class="badge-trx-status <?= htmlspecialchars($t['transaction_status']) ?>">
                                    <?php if ($t['transaction_status'] === 'settlement' || $t['transaction_status'] === 'capture'): ?>
                                        <i class="fa-solid fa-circle-check"></i> Settlement
                                    <?php elseif ($t['transaction_status'] === 'pending'): ?>
                                        <i class="fa-solid fa-clock"></i> Pending
                                    <?php else: ?>
                                        <i class="fa-solid fa-circle-xmark"></i> <?= ucfirst($t['transaction_status']) ?>
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td style="font-size:0.82rem; color:var(--text-dim);">
                                <?= date('d M Y H:i', strtotime($t['created_at'])) ?>
                            </td>
                            <td>
                                <button type="button" class="table-btn edit" onclick="openTrxModal(<?= htmlspecialchars(json_encode($t)) ?>)">
                                    <i class="fa-solid fa-eye"></i> Detail
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align:center; padding:30px; color:var(--text-dim);">
                                Tidak ada data transaksi yang ditemukan.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Detail Transaksi -->
    <div class="modal-overlay" id="trx-detail-modal" style="display:none; align-items:center; justify-content:center;">
        <div class="modal" style="max-width:540px; text-align:left;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                <h3 style="margin:0; font-size:1.15rem; color:#fff;">Detail Transaksi Midtrans</h3>
                <button type="button" onclick="closeTrxModal()" style="background:none; border:none; color:#9ca3af; font-size:1.2rem; cursor:pointer;">&times;</button>
            </div>

            <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); border-radius:12px; padding:16px; margin-bottom:16px; font-size:0.86rem; line-height:1.7;">
                <div style="display:flex; justify-content:space-between;">
                    <span style="color:var(--text-dim);">Order ID:</span>
                    <strong id="modalTrxOrderId" style="font-family:monospace; color:#38bdf8;"></strong>
                </div>
                <div style="display:flex; justify-content:space-between;">
                    <span style="color:var(--text-dim);">Pelanggan:</span>
                    <span id="modalTrxUser" style="color:#fff;"></span>
                </div>
                <div style="display:flex; justify-content:space-between;">
                    <span style="color:var(--text-dim);">Paket Langganan:</span>
                    <strong id="modalTrxPlan" style="color:var(--accent);"></strong>
                </div>
                <div style="display:flex; justify-content:space-between;">
                    <span style="color:var(--text-dim);">Total Nominal:</span>
                    <strong id="modalTrxAmount" style="color:#fff;"></strong>
                </div>
                <div style="display:flex; justify-content:space-between;">
                    <span style="color:var(--text-dim);">Metode Pembayaran:</span>
                    <span id="modalTrxType" style="text-transform:uppercase;"></span>
                </div>
                <div style="display:flex; justify-content:space-between;">
                    <span style="color:var(--text-dim);">Status Saat Ini:</span>
                    <span id="modalTrxStatus"></span>
                </div>
                <div style="display:flex; justify-content:space-between;">
                    <span style="color:var(--text-dim);">Waktu Pembayaran:</span>
                    <span id="modalTrxPaidAt" style="color:var(--text-dim);"></span>
                </div>
                <div style="margin-top:8px;">
                    <span style="color:var(--text-dim);">Snap Token:</span>
                    <div id="modalTrxToken" style="font-family:monospace; font-size:0.75rem; color:#9ca3af; word-break:break-all;"></div>
                </div>
            </div>

            <!-- Form Ubah Status oleh Admin -->
            <form method="POST" style="margin-top:14px; border-top:1px solid rgba(255,255,255,0.08); padding-top:14px;">
                <input type="hidden" name="action_update_status" value="1">
                <input type="hidden" name="trx_id" id="modalFormTrxId" value="">
                <label style="font-size:0.8rem; color:var(--text-dim); display:block; margin-bottom:6px;">Ubah Status Transaksi (Admin Override):</label>
                <div style="display:flex; gap:10px;">
                    <select name="new_status" id="modalFormSelectStatus" style="flex:1; background:#1c1d29; border:1px solid rgba(255,255,255,0.15); color:#fff; padding:8px 12px; border-radius:8px;">
                        <option value="settlement">Settlement (Aktifkan Langganan)</option>
                        <option value="pending">Pending</option>
                        <option value="cancel">Cancel (Batalkan)</option>
                        <option value="expire">Expire (Kedaluwarsa)</option>
                    </select>
                    <button type="submit" class="btn-submit" style="padding:8px 18px; font-size:0.85rem;">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</main>

</div>

<script>
function openTrxModal(trx) {
    document.getElementById('modalTrxOrderId').textContent = trx.order_id;
    document.getElementById('modalTrxUser').textContent = (trx.user_name || 'User #' + trx.user_id) + ' (' + (trx.user_email || '-') + ')';
    document.getElementById('modalTrxPlan').textContent = trx.plan.toUpperCase() + ' PLAN';
    document.getElementById('modalTrxAmount').textContent = 'Rp ' + Number(trx.amount).toLocaleString('id-ID');
    document.getElementById('modalTrxType').textContent = trx.payment_type || 'MIDTRANS';
    document.getElementById('modalTrxStatus').innerHTML = '<span class="badge-trx-status ' + trx.transaction_status + '">' + trx.transaction_status + '</span>';
    document.getElementById('modalTrxPaidAt').textContent = trx.paid_at ? trx.paid_at : 'Belum Dibayar';
    document.getElementById('modalTrxToken').textContent = trx.snap_token || '(Tidak ada token)';

    document.getElementById('modalFormTrxId').value = trx.id;
    document.getElementById('modalFormSelectStatus').value = trx.transaction_status;

    const modal = document.getElementById('trx-detail-modal');
    modal.style.display = 'flex';
}

function closeTrxModal() {
    document.getElementById('trx-detail-modal').style.display = 'none';
}
</script>
<script src="/cineva/assets/js/admin.js"></script>
</body>
</html>
