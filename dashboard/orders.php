<?php
require_once dirname(__DIR__) . '/includes/functions.php';
requireLogin();

$user = getCurrentUser();
$store = getCurrentStore();
if (!$store) { redirect(SITE_URL . '/dashboard/'); }

$status = $_GET['status'] ?? null;
$orders = getStoreOrders($store['id'], $status, 50);

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $db = getDB();
    $orderId = intval($_POST['order_id']);
    $newStatus = sanitize($_POST['new_status']);
    $db->prepare("UPDATE orders SET status = ? WHERE id = ? AND store_id = ?")->execute([$newStatus, $orderId, $store['id']]);
    
    if ($_POST['new_status'] === 'delivered') {
        $db->prepare("UPDATE orders SET payment_status = 'paid' WHERE id = ? AND payment_method = 'cash_on_delivery'")->execute([$orderId]);
    }
    redirect(SITE_URL . '/dashboard/orders');
}

// Handle payment status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_payment'])) {
    $db = getDB();
    $orderId = intval($_POST['order_id']);
    $payStatus = sanitize($_POST['payment_status']);
    $db->prepare("UPDATE orders SET payment_status = ? WHERE id = ? AND store_id = ?")->execute([$payStatus, $orderId, $store['id']]);
    redirect(SITE_URL . '/dashboard/orders');
}

$stats = getStoreDashboardStats($store['id']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commandes — PhoenixKA Shop</title>
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/dashboard.css">
    <link rel="icon" href="<?= ASSETS_URL ?>/images/logo.png">
    <style>
        .status-filters{display:flex;gap:8px;margin-bottom:24px;flex-wrap:wrap}
        .status-filter{padding:8px 18px;border-radius:50px;border:1px solid var(--border-color);background:transparent;color:var(--text-secondary);font-size:.85rem;cursor:pointer;transition:var(--transition);text-decoration:none;font-family:inherit}
        .status-filter:hover,.status-filter.active{background:var(--gold);color:#000;border-color:var(--gold);font-weight:600}
        .order-detail-row{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:8px;font-size:.9rem}
        .order-detail-row .label{color:var(--text-muted)}
        .order-detail-row .value{font-weight:500;text-align:right}
        .order-actions{display:flex;gap:8px;flex-wrap:wrap}
        .order-actions form{display:inline}
        .order-actions select{padding:6px 12px;border-radius:var(--radius-sm);background:var(--bg-surface);border:1px solid var(--border-color);color:var(--text-primary);font-size:.8rem;cursor:pointer;font-family:inherit}
        .order-actions button{padding:6px 14px;border-radius:var(--radius-sm);font-size:.8rem;font-weight:600;cursor:pointer;border:none;transition:var(--transition);font-family:inherit}
        .whatsapp-btn{background:rgba(37,211,102,.15);color:#25D366}
        .whatsapp-btn:hover{background:rgba(37,211,102,.3)}
    </style>
</head>
<body>
<div class="dashboard-layout">
    <?php 
    $currentPage = 'orders'; 
    include dirname(__DIR__) . '/includes/sidebar.php'; 
    ?>

    <main class="dashboard-main">
        <div class="dashboard-topbar">
            <div class="topbar-left">
                <h2>📦 Commandes</h2>
                <p><?= count($orders) ?> commande(s) <?= $status ? '— filtre: ' . $status : '' ?></p>
            </div>
            <div class="topbar-right">
                <a href="<?= SITE_URL ?>/auth/logout" class="btn btn-ghost btn-sm" style="color:var(--danger)">Déconnexion</a>
            </div>
        </div>

        <div class="dashboard-content">
            <!-- Status Filters -->
            <div class="status-filters">
                <a href="<?= SITE_URL ?>/dashboard/orders" class="status-filter <?= !$status ? 'active' : '' ?>">Toutes</a>
                <a href="?status=pending" class="status-filter <?= $status === 'pending' ? 'active' : '' ?>">⏳ En attente</a>
                <a href="?status=confirmed" class="status-filter <?= $status === 'confirmed' ? 'active' : '' ?>">✓ Confirmées</a>
                <a href="?status=shipped" class="status-filter <?= $status === 'shipped' ? 'active' : '' ?>">🚚 Expédiées</a>
                <a href="?status=delivered" class="status-filter <?= $status === 'delivered' ? 'active' : '' ?>">✅ Livrées</a>
                <a href="?status=cancelled" class="status-filter <?= $status === 'cancelled' ? 'active' : '' ?>">❌ Annulées</a>
            </div>

            <?php if (empty($orders)): ?>
                <div class="empty-state">
                    <div class="icon">📦</div>
                    <h3>Aucune commande</h3>
                    <p>Partagez le lien de votre boutique pour recevoir des commandes.</p>
                </div>
            <?php else: ?>
                <div class="dash-card">
                    <div class="dash-card-body" style="padding:0">
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Réf</th>
                                        <th>Client</th>
                                        <th>Téléphone</th>
                                        <th>Articles</th>
                                        <th>Total</th>
                                        <th>Statut</th>
                                        <th>Paiement</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td style="font-weight:600;color:var(--gold)"><?= $order['order_ref'] ?></td>
                                        <td><?= htmlspecialchars($order['customer_name']) ?></td>
                                        <td><?= htmlspecialchars($order['customer_phone']) ?></td>
                                        <td><?= $order['items_count'] ?> article(s)</td>
                                        <td style="font-weight:600"><?= formatPrice($order['total']) ?></td>
                                        <td><?= getStatusBadge($order['status']) ?></td>
                                        <td><?= getStatusBadge($order['payment_status']) ?></td>
                                        <td style="color:var(--text-muted);font-size:.8rem"><?= timeAgo($order['created_at']) ?></td>
                                        <td>
                                            <div class="order-actions">
                                                <form method="POST">
                                                    <input type="hidden" name="update_status" value="1">
                                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                                    <select name="new_status" onchange="this.form.submit()">
                                                        <option value="">Changer...</option>
                                                        <option value="confirmed">✓ Confirmer</option>
                                                        <option value="processing">🔄 En préparation</option>
                                                        <option value="shipped">🚚 Expédier</option>
                                                        <option value="delivered">✅ Livrée</option>
                                                        <option value="cancelled">❌ Annuler</option>
                                                    </select>
                                                </form>
                                                <?php if ($order['customer_phone']): ?>
                                                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $order['customer_phone']) ?>?text=<?= urlencode('Bonjour ! Votre commande ' . $order['order_ref'] . ' de ' . formatPrice($order['total']) . ' a été prise en compte. Merci !') ?>" target="_blank" class="whatsapp-btn" style="padding:6px 10px;border-radius:var(--radius-sm);text-decoration:none;font-size:.8rem;font-weight:600">💬</a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>

