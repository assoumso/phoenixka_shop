<?php
require_once dirname(__DIR__) . '/includes/functions.php';
requireLogin();

$user = getCurrentUser();
$store = getCurrentStore();
if (!$store) { redirect(SITE_URL . '/dashboard/'); }

$db = getDB();
$stmt = $db->prepare("SELECT c.*, (SELECT COUNT(*) FROM orders WHERE customer_id = c.id) as order_count, (SELECT COALESCE(SUM(total),0) FROM orders WHERE customer_id = c.id AND status != 'cancelled') as total_spent FROM customers c WHERE c.store_id = ? ORDER BY c.created_at DESC");
$stmt->execute([$store['id']]);
$customers = $stmt->fetchAll();

// Also get customers from orders without customer records
$stmt2 = $db->prepare("SELECT customer_name, customer_phone, customer_email, COUNT(*) as order_count, SUM(total) as total_spent, MAX(created_at) as last_order FROM orders WHERE store_id = ? AND customer_id IS NULL GROUP BY customer_phone ORDER BY last_order DESC");
$stmt2->execute([$store['id']]);
$orderCustomers = $stmt2->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clients — PhoenixKA Shop</title>
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/dashboard.css">
    <link rel="icon" href="<?= ASSETS_URL ?>/images/logo.png">
</head>
<body>
<div class="dashboard-layout">
    <?php 
    $currentPage = 'customers'; 
    include dirname(__DIR__) . '/includes/sidebar.php'; 
    ?>

    <main class="dashboard-main">
        <div class="dashboard-topbar">
            <div class="topbar-left">
                <h2>👥 Clients</h2>
                <p><?= count($customers) + count($orderCustomers) ?> client(s) au total</p>
            </div>
            <div class="topbar-right">
                <a href="<?= SITE_URL ?>/auth/logout" class="btn btn-ghost btn-sm" style="color:var(--danger)">Déconnexion</a>
            </div>
        </div>

        <div class="dashboard-content">
            <div class="dash-card">
                <div class="dash-card-header">
                    <h3>Liste des clients</h3>
                </div>
                <div class="dash-card-body" style="padding:0">
                    <?php if (empty($customers) && empty($orderCustomers)): ?>
                        <div class="empty-state">
                            <div class="icon">👥</div>
                            <h3>Aucun client pour l'instant</h3>
                            <p>Vos clients apparaîtront ici après leur première commande.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Client</th>
                                        <th>Téléphone</th>
                                        <th>Email</th>
                                        <th>Commandes</th>
                                        <th>Total dépensé</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($customers as $c): ?>
                                    <tr>
                                        <td style="font-weight:600"><?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name']) ?></td>
                                        <td><?= htmlspecialchars($c['phone']) ?></td>
                                        <td style="color:var(--text-muted)"><?= htmlspecialchars($c['email'] ?: '—') ?></td>
                                        <td><span class="badge badge-primary"><?= $c['order_count'] ?></span></td>
                                        <td style="font-weight:600;color:var(--gold)"><?= formatPrice($c['total_spent']) ?></td>
                                        <td>
                                            <?php if ($c['phone']): ?>
                                                <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $c['phone']) ?>" target="_blank" style="color:#25D366;font-size:.85rem;font-weight:600">💬 WhatsApp</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php foreach ($orderCustomers as $c): ?>
                                    <tr>
                                        <td style="font-weight:600"><?= htmlspecialchars($c['customer_name'] ?: 'Client') ?></td>
                                        <td><?= htmlspecialchars($c['customer_phone']) ?></td>
                                        <td style="color:var(--text-muted)"><?= htmlspecialchars($c['customer_email'] ?: '—') ?></td>
                                        <td><span class="badge badge-primary"><?= $c['order_count'] ?></span></td>
                                        <td style="font-weight:600;color:var(--gold)"><?= formatPrice($c['total_spent']) ?></td>
                                        <td>
                                            <?php if ($c['customer_phone']): ?>
                                                <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $c['customer_phone']) ?>" target="_blank" style="color:#25D366;font-size:.85rem;font-weight:600">💬 WhatsApp</a>
                                            <?php endif; ?>
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

