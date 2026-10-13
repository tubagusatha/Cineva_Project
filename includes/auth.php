<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireLogin($redirect = '/cineva/auth/login.php') {
    if (!isLoggedIn()) {
        header('Location: ' . $redirect);
        exit;
    }
}

function requireAdmin($redirect = '/cineva/index.php') {
    if (!isAdmin()) {
        header('Location: ' . $redirect);
        exit;
    }
}

function getCurrentUser($conn) {
    if (!isLoggedIn()) return null;
    $stmt = $conn->prepare("
        SELECT u.id, u.name, u.email, u.role, u.created_at, 
               COALESCE(c.subscription, 'free') AS subscription,
               c.subscription_expires_at,
               c.active_order_id,
               c.payment_method
        FROM users u 
        LEFT JOIN customers c ON c.user_id = u.id 
        WHERE u.id = ?
    ");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $userData = $result->fetch_assoc();
    if ($userData && !empty($userData['subscription_expires_at'])) {
        if (strtotime($userData['subscription_expires_at']) < time() && $userData['role'] !== 'admin') {
            $userData['subscription'] = 'free';
            $userData['is_expired'] = true;
        }
    }
    return $userData;
}

function getUserSubscription($conn, $userId = null) {
    if (!$userId) {
        if (!isLoggedIn()) return 'none';
        $userId = $_SESSION['user_id'];
    }
    if (isAdmin()) return 'premium';

    $stmt = $conn->prepare("SELECT subscription, subscription_expires_at FROM customers WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) return 'free';

    // Periksa masa aktif jika tanggal kedaluwarsa diset
    if (!empty($row['subscription_expires_at']) && strtotime($row['subscription_expires_at']) < time()) {
        return 'free';
    }

    return $row['subscription'] ?? 'free';
}

function hasActiveSubscription($conn, $userId = null) {
    if (isAdmin()) return true;
    $plan = getUserSubscription($conn, $userId);
    return in_array($plan, ['basic', 'standard', 'premium']);
}

function requireSubscription($conn) {
    requireLogin();
    if (isAdmin()) return;
    if (!hasActiveSubscription($conn)) {
        header('Location: /cineva/subscription.php?required=1');
        exit;
    }
}

function asset($path) {
    if (!$path) return '';
    if (str_starts_with($path, 'http')) return $path;
    return '/cineva/' . ltrim($path, '/');
}

function getYoutubeEmbedUrl($url) {
    $videoId = '';
    if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $matches)) {
        $videoId = $matches[1];
    }
    return $videoId ? 'https://www.youtube.com/embed/' . $videoId : '';
}
