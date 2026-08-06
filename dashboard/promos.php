<?php
require_once dirname(__DIR__) . '/includes/functions.php';
requireLogin();

$user = getCurrentUser();
$store = getCurrentStore();
if (!$store) { redirect(SITE_URL . '/dashboard/'); }

$db = getDB();
$error = '';
$success = '';

// Add promo code
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_promo'])) {
    $code = strtoupper(sanitize($_POST['code'] ?? ''));
    $type = sanitize($_POST['type'] ?? 'percentage');
    $value = floatval($_POST['value'] ?? 0);
    $minOrder = floatval($_POST['min_order'] ?? 0);
    $maxUses = intval($_POST['max_uses'] ?? 0) ?: null;
    $expiresAt = $_POST['expires_at'] ?? null;

    if (empty($code) || $value <= 0) {
        $error = 'Le code et la valeur sont obligatoires.';
    } else {
        $db->prepare("INSERT INTO promo_codes (store_id, code, type, value, min_order, max_uses, expires_at) VALUES (?, ?, ?, ?, ?, ?, ?)")
           ->execute([$store['id'], $code, $type, $value, $minOrder, $maxUses, $expiresAt ?: null]);
        $success = 'Code promo créé !';
    }
}

// Delete
if (isset($_GET['delete'])) {
    $db->prepare("DELETE FROM promo_codes WHERE id = ? AND store_id = ?")->execute([intval($_GET['delete']), $store['id']]);
    $success = 'Code promo supprimé.';
}

$stmt = $db->prepare("SELECT * FROM promo_codes WHERE store_id = ? ORDER BY created_at DESC");
$stmt->execute([$store['id']]);
$promos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Codes promo — PhoenixKA Shop</title>
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/dashboard.css">
    <link rel="icon" href="<?= ASSETS_URL ?>/images/logo.png">
</head>
<body>
<div class="dashboard-layout">
    <?php 
    $currentPage = 'promos'; 
    include dirname(__DIR__) . '/includes/sidebar.php'; 
    ?>

    <main class="dashboard-main">
        <div class="dashboard-topbar">
            <div class="topbar-left">
                <h2>🏷️ Codes promo</h2>
                <p>Créez des réductions pour fidéliser vos clients</p>
            </div>
            <div class="topbar-right">
                <a href="<?= SITE_URL ?>/auth/logout" class="btn btn-ghost btn-sm" style="color:var(--danger)">Déconnexion</a>
            </div>
        </div>

        <div class="dashboard-content">
            <?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

            <div style="display:grid;grid-template-columns:1fr 1.5fr;gap:24px">
                <div class="dash-card">
                    <div class="dash-card-header"><h3>➕ Nouveau code promo</h3></div>
                    <div class="dash-card-body">
                        <form method="POST">
                            <input type="hidden" name="add_promo" value="1">
                            <div class="form-group">
                                <label>Code *</label>
                                <input type="text" name="code" class="form-control" placeholder="Ex: BIENVENUE10" required style="text-transform:uppercase">
                            </div>
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                                <div class="form-group">
                                    <label>Type</label>
                                    <select name="type" class="form-control">
                                        <option value="percentage">Pourcentage (%)</option>
                                        <option value="fixed">Montant fixe (<?= DEFAULT_CURRENCY ?>)</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Valeur *</label>
                                    <input type="number" name="value" class="form-control" placeholder="10" min="1" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Commande minimum (<?= DEFAULT_CURRENCY ?>)</label>
                                <input type="number" name="min_order" class="form-control" value="0" min="0">
                            </div>
                            <div class="form-group">
                                <label>Utilisations max (0 = illimité)</label>
                                <input type="number" name="max_uses" class="form-control" value="0" min="0">
                            </div>
                            <div class="form-group">
                                <label>Date d'expiration</label>
                                <input type="date" name="expires_at" class="form-control">
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">Créer le code</button>
                        </form>
                    </div>
                </div>

                <div class="dash-card">
                    <div class="dash-card-header"><h3>Vos codes promo (<?= count($promos) ?>)</h3></div>
                    <div class="dash-card-body" style="padding:0">
                        <?php if (empty($promos)): ?>
                            <div class="empty-state" style="padding:30px">
                                <div class="icon">🏷️</div>
                                <h3>Aucun code promo</h3>
                                <p>Créez votre premier code promo.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead><tr><th>Code</th><th>Réduction</th><th>Utilisé</th><th>Expire</th><th>Statut</th><th></th></tr></thead>
                                    <tbody>
                                    <?php foreach ($promos as $p): ?>
                                        <tr>
                                            <td style="font-weight:700;color:var(--gold);font-family:monospace"><?= htmlspecialchars($p['code']) ?></td>
                                            <td><?= $p['type'] === 'percentage' ? $p['value'] . '%' : formatPrice($p['value']) ?></td>
                                            <td><?= $p['used_count'] ?><?= $p['max_uses'] ? '/' . $p['max_uses'] : '' ?></td>
                                            <td style="font-size:.8rem;color:var(--text-muted)"><?= $p['expires_at'] ? date('d/m/Y', strtotime($p['expires_at'])) : '—' ?></td>
                                            <td><?= $p['is_active'] ? '<span class="badge badge-success">Actif</span>' : '<span class="badge badge-secondary">Inactif</span>' ?></td>
                                            <td><a href="?delete=<?= $p['id'] ?>" onclick="return confirm('Supprimer ?')" style="color:var(--danger);font-size:.8rem">🗑️</a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
</body>
</html>

