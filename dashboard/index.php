<?php
require_once dirname(__DIR__) . '/includes/functions.php';
requireLogin();

$user = getCurrentUser();
$store = getCurrentStore();
$stats = $store ? getStoreDashboardStats($store['id']) : null;
$recentOrders = $store ? getStoreOrders($store['id'], null, 5) : [];
$pageTitle = 'Tableau de bord';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> — PhoenixKA Shop</title>
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/dashboard.css">
    <link rel="icon" href="<?= ASSETS_URL ?>/images/logo.png">
</head>
<body>
<div class="dashboard-layout">
    <!-- SIDEBAR -->
    <?php 
    $currentPage = 'dashboard'; 
    include dirname(__DIR__) . '/includes/sidebar.php'; 
    ?>

    <!-- MAIN -->
    <main class="dashboard-main">
        <div class="dashboard-topbar">
            <div class="topbar-left">
                <button class="mobile-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')" style="display:none;background:none;border:none;color:var(--text-primary);font-size:1.3rem;cursor:pointer;margin-right:12px" id="sidebarToggle">☰</button>
                <h2>Bonjour, <?= htmlspecialchars($user['first_name']) ?> 👋</h2>
                <p>Voici le résumé de votre boutique</p>
            </div>
            <div class="topbar-right">
                <?php if ($store): ?>
                <a href="<?= getStoreUrl($store) ?>" target="_blank" class="btn btn-ghost btn-sm">👁 Voir ma boutique</a>
                <?php endif; ?>
                <div class="topbar-user">
                    <div class="topbar-avatar"><?= strtoupper(substr($user['first_name'],0,1) . substr($user['last_name'],0,1)) ?></div>
                    <div class="topbar-user-info">
                        <div class="name"><?= htmlspecialchars($user['first_name']) ?></div>
                        <div class="role">Marchand</div>
                    </div>
                </div>
                <a href="<?= SITE_URL ?>/auth/logout" class="btn btn-ghost btn-sm" style="color:var(--danger)">Déconnexion</a>
            </div>
        </div>

        <div class="dashboard-content">
            <?php if (!$store): ?>
                <div class="empty-state">
                    <div class="icon">🏪</div>
                    <h3>Créez votre première boutique</h3>
                    <p>Commencez à vendre en ligne en créant votre boutique.</p>
                    <a href="<?= SITE_URL ?>/dashboard/create-store" class="btn btn-primary">Créer ma boutique</a>
                </div>
            <?php else: ?>

            <!-- Store Link -->
            <div class="store-link-card">
                <div>
                    <span style="font-size:0.8rem;color:var(--text-muted)">Lien de votre boutique (URL Chariow Style)</span>
                    <div class="link-text" id="storeLink"><?= getStoreUrl($store) ?></div>
                </div>
                <button class="copy-btn" onclick="copyLink()">📋 Copier le lien</button>
            </div>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card" style="border:1px solid rgba(234, 179, 8, 0.4);background:rgba(234, 179, 8, 0.04)">
                    <div class="stat-label" style="color:var(--gold);font-weight:700">💰 Solde Portefeuille</div>
                    <div class="stat-value" style="color:var(--gold)"><?= formatPrice($store['virtual_wallet'] ?? 0) ?></div>
                    <div class="stat-sub">Mode: <strong><?= ($store['payout_mode'] ?? 'auto') === 'auto' ? '⚡ Automatique' : '💰 Sur Demande' ?></strong></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Ventes aujourd'hui</div>
                    <div class="stat-value"><?= formatPrice($stats['today_sales']) ?></div>
                    <div class="stat-sub"><?= $stats['today_orders'] ?> commande(s)</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Ventes ce mois</div>
                    <div class="stat-value"><?= formatPrice($stats['month_sales']) ?></div>
                    <div class="stat-sub"><?= $stats['month_orders'] ?> commande(s)</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Produits actifs</div>
                    <div class="stat-value"><?= $stats['total_products'] ?></div>
                    <div class="stat-sub">en ligne</div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <a href="<?= SITE_URL ?>/dashboard/products?action=add" class="quick-action-btn">
                    <span class="icon">➕</span>
                    <span>Ajouter un produit</span>
                </a>
                <a href="<?= SITE_URL ?>/dashboard/orders" class="quick-action-btn">
                    <span class="icon">📦</span>
                    <span>Gérer les commandes</span>
                </a>
            </div>

            <!-- Recent Orders -->
            <div class="dash-card">
                <div class="dash-card-header">
                    <h3>Dernières commandes</h3>
                    <a href="<?= SITE_URL ?>/dashboard/orders" class="btn btn-ghost btn-sm">Voir tout →</a>
                </div>
                <div class="dash-card-body">
                    <?php if (empty($recentOrders)): ?>
                        <div class="empty-state" style="padding:30px">
                            <div class="icon">📦</div>
                            <h3>Aucune commande pour l'instant</h3>
                            <p>Partagez le lien de votre boutique pour recevoir vos premières commandes !</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Réf</th>
                                        <th>Client</th>
                                        <th>Total</th>
                                        <th>Statut</th>
                                        <th>Paiement</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($recentOrders as $order): ?>
                                    <tr>
                                        <td style="font-weight:600;color:var(--gold)"><?= $order['order_ref'] ?></td>
                                        <td><?= htmlspecialchars($order['customer_name']) ?></td>
                                        <td style="font-weight:600"><?= formatPrice($order['total']) ?></td>
                                        <td><?= getStatusBadge($order['status']) ?></td>
                                        <td><?= getStatusBadge($order['payment_status']) ?></td>
                                        <td style="color:var(--text-muted)"><?= timeAgo($order['created_at']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<script>
function copyLink() {
    const link = document.getElementById('storeLink').textContent;
    navigator.clipboard.writeText(link).then(() => {
        const btn = document.querySelector('.copy-btn');
        btn.textContent = '✓ Copié !';
        setTimeout(() => btn.textContent = '📋 Copier le lien', 2000);
    });
}
// Mobile sidebar
if (window.innerWidth <= 768) {
    document.getElementById('sidebarToggle').style.display = 'inline-block';
}
</script>
</body>
</html>

