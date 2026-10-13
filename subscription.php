<?php
$pageTitle = 'Paket Langganan';
$pageDesc = 'Pilih paket langganan Cineva dengan pembayaran Midtrans yang aman & praktis. Nikmati streaming tanpa iklan.';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/midtrans.php';
requireLogin();

$user = getCurrentUser($conn);
$successPlan = '';
$successOrder = null;
$error = '';

$currentPlan = getUserSubscription($conn, $user['id']);
$isRequired = isset($_GET['required']) && !hasActiveSubscription($conn);
$isWelcome  = isset($_GET['welcome']) && !hasActiveSubscription($conn);

// Periksa apakah baru saja menyelesaikan pembayaran via Midtrans Snap / Dummy
if (isset($_GET['success'])) {
    $planParam = strtolower(trim($_GET['success']));
    if (in_array($planParam, ['basic', 'standard', 'premium'])) {
        $successPlan = $planParam;
        $orderIdParam = trim($_GET['order_id'] ?? '');
        if ($orderIdParam) {
            $stmtTrx = $conn->prepare("SELECT * FROM subscription_transactions WHERE order_id = ? AND user_id = ? LIMIT 1");
            $stmtTrx->bind_param("si", $orderIdParam, $user['id']);
            $stmtTrx->execute();
            $successOrder = $stmtTrx->get_result()->fetch_assoc();
        }
    }
}

// Ambil info detail masa aktif customer saat ini
$stmtCust = $conn->prepare("SELECT subscription, subscription_expires_at, active_order_id, payment_method FROM customers WHERE user_id = ?");
$stmtCust->bind_param("i", $user['id']);
$stmtCust->execute();
$customerDetail = $stmtCust->get_result()->fetch_assoc();

// Ambil riwayat transaksi langganan user
$stmtHistory = $conn->prepare("SELECT * FROM subscription_transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$stmtHistory->bind_param("i", $user['id']);
$stmtHistory->execute();
$historyList = $stmtHistory->get_result();

include __DIR__ . '/includes/header.php';
?>

<!-- Include Midtrans Snap JS (Sandbox) -->
<script type="text/javascript" 
        src="<?= MidtransGateway::getSnapJsUrl() ?>" 
        data-client-key="<?= MidtransGateway::getClientKey() ?>">
</script>

<!-- ==============================================
     MODAL POP-UPS (Clean, Animated, Not Stiff)
     ============================================== -->

<!-- 1. SUCCESS CELEBRATION MODAL (MIDTRANS SETTLEMENT) -->
<?php if ($successPlan): ?>
<div class="cineva-modal-overlay active" id="modalSuccess">
    <div class="cineva-modal-box modal-celebrate">
        <div class="modal-confetti-icon">🍿</div>
        <div class="modal-badge-status"><i class="fa-solid fa-circle-check"></i> Pembayaran Berhasil & Langganan Aktif</div>
        <h2 class="modal-heading">Selamat, Akun Anda Siap!</h2>
        <p class="modal-subheading">Paket <strong><?= ucfirst($successPlan) ?> Plan</strong> berhasil diaktifkan melalui <strong>Midtrans Payment</strong>. Ribuan film dan series pilihan kini siap Anda tonton tanpa batas.</p>
        
        <div class="modal-plan-summary">
            <?php if ($successOrder): ?>
            <div class="summary-item">
                <span class="summary-label">Order ID</span>
                <span class="summary-value" style="font-family:monospace; font-size:0.85rem; color:#38bdf8;"><?= htmlspecialchars($successOrder['order_id']) ?></span>
            </div>
            <?php endif; ?>
            <div class="summary-item">
                <span class="summary-label">Paket Dipilih</span>
                <span class="summary-value text-accent"><?= ucfirst($successPlan) ?> Plan</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Masa Berlaku</span>
                <span class="summary-value">30 Hari (Aktif hingga <?= date('d M Y', strtotime('+30 days')) ?>)</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Metode Pembayaran</span>
                <span class="summary-value" style="text-transform:uppercase; font-size:0.85rem; font-weight:700;">
                    <i class="fa-solid fa-shield-halved text-accent"></i> 
                    <?= htmlspecialchars($successOrder['payment_type'] ?? ($customerDetail['payment_method'] ?? 'Midtrans')) ?>
                </span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Status Akses</span>
                <span class="summary-value text-success"><i class="fa-solid fa-lock-open"></i> Akses Terbuka Penuh</span>
            </div>
        </div>

        <a href="/cineva/" class="btn-modal-primary btn-modal-pulse">
            <i class="fa-solid fa-play"></i> Mulai Nonton Sekarang
        </a>
        <div class="modal-countdown-hint">Otomatis dialihkan dalam <span id="redirectCounter">5</span> detik...</div>
    </div>
</div>
<script>
let count = 5;
const counterEl = document.getElementById('redirectCounter');
const countdownTimer = setInterval(() => {
    count--;
    if (counterEl) counterEl.textContent = count;
    if (count <= 0) {
        clearInterval(countdownTimer);
        window.location.href = '/cineva/';
    }
}, 1000);
</script>
<?php endif; ?>

<!-- 2. WELCOME MODAL (After Register) -->
<?php if ($isWelcome && !$successPlan): ?>
<div class="cineva-modal-overlay active" id="modalWelcome">
    <div class="cineva-modal-box">
        <button type="button" class="modal-close-btn" onclick="closeCinevaModal('modalWelcome')">
            <i class="fa-solid fa-xmark"></i>
        </button>
        <div class="modal-avatar-glow">
            <i class="fa-solid fa-sparkles"></i>
        </div>
        <h2 class="modal-heading">Selamat Datang di Cineva, <?= htmlspecialchars(explode(' ', $user['name'])[0]) ?>! 🎉</h2>
        <p class="modal-subheading">Satu langkah terakhir untuk mulai streaming! Tentukan paket langganan yang paling pas untuk kebutuhan menonton Anda.</p>
        
        <div class="modal-benefits-grid">
            <div class="benefit-chip"><i class="fa-solid fa-film text-accent"></i> Ribuan Film HD & 4K</div>
            <div class="benefit-chip"><i class="fa-solid fa-ban text-accent"></i> 100% Bebas Iklan</div>
            <div class="benefit-chip"><i class="fa-solid fa-tv text-accent"></i> TV, Tablet, PC & HP</div>
            <div class="benefit-chip"><i class="fa-solid fa-rotate-left text-accent"></i> Batalkan Kapan Saja</div>
        </div>

        <button type="button" class="btn-modal-primary" onclick="closeCinevaModal('modalWelcome')">
            Pilih Paket Saya <i class="fa-solid fa-arrow-down"></i>
        </button>
    </div>
</div>
<?php endif; ?>

<!-- 3. REQUIRED SUBSCRIPTION MODAL (When accessing protected page) -->
<?php if ($isRequired && !$isWelcome && !$successPlan): ?>
<div class="cineva-modal-overlay active" id="modalRequired">
    <div class="cineva-modal-box">
        <button type="button" class="modal-close-btn" onclick="closeCinevaModal('modalRequired')">
            <i class="fa-solid fa-xmark"></i>
        </button>
        <div class="modal-avatar-glow lock-avatar">
            <i class="fa-solid fa-lock"></i>
        </div>
        <h2 class="modal-heading">Aktivasi Langganan Diperlukan</h2>
        <p class="modal-subheading">Untuk menikmati katalog film dan menonton tayangan di Cineva, Anda perlu memilih salah satu paket streaming di bawah ini.</p>
        
        <div class="modal-benefits-grid">
            <div class="benefit-chip"><i class="fa-solid fa-check text-green"></i> Akses instan langsung aktif</div>
            <div class="benefit-chip"><i class="fa-solid fa-check text-green"></i> Bayar aman dengan Midtrans</div>
        </div>

        <button type="button" class="btn-modal-primary" onclick="closeCinevaModal('modalRequired')">
            Lihat Pilihan Paket <i class="fa-solid fa-arrow-down"></i>
        </button>
    </div>
</div>
<?php endif; ?>

<!-- 4. MIDTRANS CHECKOUT PREVIEW MODAL -->
<div class="cineva-modal-overlay" id="modalMidtransCheckout">
    <div class="cineva-modal-box modal-midtrans-box">
        <button type="button" class="modal-close-btn" onclick="closeCinevaModal('modalMidtransCheckout')">
            <i class="fa-solid fa-xmark"></i>
        </button>
        
        <div class="midtrans-badge-header">
            <i class="fa-solid fa-shield-halved"></i>
            <span>MIDTRANS PAYMENT GATEWAY • SANDBOX MODE</span>
        </div>

        <h2 class="midtrans-checkout-title">Konfirmasi Pembayaran</h2>
        <p class="midtrans-checkout-desc">Selesaikan transaksi langganan Anda menggunakan berbagai opsi pembayaran resmi Midtrans Indonesia.</p>

        <!-- Order Breakdown -->
        <div class="midtrans-order-card">
            <div class="order-row">
                <span>Paket Langganan</span>
                <strong id="modalCheckoutPlanName" class="text-white">Standard Plan</strong>
            </div>
            <div class="order-row">
                <span>Durasi Akses</span>
                <span class="text-white">30 Hari Penuh</span>
            </div>
            <div class="order-row">
                <span>Nama Pelanggan</span>
                <span class="text-white"><?= htmlspecialchars($user['name']) ?></span>
            </div>
            <div class="order-row">
                <span>Email Akun</span>
                <span class="text-white"><?= htmlspecialchars($user['email']) ?></span>
            </div>
            <div class="order-row">
                <span>Biaya Transaksi / Admin</span>
                <span class="text-success"><i class="fa-solid fa-check"></i> Gratis (Promo)</span>
            </div>
            <div class="order-row total-row">
                <span>Total Pembayaran</span>
                <span class="order-total-price" id="modalCheckoutTotal">Rp 99.000</span>
            </div>
        </div>

        <!-- Channels Preview -->
        <div class="payment-channels-preview">
            <div class="payment-channels-label">Metode yang Didukung di Midtrans:</div>
            <div class="payment-channels-logos">
                <span class="channel-pill"><i class="fa-solid fa-qrcode"></i> QRIS</span>
                <span class="channel-pill"><i class="fa-solid fa-wallet"></i> GoPay / ShopeePay</span>
                <span class="channel-pill"><i class="fa-solid fa-building-columns"></i> Virtual Account BCA / Mandiri / BNI / BRI</span>
                <span class="channel-pill"><i class="fa-solid fa-credit-card"></i> Visa / Mastercard</span>
            </div>
        </div>

        <!-- Buttons -->
        <div class="checkout-action-btns">
            <button type="button" class="btn-midtrans-pay" id="btnLaunchSnap" onclick="triggerMidtransPayment()">
                <i class="fa-solid fa-lock"></i>
                <span id="btnSnapText">Bayar Sekarang via Midtrans</span>
                <i class="fa-solid fa-arrow-right"></i>
            </button>
            <button type="button" class="btn-midtrans-dummy" onclick="triggerDummySimulator()">
                <i class="fa-solid fa-laptop-code text-accent"></i>
                <span>Simulasi Instan Dummy (Untuk Pengujian XAMPP)</span>
            </button>
        </div>
    </div>
</div>

<!-- 5. DUMMY SANDBOX SIMULATOR MODAL (OFFLINE / DEMO FOR XAMPP) -->
<div class="cineva-modal-overlay" id="modalMidtransDummy">
    <div class="cineva-modal-box modal-simulator-box">
        <button type="button" class="modal-close-btn" onclick="closeCinevaModal('modalMidtransDummy')">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <div class="sim-header">
            <div>
                <div class="sim-brand-title">
                    <i class="fa-solid fa-cube text-accent"></i>
                    <span>Midtrans Sandbox Simulator</span>
                    <span class="sim-badge">Dummy XAMPP</span>
                </div>
                <div style="font-size:0.78rem; color:#9ca3af; margin-top:4px;">
                    Simulasi pembayaran langsung di browser tanpa kendala koneksi atau key.
                </div>
            </div>
            <div class="sim-order-pill" id="simOrderIdDisplay">CNV-TRX-DEMO</div>
        </div>

        <!-- Tabs -->
        <div class="sim-nav-tabs">
            <button type="button" class="sim-tab-btn active" onclick="switchSimTab('qris')">
                <i class="fa-solid fa-qrcode"></i> QRIS
            </button>
            <button type="button" class="sim-tab-btn" onclick="switchSimTab('va')">
                <i class="fa-solid fa-building-columns"></i> Transfer VA
            </button>
            <button type="button" class="sim-tab-btn" onclick="switchSimTab('card')">
                <i class="fa-solid fa-credit-card"></i> Kartu Kredit
            </button>
        </div>

        <!-- Tab 1: QRIS -->
        <div class="sim-tab-pane active" id="simPaneQris">
            <div class="qris-box">
                <div class="qris-header-logo">
                    <i class="fa-solid fa-qrcode text-accent"></i> QRIS NASIONAL
                </div>
                <div class="qris-qr-code">
                    <div class="qris-beam"></div>
                    <!-- Stylized SVG QR Dummy -->
                    <svg viewBox="0 0 100 100" width="130" height="130" fill="#111827">
                        <rect x="5" y="5" width="25" height="25" fill="#111827" />
                        <rect x="8" y="8" width="19" height="19" fill="#ffffff" />
                        <rect x="11" y="11" width="13" height="13" fill="#111827" />
                        
                        <rect x="70" y="5" width="25" height="25" fill="#111827" />
                        <rect x="73" y="8" width="19" height="19" fill="#ffffff" />
                        <rect x="76" y="11" width="13" height="13" fill="#111827" />

                        <rect x="5" y="70" width="25" height="25" fill="#111827" />
                        <rect x="8" y="73" width="19" height="19" fill="#ffffff" />
                        <rect x="11" y="76" width="13" height="13" fill="#111827" />

                        <!-- Random QR matrix dots -->
                        <rect x="35" y="10" width="6" height="6" />
                        <rect x="45" y="15" width="6" height="6" />
                        <rect x="55" y="10" width="6" height="6" />
                        <rect x="35" y="25" width="6" height="6" />
                        <rect x="50" y="30" width="6" height="6" />
                        <rect x="10" y="45" width="6" height="6" />
                        <rect x="25" y="40" width="6" height="6" />
                        <rect x="40" y="45" width="8" height="8" fill="#e50914" />
                        <rect x="55" y="45" width="6" height="6" />
                        <rect x="70" y="40" width="6" height="6" />
                        <rect x="85" y="45" width="6" height="6" />
                        <rect x="35" y="60" width="6" height="6" />
                        <rect x="50" y="65" width="6" height="6" />
                        <rect x="65" y="60" width="6" height="6" />
                        <rect x="40" y="75" width="6" height="6" />
                        <rect x="55" y="80" width="6" height="6" />
                        <rect x="70" y="75" width="6" height="6" />
                        <rect x="85" y="80" width="6" height="6" />
                    </svg>
                </div>
                <div class="qris-caption">
                    Scan via GoPay, BCA Mobile, ShopeePay, OVO, atau Dana.<br>
                    Total Tagihan: <strong id="simAmountTextQris" class="text-accent">Rp 99.000</strong>
                </div>
            </div>
            <div class="sim-actions-grid">
                <button type="button" class="btn-sim-success" onclick="confirmDummyPayment('qris')">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>Simulasi Scan QRIS & Bayar Sukses</span>
                </button>
            </div>
        </div>

        <!-- Tab 2: Virtual Account -->
        <div class="sim-tab-pane" id="simPaneVa">
            <div class="va-box">
                <div class="va-bank-select-grid">
                    <div class="va-bank-chip active" onclick="selectSimBank(this, 'bca', '8808 1290 8472 001')">BCA</div>
                    <div class="va-bank-chip" onclick="selectSimBank(this, 'mandiri', '8910 8820 9410 002')">MANDIRI</div>
                    <div class="va-bank-chip" onclick="selectSimBank(this, 'bni', '8277 0019 4811 003')">BNI</div>
                    <div class="va-bank-chip" onclick="selectSimBank(this, 'bri', '1280 0491 8274 004')">BRI</div>
                </div>
                <div style="font-size:0.75rem; color:#9ca3af; margin-bottom:6px;">Nomor Virtual Account:</div>
                <div class="va-number-card">
                    <span class="va-number-display" id="simVaNumber">8808 1290 8472 001</span>
                    <button type="button" class="btn-copy-va" onclick="copyVaNumber()">
                        <i class="fa-regular fa-copy"></i> Salin
                    </button>
                </div>
                <div style="font-size:0.8rem; color:#9ca3af; line-height:1.4;">
                    Transfer tepat nominal: <strong id="simAmountTextVa" class="text-accent">Rp 99.000</strong> ke nomor VA di atas.
                </div>
            </div>
            <div class="sim-actions-grid">
                <button type="button" class="btn-sim-success" onclick="confirmDummyPayment('bank_transfer')">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>Simulasi Transfer Bank Berhasil</span>
                </button>
            </div>
        </div>

        <!-- Tab 3: Kartu Kredit -->
        <div class="sim-tab-pane" id="simPaneCard">
            <div class="card-mockup-box">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <div class="card-chip"></div>
                    <i class="fa-brands fa-cc-visa" style="font-size:1.8rem; color:#38bdf8;"></i>
                </div>
                <div class="card-num-text">4811 •••• •••• 1111</div>
                <div class="card-holder-row">
                    <div>
                        <div style="font-size:0.65rem; color:#6b7280;">CARD HOLDER</div>
                        <div style="font-weight:700; color:#fff;"><?= strtoupper(htmlspecialchars($user['name'])) ?></div>
                    </div>
                    <div>
                        <div style="font-size:0.65rem; color:#6b7280;">EXPIRES</div>
                        <div style="font-weight:700; color:#fff;">12/28</div>
                    </div>
                </div>
            </div>
            <div class="sim-actions-grid">
                <button type="button" class="btn-sim-success" onclick="confirmDummyPayment('credit_card')">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>Simulasi Pembayaran Kartu Sukses</span>
                </button>
            </div>
        </div>

        <!-- Secondary Simulation Controls -->
        <div style="display:flex; gap:8px; margin-top:14px;">
            <button type="button" class="btn-sim-pending" style="flex:1;" onclick="confirmDummyPending()">
                <i class="fa-solid fa-clock"></i> Simulasi Status Pending
            </button>
            <button type="button" class="btn-sim-pending" style="flex:1; border-color:rgba(239,68,68,0.3); color:#f87171; background:rgba(239,68,68,0.1);" onclick="closeCinevaModal('modalMidtransDummy')">
                <i class="fa-solid fa-xmark"></i> Batalkan
            </button>
        </div>
    </div>
</div>


<!-- ==============================================
     MAIN SUBSCRIPTION PAGE CONTENT
     ============================================== -->
<div class="pricing-stage" id="pricingStage">
    <div class="container">

        <!-- Top Header -->
        <div class="pricing-hero">
            <div class="pricing-badge">
                <i class="fa-solid fa-crown"></i>
                <span>Cineva Streaming Pass • Midtrans Ready</span>
            </div>
            <h1 class="pricing-title">Pilih Pengalaman Sinematik Anda</h1>
            <p class="pricing-lead">Tonton semua judul favorit tanpa batasan dan tanpa iklan. Pembayaran otomatis dan aman melalui payment gateway Midtrans.</p>

            <?php if (hasActiveSubscription($conn)): ?>
            <div class="pricing-current-pill">
                <span class="pill-dot"></span>
                <span>
                    Paket Aktif Anda: <strong><?= isAdmin() ? 'Administrator (Akses Penuh)' : ucfirst($currentPlan) . ' Plan' ?></strong>
                    <?php if (!empty($customerDetail['subscription_expires_at'])): ?>
                        • Berlaku hingga: <strong><?= date('d M Y', strtotime($customerDetail['subscription_expires_at'])) ?></strong>
                    <?php endif; ?>
                </span>
                <a href="/cineva/" class="pill-return-link">Ke Beranda &rarr;</a>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="pricing-error-notice">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Plan Selection Cards -->
        <div class="plans-interactive-grid">

            <!-- 1. BASIC CARD -->
            <div class="plan-v2-card <?= $currentPlan === 'basic' ? 'is-current' : '' ?>" data-plan="basic" onclick="selectPlanCard('basic')">
                <div class="plan-v2-header">
                    <div class="plan-v2-radio">
                        <span class="radio-indicator"></span>
                    </div>
                    <div class="plan-v2-meta">
                        <span class="plan-v2-tag">Ekonomis</span>
                        <h3 class="plan-v2-name">Basic</h3>
                    </div>
                </div>
                <div class="plan-v2-desc">Cocok untuk penonton santai di ponsel atau laptop.</div>

                <div class="plan-v2-price">
                    <span class="currency">Rp</span>
                    <span class="amount">49.000</span>
                    <span class="freq">/bulan</span>
                </div>

                <div class="plan-v2-highlights">
                    <div class="highlight-pill"><i class="fa-solid fa-tv"></i> 1 Perangkat</div>
                    <div class="highlight-pill"><i class="fa-solid fa-video"></i> HD 720p</div>
                </div>

                <div class="plan-v2-features">
                    <div class="feature-row">
                        <i class="fa-solid fa-check feature-check"></i>
                        <span>Akses semua film & series</span>
                    </div>
                    <div class="feature-row">
                        <i class="fa-solid fa-check feature-check"></i>
                        <span>100% bebas jeda iklan</span>
                    </div>
                    <div class="feature-row">
                        <i class="fa-solid fa-check feature-check"></i>
                        <span>Dukungan PC, tablet & HP</span>
                    </div>
                    <div class="feature-row is-disabled">
                        <i class="fa-solid fa-xmark feature-cross"></i>
                        <span>Download tonton offline</span>
                    </div>
                </div>

                <div class="plan-v2-footer">
                    <button type="button" class="btn-card-select" onclick="openCheckoutModal(event, 'basic')">
                        <i class="fa-solid fa-credit-card"></i>
                        <?= $currentPlan === 'basic' ? 'Paket Aktif (Perpanjang)' : 'Pilih Basic' ?>
                    </button>
                </div>
            </div>

            <!-- 2. STANDARD CARD (POPULAR) -->
            <div class="plan-v2-card plan-v2-popular <?= $currentPlan === 'standard' ? 'is-current' : '' ?>" data-plan="standard" onclick="selectPlanCard('standard')">
                <div class="popular-ribbon">
                    <i class="fa-solid fa-fire"></i> Paling Populer
                </div>

                <div class="plan-v2-header">
                    <div class="plan-v2-radio">
                        <span class="radio-indicator"></span>
                    </div>
                    <div class="plan-v2-meta">
                        <span class="plan-v2-tag tag-popular">Rekomendasi</span>
                        <h3 class="plan-v2-name">Standard</h3>
                    </div>
                </div>
                <div class="plan-v2-desc">Pilihan terbaik untuk keluarga dengan gambar jernih & Dolby.</div>

                <div class="plan-v2-price">
                    <span class="currency">Rp</span>
                    <span class="amount">99.000</span>
                    <span class="freq">/bulan</span>
                </div>

                <div class="plan-v2-highlights">
                    <div class="highlight-pill"><i class="fa-solid fa-tv"></i> 2 Perangkat</div>
                    <div class="highlight-pill"><i class="fa-solid fa-video"></i> Full HD 1080p</div>
                    <div class="highlight-pill"><i class="fa-solid fa-volume-high"></i> Dolby Audio</div>
                </div>

                <div class="plan-v2-features">
                    <div class="feature-row">
                        <i class="fa-solid fa-check feature-check"></i>
                        <span>Akses semua film & series</span>
                    </div>
                    <div class="feature-row">
                        <i class="fa-solid fa-check feature-check"></i>
                        <span>100% bebas jeda iklan</span>
                    </div>
                    <div class="feature-row">
                        <i class="fa-solid fa-check feature-check"></i>
                        <span>Download 10 film untuk offline</span>
                    </div>
                    <div class="feature-row">
                        <i class="fa-solid fa-check feature-check"></i>
                        <span>Audio jernih Dolby Digital</span>
                    </div>
                </div>

                <div class="plan-v2-footer">
                    <button type="button" class="btn-card-select btn-card-popular" onclick="openCheckoutModal(event, 'standard')">
                        <i class="fa-solid fa-credit-card"></i>
                        <?= $currentPlan === 'standard' ? 'Paket Aktif (Perpanjang)' : 'Pilih Standard' ?>
                    </button>
                </div>
            </div>

            <!-- 3. PREMIUM CARD -->
            <div class="plan-v2-card <?= $currentPlan === 'premium' ? 'is-current' : '' ?>" data-plan="premium" onclick="selectPlanCard('premium')">
                <div class="plan-v2-header">
                    <div class="plan-v2-radio">
                        <span class="radio-indicator"></span>
                    </div>
                    <div class="plan-v2-meta">
                        <span class="plan-v2-tag tag-premium">Ultra Cinema</span>
                        <h3 class="plan-v2-name">Premium</h3>
                    </div>
                </div>
                <div class="plan-v2-desc">Pengalaman bioskop di rumah dengan kualitas visual 4K HDR.</div>

                <div class="plan-v2-price">
                    <span class="currency">Rp</span>
                    <span class="amount">149.000</span>
                    <span class="freq">/bulan</span>
                </div>

                <div class="plan-v2-highlights">
                    <div class="highlight-pill"><i class="fa-solid fa-tv"></i> 4 Perangkat</div>
                    <div class="highlight-pill"><i class="fa-solid fa-video"></i> 4K Ultra HD + HDR</div>
                    <div class="highlight-pill"><i class="fa-solid fa-headphones"></i> Dolby Atmos</div>
                </div>

                <div class="plan-v2-features">
                    <div class="feature-row">
                        <i class="fa-solid fa-check feature-check"></i>
                        <span>Akses semua film & series</span>
                    </div>
                    <div class="feature-row">
                        <i class="fa-solid fa-check feature-check"></i>
                        <span>Kualitas tertinggi 4K UHD + HDR</span>
                    </div>
                    <div class="feature-row">
                        <i class="fa-solid fa-check feature-check"></i>
                        <span>Audio Spasial & Dolby Atmos 3D</span>
                    </div>
                    <div class="feature-row">
                        <i class="fa-solid fa-check feature-check"></i>
                        <span>Download offline tanpa batas</span>
                    </div>
                </div>

                <div class="plan-v2-footer">
                    <button type="button" class="btn-card-select" onclick="openCheckoutModal(event, 'premium')">
                        <i class="fa-solid fa-credit-card"></i>
                        <?= $currentPlan === 'premium' ? 'Paket Aktif (Perpanjang)' : 'Pilih Premium' ?>
                    </button>
                </div>
            </div>

        </div><!-- /plans-interactive-grid -->

        <!-- Dynamic Bottom Action Bar -->
        <div class="pricing-cta-card">
            <div class="cta-left">
                <div class="cta-title">Paket yang dipilih: <span id="ctaPlanName" class="text-accent">Standard</span></div>
                <div class="cta-price" id="ctaPlanPrice">Rp 99.000 / bulan • Pembayaran Aman via Midtrans</div>
            </div>
            <div class="cta-right">
                <button type="button" class="btn-main-checkout" id="btnMainCheckout" onclick="openCheckoutModal(event, currentSelectedPlan)">
                    <span>Lanjutkan ke Pembayaran</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </button>
            </div>
        </div>

        <!-- Trust Badges Bar -->
        <div class="trust-grid">
            <div class="trust-item">
                <i class="fa-solid fa-shield-check"></i>
                <div>
                    <div class="trust-title">Midtrans Verified</div>
                    <div class="trust-sub">Enkripsi 256-bit standar PCI-DSS</div>
                </div>
            </div>
            <div class="trust-item">
                <i class="fa-solid fa-bolt"></i>
                <div>
                    <div class="trust-title">Aktivasi Otomatis</div>
                    <div class="trust-sub">Akses langsung terbuka seketika</div>
                </div>
            </div>
            <div class="trust-item">
                <i class="fa-solid fa-arrows-rotate"></i>
                <div>
                    <div class="trust-title">Multi Metode Bayar</div>
                    <div class="trust-sub">QRIS, VA Bank & Kartu Kredit</div>
                </div>
            </div>
            <div class="trust-item">
                <i class="fa-solid fa-circle-question"></i>
                <div>
                    <div class="trust-title">Bantuan 24/7</div>
                    <div class="trust-sub">Layanan konsumen siap membantu</div>
                </div>
            </div>
        </div>

        <!-- Riwayat Transaksi Langganan Anda -->
        <div class="trx-history-section">
            <div class="trx-history-header">
                <div class="trx-history-title">
                    <i class="fa-solid fa-receipt text-accent"></i>
                    <span>Riwayat Transaksi Langganan</span>
                </div>
                <span style="font-size:0.8rem; color:#9ca3af;">Terakhir Diperbarui</span>
            </div>

            <?php if ($historyList && $historyList->num_rows > 0): ?>
            <div style="overflow-x:auto;">
                <table class="data-table" style="width:100%; border-collapse:collapse;">
                    <thead>
                        <tr>
                            <th style="text-align:left; padding:12px;">Order ID</th>
                            <th style="text-align:left; padding:12px;">Paket</th>
                            <th style="text-align:left; padding:12px;">Nominal</th>
                            <th style="text-align:left; padding:12px;">Metode</th>
                            <th style="text-align:left; padding:12px;">Status</th>
                            <th style="text-align:left; padding:12px;">Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $historyList->fetch_assoc()): ?>
                        <tr style="border-bottom:1px solid rgba(255,255,255,0.06);">
                            <td style="padding:12px; font-family:monospace; color:#38bdf8; font-size:0.82rem;">
                                <?= htmlspecialchars($row['order_id']) ?>
                            </td>
                            <td style="padding:12px; font-weight:600; color:#fff;">
                                <?= ucfirst($row['plan']) ?> Plan
                            </td>
                            <td style="padding:12px; color:#d1d5db; font-weight:600;">
                                Rp <?= number_format($row['amount'], 0, ',', '.') ?>
                            </td>
                            <td style="padding:12px; text-transform:uppercase; font-size:0.8rem; color:#9ca3af;">
                                <?= htmlspecialchars($row['payment_type'] ?? 'Midtrans') ?>
                            </td>
                            <td style="padding:12px;">
                                <span class="badge-trx-status <?= htmlspecialchars($row['transaction_status']) ?>">
                                    <?php if ($row['transaction_status'] === 'settlement' || $row['transaction_status'] === 'capture'): ?>
                                        <i class="fa-solid fa-circle-check"></i> Berhasil
                                    <?php elseif ($row['transaction_status'] === 'pending'): ?>
                                        <i class="fa-solid fa-clock"></i> Menunggu
                                    <?php else: ?>
                                        <i class="fa-solid fa-circle-xmark"></i> <?= ucfirst($row['transaction_status']) ?>
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td style="padding:12px; font-size:0.82rem; color:#9ca3af;">
                                <?= date('d M Y H:i', strtotime($row['created_at'])) ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div style="text-align:center; padding:30px; color:#9ca3af; font-size:0.9rem;">
                <i class="fa-solid fa-inbox" style="font-size:2rem; margin-bottom:10px; opacity:0.5; display:block;"></i>
                Belum ada riwayat transaksi pembayaran. Pilih paket di atas untuk memulai langganan.
            </div>
            <?php endif; ?>
        </div>

        <!-- FAQ Accordion -->
        <div class="pricing-faq-section" style="margin-top:50px;">
            <h2 class="faq-section-title">Pertanyaan Umum Seputar Langganan & Midtrans</h2>
            <div class="faq-accordion-list">
                <div class="faq-row" onclick="toggleFaqRow(this)">
                    <div class="faq-row-q">
                        <span>Bagaimana cara pembayaran langganan melalui Midtrans?</span>
                        <i class="fa-solid fa-chevron-down faq-arrow"></i>
                    </div>
                    <div class="faq-row-a">
                        Pilih paket langganan yang Anda inginkan, klik tombol Lanjutkan, lalu jendela pembayaran Midtrans Snap akan muncul. Anda dapat memilih QRIS (GoPay, OVO, Dana), Transfer Bank (Virtual Account BCA, BNI, BRI, Mandiri), atau Kartu Kredit.
                    </div>
                </div>
                <div class="faq-row" onclick="toggleFaqRow(this)">
                    <div class="faq-row-q">
                        <span>Apakah bisa mencoba pembayaran dengan simulasi dummy di XAMPP?</span>
                        <i class="fa-solid fa-chevron-down faq-arrow"></i>
                    </div>
                    <div class="faq-row-a">
                        Tentu! Kami telah menyediakan fitur simulator Midtrans Sandbox bawaan. Anda dapat menekan tombol "Simulasi Instan Dummy" untuk menguji aktivasi paket langganan secara langsung di localhost XAMPP tanpa perlu kartu kredit asli ataupun pulsa.
                    </div>
                </div>
                <div class="faq-row" onclick="toggleFaqRow(this)">
                    <div class="faq-row-q">
                        <span>Apakah masa aktif langganan langsung bertambah setelah bayar?</span>
                        <i class="fa-solid fa-chevron-down faq-arrow"></i>
                    </div>
                    <div class="faq-row-a">
                        Ya, saat status pembayaran berubah menjadi settlement, akun Anda otomatis aktif selama 30 hari ke depan, dan Anda dapat langsung menonton seluruh katalog film Cineva.
                    </div>
                </div>
            </div>
        </div>

    </div><!-- /container -->
</div><!-- /pricing-stage -->

<script>
const planDetails = {
    'basic':    { name: 'Basic Plan',    price: 'Rp 49.000', amount: 49000 },
    'standard': { name: 'Standard Plan', price: 'Rp 99.000', amount: 99000 },
    'premium':  { name: 'Premium Plan',  price: 'Rp 149.000', amount: 149000 }
};

let currentSelectedPlan = 'standard';
let currentOrderId = '';
let currentSnapToken = '';
let isDummyMode = false;

function selectPlanCard(plan) {
    currentSelectedPlan = plan;
    document.querySelectorAll('.plan-v2-card').forEach(card => {
        card.classList.remove('is-selected');
    });

    const activeCard = document.querySelector(`.plan-v2-card[data-plan="${plan}"]`);
    if (activeCard) {
        activeCard.classList.add('is-selected');
    }

    const info = planDetails[plan] || planDetails['standard'];
    document.getElementById('ctaPlanName').textContent = info.name;
    document.getElementById('ctaPlanPrice').textContent = info.price + ' / bulan • Pembayaran Aman via Midtrans';
}

function openCheckoutModal(event, plan) {
    if (event) event.stopPropagation();
    selectPlanCard(plan);

    const info = planDetails[plan] || planDetails['standard'];
    document.getElementById('modalCheckoutPlanName').textContent = info.name;
    document.getElementById('modalCheckoutTotal').textContent = info.price;

    const modal = document.getElementById('modalMidtransCheckout');
    modal.classList.add('active');
}

// Panggil backend API untuk membuat snap token Midtrans
async function triggerMidtransPayment() {
    const btn = document.getElementById('btnLaunchSnap');
    const textEl = document.getElementById('btnSnapText');
    const oldText = textEl.textContent;
    btn.disabled = true;
    textEl.textContent = 'Menghubungkan ke Midtrans...';

    try {
        const resp = await fetch('/cineva/api/midtrans-token.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ plan: currentSelectedPlan })
        });
        const data = await resp.json();

        if (data.status === 'success') {
            currentOrderId = data.order_id;
            currentSnapToken = data.snap_token;
            isDummyMode = data.is_dummy;

            // Jika Midtrans Snap JS terpasang dan bukan mode pure dummy fallback
            if (window.snap && !isDummyMode) {
                closeCinevaModal('modalMidtransCheckout');
                window.snap.pay(data.snap_token, {
                    onSuccess: function(result) {
                        handlePaymentFinished(result, 'settlement');
                    },
                    onPending: function(result) {
                        handlePaymentFinished(result, 'pending');
                    },
                    onError: function(result) {
                        alert('Pembayaran Midtrans gagal atau dibatalkan.');
                        console.error('Midtrans Error:', result);
                    },
                    onClose: function() {
                        console.log('Customer menutup popup Midtrans Snap tanpa menyelesaikan pembayaran.');
                    }
                });
            } else {
                // Gunakan Simulator Midtrans Dummy bawaan (Sangat mulus untuk pengujian di XAMPP)
                closeCinevaModal('modalMidtransCheckout');
                openSimulatorModal(data.order_id, data.plan, data.amount_formatted);
            }
        } else {
            alert(data.message || 'Gagal membuat tiket transaksi Midtrans.');
        }
    } catch (err) {
        console.error('Network Error:', err);
        // Fallback langsung ke Dummy Simulator jika cURL atau koneksi internet offline
        closeCinevaModal('modalMidtransCheckout');
        const fallbackOrderId = 'CNV-' + currentSelectedPlan.substring(0, 3).toUpperCase() + '-' + Date.now();
        openSimulatorModal(fallbackOrderId, currentSelectedPlan, (planDetails[currentSelectedPlan] || planDetails.standard).price);
    } finally {
        btn.disabled = false;
        textEl.textContent = oldText;
    }
}

// Buka Simulator Dummy
function triggerDummySimulator() {
    closeCinevaModal('modalMidtransCheckout');
    // Request order id dari server atau buat dummy order
    fetch('/cineva/api/midtrans-token.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ plan: currentSelectedPlan })
    })
    .then(r => r.json())
    .then(data => {
        const oid = (data && data.order_id) ? data.order_id : ('CNV-' + currentSelectedPlan.toUpperCase() + '-' + Date.now());
        const amt = (data && data.amount_formatted) ? data.amount_formatted : (planDetails[currentSelectedPlan].price);
        openSimulatorModal(oid, currentSelectedPlan, amt);
    })
    .catch(() => {
        const oid = 'CNV-' + currentSelectedPlan.toUpperCase() + '-' + Date.now();
        openSimulatorModal(oid, currentSelectedPlan, (planDetails[currentSelectedPlan].price));
    });
}

function openSimulatorModal(orderId, plan, amountFormatted) {
    currentOrderId = orderId;
    document.getElementById('simOrderIdDisplay').textContent = orderId;
    document.getElementById('simAmountTextQris').textContent = amountFormatted;
    document.getElementById('simAmountTextVa').textContent = amountFormatted;
    document.getElementById('modalMidtransDummy').classList.add('active');
}

// Kirim konfirmasi pembayaran selesai ke server
async function handlePaymentFinished(result, targetStatus) {
    try {
        const payload = {
            order_id: (result && result.order_id) ? result.order_id : currentOrderId,
            transaction_status: targetStatus,
            payment_type: (result && result.payment_type) ? result.payment_type : 'midtrans',
            raw_result: result
        };

        const resp = await fetch('/cineva/api/midtrans-finish.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const resData = await resp.json();

        if (resData.status === 'success') {
            window.location.href = resData.redirect;
        } else {
            alert(resData.message || 'Pembayaran dicatat: ' + targetStatus);
            window.location.reload();
        }
    } catch (e) {
        console.error('Finish Error:', e);
        window.location.href = '/cineva/subscription.php?success=' + encodeURIComponent(currentSelectedPlan) + '&order_id=' + encodeURIComponent(currentOrderId);
    }
}

// Konfirmasi pembayaran dari tombol simulator dummy
async function confirmDummyPayment(paymentType) {
    const modal = document.getElementById('modalMidtransDummy');
    modal.classList.add('closing');
    
    await handlePaymentFinished({
        order_id: currentOrderId,
        payment_type: paymentType,
        transaction_status: 'settlement',
        gross_amount: (planDetails[currentSelectedPlan] || planDetails.standard).amount
    }, 'settlement');
}

// Konfirmasi status pending dari simulator dummy
async function confirmDummyPending() {
    await handlePaymentFinished({
        order_id: currentOrderId,
        payment_type: 'bank_transfer',
        transaction_status: 'pending'
    }, 'pending');
}

// Tab switcher di simulator dummy
function switchSimTab(tab) {
    document.querySelectorAll('.sim-tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.sim-tab-pane').forEach(pane => pane.classList.remove('active'));

    if (tab === 'qris') {
        document.querySelectorAll('.sim-tab-btn')[0].classList.add('active');
        document.getElementById('simPaneQris').classList.add('active');
    } else if (tab === 'va') {
        document.querySelectorAll('.sim-tab-btn')[1].classList.add('active');
        document.getElementById('simPaneVa').classList.add('active');
    } else if (tab === 'card') {
        document.querySelectorAll('.sim-tab-btn')[2].classList.add('active');
        document.getElementById('simPaneCard').classList.add('active');
    }
}

function selectSimBank(el, bank, vaNum) {
    document.querySelectorAll('.va-bank-chip').forEach(c => c.classList.remove('active'));
    el.classList.add('active');
    document.getElementById('simVaNumber').textContent = vaNum;
}

function copyVaNumber() {
    const text = document.getElementById('simVaNumber').textContent.replace(/\s+/g, '');
    navigator.clipboard.writeText(text).then(() => {
        alert('Nomor Virtual Account ' + text + ' berhasil disalin ke clipboard!');
    }).catch(() => {
        alert('Nomor VA: ' + text);
    });
}

function closeCinevaModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('closing');
        setTimeout(() => {
            modal.classList.remove('active');
            modal.classList.remove('closing');
        }, 200);
    }
}

function toggleFaqRow(row) {
    row.classList.toggle('open');
}

// Inisialisasi kartu paket saat pertama kali halaman dibuka
document.addEventListener('DOMContentLoaded', () => {
    const initialPlan = '<?= in_array($currentPlan, ['basic', 'standard', 'premium']) ? $currentPlan : 'standard' ?>';
    selectPlanCard(initialPlan);
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>

// Kirim konfirmasi pembayaran selesai ke server
async function handlePaymentFinished(result, targetStatus) {
    try {
        const payload = {
            order_id: (result && result.order_id) ? result.order_id : currentOrderId,
            transaction_status: targetStatus,
            payment_type: (result && result.payment_type) ? result.payment_type : 'midtrans',
            raw_result: result
        };

        const resp = await fetch('/cineva/api/midtrans-finish.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const resData = await resp.json();

        if (resData.status === 'success') {
            window.location.href = resData.redirect;
        } else {
            alert(resData.message || 'Pembayaran dicatat: ' + targetStatus);
            window.location.reload();
        }
    } catch (e) {
        console.error('Finish Error:', e);
        window.location.href = '/cineva/subscription.php?success=' + encodeURIComponent(currentSelectedPlan) + '&order_id=' + encodeURIComponent(currentOrderId);
    }
}

// Konfirmasi pembayaran dari tombol simulator dummy
async function confirmDummyPayment(paymentType) {
    const modal = document.getElementById('modalMidtransDummy');
    modal.classList.add('closing');
    
    await handlePaymentFinished({
        order_id: currentOrderId,
        payment_type: paymentType,
        transaction_status: 'settlement',
        gross_amount: (planDetails[currentSelectedPlan] || planDetails.standard).amount
    }, 'settlement');
}

// Konfirmasi status pending dari simulator dummy
async function confirmDummyPending() {
    await handlePaymentFinished({
        order_id: currentOrderId,
        payment_type: 'bank_transfer',
        transaction_status: 'pending'
    }, 'pending');
}

// Tab switcher di simulator dummy
function switchSimTab(tab) {
    document.querySelectorAll('.sim-tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.sim-tab-pane').forEach(pane => pane.classList.remove('active'));

    if (tab === 'qris') {
        document.querySelectorAll('.sim-tab-btn')[0].classList.add('active');
        document.getElementById('simPaneQris').classList.add('active');
    } else if (tab === 'va') {
        document.querySelectorAll('.sim-tab-btn')[1].classList.add('active');
        document.getElementById('simPaneVa').classList.add('active');
    } else if (tab === 'card') {
        document.querySelectorAll('.sim-tab-btn')[2].classList.add('active');
        document.getElementById('simPaneCard').classList.add('active');
    }
}

function selectSimBank(el, bank, vaNum) {
    document.querySelectorAll('.va-bank-chip').forEach(c => c.classList.remove('active'));
    el.classList.add('active');
    document.getElementById('simVaNumber').textContent = vaNum;
}

function copyVaNumber() {
    const text = document.getElementById('simVaNumber').textContent.replace(/\s+/g, '');
    navigator.clipboard.writeText(text).then(() => {
        alert('Nomor Virtual Account ' + text + ' berhasil disalin ke clipboard!');
    }).catch(() => {
        alert('Nomor VA: ' + text);
    });
}

function closeCinevaModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('closing');
        setTimeout(() => {
            modal.classList.remove('active');
            modal.classList.remove('closing');
        }, 200);
    }
}

function toggleFaqRow(row) {
    row.classList.toggle('open');
}

// Inisialisasi kartu paket saat pertama kali halaman dibuka
document.addEventListener('DOMContentLoaded', () => {
    const initialPlan = '<?= in_array($currentPlan, ['basic', 'standard', 'premium']) ? $currentPlan : 'standard' ?>';
    selectPlanCard(initialPlan);
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
