<?php
/**
 * midtrans-simulate.php
 * Trigger Midtrans Sandbox payment simulation.
 * Works by calling Midtrans Sandbox simulator endpoints to force-settle a transaction.
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/midtrans.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Login required.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
    exit;
}

$user = getCurrentUser($conn);
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$orderId = trim($input['order_id'] ?? '');

if (empty($orderId)) {
    echo json_encode(['status' => 'error', 'message' => 'order_id diperlukan.']);
    exit;
}

// Verify order belongs to this user
$stmtChk = $conn->prepare("SELECT * FROM subscription_transactions WHERE order_id = ? AND user_id = ? LIMIT 1");
$stmtChk->bind_param("si", $orderId, $user['id']);
$stmtChk->execute();
$trx = $stmtChk->get_result()->fetch_assoc();

if (!$trx) {
    echo json_encode(['status' => 'error', 'message' => 'Transaksi tidak ditemukan.']);
    exit;
}

if ($trx['transaction_status'] === 'settlement') {
    echo json_encode(['status' => 'already_paid', 'message' => 'Transaksi sudah berhasil!']);
    exit;
}

// Step 1: Check real status from Midtrans
$statusUrl = 'https://api.sandbox.midtrans.com/v2/' . urlencode($orderId) . '/status';
$ch = curl_init($statusUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Basic ' . base64_encode(MIDTRANS_SERVER_KEY . ':'),
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 8);
$statusResp = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$statusData = $statusResp ? json_decode($statusResp, true) : null;
$currentStatus = strtolower($statusData['transaction_status'] ?? 'unknown');

// If already settled in Midtrans side, activate locally
if (in_array($currentStatus, ['settlement', 'capture'])) {
    goto activate_subscription;
}

// Step 2: Try Midtrans Sandbox QRIS/GoPay simulator (force fulfill)
// This endpoint simulates a payment confirmation from GoPay/QRIS in sandbox
$simulatorEndpoints = [
    // GoPay simulator
    'https://api.sandbox.midtrans.com/v2/' . urlencode($orderId) . '/gopay/fulfill',
    // QRIS/Generic accept
    'https://api.sandbox.midtrans.com/v2/' . urlencode($orderId) . '/accept',
];

$simulatorSuccess = false;
foreach ($simulatorEndpoints as $simUrl) {
    $ch = curl_init($simUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, '{}');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode(MIDTRANS_SERVER_KEY . ':'),
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);
    $simResp = curl_exec($ch);
    $simCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $simData = $simResp ? json_decode($simResp, true) : null;
    $simStatus = strtolower($simData['transaction_status'] ?? $simData['status_code'] ?? '');

    if ($simCode >= 200 && $simCode < 300 && in_array($simStatus, ['200', 'settlement', 'capture', 'gopay-fulfilled'])) {
        $simulatorSuccess = true;
        break;
    }
}

// Step 3: Re-check status after simulate
$ch = curl_init($statusUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Basic ' . base64_encode(MIDTRANS_SERVER_KEY . ':'),
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 8);
$reCheckResp = curl_exec($ch);
curl_close($ch);

$reCheck = $reCheckResp ? json_decode($reCheckResp, true) : null;
$finalStatus = strtolower($reCheck['transaction_status'] ?? 'unknown');

if (!in_array($finalStatus, ['settlement', 'capture'])) {
    // Cannot confirm via Midtrans API — activate anyway as dummy (best effort for localhost)
    echo json_encode([
        'status' => 'fallback',
        'message' => 'Tidak bisa konfirmasi via Midtrans API (localhost limitation). Gunakan tombol Bayar Dummy.',
        'mt_status' => $finalStatus,
        'sim_attempted' => $simulatorSuccess
    ]);
    exit;
}

activate_subscription:
// Activate the subscription
$paymentType = $reCheck['payment_type'] ?? $statusData['payment_type'] ?? 'midtrans_sandbox';

$stmtUpd = $conn->prepare("UPDATE subscription_transactions SET transaction_status='settlement', payment_type=?, paid_at=NOW() WHERE order_id=? AND user_id=?");
$stmtUpd->bind_param("ssi", $paymentType, $orderId, $user['id']);
$stmtUpd->execute();

$plan = $trx['plan'];
$stmtCust = $conn->prepare("INSERT INTO customers (user_id, name, email, password, subscription, subscription_expires_at, active_order_id, payment_method)
    SELECT u.id, u.name, u.email, u.password, ?, DATE_ADD(NOW(), INTERVAL 30 DAY), ?, ?
    FROM users u WHERE u.id = ?
    ON DUPLICATE KEY UPDATE subscription=VALUES(subscription), subscription_expires_at=VALUES(subscription_expires_at), active_order_id=VALUES(active_order_id), payment_method=VALUES(payment_method)");
$stmtCust->bind_param("sssi", $plan, $orderId, $paymentType, $user['id']);
$stmtCust->execute();

echo json_encode([
    'status' => 'success',
    'message' => 'Pembayaran berhasil dikonfirmasi! Paket ' . ucfirst($plan) . ' aktif.',
    'plan' => $plan,
    'order_id' => $orderId,
    'redirect' => '/cineva/subscription.php?success=' . urlencode($plan) . '&order_id=' . urlencode($orderId)
]);
