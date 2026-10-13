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
$plan = strtolower(trim($input['plan'] ?? ($_POST['plan'] ?? '')));

$plans = CINEVA_PLANS;
if (!isset($plans[$plan])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Paket langganan tidak valid.']);
    exit;
}

$planInfo = $plans[$plan];
$amount = (int)$planInfo['price'];

// Buat Order ID Unik (contoh: CNV-STD-2-1718902-A8F)
$prefix = strtoupper(substr($plan, 0, 3));
$orderId = 'CNV-' . $prefix . '-' . $user['id'] . '-' . time() . '-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));

// Parameter untuk Midtrans Snap
$params = [
    'transaction_details' => [
        'order_id' => $orderId,
        'gross_amount' => $amount
    ],
    'item_details' => [
        [
            'id' => 'CINEVA-' . strtoupper($plan),
            'price' => $amount,
            'quantity' => 1,
            'name' => 'Cineva ' . $planInfo['name'] . ' (1 Bulan)'
        ]
    ],
    'customer_details' => [
        'first_name' => $user['name'],
        'email' => $user['email']
    ]
];

// Dapatkan Snap Token dari Midtrans Gateway
$snapResult = MidtransGateway::createSnapToken($params);
$snapToken = $snapResult['token'] ?? null;
$isDummy = !empty($snapResult['is_dummy']);

// Simpan transaksi ke tabel subscription_transactions dengan status 'pending'
$stmt = $conn->prepare("
    INSERT INTO subscription_transactions (user_id, order_id, plan, amount, snap_token, payment_type, transaction_status)
    VALUES (?, ?, ?, ?, ?, 'midtrans', 'pending')
");
$stmt->bind_param("issis", $user['id'], $orderId, $plan, $amount, $snapToken);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal membuat catatan transaksi: ' . $conn->error]);
    exit;
}

echo json_encode([
    'status' => 'success',
    'order_id' => $orderId,
    'plan' => $plan,
    'plan_name' => $planInfo['name'],
    'amount' => $amount,
    'amount_formatted' => 'Rp ' . number_format($amount, 0, ',', '.'),
    'snap_token' => $snapToken,
    'client_key' => MidtransGateway::getClientKey(),
    'is_dummy' => $isDummy,
    'message' => $isDummy ? 'Mode Sandbox Dummy Aktif (Bisa disimulasikan langsung di XAMPP)' : 'Snap token berhasil dibuat'
]);
