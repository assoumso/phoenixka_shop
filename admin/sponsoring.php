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

// Handle Status Updates (Approved, Active, Completed, Cancelled)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sponsoring_id'], $_POST['action_status'])) {
    $sponsoringId = intval($_POST['sponsoring_id']);
    $actionStatus = $_POST['action_status']; // approved, active, completed, cancelled

    $stmtS = $db->prepare("SELECT s.*, st.name as store_name, st.user_id FROM sponsoring_requests s JOIN stores st ON s.store_id = st.id WHERE s.id = ?");
    $stmtS->execute([$sponsoringId]);
    $sponsoring = $stmtS->fetch();

    if ($sponsoring) {
        $db->prepare("UPDATE sponsoring_requests SET status = ? WHERE id = ?")->execute([$actionStatus, $sponsoringId]);

        if ($actionStatus === 'active') {
            addNotification('merchant', $sponsoring['store_id'], $sponsoring['user_id'], 'sponsoring_active', 
                '🟢 Campagne Sponsoring Active !', 
                "Votre campagne publicitaire ({$sponsoring['pack_name']}) est maintenant en cours de diffusion sur les réseaux cibles. Vos ventes vont décoller !"
            );
            $success = "La campagne #{$sponsoringId} est désormais ACTIVE 🟢.";
        } elseif ($actionStatus === 'completed') {
            addNotification('merchant', $sponsoring['store_id'], $sponsoring['user_id'], 'sponsoring_completed', 
                '✅ Campagne Sponsoring Terminée', 
                "Votre campagne ({$sponsoring['pack_name']}) s'est terminée avec succès. Merci d'avoir utilisé le Sponsoring 50% PhoenixKA !"
            );
            $success = "La campagne #{$sponsoringId} est marquée comme TERMINÉE.";
        } else {
            $success = "Statut de la campagne #{$sponsoringId} mis à jour : " . ucfirst($actionStatus);
        }
    }
}

// Fetch all Sponsoring requests across all merchants
$requests = [];
try {
    $stmt = $db->query("
        SELECT s.*, st.name as store_name, u.first_name, u.last_name, u.email as owner_email, u.phone as owner_phone, p.name as product_name
        FROM sponsoring_requests s
        JOIN stores st ON s.store_id = st.id
        JOIN users u ON st.user_id = u.id
        LEFT JOIN products p ON s.product_id = p.id
        ORDER BY s.created_at DESC
    ");
    $requests = $stmt->fetchAll();
} catch (PDOException $e) {
    $requests = [];
}

// Stats
$pendingCount = 0;
$activeCount = 0;
$totalBudgetSum = 0;

foreach ($requests as $r) {
    if ($r['status'] === 'pending') $pendingCount++;
    if ($r['status'] === 'active' || $r['status'] === 'approved') $activeCount++;
    $totalBudgetSum += floatval($r['total_budget']);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration — Sponsoring &amp; Pub 50% | PhoenixKA</title>
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/dashboard.css">
    <link rel="icon" href="<?= ASSETS_URL ?>/images/logo.png">
    <style>
        .admin-stats-grid{display:grid;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));gap:20px;margin-bottom:30px}
        .stat-card-admin{background:var(--bg-card);border:1px solid var(--border-color);border-radius:18px;padding:22px}
        .stat-val{font-size:1.8rem;font-weight:800;margin-top:6px}
        
        .btn-act{background:#22C55E;color:#000;border:none;padding:5px 10px;border-radius:6px;font-size:0.75rem;font-weight:800;cursor:pointer}
        .btn-app{background:#3B82F6;color:#FFF;border:none;padding:5px 10px;border-radius:6px;font-size:0.75rem;font-weight:700;cursor:pointer}
        .btn-com{background:#64748B;color:#FFF;border:none;padding:5px 10px;border-radius:6px;font-size:0.75rem;font-weight:700;cursor:pointer}
        .btn-can{background:#EF4444;color:#FFF;border:none;padding:5px 10px;border-radius:6px;font-size:0.75rem;font-weight:700;cursor:pointer}
    </style>
</head>
<body>
<div class="dashboard-layout">
    <?php 
    $currentPage = 'admin_sponsoring'; 
    include dirname(__DIR__) . '/includes/sidebar.php'; 
    ?>

    <main class="dashboard-main">
        <div class="dashboard-topbar">
            <div class="topbar-left">
                <h2>🚀 Gestion des Campagnes de Sponsoring (50%)</h2>
                <p>Validez et activez les campagnes publicitaires souscrites par les marchands</p>
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
                    <div style="font-size:0.85rem;color:var(--text-muted);font-weight:700">Demandes en Attente</div>
                    <div class="stat-val" style="color:#EAB308"><?= $pendingCount ?> demande(s)</div>
                </div>
                <div class="stat-card-admin" style="border-color:rgba(34, 197, 94, 0.4)">
                    <div style="font-size:0.85rem;color:var(--text-muted);font-weight:700">Campagnes Actives</div>
                    <div class="stat-val" style="color:#22C55E"><?= $activeCount ?> active(s)</div>
                </div>
                <div class="stat-card-admin" style="border-color:rgba(0, 180, 216, 0.4)">
                    <div style="font-size:0.85rem;color:var(--text-muted);font-weight:700">Cumul Budgets Pub</div>
                    <div class="stat-val" style="color:#00B4D8"><?= formatPrice($totalBudgetSum) ?></div>
                </div>
            </div>

            <!-- SPONSORING REQUESTS TABLE -->
            <div class="dash-card">
                <div class="dash-card-header">
                    <h3>📋 Demandes de Sponsoring Co-financé (Meta &amp; TikTok Ads)</h3>
                </div>
                <div class="dash-card-body" style="padding:0">
                    <?php if (empty($requests)): ?>
                        <div style="padding:40px;text-align:center;color:var(--text-muted)">
                            Aucune demande de sponsoring souscrite pour le moment.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="dash-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Boutique &amp; Contact</th>
                                        <th>Pack Souscrit</th>
                                        <th>Produit Promu</th>
                                        <th>Canaux Cibles</th>
                                        <th>Quote-Part Marchand</th>
                                        <th>Préfinancement 50%</th>
                                        <th>Statut</th>
                                        <th>Actions Administrateur</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($requests as $r): ?>
                                        <tr>
                                            <td style="font-size:0.85rem;color:var(--text-muted)"><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></td>
                                            <td>
                                                <strong style="color:var(--text-primary)"><?= htmlspecialchars($r['store_name']) ?></strong><br>
                                                <small style="color:var(--text-muted)"><?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?> (<?= htmlspecialchars($r['owner_phone'] ?: $r['owner_email']) ?>)</small>
                                            </td>
                                            <td style="font-weight:700;color:var(--gold)"><?= htmlspecialchars($r['pack_name']) ?></td>
                                            <td><?= htmlspecialchars($r['product_name'] ?: 'Toute la boutique') ?></td>
                                            <td style="font-size:0.85rem"><?= htmlspecialchars($r['platform_target']) ?></td>
                                            <td style="font-weight:700;color:var(--gold)"><?= formatPrice($r['merchant_amount']) ?></td>
                                            <td style="font-weight:700;color:var(--success)"><?= formatPrice($r['phoenix_amount']) ?></td>
                                            <td>
                                                <?php if ($r['status'] === 'pending'): ?>
                                                    <span style="background:rgba(234,179,8,0.15);color:#EAB308;padding:3px 8px;border-radius:50px;font-size:0.75rem;font-weight:700">⏳ En attente</span>
                                                <?php elseif ($r['status'] === 'active'): ?>
                                                    <span style="background:rgba(34,197,94,0.15);color:#22C55E;padding:3px 8px;border-radius:50px;font-size:0.75rem;font-weight:700">🟢 Active</span>
                                                <?php elseif ($r['status'] === 'completed'): ?>
                                                    <span style="background:rgba(100,116,139,0.15);color:#94A3B8;padding:3px 8px;border-radius:50px;font-size:0.75rem;font-weight:700">✓ Terminée</span>
                                                <?php else: ?>
                                                    <span style="background:rgba(59,130,246,0.15);color:#3B82F6;padding:3px 8px;border-radius:50px;font-size:0.75rem;font-weight:700"><?= ucfirst($r['status']) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <form method="POST" style="display:flex;gap:4px;flex-wrap:wrap">
                                                    <input type="hidden" name="sponsoring_id" value="<?= $r['id'] ?>">
                                                    <?php if ($r['status'] === 'pending'): ?>
                                                        <button type="submit" name="action_status" value="active" class="btn-act">🟢 Lancer la Pub</button>
                                                        <button type="submit" name="action_status" value="cancelled" class="btn-can" onclick="return confirm('Annuler cette campagne ?')">Annuler</button>
                                                    <?php elseif ($r['status'] === 'active'): ?>
                                                        <button type="submit" name="action_status" value="completed" class="btn-com">✓ Marquer Terminée</button>
                                                    <?php else: ?>
                                                        <button type="submit" name="action_status" value="active" class="btn-act">🟢 Ré-activer</button>
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
