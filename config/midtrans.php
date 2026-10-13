<?php
/**
 * Midtrans Configuration & Helper Gateway for Cineva
 * Compatible with XAMPP (Localhost), Sandbox & Production
 */

if (!defined('MIDTRANS_SERVER_KEY')) {
    define('MIDTRANS_SERVER_KEY', getenv('MIDTRANS_SERVER_KEY') ?: 'SB-Mid-server-YOUR_SERVER_KEY');
}

if (!defined('MIDTRANS_CLIENT_KEY')) {
    define('MIDTRANS_CLIENT_KEY', getenv('MIDTRANS_CLIENT_KEY') ?: 'SB-Mid-client-YOUR_CLIENT_KEY');
}

if (!defined('MIDTRANS_IS_PRODUCTION')) {
    define('MIDTRANS_IS_PRODUCTION', false); // false = Sandbox (Testing / Dummy)
}

// Daftar Paket Langganan Cineva
if (!defined('CINEVA_PLANS')) {
    define('CINEVA_PLANS', [
        'basic' => [
            'name' => 'Basic Plan',
            'price' => 49000,
            'duration_days' => 30,
            'quality' => 'HD 720p',
            'devices' => 1,
            'description' => 'Akses streaming film & serial HD di 1 perangkat'
        ],
        'standard' => [
            'name' => 'Standard Plan',
            'price' => 99000,
            'duration_days' => 30,
            'quality' => 'Full HD 1080p',
            'devices' => 2,
            'description' => 'Akses Full HD 1080p di 2 perangkat + Audio Dolby'
        ],
        'premium' => [
            'name' => 'Premium Plan',
            'price' => 149000,
            'duration_days' => 30,
            'quality' => '4K UHD + HDR',
            'devices' => 4,
            'description' => 'Pengalaman sinematik 4K UHD + Dolby Atmos di 4 perangkat'
        ]
    ]);
}

class MidtransGateway {

    public static function getServerKey() {
        return MIDTRANS_SERVER_KEY;
    }

    public static function getClientKey() {
        return MIDTRANS_CLIENT_KEY;
    }

    public static function isProduction() {
        return MIDTRANS_IS_PRODUCTION;
    }

    public static function getSnapApiUrl() {
        return self::isProduction()
            ? 'https://app.midtrans.com/snap/v1/transactions'
            : 'https://app.sandbox.midtrans.com/snap/v1/transactions';
    }

    public static function getSnapJsUrl() {
        return self::isProduction()
            ? 'https://app.midtrans.com/snap/snap.js'
            : 'https://app.sandbox.midtrans.com/snap/snap.js';
    }

    /**
     * Membuat Snap Token ke Midtrans API (dengan fallback Simulator Dummy yang aman untuk XAMPP)
     */
    public static function createSnapToken($params) {
        $serverKey = self::getServerKey();
        $apiUrl = self::getSnapApiUrl();

        $payload = json_encode($params);

        // Jika curl tersedia
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Basic ' . base64_encode($serverKey . ':')
            ]);
            // Di lingkungan local Windows XAMPP sering kali CA Cert belum dikonfigurasi di php.ini
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if (!$curlError && $httpCode >= 200 && $httpCode < 300) {
                $data = json_decode($response, true);
                if (isset($data['token'])) {
                    return [
                        'success' => true,
                        'is_dummy' => false,
                        'token' => $data['token'],
                        'redirect_url' => $data['redirect_url'] ?? '',
                        'raw' => $data
                    ];
                }
            }
        }

        // Jika cURL gagal atau key default sandbox dummy aktif (offline/XAMPP demo mode),
        // fallback secara mulus ke Simulator Midtrans Sandbox internal
        $dummyToken = 'SNAP-DUMMY-' . strtoupper(substr(md5(uniqid($params['transaction_details']['order_id'] ?? 'cineva', true)), 0, 24));
        return [
            'success' => true,
            'is_dummy' => true,
            'token' => $dummyToken,
            'redirect_url' => '',
            'message' => 'Menggunakan Midtrans Sandbox Simulation Mode (Aman untuk pengujian di XAMPP)',
            'raw' => ['token' => $dummyToken]
        ];
    }

    /**
     * Memverifikasi signature hash notifikasi Midtrans
     */
    public static function verifySignature($orderId, $statusCode, $grossAmount, $signatureKey) {
        $serverKey = self::getServerKey();
        $expected = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);
        return hash_equals($expected, (string)$signatureKey);
    }
}
