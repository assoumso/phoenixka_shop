<?php
require_once dirname(__DIR__) . '/includes/functions.php';
requireLogin();

$user = getCurrentUser();
$store = getCurrentStore();
if (!$store) { redirect(SITE_URL . '/dashboard/'); }

$db = getDB();
$success = '';
$error = '';

$enabledPayments = getEnabledPaymentMethods();

// Handle Payout Request Submission (On-demand mode)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_payout'])) {
    $amount = floatval($_POST['amount'] ?? 0);
    $walletBalance = floatval($store['virtual_wallet'] ?? 0);
    $paymentNumber = getActivePaymentNumber($store);
    $paymentMethod = sanitize($_POST['payment_method'] ?? 'Wave');

    $methodKeyMap = [
        'Wave' => 'wave',
        'Orange Money' => 'orange_money',
        'MTN MoMo' => 'mobile_money'
    ];
    $reqKey = $methodKeyMap[$paymentMethod] ?? 'wave';

    if (empty($enabledPayments[$reqKey])) {
        $error = '🔒 Le moyen de décaissement choisi (' . htmlspecialchars($paymentMethod) . ') est actuellement verrouillé au niveau de la plateforme par l\'administrateur.';
    } else if ($amount <= 0) {
        $error = 'Veuillez entrer un montant valide.';
    } else if ($amount > $walletBalance) {
        $error = 'Le montant demandé dépasse votre solde disponible (' . formatPrice($walletBalance) . ').';
    } else if (empty($paymentNumber)) {
        $error = 'Veuillez d\'abord renseigner votre numéro Mobile Money dans les paramètres de la boutique.';
    } else {
        // Commission / Frais (2.5% PHENIXKA + 1% Opérateur = 3.5%)
        $feeRate = 0.035;
        $feeAmount = round($amount * $feeRate, 2);
        $netAmount = round($amount - $feeAmount, 2);

        // Deduct from virtual wallet
        $db->prepare("UPDATE stores SET virtual_wallet = virtual_wallet - ? WHERE id = ?")->execute([$amount, $store['id']]);

        // Insert payout request
        $stmt = $db->prepare("INSERT INTO payout_requests (store_id, amount_gross, fee_amount, amount_net, payment_method, payment_number, payout_type, status) VALUES (?, ?, ?, ?, ?, ?, 'on_demand', 'pending')");
        $stmt->execute([$store['id'], $amount, $feeAmount, $netAmount, $paymentMethod, $paymentNumber]);

        // Notifications
        addNotification('merchant', $store['id'], $user['id'], 'payout_requested', 
            '📥 Demande de retrait enregistrée', 
            "Votre demande de retrait de " . formatPrice($amount) . " (Net à recevoir: " . formatPrice($netAmount) . ") a été enregistrée avec succès. Traitement sous 24h."
        );

        addNotification('admin', $store['id'], null, 'payout_requested_admin', 
            '🔔 Nouvelle demande de retrait (' . $store['name'] . ')', 
            "La boutique {$store['name']} demande un retrait de " . formatPrice($netAmount) . " vers le numéro $paymentNumber ($paymentMethod)."
        );

        $success = 'Votre demande de retrait a été enregistrée avec succès ! Elle sera traitée sous 24 heures.';
        $store = getCurrentStore(); // Refresh wallet balance
    }
}

// Fetch merchant's payout requests
$stmtPayouts = $db->prepare("SELECT * FROM payout_requests WHERE store_id = ? ORDER BY created_at DESC");
$stmtPayouts->execute([$store['id']]);
$payoutRequests = $stmtPayouts->fetchAll();

// Include sidebar active state
$currentPage = 'wallet';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portefeuille & Décaissements — PhoenixKA Shop</title>
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/dashboard.css">
    <link rel="icon" href="<?= ASSETS_URL ?>/images/logo.png">
    <style>
        .wallet-banner{background:linear-gradient(135deg, rgba(234, 179, 8, 0.15), rgba(0, 180, 216, 0.15));border:1px solid var(--border-gold);border-radius:20px;padding:28px;margin-bottom:30px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:20px}
        .wallet-balance-num{font-size:2.4rem;font-weight:800;color:var(--gold);margin-top:6px}

        /* Payout History List Styling */
        .payout-history-list { display: flex; flex-direction: column; }
        .payout-item { display: flex; align-items: center; justify-content: space-between; padding: 18px 24px; border-bottom: 1px solid var(--border-color); transition: background 0.2s ease; gap: 16px; }
        .payout-item:last-child { border-bottom: none; }
        .payout-item:hover { background: rgba(234, 179, 8, 0.03); }
        .payout-item-left { display: flex; align-items: center; gap: 14px; min-width: 260px; }
        .payout-icon-box { width: 44px; height: 44px; border-radius: 12px; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); display: flex; align-items: center; justify-content: center; padding: 6px; flex-shrink: 0; }
        .payout-icon-box img { max-width: 100%; max-height: 100%; object-fit: contain; }
        .payout-method-name { font-weight: 700; font-size: 0.95rem; color: var(--text-primary); display: flex; align-items: center; gap: 8px; }
        .payout-type-pill { font-size: 0.7rem; font-weight: 600; background: rgba(255, 255, 255, 0.07); color: var(--text-secondary); padding: 2px 8px; border-radius: 50px; }
        .payout-details { font-size: 0.8rem; color: var(--text-muted); margin-top: 3px; display: flex; align-items: center; gap: 6px; }
        .dot-separator { color: rgba(255, 255, 255, 0.2); }
        .payout-item-center { display: flex; align-items: center; justify-content: center; }
        .status-pill { padding: 6px 14px; border-radius: 50px; font-size: 0.78rem; font-weight: 700; white-space: nowrap; }
        .status-pill.status-pending { background: rgba(234, 179, 8, 0.12); color: #EAB308; border: 1px solid rgba(234, 179, 8, 0.3); }
        .status-pill.status-approved { background: rgba(59, 130, 246, 0.12); color: #3B82F6; border: 1px solid rgba(59, 130, 246, 0.3); }
        .status-pill.status-paid { background: rgba(34, 197, 94, 0.12); color: #22C55E; border: 1px solid rgba(34, 197, 94, 0.3); }
        .status-pill.status-rejected { background: rgba(239, 68, 68, 0.12); color: #EF4444; border: 1px solid rgba(239, 68, 68, 0.3); }
        .payout-item-right { text-align: right; min-width: 160px; }
        .payout-amount-net { font-size: 1.15rem; font-weight: 800; color: var(--gold); letter-spacing: -0.3px; }
        .payout-amount-sub { font-size: 0.78rem; color: var(--text-muted); margin-top: 2px; }
        .fee-text { color: var(--danger); }
        @media (max-width: 768px) {
            .payout-item { flex-direction: column; align-items: flex-start; gap: 12px; }
            .payout-item-right { text-align: left; width: 100%; display: flex; justify-content: space-between; align-items: center; border-top: 1px dashed rgba(255, 255, 255, 0.08); padding-top: 8px; }
        }
    </style>
</head>
<body>
<div class="dashboard-layout">
    <?php 
    $currentPage = 'wallet'; 
    include dirname(__DIR__) . '/includes/sidebar.php'; 
    ?>

    <main class="dashboard-main">
        <div class="dashboard-topbar">
            <div class="topbar-left">
                <h2>💰 Portefeuille Virtuel &amp; Décaissements</h2>
                <p>Gérez vos gains et effectuez vos retours de trésorerie</p>
            </div>
            <div class="topbar-right">
                <a href="<?= SITE_URL ?>/dashboard/store-settings" class="btn btn-ghost btn-sm">⚙️ Mode de décaissement (<?= ($store['payout_mode'] ?? 'auto') === 'auto' ? 'Automatique' : 'Sur Demande' ?>)</a>
            </div>
        </div>

        <div class="dashboard-content">
            <?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

            <!-- WALLET BALANCE CARD -->
            <div class="wallet-banner">
                <div>
                    <div style="text-transform:uppercase;letter-spacing:1px;font-size:0.8rem;color:var(--text-muted);font-weight:700">Solde Portefeuille Disponible</div>
                    <div class="wallet-balance-num"><?= formatPrice($store['virtual_wallet'] ?? 0) ?></div>
                    <div style="font-size:0.85rem;color:var(--text-muted);margin-top:6px">
                        Mode actif : <strong><?= ($store['payout_mode'] ?? 'auto') === 'auto' ? '⚡ Paiement Automatique' : '💰 Paiement Sur Demande' ?></strong>
                        • Numéro d'encaissement : <strong><?= htmlspecialchars(getActivePaymentNumber($store) ?: 'Non configuré') ?></strong>
                    </div>
                </div>

                <div>
                    <?php if (($store['payout_mode'] ?? 'auto') === 'on_demand'): ?>
                        <button type="button" class="btn btn-primary btn-lg" onclick="document.getElementById('payoutModal').style.display='flex'">
                            💸 Demander un décaissement
                        </button>
                    <?php else: ?>
                        <a href="<?= SITE_URL ?>/dashboard/store-settings" class="btn btn-secondary btn-md">
                            ⚙️ Modifier le mode de paiement
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- PAYOUT REQUESTS LIST -->
            <div class="dash-card">
                <div class="dash-card-header">
                    <h3>📜 Historique des Décaissements &amp; Retraits</h3>
                </div>
                <div class="dash-card-body" style="padding:0">
                    <?php if (empty($payoutRequests)): ?>
                        <div style="padding:40px;text-align:center;color:var(--text-muted)">
                            <div style="font-size:3rem;margin-bottom:12px">💳</div>
                            Aucune demande de décaissement pour le moment.
                        </div>
                    <?php else: ?>
                        <div class="payout-history-list">
                            <?php foreach ($payoutRequests as $pr): 
                                $methodLower = strtolower($pr['payment_method']);
                                $logoSrc = ASSETS_URL . '/images/payments/';
                                if (strpos($methodLower, 'wave') !== false) {
                                    $logoSrc .= 'wave.png';
                                    $methodLabel = 'Wave Direct';
                                } elseif (strpos($methodLower, 'orange') !== false) {
                                    $logoSrc .= 'orange-money.png';
                                    $methodLabel = 'Orange Money';
                                } elseif (strpos($methodLower, 'mtn') !== false || strpos($methodLower, 'mobile') !== false) {
                                    $logoSrc .= 'mtn-momo.png';
                                    $methodLabel = 'MTN MoMo';
                                } else {
                                    $logoSrc .= 'cash.png';
                                    $methodLabel = htmlspecialchars($pr['payment_method']);
                                }
                            ?>
                            <div class="payout-item">
                                <div class="payout-item-left">
                                    <div class="payout-icon-box">
                                        <img src="<?= $logoSrc ?>" alt="<?= $methodLabel ?>" onerror="this.src='<?= ASSETS_URL ?>/images/logo.png'">
                                    </div>
                                    <div class="payout-meta">
                                        <div class="payout-method-name">
                                            <?= $methodLabel ?>
                                            <span class="payout-type-pill"><?= $pr['payout_type'] === 'auto' ? '⚡ Automatique' : '💰 Sur Demande' ?></span>
                                        </div>
                                        <div class="payout-details">
                                            <span>N° <?= htmlspecialchars($pr['payment_number']) ?></span>
                                            <span class="dot-separator">•</span>
                                            <span><?= date('d/m/Y à H:i', strtotime($pr['created_at'])) ?></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="payout-item-center">
                                    <?php if ($pr['status'] === 'pending'): ?>
                                        <span class="status-pill status-pending">⏳ En attente de validation</span>
                                    <?php elseif ($pr['status'] === 'approved'): ?>
                                        <span class="status-pill status-approved">🔵 Transfert imminent</span>
                                    <?php elseif ($pr['status'] === 'paid'): ?>
                                        <span class="status-pill status-paid">🟢 Payé / Effectué</span>
                                    <?php else: ?>
                                        <span class="status-pill status-rejected">🔴 Refusé</span>
                                    <?php endif; ?>
                                </div>

                                <div class="payout-item-right">
                                    <div class="payout-amount-net">+ <?= formatPrice($pr['amount_net']) ?></div>
                                    <div class="payout-amount-sub">
                                        Brut: <?= formatPrice($pr['amount_gross']) ?> <span class="fee-text">(-<?= formatPrice($pr['fee_amount']) ?> frais)</span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- PAYOUT MODAL -->
<div id="payoutModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);z-index:999;align-items:center;justify-content:center">
    <div style="background:var(--bg-card);border:1px solid var(--border-gold);border-radius:20px;padding:30px;max-width:500px;width:90%;position:relative">
        <h3 style="margin-bottom:16px;color:var(--gold)">💸 Demande de Décaissement</h3>
        <p style="color:var(--text-muted);font-size:0.9rem;margin-bottom:20px">
            Entrez le montant que vous souhaitez retirer de votre solde virtuel (<strong><?= formatPrice($store['virtual_wallet'] ?? 0) ?></strong>).
        </p>

        <form method="POST">
            <input type="hidden" name="request_payout" value="1">
            <div class="form-group">
                <label>Montant à retirer (<?= DEFAULT_CURRENCY ?>) *</label>
                <input type="number" name="amount" class="form-control" value="<?= floatval($store['virtual_wallet'] ?? 0) ?>" max="<?= floatval($store['virtual_wallet'] ?? 0) ?>" min="500" required>
            </div>
            
            <div class="form-group">
                <label>Opérateur de réception</label>
                <select name="payment_method" class="form-control">
                    <option value="Wave" <?= empty($enabledPayments['wave']) ? 'disabled' : '' ?>>🌊 Wave Direct <?= empty($enabledPayments['wave']) ? '🔒 (Verrouillé par Admin)' : '' ?></option>
                    <option value="Orange Money" <?= empty($enabledPayments['orange_money']) ? 'disabled' : '' ?>>🍊 Orange Money <?= empty($enabledPayments['orange_money']) ? '🔒 (Verrouillé par Admin)' : '' ?></option>
                    <option value="MTN MoMo" <?= empty($enabledPayments['mobile_money']) ? 'disabled' : '' ?>>🟡 MTN Mobile Money <?= empty($enabledPayments['mobile_money']) ? '🔒 (Verrouillé par Admin)' : '' ?></option>
                </select>
            </div>

            <div class="form-group">
                <label>Numéro de téléphone de réception</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars(getActivePaymentNumber($store)) ?>" readonly style="opacity:0.7">
                <small style="color:var(--text-muted)">Le transfert sera envoyé à ce numéro défini dans vos paramètres.</small>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:12px;margin-top:24px">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('payoutModal').style.display='none'">Annuler</button>
                <button type="submit" class="btn btn-primary">✓ Valider la demande</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>

