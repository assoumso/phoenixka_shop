<?php
require_once dirname(__DIR__) . '/includes/functions.php';
requireLogin();

$user = getCurrentUser();
$store = getCurrentStore();
if (!$store) { redirect(SITE_URL . '/dashboard/'); }

$db = getDB();

// Approve/reject review
if (isset($_GET['approve'])) {
    $db->prepare("UPDATE reviews SET is_approved = 1 WHERE id = ? AND store_id = ?")->execute([intval($_GET['approve']), $store['id']]);
}
if (isset($_GET['reject'])) {
    $db->prepare("DELETE FROM reviews WHERE id = ? AND store_id = ?")->execute([intval($_GET['reject']), $store['id']]);
}

$stmt = $db->prepare("SELECT r.*, p.name as product_name FROM reviews r LEFT JOIN products p ON r.product_id = p.id WHERE r.store_id = ? ORDER BY r.created_at DESC");
$stmt->execute([$store['id']]);
$reviews = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avis clients — PhoenixKA Shop</title>
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/dashboard.css">
    <link rel="icon" href="<?= ASSETS_URL ?>/images/logo.png">
</head>
<body>
<div class="dashboard-layout">
    <?php 
    $currentPage = 'reviews'; 
    include dirname(__DIR__) . '/includes/sidebar.php'; 
    ?>

    <main class="dashboard-main">
        <div class="dashboard-topbar">
            <div class="topbar-left">
                <h2>⭐ Avis clients</h2>
                <p><?= count($reviews) ?> avis au total</p>
            </div>
            <div class="topbar-right">
                <a href="<?= SITE_URL ?>/auth/logout" class="btn btn-ghost btn-sm" style="color:var(--danger)">Déconnexion</a>
            </div>
        </div>

        <div class="dashboard-content">
            <div class="dash-card">
                <div class="dash-card-body" style="padding:0">
                    <?php if (empty($reviews)): ?>
                        <div class="empty-state">
                            <div class="icon">⭐</div>
                            <h3>Aucun avis</h3>
                            <p>Les avis de vos clients apparaîtront ici.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead><tr><th>Client</th><th>Produit</th><th>Note</th><th>Commentaire</th><th>Statut</th><th>Actions</th></tr></thead>
                                <tbody>
                                <?php foreach ($reviews as $r): ?>
                                    <tr>
                                        <td style="font-weight:600"><?= htmlspecialchars($r['customer_name'] ?: 'Anonyme') ?></td>
                                        <td style="color:var(--text-muted)"><?= htmlspecialchars($r['product_name'] ?: '—') ?></td>
                                        <td style="color:var(--gold)"><?= str_repeat('★', $r['rating']) . str_repeat('☆', 5 - $r['rating']) ?></td>
                                        <td style="font-size:.85rem;max-width:300px"><?= htmlspecialchars(mb_substr($r['comment'], 0, 100)) ?></td>
                                        <td><?= $r['is_approved'] ? '<span class="badge badge-success">Approuvé</span>' : '<span class="badge badge-warning">En attente</span>' ?></td>
                                        <td>
                                            <?php if (!$r['is_approved']): ?>
                                                <a href="?approve=<?= $r['id'] ?>" style="color:var(--success);font-size:.8rem;margin-right:8px">✓ Approuver</a>
                                            <?php endif; ?>
                                            <a href="?reject=<?= $r['id'] ?>" onclick="return confirm('Supprimer cet avis ?')" style="color:var(--danger);font-size:.8rem">🗑️</a>
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

