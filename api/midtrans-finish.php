<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/midtrans.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Silakan masuk terlebih dahulu.']);
    exit;
}

$user = getCurrentUser($conn);
if (!$user) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Pengguna tidak valid.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Metode permintaan tidak diizinkan.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$orderId = trim($input['order_id'] ?? '');
$trxStatus = strtolower(trim($input['transaction_status'] ?? ($input['status'] ?? 'settlement')));
$paymentType = trim($input['payment_type'] ?? 'midtrans');
$rawResult = is_array($input['raw_result'] ?? null) ? json_encode($input['raw_result']) : ($input['raw_result'] ?? null);

if (empty($orderId)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Order ID tidak ditemukan.']);
    exit;
}

// Cari transaksi yang sesuai dengan user
$stmt = $conn->prepare("SELECT * FROM subscription_transactions WHERE order_id = ? AND user_id = ?");
$stmt->bind_param("si", $orderId, $user['id']);
$stmt->execute();
$trx = $stmt->get_result()->fetch_assoc();

if (!$trx) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Transaksi tidak ditemukan.']);
    exit;
}

// Normalisasi status Midtrans
$validStatusMap = [
    'settlement' => 'settlement',
    'capture' => 'settlement',
    'success' => 'settlement',
    'pending' => 'pending',
    'cancel' => 'cancel',
    'cancelled' => 'cancel',
    'expire' => 'expire',
    'expired' => 'expire',
    'deny' => 'deny'
];
$mappedStatus = $validStatusMap[$trxStatus] ?? 'pending';

if ($mappedStatus === 'settlement') {
    // 1. Update tabel subscription_transactions
    $stmtUpdate = $conn->prepare("
        UPDATE subscription_transactions 
        SET transaction_status = 'settlement', 
            payment_type = ?, 
            paid_at = NOW(), 
            payment_response = ? 
        WHERE id = ?
    ");
    $stmtUpdate->bind_param("ssi", $paymentType, $rawResult, $trx['id']);
    $stmtUpdate->execute();

    // 2. Update atau Insert ke tabel customers
    $plan = $trx['plan'];
    $stmtCust = $conn->prepare("
        INSERT INTO customers (user_id, name, email, password, subscription, subscription_expires_at, active_order_id, payment_method)
        SELECT u.id, u.name, u.email, u.password, ?, DATE_ADD(NOW(), INTERVAL 30 DAY), ?, ?
        FROM users u
        WHERE u.id = ?
        ON DUPLICATE KEY UPDATE 
            subscription = VALUES(subscription),
            subscription_expires_at = VALUES(subscription_expires_at),
            active_order_id = VALUES(active_order_id),
            payment_method = VALUES(payment_method),
            name = VALUES(name),
            email = VALUES(email),
            password = VALUES(password)
    ");
    $stmtCust->bind_param("sssi", $plan, $orderId, $paymentType, $user['id']);
    $stmtCust->execute();

    echo json_encode([
        'status' => 'success',
        'transaction_status' => 'settlement',
        'message' => 'Pembayaran berhasil! Paket ' . ucfirst($plan) . ' Anda telah aktif selama 30 hari.',
        'order_id' => $orderId,
        'plan' => $plan,
        'redirect' => '/cineva/subscription.php?success=' . urlencode($plan) . '&order_id=' . urlencode($orderId)
    ]);
    exit;
} else {
    // Status pending / cancel
    $stmtUpdate = $conn->prepare("
        UPDATE subscription_transactions 
        SET transaction_status = ?, 
            payment_type = ?, 
            payment_response = ? 
        WHERE id = ?
    ");
    $stmtUpdate->bind_param("sssi", $mappedStatus, $paymentType, $rawResult, $trx['id']);
    $stmtUpdate->execute();

    echo json_encode([
        'status' => 'info',
        'transaction_status' => $mappedStatus,
        'message' => 'Status transaksi diperbarui: ' . ucfirst($mappedStatus),
        'order_id' => $orderId,
        'plan' => $trx['plan'],
        'redirect' => '/cineva/subscription.php'
    ]);
    exit;
}
