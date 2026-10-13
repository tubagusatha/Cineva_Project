<?php
/**
 * dummy-pay.php
 * Direct dummy payment endpoint for XAMPP/localhost testing.
 * Inserts transaction and activates subscription without Midtrans API.
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

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
    echo json_encode(['status' => 'error', 'message' => 'Metode tidak diizinkan.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$plan        = strtolower(trim($input['plan'] ?? 'standard'));
$paymentType = trim($input['payment_type'] ?? 'dummy');

$planPrices = [
    'basic'    => 49000,
    'standard' => 99000,
    'premium'  => 149000,
];

if (!array_key_exists($plan, $planPrices)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Paket tidak valid.']);
    exit;
}

$amount  = $planPrices[$plan];
$orderId = 'CNV-DUMMY-' . strtoupper($plan) . '-' . date('YmdHis') . '-' . rand(100, 999);

try {
    $conn->begin_transaction();

    // Cancel transaksi pending lama jika ada
    $stmtCancel = $conn->prepare("UPDATE subscription_transactions SET transaction_status = 'cancel' WHERE user_id = ? AND transaction_status = 'pending'");
    $stmtCancel->bind_param("i", $user['id']);
    $stmtCancel->execute();

    // Insert transaksi baru langsung dengan status settlement
    $stmtInsert = $conn->prepare("INSERT INTO subscription_transactions (user_id, order_id, plan, amount, transaction_status, payment_type, snap_token, paid_at, created_at) VALUES (?, ?, ?, ?, 'settlement', ?, 'dummy-no-snap', NOW(), NOW())");
    $stmtInsert->bind_param("issds", $user['id'], $orderId, $plan, $amount, $paymentType);
    $stmtInsert->execute();

    // Upsert customers table
    $stmtCust = $conn->prepare("INSERT INTO customers (user_id, name, email, password, subscription, subscription_expires_at, active_order_id, payment_method) SELECT u.id, u.name, u.email, u.password, ?, DATE_ADD(NOW(), INTERVAL 30 DAY), ?, ? FROM users u WHERE u.id = ? ON DUPLICATE KEY UPDATE subscription = VALUES(subscription), subscription_expires_at = VALUES(subscription_expires_at), active_order_id = VALUES(active_order_id), payment_method = VALUES(payment_method), name = VALUES(name), email = VALUES(email), password = VALUES(password)");
    $stmtCust->bind_param("sssi", $plan, $orderId, $paymentType, $user['id']);
    $stmtCust->execute();

    $conn->commit();

    echo json_encode([
        'status'             => 'success',
        'message'            => 'Pembayaran dummy berhasil! Paket ' . ucfirst($plan) . ' aktif 30 hari.',
        'order_id'           => $orderId,
        'plan'               => $plan,
        'amount'             => $amount,
        'transaction_status' => 'settlement',
        'redirect'           => '/cineva/subscription.php?success=' . urlencode($plan) . '&order_id=' . urlencode($orderId),
    ]);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
