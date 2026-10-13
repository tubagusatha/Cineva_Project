<?php
/**
 * Midtrans Webhook / HTTP Notification Handler
 * Endpoint: http://localhost/cineva/api/midtrans-notification.php
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/midtrans.php';

$rawBody = file_get_contents('php://input');
$data = json_decode($rawBody, true);

if (!$data || !isset($data['order_id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Payload not valid']);
    exit;
}

$orderId = $data['order_id'];
$statusCode = $data['status_code'] ?? '';
$grossAmount = $data['gross_amount'] ?? '';
$signatureKey = $data['signature_key'] ?? '';
$transactionStatus = $data['transaction_status'] ?? '';
$fraudStatus = $data['fraud_status'] ?? 'accept';
$paymentType = $data['payment_type'] ?? 'midtrans';

// Verifikasi signature jika Server Key diisi valid (bukan dummy placeholder)
if (strpos(MIDTRANS_SERVER_KEY, 'dummy') === false && !empty($signatureKey)) {
    if (!MidtransGateway::verifySignature($orderId, $statusCode, $grossAmount, $signatureKey)) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Invalid signature']);
        exit;
    }
}

// Cari transaksi
$stmt = $conn->prepare("SELECT * FROM subscription_transactions WHERE order_id = ?");
$stmt->bind_param("s", $orderId);
$stmt->execute();
$trx = $stmt->get_result()->fetch_assoc();

if (!$trx) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Transaction not found']);
    exit;
}

$isSettled = false;
$newStatus = 'pending';

if ($transactionStatus == 'capture') {
    if ($fraudStatus == 'challenge') {
        $newStatus = 'pending';
    } else if ($fraudStatus == 'accept') {
        $newStatus = 'settlement';
        $isSettled = true;
    }
} else if ($transactionStatus == 'settlement') {
    $newStatus = 'settlement';
    $isSettled = true;
} else if (in_array($transactionStatus, ['cancel', 'deny'])) {
    $newStatus = 'cancel';
} else if ($transactionStatus == 'expire') {
    $newStatus = 'expire';
} else if ($transactionStatus == 'pending') {
    $newStatus = 'pending';
}

// Update transaksi
$stmtUpdate = $conn->prepare("
    UPDATE subscription_transactions 
    SET transaction_status = ?, 
        payment_type = ?, 
        paid_at = " . ($isSettled ? "NOW()" : "paid_at") . ", 
        payment_response = ? 
    WHERE id = ?
");
$stmtUpdate->bind_param("sssi", $newStatus, $paymentType, $rawBody, $trx['id']);
$stmtUpdate->execute();

// Jika status settlement, aktifkan langganan di tabel customers
if ($isSettled) {
    $plan = $trx['plan'];
    $userId = $trx['user_id'];
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
    $stmtCust->bind_param("sssi", $plan, $orderId, $paymentType, $userId);
    $stmtCust->execute();
}

http_response_code(200);
echo json_encode(['status' => 'success', 'transaction_status' => $newStatus]);
