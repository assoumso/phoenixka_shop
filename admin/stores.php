<?php
require_once dirname(__DIR__) . '/includes/functions.php';
requireLogin();

$user = getCurrentUser();
// Check if admin
if (($user['role'] ?? '') !== 'admin' && ($user['is_admin'] ?? 0) != 1 && ($user['email'] ?? '') !== 'admin@phoenixka.shop') {
    die("<div style='padding:50px;text-align:center;font-family:sans-serif;background:#111;color:#fff'><h2>⛔ Accès Restreint Administrateur</h2><p>Seul l'administrateur de PHOENIXKA peut accéder à cet espace.</p><a href='" . SITE_URL . "/dashboard/' style='color:#EAB308'>Retour au tableau de bord</a></div>");
}

$db = getDB();
$success = '';
$error = '';

// Handle Store Actions (Active Toggle, Featured Toggle, Environment Switch)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['store_id'])) {
    $storeId = intval($_POST['store_id']);

    if (isset($_POST['toggle_active'])) {
        $newVal = intval($_POST['toggle_active']);
        $db->prepare("UPDATE stores SET is_active = ? WHERE id = ?")->execute([$newVal, $storeId]);
        if ($newVal === 1) {
            triggerReferralReward($storeId);
        }
        $success = "Statut de la boutique #" . $storeId . " mis à jour : " . ($newVal ? "Active 🟢 (Bonus parrainage reversé)" : "Suspendue 🔴");
    }

    if (isset($_POST['toggle_featured'])) {
        $newVal = intval($_POST['toggle_featured']);
        $db->prepare("UPDATE stores SET is_featured = ? WHERE id = ?")->execute([$newVal, $storeId]);
        $success = "Mise en avant de la boutique #" . $storeId . " mise à jour : " . ($newVal ? "En Vedette / Certifiée 🌟" : "Standard");
    }

    if (isset($_POST['toggle_env'])) {
        $newEnv = $_POST['toggle_env'] === 'sandbox' ? 'sandbox' : 'live';
        $db->prepare("UPDATE stores SET payment_environment = ? WHERE id = ?")->execute([$newEnv, $storeId]);
        $success = "Environnement de la boutique #" . $storeId . " basculé sur : " . ($newEnv === 'sandbox' ? '🧪 Sandbox (Démo)' : '🚀 Production (Live)');
    }
}

// Fetch all stores with owner info and product counts
$stmtStores = $db->query("
    SELECT s.*, u.first_name, u.last_name, u.email as owner_email, u.phone as owner_phone,
    (SELECT COUNT(*) FROM products WHERE store_id = s.id AND is_active = 1) as total_products,
    (SELECT COUNT(*) FROM orders WHERE store_id = s.id) as total_orders
    FROM stores s
    JOIN users u ON s.user_id = u.id
    ORDER BY s.created_at DESC
");
$stores = $stmtStores->fetchAll();

// Stats
$totalStoresCount = count($stores);
$activeStoresCount = 0;
$totalPlatformWallet = 0;

foreach ($stores as $st) {
    if (!empty($st['is_active'])) $activeStoresCount++;
    $totalPlatformWallet += floatval($st['virtual_wallet'] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration — Gestion des Boutiques | PhoenixKA</title>
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/dashboard.css">
    <link rel="icon" href="<?= ASSETS_URL ?>/images/logo.png">
    <style>
        .admin-stats-grid{display:grid;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));gap:20px;margin-bottom:30px}
        .stat-card-admin{background:var(--bg-card);border:1px solid var(--border-color);border-radius:18px;padding:22px}
        .stat-val{font-size:1.8rem;font-weight:800;margin-top:6px}
        
        .btn-act{background:#22C55E;color:#000;border:none;padding:5px 10px;border-radius:6px;font-size:0.75rem;font-weight:800;cursor:pointer}
        .btn-des{background:#EF4444;color:#FFF;border:none;padding:5px 10px;border-radius:6px;font-size:0.75rem;font-weight:700;cursor:pointer}
        .btn-star{background:linear-gradient(135deg,#FFD700,#FFA500);color:#000;border:none;padding:5px 10px;border-radius:6px;font-size:0.75rem;font-weight:800;cursor:pointer}
        .btn-env{background:#00B4D8;color:#FFF;border:none;padding:5px 10px;border-radius:6px;font-size:0.75rem;font-weight:700;cursor:pointer}
    </style>
</head>
<body>
<div class="dashboard-layout">
    <?php 
    $currentPage = 'admin_stores'; 
    include dirname(__DIR__) . '/includes/sidebar.php'; 
    ?>

    <main class="dashboard-main">
        <div class="dashboard-topbar">
            <div class="topbar-left">
                <h2>🏪 Gestion des Boutiques Marchandes</h2>
                <p>Supervisez, activez et modérez toutes les boutiques de la plateforme PhoenixKA</p>
            </div>
            <div class="topbar-right">
                <a href="<?= SITE_URL ?>/dashboard/" class="btn btn-ghost btn-sm">← Mon Dashboard</a>
            </div>
        </div>

        <div class="dashboard-content">
            <?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

            <!-- STATS CARDS -->
            <div class="admin-stats-grid">
                <div class="stat-card-admin" style="border-color:rgba(234, 179, 8, 0.4)">
                    <div style="font-size:0.85rem;color:var(--text-muted);font-weight:700">Total Boutiques</div>
                    <div class="stat-val" style="color:var(--gold)"><?= $totalStoresCount ?> boutique(s)</div>
                </div>
                <div class="stat-card-admin" style="border-color:rgba(34, 197, 94, 0.4)">
                    <div style="font-size:0.85rem;color:var(--text-muted);font-weight:700">Boutiques Actives</div>
                    <div class="stat-val" style="color:#22C55E"><?= $activeStoresCount ?> / <?= $totalStoresCount ?></div>
                </div>
                <div class="stat-card-admin" style="border-color:rgba(0, 180, 216, 0.4)">
                    <div style="font-size:0.85rem;color:var(--text-muted);font-weight:700">Cumul Portefeuilles Virtuels</div>
                    <div class="stat-val" style="color:#00B4D8"><?= formatPrice($totalPlatformWallet) ?></div>
                </div>
            </div>

            <!-- STORES LIST TABLE -->
            <div class="dash-card">
                <div class="dash-card-header">
                    <h3>📋 Liste des Boutiques Partenaires</h3>
                </div>
                <div class="dash-card-body" style="padding:0">
                    <?php if (empty($stores)): ?>
                        <div style="padding:40px;text-align:center;color:var(--text-muted)">
                            Aucune boutique enregistrée sur la plateforme.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="dash-table">
                                <thead>
                                    <tr>
                                        <th>Boutique &amp; URL</th>
                                        <th>Propriétaire</th>
                                        <th>Produits / Commandes</th>
                                        <th>Solde Virtuel</th>
                                        <th>Environnement</th>
                                        <th>Statut</th>
                                        <th>Actions Administrateur</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stores as $st): ?>
                                        <tr>
                                            <td>
                                                <div style="display:flex;align-items:center;gap:10px">
                                                    <?php if ($st['logo']): ?>
                                                        <img src="<?= ASSETS_URL ?>/uploads/stores/<?= $st['logo'] ?>" alt="" style="width:32px;height:32px;border-radius:8px;object-fit:cover">
                                                    <?php endif; ?>
                                                    <div>
                                                        <strong style="color:var(--text-primary)"><?= htmlspecialchars($st['name']) ?></strong>
                                                        <?php if ($st['is_featured']): ?>
                                                            <span style="font-size:0.75rem;background:rgba(234,179,8,0.2);color:var(--gold);padding:2px 6px;border-radius:4px">🌟 Vedette</span>
                                                        <?php endif; ?><br>
                                                        <a href="<?= SITE_URL ?>/<?= htmlspecialchars($st['slug']) ?>" target="_blank" style="font-size:0.8rem;color:var(--gold);text-decoration:underline">
                                                            /<?= htmlspecialchars($st['slug']) ?> ↗
                                                        </a>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <strong><?= htmlspecialchars($st['first_name'] . ' ' . $st['last_name']) ?></strong><br>
                                                <small style="color:var(--text-muted)"><?= htmlspecialchars($st['owner_email']) ?> • <?= htmlspecialchars($st['owner_phone'] ?: 'N/A') ?></small>
                                            </td>
                                            <td>
                                                <span style="font-weight:700"><?= $st['total_products'] ?> produit(s)</span><br>
                                                <small style="color:var(--text-muted)"><?= $st['total_orders'] ?> commande(s)</small>
                                            </td>
                                            <td>
                                                <strong style="color:var(--gold)"><?= formatPrice($st['virtual_wallet'] ?? 0) ?></strong>
                                            </td>
                                            <td>
                                                <?php if (($st['payment_environment'] ?? 'live') === 'sandbox'): ?>
                                                    <span style="background:rgba(234,179,8,0.2);color:#FACC15;padding:3px 8px;border-radius:50px;font-size:0.75rem;font-weight:700">🧪 Sandbox</span>
                                                <?php else: ?>
                                                    <span style="background:rgba(34,197,94,0.2);color:#22C55E;padding:3px 8px;border-radius:50px;font-size:0.75rem;font-weight:700">🚀 Production</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($st['is_active'])): ?>
                                                    <span style="background:rgba(34,197,94,0.15);color:#22C55E;padding:3px 8px;border-radius:50px;font-size:0.75rem;font-weight:700">🟢 Active</span>
                                                <?php else: ?>
                                                    <span style="background:rgba(239,68,68,0.15);color:#EF4444;padding:3px 8px;border-radius:50px;font-size:0.75rem;font-weight:700">🔴 Suspendue</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div style="display:flex;gap:4px;flex-wrap:wrap">
                                                    <!-- Toggle Active -->
                                                    <form method="POST" style="display:inline">
                                                        <input type="hidden" name="store_id" value="<?= $st['id'] ?>">
                                                        <?php if (!empty($st['is_active'])): ?>
                                                            <button type="submit" name="toggle_active" value="0" class="btn-des" onclick="return confirm('Désactiver cette boutique ?')">Suspendre</button>
                                                        <?php else: ?>
                                                            <button type="submit" name="toggle_active" value="1" class="btn-act">Activer</button>
                                                        <?php endif; ?>
                                                    </form>

                                                    <!-- Toggle Featured -->
                                                    <form method="POST" style="display:inline">
                                                        <input type="hidden" name="store_id" value="<?= $st['id'] ?>">
                                                        <button type="submit" name="toggle_featured" value="<?= $st['is_featured'] ? 0 : 1 ?>" class="btn-star" title="Certifier / Mettre en vedette">
                                                            <?= $st['is_featured'] ? '★ Normal' : '🌟 Vedette' ?>
                                                        </button>
                                                    </form>

                                                    <!-- Toggle Env -->
                                                    <form method="POST" style="display:inline">
                                                        <input type="hidden" name="store_id" value="<?= $st['id'] ?>">
                                                        <button type="submit" name="toggle_env" value="<?= ($st['payment_environment'] ?? 'live') === 'sandbox' ? 'live' : 'sandbox' ?>" class="btn-env">
                                                            <?= ($st['payment_environment'] ?? 'live') === 'sandbox' ? '🚀 Live' : '🧪 Demo' ?>
                                                        </button>
                                                    </form>
                                                </div>
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
