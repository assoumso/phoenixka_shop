<?php
require_once dirname(__DIR__) . '/includes/functions.php';
requireLogin();

$user = getCurrentUser();
// Check if admin
if (($user['role'] ?? '') !== 'admin' && ($user['is_admin'] ?? 0) != 1 && ($user['email'] ?? '') !== 'admin@phoenixka.shop') {
    die("<div style='padding:50px;text-align:center;font-family:sans-serif;background:#111;color:#fff'><h2>⛔ Accès Restreint Administrateur</h2><p>Seul l'administrateur de PHOENIXKA peut accéder à cet espace de décaissement.</p><a href='" . SITE_URL . "/dashboard/' style='color:#EAB308'>Retour au tableau de bord</a></div>");
}

$db = getDB();
$success = '';
$error = '';

// Handle Global Payment Gateway Activation/Deactivation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_payment_gateways'])) {
    $enabledGateways = [
        'wave' => isset($_POST['gateway_wave']) ? 1 : 0,
        'orange_money' => isset($_POST['gateway_orange_money']) ? 1 : 0,
        'mobile_money' => isset($_POST['gateway_mobile_money']) ? 1 : 0,
        'cash_on_delivery' => isset($_POST['gateway_cash_on_delivery']) ? 1 : 0,
    ];
    savePlatformSetting('available_payment_methods', $enabledGateways);
    $success = "Les paramètres globaux des moyens de paiement ont été mis à jour avec succès !";
}

// Handle Store Environment & Active Status Switch
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['store_id'])) {
    $storeIdToToggle = intval($_POST['store_id']);

    if (isset($_POST['toggle_store_env'])) {
        $newEnv = $_POST['toggle_store_env'] === 'sandbox' ? 'sandbox' : 'live';
        $db->prepare("UPDATE stores SET payment_environment = ? WHERE id = ?")->execute([$newEnv, $storeIdToToggle]);
        $success = "L'environnement de la boutique #" . $storeIdToToggle . " a été basculé sur : " . ($newEnv === 'sandbox' ? '🧪 DÉMONSTRATION (Sandbox)' : '🚀 PRODUCTION (Live)');
    }

    if (isset($_POST['toggle_store_status'])) {
        $newStatus = intval($_POST['toggle_store_status']);
        $db->prepare("UPDATE stores SET is_active = ? WHERE id = ?")->execute([$newStatus, $storeIdToToggle]);
        $success = "Statut de la boutique #" . $storeIdToToggle . " mis à jour avec succès (" . ($newStatus ? 'Active 🟢' : 'Inactive 🔴') . ").";
    }
}

// Handle Payout Status Updates (Approuver, Refuser, Payer, Remettre en attente)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payout_id'], $_POST['action_type'])) {
    $payoutId = intval($_POST['payout_id']);
    $actionType = $_POST['action_type']; // approve, reject, mark_paid, reset_pending

    $stmtP = $db->prepare("SELECT pr.*, s.name as store_name, s.user_id, s.virtual_wallet FROM payout_requests pr JOIN stores s ON pr.store_id = s.id WHERE pr.id = ?");
    $stmtP->execute([$payoutId]);
    $payout = $stmtP->fetch();

    if ($payout) {
        if ($actionType === 'approve') {
            $db->prepare("UPDATE payout_requests SET status = 'approved' WHERE id = ?")->execute([$payoutId]);
            addNotification('merchant', $payout['store_id'], $payout['user_id'], 'payout_approved', 
                '✅ Demande de décaissement approuvée', 
                "Votre demande de retrait de " . formatPrice($payout['amount_net']) . " a été approuvée par l'administrateur. Le transfert Mobile Money est en cours d'exécution."
            );
            $success = "La demande #{$payoutId} a été approuvée avec succès.";
        } else if ($actionType === 'mark_paid') {
            $db->prepare("UPDATE payout_requests SET status = 'paid' WHERE id = ?")->execute([$payoutId]);
            addNotification('merchant', $payout['store_id'], $payout['user_id'], 'payout_paid', 
                '🎉 Décaissement effectué !', 
                "Le transfert de " . formatPrice($payout['amount_net']) . " vers votre numéro {$payout['payment_number']} ({$payout['payment_method']}) a été effectué avec succès."
            );
            $success = "La demande #{$payoutId} est maintenant marquée comme PAYÉE !";
        } else if ($actionType === 'reject') {
            // Only re-credit if it wasn't rejected previously
            if ($payout['status'] !== 'rejected') {
                $db->prepare("UPDATE payout_requests SET status = 'rejected' WHERE id = ?")->execute([$payoutId]);
                
                // Re-credit wallet if on_demand
                if ($payout['payout_type'] === 'on_demand') {
                    $db->prepare("UPDATE stores SET virtual_wallet = virtual_wallet + ? WHERE id = ?")->execute([$payout['amount_gross'], $payout['store_id']]);
                }

                addNotification('merchant', $payout['store_id'], $payout['user_id'], 'payout_rejected', 
                    '❌ Demande de décaissement refusée', 
                    "Votre demande de retrait de " . formatPrice($payout['amount_gross']) . " a été refusée. " . ($payout['payout_type'] === 'on_demand' ? "Le montant a été récrédité sur votre solde." : "")
                );
            }
            $success = "La demande #{$payoutId} a été refusée.";
        } else if ($actionType === 'reset_pending') {
            // If it was rejected, deduct again when resetting to pending
            if ($payout['status'] === 'rejected' && $payout['payout_type'] === 'on_demand') {
                $db->prepare("UPDATE stores SET virtual_wallet = GREATEST(0, virtual_wallet - ?) WHERE id = ?")->execute([$payout['amount_gross'], $payout['store_id']]);
            }
            $db->prepare("UPDATE payout_requests SET status = 'pending' WHERE id = ?")->execute([$payoutId]);
            $success = "La demande #{$payoutId} a été ré-initialisée au statut 'En attente'.";
        }
    }
}

// Fetch Enabled Payment Gateways
$enabledPayments = getEnabledPaymentMethods();

// Fetch all stores for admin management
$allStores = $db->query("SELECT s.*, u.first_name, u.last_name, u.email FROM stores s JOIN users u ON s.user_id = u.id ORDER BY s.created_at DESC")->fetchAll();

// Fetch all payout requests across all merchants
$stmtAll = $db->query("
    SELECT pr.*, s.name as store_name, u.first_name, u.last_name, u.email as owner_email, u.phone as owner_phone
    FROM payout_requests pr
    JOIN stores s ON pr.store_id = s.id
    JOIN users u ON s.user_id = u.id
    ORDER BY pr.created_at DESC
");
$allPayouts = $stmtAll->fetchAll();

// Stats
$pendingCount = 0;
$totalPendingNet = 0;
$totalPaidNet = 0;

foreach ($allPayouts as $p) {
    if ($p['status'] === 'pending' || $p['status'] === 'approved') {
        $pendingCount++;
        $totalPendingNet += $p['amount_net'];
    } elseif ($p['status'] === 'paid') {
        $totalPaidNet += $p['amount_net'];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration — Gestion des Décaissements &amp; Moyens de Paiement | PhoenixKA</title>
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/dashboard.css">
    <link rel="icon" href="<?= ASSETS_URL ?>/images/logo.png">
    <style>
        .admin-stats-grid{display:grid;grid-template-columns:repeat(auto-fit, minmax(240px, 1fr));gap:20px;margin-bottom:30px}
        .stat-card-admin{background:var(--bg-card);border:1px solid var(--border-color);border-radius:18px;padding:22px}
        .stat-val{font-size:1.8rem;font-weight:800;margin-top:6px}
        
        .gateway-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-top: 16px; }
        .gateway-card { background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 14px; padding: 16px; display: flex; align-items: center; justify-content: space-between; }
        .gateway-info { display: flex; align-items: center; gap: 12px; }
        .gateway-info img { width: 36px; height: 36px; object-fit: contain; }
        .gateway-name { font-weight: 700; font-size: 0.95rem; }

        /* Switch Toggle */
        .switch { position: relative; display: inline-block; width: 46px; height: 24px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #334155; transition: .3s; border-radius: 34px; }
        .slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .3s; border-radius: 50%; }
        input:checked + .slider { background-color: var(--gold); }
        input:checked + .slider:before { transform: translateX(22px); }

        .btn-approve{background:#3B82F6;color:#FFF;border:none;padding:6px 12px;border-radius:8px;font-size:0.8rem;font-weight:700;cursor:pointer}
        .btn-approve:hover{background:#2563EB}
        .btn-pay{background:#22C55E;color:#000;border:none;padding:6px 12px;border-radius:8px;font-size:0.8rem;font-weight:800;cursor:pointer}
        .btn-pay:hover{background:#16A34A;color:#FFF}
        .btn-reject{background:#EF4444;color:#FFF;border:none;padding:6px 12px;border-radius:8px;font-size:0.8rem;font-weight:700;cursor:pointer}
        .btn-reject:hover{background:#DC2626}
        .btn-reset{background:#64748B;color:#FFF;border:none;padding:6px 12px;border-radius:8px;font-size:0.8rem;font-weight:700;cursor:pointer}
        .btn-reset:hover{background:#475569}
    </style>
</head>
<body>
<div class="dashboard-layout">
    <?php 
    $currentPage = 'admin_payouts'; 
    include dirname(__DIR__) . '/includes/sidebar.php'; 
    ?>

    <main class="dashboard-main">
        <div class="dashboard-topbar">
            <div class="topbar-left">
                <h2>👑 Panneau Administration &amp; Décaissements</h2>
                <p>Contrôlez les moyens de paiement et validez les décaissements marchands</p>
            </div>
            <div class="topbar-right">
                <a href="<?= SITE_URL ?>/dashboard/" class="btn btn-ghost btn-sm">← Vue Marchand</a>
            </div>
        </div>

        <div class="dashboard-content">
            <?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

            <!-- STATS CARDS -->
            <div class="admin-stats-grid">
                <div class="stat-card-admin" style="border-color:rgba(234, 179, 8, 0.4)">
                    <div style="font-size:0.85rem;color:var(--text-muted);font-weight:700">Demandes en Attente</div>
                    <div class="stat-val" style="color:#EAB308"><?= $pendingCount ?> demande(s)</div>
                </div>
                <div class="stat-card-admin" style="border-color:rgba(59, 130, 246, 0.4)">
                    <div style="font-size:0.85rem;color:var(--text-muted);font-weight:700">Total Net à Décaisser</div>
                    <div class="stat-val" style="color:#3B82F6"><?= formatPrice($totalPendingNet) ?></div>
                </div>
                <div class="stat-card-admin" style="border-color:rgba(34, 197, 94, 0.4)">
                    <div style="font-size:0.85rem;color:var(--text-muted);font-weight:700">Total Transféré / Payé</div>
                    <div class="stat-val" style="color:#22C55E"><?= formatPrice($totalPaidNet) ?></div>
                </div>
            </div>

            <!-- GLOBAL PAYMENT GATEWAYS ACTIVATION / DEACTIVATION CONTROL -->
            <div class="dash-card" style="margin-bottom:30px;border:1px solid rgba(234, 179, 8, 0.4)">
                <div class="dash-card-header" style="background:rgba(234, 179, 8, 0.05);display:flex;justify-content:space-between;align-items:center">
                    <div>
                        <h3 style="color:var(--gold)">⚡ Activation / Désactivation Globale des Moyens de Paiement</h3>
                        <p style="font-size:0.82rem;color:var(--text-muted);margin:0">Activer ou désactiver les modes de paiement sur l'ensemble de la plateforme (répercuté sur les boutiques &amp; produits)</p>
                    </div>
                </div>
                <div class="dash-card-body">
                    <form method="POST">
                        <input type="hidden" name="update_payment_gateways" value="1">
                        
                        <div class="gateway-grid">
                            <!-- WAVE DIRECT -->
                            <div class="gateway-card">
                                <div class="gateway-info">
                                    <img src="<?= ASSETS_URL ?>/images/payments/wave.png" alt="Wave" onerror="this.src='<?= ASSETS_URL ?>/images/logo.png'">
                                    <div>
                                        <div class="gateway-name">Wave Direct</div>
                                        <small style="color:var(--text-muted)"><?= !empty($enabledPayments['wave']) ? '🟢 Actif' : '🔴 Inactif' ?></small>
                                    </div>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" name="gateway_wave" value="1" <?= !empty($enabledPayments['wave']) ? 'checked' : '' ?>>
                                    <span class="slider"></span>
                                </label>
                            </div>

                            <!-- ORANGE MONEY -->
                            <div class="gateway-card">
                                <div class="gateway-info">
                                    <img src="<?= ASSETS_URL ?>/images/payments/orange-money.png" alt="Orange Money" onerror="this.src='<?= ASSETS_URL ?>/images/logo.png'">
                                    <div>
                                        <div class="gateway-name">Orange Money</div>
                                        <small style="color:var(--text-muted)"><?= !empty($enabledPayments['orange_money']) ? '🟢 Actif' : '🔴 Inactif' ?></small>
                                    </div>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" name="gateway_orange_money" value="1" <?= !empty($enabledPayments['orange_money']) ? 'checked' : '' ?>>
                                    <span class="slider"></span>
                                </label>
                            </div>

                            <!-- MTN MOMO -->
                            <div class="gateway-card">
                                <div class="gateway-info">
                                    <img src="<?= ASSETS_URL ?>/images/payments/mtn-momo.png" alt="MTN MoMo" onerror="this.src='<?= ASSETS_URL ?>/images/logo.png'">
                                    <div>
                                        <div class="gateway-name">MTN MoMo</div>
                                        <small style="color:var(--text-muted)"><?= !empty($enabledPayments['mobile_money']) ? '🟢 Actif' : '🔴 Inactif' ?></small>
                                    </div>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" name="gateway_mobile_money" value="1" <?= !empty($enabledPayments['mobile_money']) ? 'checked' : '' ?>>
                                    <span class="slider"></span>
                                </label>
                            </div>

                            <!-- CASH ON DELIVERY -->
                            <div class="gateway-card">
                                <div class="gateway-info">
                                    <img src="<?= ASSETS_URL ?>/images/payments/cash.png" alt="Payer à la livraison" onerror="this.src='<?= ASSETS_URL ?>/images/logo.png'">
                                    <div>
                                        <div class="gateway-name">Payer à la livraison</div>
                                        <small style="color:var(--text-muted)"><?= !empty($enabledPayments['cash_on_delivery']) ? '🟢 Actif' : '🔴 Inactif' ?></small>
                                    </div>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" name="gateway_cash_on_delivery" value="1" <?= !empty($enabledPayments['cash_on_delivery']) ? 'checked' : '' ?>>
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>

                        <div style="margin-top:20px;text-align:right">
                            <button type="submit" class="btn btn-primary">💾 Mettre à jour les moyens de paiement</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- STORES ENVIRONMENT CONTROL (ADMIN ONLY) -->
            <div class="dash-card" style="margin-bottom:30px;border:1px solid rgba(0, 180, 216, 0.3)">
                <div class="dash-card-header" style="background:rgba(0, 180, 216, 0.05)">
                    <h3 style="color:#00B4D8">🏪 Contrôle des Boutiques (Environnement &amp; Activation)</h3>
                </div>
                <div class="dash-card-body" style="padding:0">
                    <div class="table-responsive">
                        <table class="dash-table">
                            <thead>
                                <tr>
                                    <th>Boutique</th>
                                    <th>Propriétaire</th>
                                    <th>Statut Boutique</th>
                                    <th>Environnement Actif</th>
                                    <th>N° Sandbox</th>
                                    <th>N° Live (Production)</th>
                                    <th>Actions Administrateur</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allStores as $st): ?>
                                    <tr>
                                        <td><strong style="color:var(--gold)"><?= htmlspecialchars($st['name']) ?></strong></td>
                                        <td><?= htmlspecialchars($st['first_name'] . ' ' . $st['last_name']) ?></td>
                                        <td>
                                            <?php if (!empty($st['is_active'])): ?>
                                                <span style="background:rgba(34,197,94,0.15);color:#22C55E;padding:3px 10px;border-radius:50px;font-size:0.75rem;font-weight:700">🟢 Active</span>
                                            <?php else: ?>
                                                <span style="background:rgba(239,68,68,0.15);color:#EF4444;padding:3px 10px;border-radius:50px;font-size:0.75rem;font-weight:700">🔴 Inactive / Suspendue</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (($st['payment_environment'] ?? 'live') === 'sandbox'): ?>
                                                <span style="background:rgba(234,179,8,0.2);color:#FACC15;padding:4px 10px;border-radius:50px;font-size:0.75rem;font-weight:700">🧪 Sandbox (Démo)</span>
                                            <?php else: ?>
                                                <span style="background:rgba(34,197,94,0.2);color:#22C55E;padding:4px 10px;border-radius:50px;font-size:0.75rem;font-weight:700">🚀 Production (Live)</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><span style="font-family:monospace"><?= htmlspecialchars($st['wave_number_sandbox'] ?: '—') ?></span></td>
                                        <td><span style="font-family:monospace;font-weight:700"><?= htmlspecialchars($st['wave_number_live'] ?: $st['wave_number'] ?: '—') ?></span></td>
                                        <td>
                                            <div style="display:flex;gap:6px">
                                                <!-- Toggle Env -->
                                                <form method="POST" style="display:inline">
                                                    <input type="hidden" name="store_id" value="<?= $st['id'] ?>">
                                                    <?php if (($st['payment_environment'] ?? 'live') === 'sandbox'): ?>
                                                        <button type="submit" name="toggle_store_env" value="live" class="btn-pay" style="padding:4px 10px;font-size:0.75rem">🚀 Passer en Live</button>
                                                    <?php else: ?>
                                                        <button type="submit" name="toggle_store_env" value="sandbox" class="btn-approve" style="background:#EAB308;color:#000;padding:4px 10px;font-size:0.75rem">🧪 Mode Démo</button>
                                                    <?php endif; ?>
                                                </form>

                                                <!-- Toggle Active Status -->
                                                <form method="POST" style="display:inline">
                                                    <input type="hidden" name="store_id" value="<?= $st['id'] ?>">
                                                    <?php if (!empty($st['is_active'])): ?>
                                                        <button type="submit" name="toggle_store_status" value="0" class="btn-reject" style="padding:4px 10px;font-size:0.75rem" onclick="return confirm('Désactiver cette boutique ?')">Désactiver</button>
                                                    <?php else: ?>
                                                        <button type="submit" name="toggle_store_status" value="1" class="btn-pay" style="padding:4px 10px;font-size:0.75rem">Activer</button>
                                                    <?php endif; ?>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- PAYOUT REQUESTS TABLE -->
            <div class="dash-card">
                <div class="dash-card-header">
                    <h3>📋 Validation &amp; Traitement des Décaissements Marchands</h3>
                </div>
                <div class="dash-card-body" style="padding:0">
                    <?php if (empty($allPayouts)): ?>
                        <div style="padding:40px;text-align:center;color:var(--text-muted)">
                            Aucun décaissement en attente.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="dash-table">
                                <thead>
                                    <tr>
                                        <th>Boutique &amp; Propriétaire</th>
                                        <th>Mode</th>
                                        <th>Montant Brut</th>
                                        <th>Commissions / Frais</th>
                                        <th>Net à Envoyer</th>
                                        <th>Opérateur &amp; Numéro</th>
                                        <th>Date</th>
                                        <th>Statut</th>
                                        <th>Actions Administrateur</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($allPayouts as $p): ?>
                                        <tr>
                                            <td>
                                                <strong style="color:var(--text-primary)"><?= htmlspecialchars($p['store_name']) ?></strong><br>
                                                <small style="color:var(--text-muted)"><?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?> (<?= htmlspecialchars($p['owner_phone'] ?: $p['owner_email']) ?>)</small>
                                            </td>
                                            <td>
                                                <span class="badge" style="background:rgba(255,255,255,0.06)">
                                                    <?= $p['payout_type'] === 'auto' ? '⚡ Automatique' : '💰 Sur Demande' ?>
                                                </span>
                                            </td>
                                            <td><?= formatPrice($p['amount_gross']) ?></td>
                                            <td style="color:var(--danger)">-<?= formatPrice($p['fee_amount']) ?></td>
                                            <td><strong style="color:var(--gold);font-size:1.05rem"><?= formatPrice($p['amount_net']) ?></strong></td>
                                            <td>
                                                <strong style="color:#00B4D8"><?= htmlspecialchars(strtoupper($p['payment_method'])) ?></strong><br>
                                                <span style="font-family:monospace;font-size:0.95rem;font-weight:700"><?= htmlspecialchars($p['payment_number']) ?></span>
                                            </td>
                                            <td><?= date('d/m/Y H:i', strtotime($p['created_at'])) ?></td>
                                            <td>
                                                <?php if ($p['status'] === 'pending'): ?>
                                                    <span style="background:rgba(234,179,8,0.15);color:#EAB308;padding:4px 10px;border-radius:50px;font-size:0.75rem;font-weight:700">🟠 En attente</span>
                                                <?php elseif ($p['status'] === 'approved'): ?>
                                                    <span style="background:rgba(59,130,246,0.15);color:#3B82F6;padding:4px 10px;border-radius:50px;font-size:0.75rem;font-weight:700">🔵 Approuvée</span>
                                                <?php elseif ($p['status'] === 'paid'): ?>
                                                    <span style="background:rgba(34,197,94,0.15);color:#22C55E;padding:4px 10px;border-radius:50px;font-size:0.75rem;font-weight:700">🟢 Payée</span>
                                                <?php else: ?>
                                                    <span style="background:rgba(239,68,68,0.15);color:#EF4444;padding:4px 10px;border-radius:50px;font-size:0.75rem;font-weight:700">🔴 Refusée</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <form method="POST" style="display:flex;gap:6px;flex-wrap:wrap">
                                                    <input type="hidden" name="payout_id" value="<?= $p['id'] ?>">
                                                    
                                                    <?php if ($p['status'] === 'pending'): ?>
                                                        <button type="submit" name="action_type" value="approve" class="btn-approve">Approuver</button>
                                                        <button type="submit" name="action_type" value="mark_paid" class="btn-pay">✓ Payer</button>
                                                        <button type="submit" name="action_type" value="reject" class="btn-reject" onclick="return confirm('Refuser cette demande ?')">Refuser</button>
                                                    <?php elseif ($p['status'] === 'approved'): ?>
                                                        <button type="submit" name="action_type" value="mark_paid" class="btn-pay">✓ Marquer Payé</button>
                                                        <button type="submit" name="action_type" value="reject" class="btn-reject" onclick="return confirm('Refuser cette demande ?')">Refuser</button>
                                                    <?php else: ?>
                                                        <button type="submit" name="action_type" value="reset_pending" class="btn-reset" onclick="return confirm('Remettre cette demande en attente ?')">🔄 Ré-ouvrir</button>
                                                    <?php endif; ?>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>
</body>
</html>
